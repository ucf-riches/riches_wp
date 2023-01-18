<?php 

//Theme support


include_once 'includes/header-functions.php';
include_once 'includes/footer-functions.php';
include_once 'includes/config.php';
include_once 'includes/shortcodes.php';

/*
add_action('wp_enqueue_scripts', 'riches_theme_enqueue_style', 10, 0);
function riches_theme_enqueue_style() {
    wp_enqueue_style(
        'parent-style',
        get_template_directory_uri() . "style.css",
        []
        filemtime(get_template_directory() . "style.css",
        'all'
    );

    wp_enqueue_style(
        'child-style',
        get_stylesheet_directory_uri() . "style.css",
        ['parent-style'],
        filemtime(get_stylesheet_directory() . "style.css"),
        'all'
    );
}*/
?>
