<?php
/**
 * ACF local field groups: map viewport, pins, pin category colors.
 *
 * Requires Advanced Custom Fields. If ACF is inactive, groups are not registered.
 *
 * @package UCF-WordPress-Theme-child-RICHES
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register all map-related field groups.
 */
function riches_map_pins_register_acf_field_groups() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'                   => 'group_riches_map_pin_cat',
			'title'                 => __( 'Pin category appearance', 'UCF-WordPress-Theme-child-RICHES' ),
			'fields'                => array(
				array(
					'key'               => 'field_riches_pcc_color',
					'label'             => __( 'Marker color', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'              => 'pin_cat_color',
					'type'              => 'color_picker',
					'default_value'     => '#0d6efd',
					'enable_opacity'    => 0,
					'return_format'     => 'string',
				),
				array(
					'key'           => 'field_riches_pcc_legend_order',
					'label'         => __( 'Legend order', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'          => 'pin_cat_legend_order',
					'type'          => 'number',
					'default_value' => 10,
					'min'           => 0,
					'step'          => 1,
					'instructions'  => __( 'Lower numbers appear first in the map legend.', 'UCF-WordPress-Theme-child-RICHES' ),
				),
				array(
					'key'          => 'field_riches_pcc_description',
					'label'        => __( 'Legend subtitle', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'         => 'pin_cat_description',
					'type'         => 'text',
					'instructions' => __( 'Optional short line under the category name in the legend.', 'UCF-WordPress-Theme-child-RICHES' ),
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'taxonomy',
						'operator' => '==',
						'value'    => 'riches_map_pin_cat',
					),
				),
			),
			'position'              => 'acf_after_title',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
		)
	);

	acf_add_local_field_group(
		array(
			'key'                   => 'group_riches_map_pin',
			'title'                 => __( 'Map pin details', 'UCF-WordPress-Theme-child-RICHES' ),
			'fields'                => array(
				array(
					'key'          => 'field_riches_pin_map_slug',
					'label'        => __( 'Map slug', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'         => 'pin_map_slug',
					'type'         => 'text',
					'required'     => 1,
					'instructions' => __( 'Must match the slug of a RICHES Map post (e.g. community). Pins only appear on that map embed.', 'UCF-WordPress-Theme-child-RICHES' ),
				),
				array(
					'key'          => 'field_riches_pin_lat',
					'label'        => __( 'Latitude', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'         => 'pin_lat',
					'type'         => 'number',
					'required'     => 1,
					'step'         => 'any',
				),
				array(
					'key'          => 'field_riches_pin_lng',
					'label'        => __( 'Longitude', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'         => 'pin_lng',
					'type'         => 'number',
					'required'     => 1,
					'step'         => 'any',
				),
				array(
					'key'          => 'field_riches_pin_category',
					'label'        => __( 'Pin category', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'         => 'pin_category',
					'type'         => 'taxonomy',
					'taxonomy'     => 'riches_map_pin_cat',
					'field_type'   => 'select',
					'allow_null'   => 0,
					'add_term'     => 1,
					'save_terms'   => 1,
					'load_terms'   => 1,
					'return_format' => 'id',
					'required'     => 1,
				),
				array(
					'key'          => 'field_riches_pin_teaser',
					'label'        => __( 'Short description (hover)', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'         => 'pin_teaser',
					'type'         => 'textarea',
					'rows'         => 2,
					'new_lines'    => 'br',
					'required'     => 1,
				),
				array(
					'key'          => 'field_riches_pin_detail',
					'label'        => __( 'Detail (click / toggle)', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'         => 'pin_detail',
					'type'         => 'wysiwyg',
					'tabs'         => 'all',
					'toolbar'      => 'basic',
					'media_upload' => 0,
				),
				array(
					'key'   => 'field_riches_pin_more_url',
					'label' => __( 'Optional link URL', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'  => 'pin_more_url',
					'type'  => 'url',
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'riches_map_pin',
					),
				),
			),
			'position'              => 'acf_after_title',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
		)
	);

	acf_add_local_field_group(
		array(
			'key'                   => 'group_riches_map',
			'title'                 => __( 'Map display', 'UCF-WordPress-Theme-child-RICHES' ),
			'fields'                => array(
				array(
					'key'          => 'field_riches_map_center_lat',
					'label'        => __( 'Center latitude', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'         => 'map_center_lat',
					'type'         => 'number',
					'required'     => 1,
					'step'         => 'any',
				),
				array(
					'key'          => 'field_riches_map_center_lng',
					'label'        => __( 'Center longitude', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'         => 'map_center_lng',
					'type'         => 'number',
					'required'     => 1,
					'step'         => 'any',
				),
				array(
					'key'           => 'field_riches_map_zoom',
					'label'         => __( 'Zoom level', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'          => 'map_zoom',
					'type'          => 'number',
					'default_value' => 12,
					'min'           => 1,
					'max'           => 19,
					'step'          => 1,
				),
				array(
					'key'           => 'field_riches_map_height',
					'label'         => __( 'Embed height', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'          => 'map_height',
					'type'          => 'text',
					'default_value' => '420px',
					'instructions'  => __( 'CSS height for the map box, e.g. 420px or 60vh.', 'UCF-WordPress-Theme-child-RICHES' ),
				),
				array(
					'key'           => 'field_riches_map_show_legend',
					'label'         => __( 'Show legend', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'          => 'map_show_legend',
					'type'          => 'true_false',
					'default_value' => 1,
					'ui'            => 1,
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'riches_map',
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

add_action( 'acf/init', 'riches_map_pins_register_acf_field_groups' );
