<?php
/**
 * Handle all theme configuration here
 **/

/*
 * TEMPORARY (layout assessment): when false, oral-history / YouTube posts render
 * with the standard featured-image card EVERYWHERE — the aggregator template and
 * the frontpage shortcode queues — instead of the dedicated YouTube tile. Set to
 * true (or delete this block) to restore the oral-history card.
 */
if ( ! defined( 'RICHES_USE_YOUTUBE_CARD' ) ) {
    define( 'RICHES_USE_YOUTUBE_CARD', false );
}

/**
 * Force the parent theme to load Font Awesome 5 (5.15.4, solid + regular + brands).
 *
 * The parent reads the `font_awesome_version` theme mod. Filtering it here keeps
 * the choice in code, so every environment loads exactly one icon set without a
 * Customizer step. The parent's Customizer control for this setting is
 * therefore inert while this filter is active.
 *
 * @param mixed $value Stored theme mod value.
 * @return string
 */
function riches_force_font_awesome_5( $value ) {
	return '5';
}
add_filter( 'theme_mod_font_awesome_version', 'riches_force_font_awesome_5' );

add_action( 'wp_enqueue_scripts', 'riches_enqueue_styles', 11 );
function riches_enqueue_styles() {
	$output_css = get_stylesheet_directory() . '/static/css/output.css';
	wp_enqueue_style( 'child-style', get_stylesheet_uri() );
	wp_enqueue_style( 'output', get_stylesheet_directory_uri() . '/static/css/output.css', array(), file_exists( $output_css ) ? filemtime( $output_css ) : null );
}

 ?>