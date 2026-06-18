<?php
/**
 * Map embed: build JSON from ACF/CPT and render shortcode markup.
 *
 * @package UCF-WordPress-Theme-child-RICHES
 */

defined( 'ABSPATH' ) || exit;

/** Default pin color when category has none. */
const RICHES_MAP_DEFAULT_PIN_COLOR = '#6c757d';

/**
 * Sanitize a CSS height value for the map container.
 *
 * @param string $height Raw height from ACF.
 * @return string
 */
function riches_map_sanitize_height( $height ) {
	$height = trim( (string) $height );
	if ( $height === '' ) {
		return '420px';
	}
	if ( preg_match( '/^\d+(\.\d+)?(px|vh|vw|rem|em|%)$/', $height ) ) {
		return $height;
	}
	if ( preg_match( '/^\d+$/', $height ) ) {
		return $height . 'px';
	}
	return '420px';
}

/**
 * Normalize hex color to #rrggbb.
 *
 * @param string $color Raw color.
 * @return string
 */
function riches_map_normalize_hex_color( $color ) {
	$color = trim( (string) $color );
	if ( $color === '' ) {
		return RICHES_MAP_DEFAULT_PIN_COLOR;
	}
	if ( preg_match( '/^#([0-9a-fA-F]{3})$/', $color, $m ) ) {
		$c = $m[1];
		return '#' . $c[0] . $c[0] . $c[1] . $c[1] . $c[2] . $c[2];
	}
	if ( preg_match( '/^#([0-9a-fA-F]{6})$/', $color ) ) {
		return strtolower( $color );
	}
	return RICHES_MAP_DEFAULT_PIN_COLOR;
}

/**
 * Load a map post by slug.
 *
 * @param string $slug Map post_name.
 * @return WP_Post|null
 */
function riches_map_get_map_post_by_slug( $slug ) {
	$slug = sanitize_title( $slug );
	if ( ! $slug ) {
		return null;
	}
	$posts = get_posts(
		array(
			'post_type'              => 'riches_map',
			'name'                   => $slug,
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		)
	);
	return ! empty( $posts[0] ) ? $posts[0] : null;
}

/**
 * Category metadata for legend and markers.
 *
 * @param WP_Term $term Pin category term.
 * @return array{slug: string, label: string, color: string, order: int, description: string}
 */
function riches_map_pin_category_payload( $term ) {
	if ( ! $term instanceof WP_Term ) {
		return array();
	}
	$order = 10;
	$desc  = '';
	$color = RICHES_MAP_DEFAULT_PIN_COLOR;
	if ( function_exists( 'get_field' ) ) {
		$color = riches_map_normalize_hex_color( (string) get_field( 'pin_cat_color', $term ) );
		$order = (int) get_field( 'pin_cat_legend_order', $term );
		$desc  = (string) get_field( 'pin_cat_description', $term );
	}
	return array(
		'slug'        => $term->slug,
		'label'       => $term->name,
		'color'       => $color,
		'order'       => $order,
		'description' => $desc,
	);
}

/**
 * Resolve primary pin category term for a pin post.
 *
 * @param int $pin_id Pin post ID.
 * @return WP_Term|null
 */
function riches_map_pin_primary_category_term( $pin_id ) {
	$terms = get_the_terms( $pin_id, 'riches_map_pin_cat' );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return null;
	}
	return $terms[0];
}

/**
 * Build one pin object for JSON.
 *
 * @param WP_Post $pin Pin post.
 * @return array|null
 */
function riches_map_build_pin_payload( $pin ) {
	if ( ! $pin instanceof WP_Post ) {
		return null;
	}

	$lat = function_exists( 'get_field' ) ? get_field( 'pin_lat', $pin->ID ) : null;
	$lng = function_exists( 'get_field' ) ? get_field( 'pin_lng', $pin->ID ) : null;
	if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) ) {
		return null;
	}

	$term = riches_map_pin_primary_category_term( $pin->ID );
	$cat  = riches_map_pin_category_payload( $term );
	if ( empty( $cat['slug'] ) ) {
		$cat = array(
			'slug'  => 'uncategorized',
			'label' => __( 'Other', 'UCF-WordPress-Theme-child-RICHES' ),
			'color' => RICHES_MAP_DEFAULT_PIN_COLOR,
			'order' => 99,
		);
	}

	$teaser = function_exists( 'get_field' ) ? (string) get_field( 'pin_teaser', $pin->ID ) : '';
	$detail_raw = function_exists( 'get_field' ) ? (string) get_field( 'pin_detail', $pin->ID ) : '';
	$detail     = $detail_raw !== '' ? wp_kses_post( $detail_raw ) : '';
	$url    = function_exists( 'get_field' ) ? (string) get_field( 'pin_more_url', $pin->ID ) : '';

	return array(
		'id'       => (string) $pin->ID,
		'lat'      => (float) $lat,
		'lng'      => (float) $lng,
		'title'    => get_the_title( $pin ),
		'category' => $cat['slug'],
		'color'    => $cat['color'],
		'teaser'   => $teaser,
		'detail'   => $detail,
		'url'      => esc_url_raw( $url ),
	);
}

