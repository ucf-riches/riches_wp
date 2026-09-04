<?php
/**
 * Aggregator page template rendering.
 *
 * The ACF configuration groups and source-resolution helpers live in the
 * riches-core plugin (includes/aggregator-fields.php). This file provides
 * riches_render_aggregator(), which reads that configuration and renders the
 * control bar plus the pooled posts as cards, and the conditional enqueue of
 * the client-side filter/sort script.
 *
 * Filtering/sorting stays fully client-side (no AJAX); see
 * static/js/riches-aggregator-filter.js.
 *
 * @package UCF-WordPress-Theme-child-RICHES
 */


defined( 'ABSPATH' ) || exit;

/**
 * Fallback for the template filename when riches-core is inactive. The plugin
 * loads first and defines the canonical value.
 */
if ( ! defined( 'RICHES_AGGREGATOR_TEMPLATE' ) ) {
	define( 'RICHES_AGGREGATOR_TEMPLATE', 'template-aggregator.php' );
}

/**
 * Render the aggregator: a configurable control bar + the pooled posts as cards.
 *
 * The pool is the base category and all of its descendants. Search box, filter
 * dropdowns, and sort options render only when the page is configured for them.
 *
 * @param array $args {
 *     @type string $category       Base category slug. Pools that category + its
 *                                  descendants. Ignored when 'all_posts' is true.
 *     @type bool   $all_posts      Pool every published post (used by home.php for
 *                                  the blog posts index). Default false.
 *     @type int    $config_post_id Post/page ID to read the filter/sort ACF config
 *                                  from. Defaults to the current post (0). The posts
 *                                  page passes get_option('page_for_posts') here.
 * }
 * @return string HTML fragment (already escaped internally).
 */
