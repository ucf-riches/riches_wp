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

add_action( 'wp_enqueue_scripts', 'riches_enqueue_styles', 11 );
function riches_enqueue_styles() {
    $output_css = get_stylesheet_directory() . '/static/css/output.css';
    wp_enqueue_style( 'child-style', get_stylesheet_uri() );
    wp_enqueue_style('output', get_stylesheet_directory_uri() . '/static/css/output.css', array(), file_exists( $output_css ) ? filemtime( $output_css ) : null);
    wp_enqueue_style('fa', get_stylesheet_directory_uri() . '/static/fontawesome/css/all.min.css', array(), null);
}

 ?>