/**
 * Query pins for a map slug.
 *
 * @param string $map_slug Map post_name.
 * @return WP_Post[]
 */
function riches_map_query_pins_for_slug( $map_slug ) {
	$map_slug = sanitize_title( $map_slug );
	if ( ! $map_slug ) {
		return array();
	}

	return get_posts(
		array(
			'post_type'              => 'riches_map_pin',
			'post_status'            => 'publish',
			'posts_per_page'         => 500,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'meta_query'             => array(
				array(
					'key'     => 'pin_map_slug',
					'value'   => $map_slug,
					'compare' => '=',
				),
			),
		)
	);
}

/**
 * Assemble full map config for front-end script.
 *
 * @param string $map_slug Map post_name.
 * @return array|null Null if map not found or invalid center.
 */
function riches_map_build_config( $map_slug ) {
	$map_post = riches_map_get_map_post_by_slug( $map_slug );
	if ( ! $map_post ) {
		return null;
	}

	$center_lat = function_exists( 'get_field' ) ? get_field( 'map_center_lat', $map_post->ID ) : null;
	$center_lng = function_exists( 'get_field' ) ? get_field( 'map_center_lng', $map_post->ID ) : null;
	if ( ! is_numeric( $center_lat ) || ! is_numeric( $center_lng ) ) {
		return null;
	}

	$zoom    = function_exists( 'get_field' ) ? (int) get_field( 'map_zoom', $map_post->ID ) : 12;
	$height  = function_exists( 'get_field' ) ? (string) get_field( 'map_height', $map_post->ID ) : '420px';
	$legend  = function_exists( 'get_field' ) ? (bool) get_field( 'map_show_legend', $map_post->ID ) : true;

	$zoom = max( 1, min( 19, $zoom ) );

	$categories = array();
	$pins       = array();

	foreach ( riches_map_query_pins_for_slug( $map_slug ) as $pin_post ) {
		$payload = riches_map_build_pin_payload( $pin_post );
		if ( ! $payload ) {
			continue;
		}
		$pins[] = $payload;

		$slug = $payload['category'];
		if ( $slug && ! isset( $categories[ $slug ] ) ) {
			$term = get_term_by( 'slug', $slug, 'riches_map_pin_cat' );
			if ( $term instanceof WP_Term ) {
				$categories[ $slug ] = riches_map_pin_category_payload( $term );
			} elseif ( 'uncategorized' === $slug ) {
				$categories[ $slug ] = array(
					'slug'        => 'uncategorized',
					'label'       => __( 'Other', 'UCF-WordPress-Theme-child-RICHES' ),
					'color'       => RICHES_MAP_DEFAULT_PIN_COLOR,
					'order'       => 99,
					'description' => '',
				);
			}
		}
	}

	uasort(
		$categories,
		static function ( $a, $b ) {
			return ( $a['order'] ?? 10 ) <=> ( $b['order'] ?? 10 );
		}
	);

	return array(
		'slug'       => $map_slug,
		'title'      => get_the_title( $map_post ),
		'center'     => array( (float) $center_lat, (float) $center_lng ),
		'zoom'       => $zoom,
		'height'     => riches_map_sanitize_height( $height ),
		'legend'     => $legend,
		'categories' => $categories,
		'pins'       => $pins,
	);
}

/**
 * Mark that a map embed is on the current request (for asset enqueue).
 *
 * @param string $map_slug Map slug.
 */
function riches_map_mark_embed_present( $map_slug ) {
	if ( ! isset( $GLOBALS['riches_map_embed_slugs'] ) || ! is_array( $GLOBALS['riches_map_embed_slugs'] ) ) {
		$GLOBALS['riches_map_embed_slugs'] = array();
	}
	$GLOBALS['riches_map_embed_slugs'][ sanitize_title( $map_slug ) ] = true;
}

/**
 * Resolve map slug from shortcode attributes (slug, map, or id).
 *
 * @param array $atts Parsed shortcode attributes.
 * @return string
 */
function riches_map_shortcode_resolve_slug( $atts ) {
	if ( ! is_array( $atts ) ) {
		return '';
	}
	foreach ( array( 'slug', 'map', 'id' ) as $key ) {
		if ( ! empty( $atts[ $key ] ) ) {
			return sanitize_title( (string) $atts[ $key ] );
		}
	}
	return '';
}

/**
 * Build the embed shortcode string for editors to copy.
 *
 * @param string $slug   Map post_name.
 * @param string $height Optional height override.
 * @return string
 */
