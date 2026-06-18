<?php
/**
 * Map pins: custom post types and taxonomies (Leaflet embed foundation).
 *
 * @package UCF-WordPress-Theme-child-RICHES
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post type: single map definition (center, zoom, which pins via slug match).
 */
function riches_register_map_post_type() {
	register_post_type(
		'riches_map',
		array(
			'labels'              => array(
				'name'          => __( 'RICHES Maps', 'UCF-WordPress-Theme-child-RICHES' ),
				'singular_name' => __( 'RICHES Map', 'UCF-WordPress-Theme-child-RICHES' ),
				'add_new_item'  => __( 'Add New Map', 'UCF-WordPress-Theme-child-RICHES' ),
				'edit_item'     => __( 'Edit Map', 'UCF-WordPress-Theme-child-RICHES' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-location-alt',
			'menu_position'       => 26,
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => array( 'title' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'show_in_rest'        => true,
		)
	);
}

/**
 * Post type: one pin per location.
 */
function riches_register_map_pin_post_type() {
	register_post_type(
		'riches_map_pin',
		array(
			'labels'              => array(
				'name'          => __( 'Map Pins', 'UCF-WordPress-Theme-child-RICHES' ),
				'singular_name' => __( 'Map Pin', 'UCF-WordPress-Theme-child-RICHES' ),
				'add_new_item'  => __( 'Add New Pin', 'UCF-WordPress-Theme-child-RICHES' ),
				'edit_item'     => __( 'Edit Pin', 'UCF-WordPress-Theme-child-RICHES' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-admin-site',
			'menu_position'       => 27,
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => array( 'title' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'show_in_rest'        => true,
		)
	);
}

/**
 * Taxonomy: pin category (color + legend metadata via ACF on terms).
 */
function riches_register_map_pin_category_taxonomy() {
	register_taxonomy(
		'riches_map_pin_cat',
		array( 'riches_map_pin' ),
		array(
			'labels'            => array(
				'name'          => __( 'Pin categories', 'UCF-WordPress-Theme-child-RICHES' ),
				'singular_name' => __( 'Pin category', 'UCF-WordPress-Theme-child-RICHES' ),
				'add_new_item'  => __( 'Add pin category', 'UCF-WordPress-Theme-child-RICHES' ),
				'edit_item'     => __( 'Edit pin category', 'UCF-WordPress-Theme-child-RICHES' ),
			),
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'meta_box_cb'       => false,
			'hierarchical'      => false,
			'rewrite'           => false,
			'show_in_rest'      => true,
		)
	);
}

add_action( 'init', 'riches_register_map_post_type', 5 );
add_action( 'init', 'riches_register_map_pin_post_type', 5 );
add_action( 'init', 'riches_register_map_pin_category_taxonomy', 5 );

/**
 * Flush rewrite rules once when theme is switched (CPT/tax registered).
 */
function riches_map_pins_rewrite_flush() {
	riches_register_map_post_type();
	riches_register_map_pin_post_type();
	riches_register_map_pin_category_taxonomy();
	flush_rewrite_rules( false );
}

add_action( 'after_switch_theme', 'riches_map_pins_rewrite_flush' );
