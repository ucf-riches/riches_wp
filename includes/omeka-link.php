<?php
/**
 * "View in Omeka" link support for aggregated queue cards.
 *
 * Surfaces a link to the project's external Omeka archive
 * (https://richesmi.cah.ucf.edu/omeka2/) on collection/exhibit queue cards.
 *
 * URL resolution is hybrid: an optional ACF override field on the post wins;
 * otherwise the first Omeka anchor in the raw post body is used (covering the
 * ~2500 legacy posts that only have the link inline). The visible label is a
 * site-wide, CMS-editable Customizer setting.
 *
 * @package UCF-WordPress-Theme-child-RICHES
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve the external Omeka archive URL for a post.
 *
 * Order of precedence:
 *   1. ACF `riches_omeka_url` override field (if set).
 *   2. First Omeka anchor parsed from the raw post_content, prioritizing
 *      /collections/show/{id}, then /exhibits/show/{slug}, then any Omeka link.
 *
 * Raw post_content is used deliberately (not get_the_content()), because in
 * secondary loops such as tab panels the queried object is the parent page and
 * <!--more--> would truncate the content — the same reasoning documented for the
 * YouTube detection in category-queue.php.
 *
 * @param int $post_id Post ID.
 * @return string Escaped URL, or '' when none is found.
 */
function riches_get_omeka_url( $post_id ) {
	static $cache = array();

	$post_id = (int) $post_id;
	if ( ! $post_id ) {
		return '';
	}
	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}

	$url = '';

	// 1. Editor override via ACF.
	if ( function_exists( 'get_field' ) ) {
		$override = (string) get_field( 'riches_omeka_url', $post_id );
		if ( '' !== trim( $override ) ) {
			$cache[ $post_id ] = esc_url_raw( $override );
			return $cache[ $post_id ];
		}
	}

	// 2. Parse the first Omeka anchor out of the raw post body.
	$post = get_post( $post_id );
	$content = ( $post instanceof WP_Post ) ? $post->post_content : '';

	if ( '' !== $content
		&& preg_match_all(
			'/href\s*=\s*(["\'])(https?:\/\/richesmi\.cah\.ucf\.edu\/omeka2?\/[^"\']+)\1/i',
			$content,
			$matches
		)
	) {
		$hrefs = $matches[2];

		// Priority 1: collection records.
		foreach ( $hrefs as $href ) {
			if ( preg_match( '#/omeka2?/collections/show/\d+#i', $href ) ) {
				$url = $href;
				break;
			}
		}
		// Priority 2: exhibit records.
		if ( '' === $url ) {
			foreach ( $hrefs as $href ) {
				if ( preg_match( '#/omeka2?/exhibits/show/[\w-]+#i', $href ) ) {
					$url = $href;
					break;
				}
			}
		}
		// Fallback: first Omeka link of any kind.
		if ( '' === $url ) {
			$url = $hrefs[0];
		}
	}

	$cache[ $post_id ] = $url ? esc_url_raw( $url ) : '';
	return $cache[ $post_id ];
}

/**
 * The CMS-configurable label for the "View in Omeka" bar.
 *
 * @return string
 */
function riches_get_omeka_bar_label() {
	$default = __( 'View in Omeka', 'UCF-WordPress-Theme-child-RICHES' );
	$label   = trim( (string) get_theme_mod( 'riches_omeka_bar_label', $default ) );
	if ( '' === $label ) {
		$label = $default;
	}

	/**
	 * Filter the "View in Omeka" bar label.
	 *
	 * @param string $label The resolved label text.
	 */
	return (string) apply_filters( 'riches_omeka_bar_label', $label );
}

/**
 * Echo the "View in Omeka" bar for a post, if an Omeka URL is available.
 *
 * Renders nothing when no URL resolves (graceful degradation — no empty bar).
 *
 * @param int $post_id Post ID.
 * @return void
 */
function riches_render_omeka_bar( $post_id ) {
	$url = riches_get_omeka_url( $post_id );
	if ( '' === $url ) {
		return;
	}
	?>
	<div class="riches-omeka-bar">
		<a class="riches-omeka-bar__link btn btn-primary btn-sm" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
			<span class="fas fa-external-link-alt" aria-hidden="true"></span>
			<span><?php echo esc_html( riches_get_omeka_bar_label() ); ?></span>
			<span class="sr-only">: <?php echo esc_html( get_the_title( $post_id ) ); ?> <?php esc_html_e( '(opens in a new tab)', 'UCF-WordPress-Theme-child-RICHES' ); ?></span>
		</a>
	</div>
	<?php
}

/**
 * Register the ACF override field group for the Omeka URL.
 *
 * Mirrors the pattern in includes/map-pins-acf.php. No-op if ACF is inactive.
 */
function riches_register_omeka_field_group() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'                   => 'group_riches_omeka',
			'title'                 => __( 'Omeka archive link', 'UCF-WordPress-Theme-child-RICHES' ),
			'fields'                => array(
				array(
					'key'          => 'field_riches_omeka_url',
					'label'        => __( 'Omeka URL (override)', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'         => 'riches_omeka_url',
					'type'         => 'url',
					'instructions' => __( 'Optional. Overrides the Omeka link auto-detected from the post body. Leave blank to use the first Omeka link found in the content.', 'UCF-WordPress-Theme-child-RICHES' ),
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'post',
					),
				),
			),
			'position'              => 'acf_after_title',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
		)
	);
}
add_action( 'acf/init', 'riches_register_omeka_field_group' );

/**
 * Register the Customizer control for the site-wide bar label.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function riches_register_omeka_customizer( $wp_customize ) {
	$wp_customize->add_section(
		'riches_options',
		array(
			'title'    => __( 'RICHES Options', 'UCF-WordPress-Theme-child-RICHES' ),
			'priority' => 160,
		)
	);

	$wp_customize->add_setting(
		'riches_omeka_bar_label',
		array(
			'type'              => 'theme_mod',
			'default'           => __( 'View in Omeka', 'UCF-WordPress-Theme-child-RICHES' ),
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'riches_omeka_bar_label',
		array(
			'label'       => __( 'Archive link label', 'UCF-WordPress-Theme-child-RICHES' ),
			'description' => __( 'Text shown on the "View in Omeka" link on collection/exhibit queue cards.', 'UCF-WordPress-Theme-child-RICHES' ),
			'section'     => 'riches_options',
			'type'        => 'text',
		)
	);
}
add_action( 'customize_register', 'riches_register_omeka_customizer' );
