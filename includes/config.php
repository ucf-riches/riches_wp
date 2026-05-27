<?php
/**
 * Handle all theme configuration here
 **/

add_action( 'wp_enqueue_scripts', 'riches_enqueue_styles', 11 );
function riches_enqueue_styles() {
    wp_enqueue_style( 'child-style', get_stylesheet_uri() );
    wp_enqueue_style('output', get_stylesheet_directory_uri() . '/static/css/output.css', array(), null);
    wp_enqueue_style('fa', get_stylesheet_directory_uri() . '/static/fontawesome/css/all.min.css', array(), null);
}

 ?>