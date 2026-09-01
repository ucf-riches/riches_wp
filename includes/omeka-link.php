<?php
/**
 * Configurable "link bar" support for aggregated queue cards.
 *
 * Each queue card can show a call-to-action bar linking to an external source.
 * Per post, an editor may override BOTH the URL and the link text — pointing the
 * bar at any source with any label. When no per-post URL is set, the URL is
 * auto-detected from the post body using a site-configurable domain fragment and
 * priority list. When no per-post text is set, a site-configurable default label
 * is used.
 *
 * Defaults target the RICHES Omeka archive, but every default is exposed as a
 * theme customization (Customizer) plus a filter, so the same theme can
 * prioritize a different set of links on another site.
 *
 * @package UCF-WordPress-Theme-child-RICHES
 */

defined( 'ABSPATH' ) || exit;

/**
 * Site-configurable URL fragment that an auto-detected link must contain.
 *
 * Default 'richesmi.cah.ucf.edu/omeka' matches both /omeka/ and /omeka2/ paths.
 * Returns '' to disable body auto-detection entirely.
 *
 * @return string
 */
function riches_linkbar_match_domain() {
	$default = 'richesmi.cah.ucf.edu/omeka';
	$value   = trim( (string) get_theme_mod( 'riches_linkbar_domain', $default ) );

	/**
	 * Filter the URL fragment used to auto-detect card link-bar links.
	 *
	 * @param string $value Domain/URL fragment ('' disables auto-detection).
	 */
	return (string) apply_filters( 'riches_linkbar_match_domain', $value );
}

/**
 * Ordered list of URL fragments that rank an auto-detected link higher.
 *
 * Default prioritizes Omeka collection records, then exhibit records.
 *
 * @return string[]
 */
function riches_linkbar_priority_patterns() {
	$default = 'collections/show,exhibits/show';
	$raw     = (string) get_theme_mod( 'riches_linkbar_priority', $default );
	$parts   = array_values( array_filter( array_map( 'trim', explode( ',', $raw ) ) ) );

	/**
	 * Filter the ordered priority fragments for auto-detected links.
	 *
	 * @param string[] $parts Priority URL fragments, most-preferred first.
	 */
	return (array) apply_filters( 'riches_linkbar_priority_patterns', $parts );
}

/**
 * The site-wide default link text (used when a post sets no text override).
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
	 * Filter the default card link-bar label.
	 *
	 * @param string $label The resolved default label text.
	 */
	return (string) apply_filters( 'riches_omeka_bar_label', $label );
}

/**
 * Resolve the link URL for a post's card bar.
 *
 * Order of precedence:
 *   1. ACF `riches_omeka_url` override (any URL) wins.
 *   2. Else auto-detect from the raw post_content: the first link whose URL
 *      contains the configured domain fragment, preferring the configured
 *      priority fragments in order.
 *
 * Raw post_content is used deliberately (not get_the_content()) — in secondary
 * loops such as tab panels <!--more--> would truncate the body.
 *
 * @param int $post_id Post ID.
 * @return string Escaped URL, or '' when none resolves.
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

	// 1. Per-post override (any source).
	if ( function_exists( 'get_field' ) ) {
		$override = (string) get_field( 'riches_omeka_url', $post_id );
		if ( '' !== trim( $override ) ) {
			$cache[ $post_id ] = esc_url_raw( $override );
			return $cache[ $post_id ];
		}
	}

	// 2. Auto-detect from the body, scoped to the configured domain fragment.
	$url     = '';
	$domain  = riches_linkbar_match_domain();
	$post    = get_post( $post_id );
	$content = ( $post instanceof WP_Post ) ? $post->post_content : '';

	if ( '' !== $domain && '' !== $content
		&& preg_match_all( '/href\s*=\s*(["\'])(https?:\/\/[^"\']+)\1/i', $content, $matches )
	) {
		$candidates = array();
		foreach ( $matches[2] as $href ) {
			if ( false !== stripos( $href, $domain ) ) {
				$candidates[] = $href;
			}
		}

		if ( $candidates ) {
			foreach ( riches_linkbar_priority_patterns() as $pattern ) {
				foreach ( $candidates as $href ) {
					if ( false !== stripos( $href, $pattern ) ) {
						$url = $href;
						break 2;
					}
				}
			}
			if ( '' === $url ) {
				$url = reset( $candidates );
			}
		}
	}

	$cache[ $post_id ] = $url ? esc_url_raw( $url ) : '';
	return $cache[ $post_id ];
}

/**
 * Resolve the link text for a post's card bar.
 *
 * Per-post `riches_omeka_link_text` override wins; otherwise the site default
 * label (riches_get_omeka_bar_label()).
 *
 * @param int $post_id Post ID.
 * @return string
 */