function riches_render_aggregator( $args = array() ) {
	if ( ! function_exists( 'riches_agg_resolve_source' ) || ! function_exists( 'riches_aggregator_field' ) ) {
		if ( current_user_can( 'edit_pages' ) ) {
			return '<p class="text-muted">' . esc_html__( 'Aggregator: activate the RICHES Core plugin to configure and render this page.', 'UCF-WordPress-Theme-child-RICHES' ) . '</p>';
		}
		return '';
	}

	$args = wp_parse_args(
		$args,
		array(
			'category'       => '',
			'all_posts'      => false,
			'config_post_id' => 0,
		)
	);

	// Read page config up front — the WP_Query loop below rebinds the global post.
	$page_id     = $args['config_post_id'] ? (int) $args['config_post_id'] : (int) get_the_ID();
	$show_search = (bool) riches_aggregator_field( 'riches_agg_show_search', $page_id );
	$sort_keys   = (array) riches_aggregator_field( 'riches_agg_sorts', $page_id );
	$raw_filters = (array) riches_aggregator_field( 'riches_agg_filters', $page_id );

	// Resolve the pool: every post, or one base category + its descendants.
	$base_term = false;
	if ( ! $args['all_posts'] ) {
		$slug      = sanitize_key( $args['category'] );
		$base_term = ( '' !== $slug ) ? get_term_by( 'slug', $slug, 'category' ) : false;
		if ( ! $base_term instanceof WP_Term ) {
			if ( current_user_can( 'edit_pages' ) ) {
				return '<p class="text-muted">' . esc_html__( 'Aggregator: choose a Base category in the page editor.', 'UCF-WordPress-Theme-child-RICHES' ) . '</p>';
			}
			return '';
		}
	}

	// Resolve the configured filters into a working structure.
	$filters = array();
	foreach ( $raw_filters as $row ) {
		$resolved = riches_agg_resolve_source( isset( $row['source'] ) ? $row['source'] : '' );
		if ( null === $resolved ) {
			continue;
		}
		$filters[] = array(
			'label'     => isset( $row['label'] ) ? (string) $row['label'] : '',
			'taxonomy'  => $resolved['taxonomy'],
			'parent_id' => $resolved['parent_id'],
			'sortable'  => ! empty( $row['sortable'] ),
			// Term IDs allowed as options (descendants of the parent), or null for the whole taxonomy.
			'allowed'   => $resolved['parent_id']
				? array_flip( (array) get_term_children( $resolved['parent_id'], $resolved['taxonomy'] ) )
				: null,
			'options'   => array(), // slug => name, accumulated in the loop
		);
	}

	// The distinct taxonomies we must emit as data-* attributes on each card.
	$needed_taxes   = array();
	$sortable_taxes = array();
	foreach ( $filters as $f ) {
		$needed_taxes[ $f['taxonomy'] ] = true;
		if ( $f['sortable'] ) {
			$sortable_taxes[ $f['taxonomy'] ] = true;
		}
	}

	$query_args = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
		'posts_per_page' => -1,
		'no_found_rows'  => true,
	);
	if ( $base_term instanceof WP_Term ) {
		$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy'         => 'category',
				'field'            => 'term_id',
				'terms'            => (int) $base_term->term_id,
				'include_children' => true,
			),
		);
	}
	$q = new WP_Query( $query_args );
	// One query for every attachment post + one for their meta, instead of two per card.
	update_post_thumbnail_cache( $q );

	if ( ! $q->have_posts() ) {
		wp_reset_postdata();
		return '<p class="text-muted">' . esc_html__( 'No entries yet.', 'UCF-WordPress-Theme-child-RICHES' ) . '</p>';
	}

	// Buffer the cards first so the control bar can list the terms actually present.
	ob_start();
	$card_index = 0;
	while ( $q->have_posts() ) :
		$q->the_post();
		$card_index++;
		$pid    = get_the_ID();
		$f_date = (string) riches_aggregator_field( 'riches_date_recorded', $pid ); // 'Ymd' or ''

		// Human-readable date: reformat Ymd, else fall back to the published date.
		$date_human = '';
		if ( '' !== $f_date ) {
			$dt = DateTime::createFromFormat( 'Ymd', $f_date );
			if ( $dt instanceof DateTime ) {
				$date_human = $dt->format( 'F j, Y' );
			}
		}

		// Per-taxonomy term data for this post; feed the filter option lists too.
		$tax_slugs      = array(); // taxonomy => 'slug slug'
		$tax_first_name = array(); // taxonomy => lowercased first term name (sort key)
		$search_names   = array();
		foreach ( $needed_taxes as $tax => $_true ) {
			$terms = get_the_terms( $pid, $tax );
			$slugs = array();
			$names = array();
			if ( is_array( $terms ) ) {
				foreach ( $terms as $tt ) {
					$slugs[]        = $tt->slug;
					$names[]        = $tt->name;
					$search_names[] = $tt->name;
					// Accumulate options for each filter on this taxonomy (respecting parent scope).
					foreach ( $filters as $i => $f ) {
						if ( $f['taxonomy'] !== $tax ) {
							continue;
						}
						if ( null !== $f['allowed'] && ! isset( $f['allowed'][ $tt->term_id ] ) ) {
							continue;
						}
						$filters[ $i ]['options'][ $tt->slug ] = $tt->name;
					}
				}
			}
			sort( $names, SORT_NATURAL | SORT_FLAG_CASE );
			$tax_slugs[ $tax ]      = implode( ' ', $slugs );
			$tax_first_name[ $tax ] = isset( $names[0] ) ? strtolower( $names[0] ) : '';
		}

		// Generalized data-* attributes consumed by the client filter/sort script.
		$data_attrs  = ' data-title="' . esc_attr( get_the_title() ) . '"';
		$data_attrs .= ' data-search="' . esc_attr( strtolower( get_the_title() . ' ' . implode( ' ', $search_names ) ) ) . '"';
		$data_attrs .= ' data-date-recorded="' . esc_attr( $f_date ) . '"';
		$data_attrs .= ' data-published="' . esc_attr( get_the_date( 'Ymd' ) ) . '"';
		foreach ( $needed_taxes as $tax => $_true ) {
			$data_attrs .= ' data-tax-' . esc_attr( $tax ) . '="' . esc_attr( $tax_slugs[ $tax ] ) . '"';
			if ( isset( $sortable_taxes[ $tax ] ) ) {
				$data_attrs .= ' data-taxsort-' . esc_attr( $tax ) . '="' . esc_attr( $tax_first_name[ $tax ] ) . '"';
			}
		}

		// YouTube detection (shared helper; parity with the standard card renderer).
		$_post_raw = get_post();
		$yt_id     = ( function_exists( 'riches_youtube_id_from_content' ) && $_post_raw instanceof WP_Post )
			? riches_youtube_id_from_content( $_post_raw->post_content )
			: null;
		?>
		<div class="card riches-agg-card"<?php echo $data_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- assembled from esc_attr() values above ?>>
			<?php if ( $yt_id && RICHES_USE_YOUTUBE_CARD ) : ?>
				<?php
				$yt_url   = 'https://www.youtube.com/watch?v=' . rawurlencode( $yt_id );
				$yt_thumb = 'https://img.youtube.com/vi/' . rawurlencode( $yt_id ) . '/hqdefault.jpg';
				?>
				<a href="<?php echo esc_url( $yt_url ); ?>" target="_blank" rel="noopener noreferrer" class="yt-card-link">
					<img src="<?php echo esc_url( $yt_thumb ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>">
					<div class="yt-overlay">
						<p><?php the_title(); ?></p>
					</div>
				</a>
				<?php riches_render_omeka_bar( get_the_ID() ); ?>
				<div class="card-block">
					<p class="card-text text-muted"><?php echo esc_html( '' !== $date_human ? $date_human : get_the_time( 'F j, Y' ) ); ?></p>
				</div>
			<?php else : ?>
				<?php
				the_post_thumbnail(
					'large',
					array(
						'class'   => 'card-img-top',
						// home.php never runs the main loop, so core treats every image here as above the fold and adds no loading attribute (see wp_get_loading_optimization_attributes, the before_loop rule); set it explicitly, exempting the first row.
						'loading' => ( $card_index <= 3 ) ? false : 'lazy',
					)
				);
				?>
				<?php riches_render_omeka_bar( get_the_ID() ); ?>
				<div class="card-block">
					<h4 class="card-title">
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</h4>
					<p class="card-text"><?php the_excerpt(); ?></p>
					<p class="card-text text-muted"><?php echo esc_html( '' !== $date_human ? $date_human : get_the_time( 'F j, Y' ) ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	endwhile;
	$cards_html = ob_get_clean();
	wp_reset_postdata();

	// Keep only filters that actually surfaced terms; sort their options naturally.
	$active_filters = array();
	foreach ( $filters as $f ) {
		if ( empty( $f['options'] ) ) {
			continue;
		}
		natcasesort( $f['options'] );
		$active_filters[] = $f;
	}

	// Build the "Sort by" option list: chosen built-ins, then sortable filters.
	$sort_labels = array(
		'title'         => __( 'Title', 'UCF-WordPress-Theme-child-RICHES' ),
		'published'     => __( 'Date posted', 'UCF-WordPress-Theme-child-RICHES' ),
		'date_recorded' => __( 'Date recorded', 'UCF-WordPress-Theme-child-RICHES' ),
	);
	$sort_opts = array();
	foreach ( array( 'title', 'published', 'date_recorded' ) as $k ) {
		if ( in_array( $k, $sort_keys, true ) ) {
			$sort_opts[] = array(
				'type'  => $k,
				'tax'   => '',
				'label' => $sort_labels[ $k ],
			);
		}
	}
	foreach ( $active_filters as $f ) {
		if ( $f['sortable'] ) {
			$sort_opts[] = array(
				'type'  => 'taxonomy',
				'tax'   => $f['taxonomy'],
				'label' => $f['label'],
			);
		}
	}

	$has_controls = $show_search || ! empty( $active_filters ) || ! empty( $sort_opts );
	// Dates default to newest-first; text/term sorts default A→Z.
	$default_desc = ! empty( $sort_opts ) && in_array( $sort_opts[0]['type'], array( 'date_recorded', 'published' ), true );

	ob_start();
	?>
	<div class="riches-aggregator">
		<?php if ( $has_controls ) : ?>
			<div class="riches-aggregator__controls container" role="group" aria-label="<?php esc_attr_e( 'Filter and sort entries', 'UCF-WordPress-Theme-child-RICHES' ); ?>">
				<?php if ( $show_search ) : ?>
					<div class="riches-aggregator__field">
						<label for="riches-agg-search"><?php esc_html_e( 'Search by title or term', 'UCF-WordPress-Theme-child-RICHES' ); ?></label>
						<input type="search" id="riches-agg-search" class="riches-aggregator__search" autocomplete="off" placeholder="<?php esc_attr_e( 'Search…', 'UCF-WordPress-Theme-child-RICHES' ); ?>">
					</div>
				<?php endif; ?>

				<?php foreach ( $active_filters as $idx => $f ) : ?>
					<?php $field_id = 'riches-agg-filter-' . $idx; ?>
					<div class="riches-aggregator__field">
						<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $f['label'] ); ?></label>
						<select id="<?php echo esc_attr( $field_id ); ?>" class="riches-aggregator__filter" data-filter-taxonomy="<?php echo esc_attr( $f['taxonomy'] ); ?>">
							<option value="">
								<?php
								/* translators: %s: filter label, e.g. "All Collections" */
								echo esc_html( sprintf( __( 'All %s', 'UCF-WordPress-Theme-child-RICHES' ), $f['label'] ) );
								?>
							</option>
							<?php foreach ( $f['options'] as $slug => $name ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endforeach; ?>

				<?php if ( ! empty( $sort_opts ) ) : ?>
					<div class="riches-aggregator__field">
						<label for="riches-agg-sort"><?php esc_html_e( 'Sort by', 'UCF-WordPress-Theme-child-RICHES' ); ?></label>
						<select id="riches-agg-sort" class="riches-aggregator__sort">
							<?php foreach ( $sort_opts as $s ) : ?>
								<option value="<?php echo esc_attr( $s['type'] ); ?>" data-sort-type="<?php echo esc_attr( $s['type'] ); ?>" data-sort-taxonomy="<?php echo esc_attr( $s['tax'] ); ?>">
									<?php echo esc_html( $s['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="riches-aggregator__field">
						<label for="riches-agg-dir"><?php esc_html_e( 'Direction', 'UCF-WordPress-Theme-child-RICHES' ); ?></label>
						<select id="riches-agg-dir" class="riches-aggregator__dir">
							<option value="desc" <?php selected( $default_desc ); ?>><?php esc_html_e( 'Descending', 'UCF-WordPress-Theme-child-RICHES' ); ?></option>
							<option value="asc" <?php selected( ! $default_desc ); ?>><?php esc_html_e( 'Ascending', 'UCF-WordPress-Theme-child-RICHES' ); ?></option>
						</select>
					</div>
				<?php endif; ?>

				<p class="riches-aggregator__status" role="status" aria-live="polite"></p>
			</div>
		<?php endif; ?>

		<p class="riches-aggregator__empty container text-muted"><?php esc_html_e( 'No entries match your filters.', 'UCF-WordPress-Theme-child-RICHES' ); ?></p>

		<div class="riches-collections">
			<div class="container">
				<div class="card-deck mb-3">
					<?php echo $cards_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with escaped values above ?>
				</div>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Register the client-side filter/sort script (footer, filemtime-versioned).
 */
function riches_aggregator_register_assets() {
	$theme_uri  = get_stylesheet_directory_uri();
	$theme_path = get_stylesheet_directory();
	$js_file    = $theme_path . '/static/js/riches-aggregator-filter.js';

	wp_register_script(
		'riches-aggregator-filter',
		$theme_uri . '/static/js/riches-aggregator-filter.js',
		array(),
		file_exists( $js_file ) ? (string) filemtime( $js_file ) : '1.0.0',
		true
	);
}
add_action( 'wp_enqueue_scripts', 'riches_aggregator_register_assets', 5 );

/**
 * Enqueue the filter/sort script only on the aggregator page template.
 */
function riches_aggregator_enqueue_assets() {
	// Aggregator template pages, plus the blog posts index (home.php).
	if ( ! is_page_template( RICHES_AGGREGATOR_TEMPLATE ) && ! is_home() ) {
		return;
	}
	wp_enqueue_script( 'riches-aggregator-filter' );
}
add_action( 'wp_enqueue_scripts', 'riches_aggregator_enqueue_assets', 20 );