function riches_map_get_shortcode_string( $slug, $height = '' ) {
	$slug = sanitize_title( $slug );
	if ( ! $slug ) {
		return '[riches_map slug="your-map-slug"]';
	}
	$out = '[riches_map slug="' . $slug . '"';
	if ( $height !== '' ) {
		$out .= ' height="' . esc_attr( riches_map_sanitize_height( $height ) ) . '"';
	}
	$out .= ']';
	return $out;
}

/**
 * Whether post content (or blocks) contains a riches_map shortcode.
 *
 * @param string $content Post content.
 * @return bool
 */
function riches_map_content_includes_shortcode( $content ) {
	$content = (string) $content;
	if ( $content === '' ) {
		return false;
	}
	if ( has_shortcode( $content, 'riches_map' ) ) {
		return true;
	}
	return (bool) preg_match( '/\[riches_map(?:\s|\]|\/)/', $content );
}

/**
 * Whether the current singular content includes [riches_map].
 *
 * @return bool
 */
function riches_map_content_has_shortcode() {
	if ( ! is_singular() ) {
		return false;
	}
	$post = get_post();
	if ( ! $post instanceof WP_Post ) {
		return false;
	}
	return riches_map_content_includes_shortcode( $post->post_content );
}

/**
 * Shortcode callback: [riches_map slug="community" height="420px" full_width="1"]
 * Aliases: map= and id= for slug (same as slug=).
 *
 * @param array $atts Attributes.
 * @return string
 */
function riches_map_shortcode( $atts ) {
	$a = shortcode_atts(
		array(
			'slug'       => '',
			'map'        => '',
			'id'         => '',
			'height'     => '',
			'full_width' => '',
		),
		$atts,
		'riches_map'
	);

	$slug = riches_map_shortcode_resolve_slug( $a );
	if ( ! $slug ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return '<p class="riches-map__notice text-muted">' . esc_html__( 'Map shortcode: set slug="your-map-slug".', 'UCF-WordPress-Theme-child-RICHES' ) . '</p>';
		}
		return '';
	}

	$config = riches_map_build_config( $slug );
	if ( ! $config ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return '<p class="riches-map__notice text-muted">' . esc_html__( 'Map not found or missing center coordinates. Check RICHES Maps in the admin.', 'UCF-WordPress-Theme-child-RICHES' ) . '</p>';
		}
		return '';
	}

	if ( $a['height'] !== '' ) {
		$config['height'] = riches_map_sanitize_height( $a['height'] );
	}

	riches_map_mark_embed_present( $slug );

	$instance_id = 'riches-map-' . substr( md5( wp_json_encode( $config ) . (string) wp_rand() ), 0, 8 );
	$json        = wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP );
	if ( ! $json ) {
		return '';
	}

	$full_width = riches_shortcode_reduced_flag( $a['full_width'] );

	$map_classes = array( 'riches-map' );
	if ( $full_width ) {
		$map_classes[] = 'riches-map--full-width';
	}

	$legend_html = '';
	if ( ! empty( $config['legend'] ) && ! empty( $config['categories'] ) ) {
		$legend_html = '<ul class="riches-map__legend" aria-label="' . esc_attr__( 'Map pin categories', 'UCF-WordPress-Theme-child-RICHES' ) . '">';
		foreach ( $config['categories'] as $cat ) {
			$color = esc_attr( $cat['color'] );
			$legend_html .= '<li class="riches-map__legend-item">';
			$legend_html .= '<span class="riches-map__legend-swatch" style="--pin-fill:' . $color . ';" aria-hidden="true"></span>';
			$legend_html .= '<span class="riches-map__legend-label">';
			$legend_html .= esc_html( $cat['label'] );
			if ( ! empty( $cat['description'] ) ) {
				$legend_html .= ' <span class="riches-map__legend-desc text-muted">' . esc_html( $cat['description'] ) . '</span>';
			}
			$legend_html .= '</span></li>';
		}
		$legend_html .= '</ul>';
	}

	ob_start();
	?>
	<div
		class="<?php echo esc_attr( implode( ' ', $map_classes ) ); ?>"
		id="<?php echo esc_attr( $instance_id ); ?>"
		style="--riches-map-height: <?php echo esc_attr( $config['height'] ); ?>;"
	>
		<script type="application/json" class="riches-map__config"><?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON_HEX_TAG|JSON_HEX_AMP prevent </script> injection ?></script>
		<div class="riches-map__canvas" role="region" aria-label="<?php echo esc_attr( $config['title'] ); ?>"></div>
		<div class="riches-map__panel" hidden aria-live="polite">
			<button type="button" class="riches-map__panel-close" aria-label="<?php esc_attr_e( 'Close', 'UCF-WordPress-Theme-child-RICHES' ); ?>">&times;</button>
			<div class="riches-map__panel-body"></div>
		</div>
		<?php echo $legend_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_* above ?>
	</div>
	<?php
	return ob_get_clean();
}