function riches_get_omeka_link_text( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id && function_exists( 'get_field' ) ) {
		$override = trim( (string) get_field( 'riches_omeka_link_text', $post_id ) );
		if ( '' !== $override ) {
			return $override;
		}
	}
	return riches_get_omeka_bar_label();
}

/**
 * Echo the card link bar for a post, if a URL resolves.
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
	$text = riches_get_omeka_link_text( $post_id );
	?>
	<div class="riches-omeka-bar">
		<a class="riches-omeka-bar__link btn btn-primary btn-sm" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
			<span class="fas fa-external-link-alt" aria-hidden="true"></span>
			<span><?php echo esc_html( $text ); ?></span>
			<span class="sr-only">: <?php echo esc_html( get_the_title( $post_id ) ); ?> <?php esc_html_e( '(opens in a new tab)', 'UCF-WordPress-Theme-child-RICHES' ); ?></span>
		</a>
	</div>
	<?php
}

/**
 * Register the per-post ACF override fields (URL + link text).
 *
 * Mirrors the pattern in the leaflet-pinner plugin's includes/acf-fields.php. No-op if ACF is inactive.
 */
function riches_register_omeka_field_group() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'                   => 'group_riches_omeka',
			'title'                 => __( 'Card link bar', 'UCF-WordPress-Theme-child-RICHES' ),
			'fields'                => array(
				array(
					'key'          => 'field_riches_omeka_url',
					'label'        => __( 'Link URL (override)', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'         => 'riches_omeka_url',
					'type'         => 'url',
					'instructions' => __( 'Optional. Point this card\'s link bar at any URL. Leave blank to auto-detect the archive link from the post body.', 'UCF-WordPress-Theme-child-RICHES' ),
				),
				array(
					'key'          => 'field_riches_omeka_link_text',
					'label'        => __( 'Link text (override)', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'         => 'riches_omeka_link_text',
					'type'         => 'text',
					'instructions' => __( 'Optional. Text shown on this card\'s link bar. Leave blank to use the site default label (Appearance &rarr; Customize &rarr; RICHES Options).', 'UCF-WordPress-Theme-child-RICHES' ),
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
 * Register the Customizer controls: default label + auto-detect domain/priority.
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

	// Default link text.
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
			'label'       => __( 'Card link: default text', 'UCF-WordPress-Theme-child-RICHES' ),
			'description' => __( 'Default text for the card link bar. Individual posts can override this.', 'UCF-WordPress-Theme-child-RICHES' ),
			'section'     => 'riches_options',
			'type'        => 'text',
		)
	);

	// Auto-detect domain fragment.
	$wp_customize->add_setting(
		'riches_linkbar_domain',
		array(
			'type'              => 'theme_mod',
			'default'           => 'richesmi.cah.ucf.edu/omeka',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'riches_linkbar_domain',
		array(
			'label'       => __( 'Card link: auto-detect domain', 'UCF-WordPress-Theme-child-RICHES' ),
			'description' => __( 'When a post has no URL override, the first body link whose URL contains this fragment is used. Clear to disable auto-detection.', 'UCF-WordPress-Theme-child-RICHES' ),
			'section'     => 'riches_options',
			'type'        => 'text',
		)
	);

	// Auto-detect priority fragments.
	$wp_customize->add_setting(
		'riches_linkbar_priority',
		array(
			'type'              => 'theme_mod',
			'default'           => 'collections/show,exhibits/show',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'riches_linkbar_priority',
		array(
			'label'       => __( 'Card link: auto-detect priority', 'UCF-WordPress-Theme-child-RICHES' ),
			'description' => __( 'Comma-separated URL fragments to prefer when several matching links exist, in order (e.g. collections/show,exhibits/show).', 'UCF-WordPress-Theme-child-RICHES' ),
			'section'     => 'riches_options',
			'type'        => 'text',
		)
	);
}
add_action( 'customize_register', 'riches_register_omeka_customizer' );
