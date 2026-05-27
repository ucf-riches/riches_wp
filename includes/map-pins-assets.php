<?php
/**
 * Enqueue Leaflet and RICHES map assets when a map embed is present.
 *
 * @package UCF-WordPress-Theme-child-RICHES
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether map scripts/styles should load on this request.
 *
 * @return bool
 */
function riches_map_should_enqueue_assets() {
	if ( ! empty( $GLOBALS['riches_map_embed_slugs'] ) && is_array( $GLOBALS['riches_map_embed_slugs'] ) ) {
		return true;
	}
	return riches_map_content_has_shortcode();
}

/**
 * Register Leaflet (CDN) and theme map bundle.
 */
function riches_map_register_assets() {
	$ver_leaflet = '1.9.4';
	$theme_uri   = get_stylesheet_directory_uri();
	$theme_path  = get_stylesheet_directory();

	wp_register_style(
		'leaflet',
		'https://unpkg.com/leaflet@' . $ver_leaflet . '/dist/leaflet.css',
		array(),
		$ver_leaflet
	);

	wp_register_script(
		'leaflet',
		'https://unpkg.com/leaflet@' . $ver_leaflet . '/dist/leaflet.js',
		array(),
		$ver_leaflet,
		true
	);

	$js_file = $theme_path . '/static/js/riches-map.js';
	wp_register_script(
		'riches-map',
		$theme_uri . '/static/js/riches-map.js',
		array( 'leaflet' ),
		file_exists( $js_file ) ? (string) filemtime( $js_file ) : '1.0.0',
		true
	);
}

add_action( 'wp_enqueue_scripts', 'riches_map_register_assets', 5 );

/**
 * Enqueue when shortcode ran or content contains shortcode.
 */
function riches_map_enqueue_assets() {
	if ( ! riches_map_should_enqueue_assets() ) {
		return;
	}
	wp_enqueue_style( 'leaflet' );
	wp_enqueue_script( 'riches-map' );
}

add_action( 'wp_enqueue_scripts', 'riches_map_enqueue_assets', 20 );

/**
 * Shortcode runs after wp_enqueue_scripts; enqueue in footer if a map rendered earlier.
 */
function riches_map_enqueue_assets_late() {
	if ( ! riches_map_should_enqueue_assets() ) {
		return;
	}
	if ( wp_script_is( 'riches-map', 'enqueued' ) ) {
		return;
	}
	riches_map_enqueue_assets();
}

add_action( 'wp_footer', 'riches_map_enqueue_assets_late', 1 );

/**
 * Defer shortcode detection: parse post content before enqueue priority 20.
 */
function riches_map_prime_shortcode_from_content() {
	if ( ! riches_map_content_has_shortcode() ) {
		return;
	}
	$post = get_post();
	if ( ! $post instanceof WP_Post ) {
		return;
	}
	if ( preg_match_all( '/\[riches_map\s+([^\]]+)\]/', (string) $post->post_content, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $m ) {
			$atts = shortcode_parse_atts( $m[1] );
			$slug = riches_map_shortcode_resolve_slug( is_array( $atts ) ? $atts : array() );
			if ( $slug ) {
				riches_map_mark_embed_present( $slug );
			}
		}
	}
}

add_action( 'wp', 'riches_map_prime_shortcode_from_content', 5 );
