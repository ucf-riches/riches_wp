<?php
/**
 * Tabbed wrapper for multiple [riches_category_row] queues.
 *
 * Styles ship with the child theme `output.css` (see src/css/_riches-queue-tabs.scss).
 * Use filter `riches_queue_tabs_force_enqueue` only if you render this shortcode outside
 * singular post content (e.g. hard-coded do_shortcode in a template).
 *
 * @package UCF-WordPress-Theme-child-RICHES
 */

/**
 * Parse nested [riches_category_row ...] shortcodes from raw inner content.
 *
 * @param string $content Raw shortcode inner content.
 * @return array<int, array{category: string, label: string, posts_per_page: int, reduced: bool}>
 */
function riches_queue_tabs_parse_nested_rows( $content ) {
	$queues = array();
	if ( ! is_string( $content ) || $content === '' ) {
		return $queues;
	}

	if ( ! preg_match_all( '/\[riches_category_row\s+([^\]]+)\]/', $content, $matches, PREG_SET_ORDER ) ) {
		return $queues;
	}

	foreach ( $matches as $m ) {
		$atts = shortcode_parse_atts( trim( $m[1] ) );
		if ( ! is_array( $atts ) ) {
			continue;
		}
		$slug = isset( $atts['category'] ) ? sanitize_key( $atts['category'] ) : '';
		if ( ! $slug ) {
			continue;
		}
		$label = isset( $atts['label'] ) ? (string) $atts['label'] : '';
		$ppp   = isset( $atts['posts_per_page'] ) ? absint( $atts['posts_per_page'] ) : 3;
		$reduced_explicit = isset( $atts['reduced'] );
		$reduced            = $reduced_explicit ? riches_shortcode_reduced_flag( $atts['reduced'] ) : false;
		$link_titles_explicit = isset( $atts['link_titles'] );
		$link_titles          = $link_titles_explicit ? riches_shortcode_bool_flag( $atts['link_titles'], true ) : true;

		$queues[] = array(
			'category'             => $slug,
			'label'                => $label,
			'posts_per_page'       => $ppp > 0 ? $ppp : 3,
			'reduced'              => $reduced,
			'reduced_explicit'     => $reduced_explicit,
			'link_titles'          => $link_titles,
			'link_titles_explicit' => $link_titles_explicit,
		);
	}

	return $queues;
}

/**
 * True when tab markup is likely needed (for templates that call do_shortcode outside the editor).
 *
 * @return bool
 */
function riches_queue_tabs_should_load_assets() {
	if ( apply_filters( 'riches_queue_tabs_force_enqueue', false ) ) {
		return true;
	}

	if ( ! is_singular() ) {
		return false;
	}

	global $post;
	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	return has_shortcode( (string) $post->post_content, 'riches_queue_tabs' );
}

/**
 * Body class when the shortcode is present (optional hook for child overrides / debugging).
 */
function riches_queue_tabs_body_class( $classes ) {
	if ( riches_queue_tabs_should_load_assets() ) {
		$classes[] = 'has-riches-queue-tabs';
	}
	return $classes;
}

add_filter( 'body_class', 'riches_queue_tabs_body_class' );

/**
 * Shortcode: [riches_queue_tabs default="slug" id="optional-root-id" reduced="1"] nested rows [/riches_queue_tabs]
 *
 * When reduced="1" on the wrapper, nested rows without their own reduced attribute are forced to reduced mode.
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Inner content (unparsed nested shortcodes).
 * @return string
 */
function riches_queue_tabs_shortcode( $atts, $content = null ) {
	$a = shortcode_atts(
		array(
			'default'     => '',
			'id'          => '',
			'reduced'     => '',
			'link_titles' => '',
		),
		$atts,
		'riches_queue_tabs'
	);

	$queues = riches_queue_tabs_parse_nested_rows( $content ? $content : '' );
	if ( empty( $queues ) ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return '<p class="riches-queue-tabs__empty text-muted">' . esc_html__( 'Add one or more [riches_category_row] shortcodes inside [riches_queue_tabs].', 'UCF-WordPress-Theme-child-RICHES' ) . '</p>';
		}
		return '';
	}

	// Wrapper-level toggles cascade to nested rows that did not set their own.
	$wrapper_reduced     = riches_shortcode_reduced_flag( $a['reduced'] );
	$wrapper_link_set    = ( '' !== $a['link_titles'] );
	$wrapper_link_titles = riches_shortcode_bool_flag( $a['link_titles'], true );

	foreach ( $queues as $i => $row ) {
		if ( $wrapper_reduced && empty( $row['reduced_explicit'] ) ) {
			$queues[ $i ]['reduced'] = true;
		}
		if ( $wrapper_link_set && empty( $row['link_titles_explicit'] ) ) {
			$queues[ $i ]['link_titles'] = $wrapper_link_titles;
		}
		unset( $queues[ $i ]['reduced_explicit'], $queues[ $i ]['link_titles_explicit'] );
	}

	$default_slug = sanitize_key( $a['default'] );
	$active_index  = 0;
	foreach ( $queues as $i => $row ) {
		if ( $default_slug && $row['category'] === $default_slug ) {
			$active_index = $i;
			break;
		}
	}

	$root_id = $a['id'] ? sanitize_html_class( $a['id'] ) : '';
	if ( ! $root_id ) {
		$root_id = 'riches-queue-tabs-' . substr( md5( wp_json_encode( $queues ) . (string) $content ), 0, 8 );
	}

	$tab_ids   = array();
	$panel_ids = array();
	foreach ( $queues as $i => $row ) {
		$tab_ids[]   = $root_id . '-tab-' . $i;
		$panel_ids[] = $root_id . '-panel-' . $i;
	}

	$GLOBALS['riches_queue_tabs_ctx'] = compact( 'queues', 'active_index', 'root_id', 'tab_ids', 'panel_ids' );
	ob_start();
	require get_stylesheet_directory() . '/template-parts/riches-queue-tabs.php';
	unset( $GLOBALS['riches_queue_tabs_ctx'] );

	return ob_get_clean();
}

add_shortcode( 'riches_queue_tabs', 'riches_queue_tabs_shortcode' );
