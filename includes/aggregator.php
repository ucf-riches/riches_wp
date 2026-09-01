<?php
/**
 * Aggregator page template support.
 *
 * Provides:
 *   - Page-scoped ACF config: a base category (pool = that term + descendants),
 *     a search toggle, a repeater of modular taxonomy "filter" facets, and an
 *     opt-in set of sort options.
 *   - One post ACF field (date recorded) used as a structured sort key.
 *   - riches_render_aggregator(): loads the pool as cards carrying generalized
 *     data-* attributes, preceded by a control bar whose search box, filter
 *     dropdowns, and sort options render ONLY when the page configures them.
 *   - Conditional enqueue of the client-side filter/sort script on the template.
 *
 * Filtering/sorting stays fully client-side (no AJAX); see
 * static/js/riches-aggregator-filter.js.
 *
 * @package UCF-WordPress-Theme-child-RICHES
 */


defined( 'ABSPATH' ) || exit;

/**
 * The page template filename. Must match the actual template file, the ACF
 * page_template location rule, and the asset enqueue gate.
 */
if ( ! defined( 'RICHES_AGGREGATOR_TEMPLATE' ) ) {
	define( 'RICHES_AGGREGATOR_TEMPLATE', 'template-aggregator.php' );
}

/**
 * Register the ACF field groups: the page config and the post sort field.
 *
 * No-op if ACF is inactive. Mirrors includes/omeka-link.php and the riches-map plugin's includes/acf-fields.php.
 */
function riches_register_aggregator_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	// Group A: per-page configuration for what this aggregator pools, filters, and sorts.
	acf_add_local_field_group(
		array(
			'key'                   => 'group_riches_aggregator_page',
			'title'                 => __( 'Aggregator', 'UCF-WordPress-Theme-child-RICHES' ),
			'fields'                => array(
				array(
					'key'           => 'field_riches_agg_category',
					'label'         => __( 'Base category', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'          => 'riches_agg_category',
					'type'          => 'taxonomy',
					'taxonomy'      => 'category',
					'field_type'    => 'select',
					'add_term'      => 0,
					'save_terms'    => 0, // do NOT make the page a member of the category
					'load_terms'    => 0,
					'return_format' => 'object',
					'allow_null'    => 1,
					'required'      => 0,
					'instructions'  => __( 'The pool: this category and all of its child categories. Ignored on the Posts page, which pools all posts.', 'UCF-WordPress-Theme-child-RICHES' ),
				),
				array(
					'key'           => 'field_riches_agg_show_search',
					'label'         => __( 'Show search box', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'          => 'riches_agg_show_search',
					'type'          => 'true_false',
					'ui'            => 1,
					'default_value' => 1,
					'instructions'  => __( 'A text box that searches entry titles and their term labels.', 'UCF-WordPress-Theme-child-RICHES' ),
				),
				array(
					'key'          => 'field_riches_agg_filters',
					'label'        => __( 'Filter dropdowns', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'         => 'riches_agg_filters',
					'type'         => 'repeater',
					'layout'       => 'table',
					'button_label' => __( 'Add filter', 'UCF-WordPress-Theme-child-RICHES' ),
					'instructions' => __( 'Each row adds one dropdown to the live filter bar. A dropdown appears only if the pool actually contains matching terms.', 'UCF-WordPress-Theme-child-RICHES' ),
					'sub_fields'   => array(
						array(
							'key'          => 'field_riches_agg_filter_label',
							'label'        => __( 'Label', 'UCF-WordPress-Theme-child-RICHES' ),
							'name'         => 'label',
							'type'         => 'text',
							'required'     => 1,
							'instructions' => __( 'Shown above the dropdown, e.g. "Collections".', 'UCF-WordPress-Theme-child-RICHES' ),
						),
						array(
							'key'          => 'field_riches_agg_filter_source',
							'label'        => __( 'Filter source', 'UCF-WordPress-Theme-child-RICHES' ),
							'name'         => 'source',
							'type'         => 'select',
							'ui'           => 1,
							'required'     => 1,
							'choices'      => array(), // populated in riches_agg_filter_source_choices()
							'instructions' => __( 'A whole taxonomy ("All Categories/Tags") or the sub-terms of a nested parent.', 'UCF-WordPress-Theme-child-RICHES' ),
						),
						array(
							'key'          => 'field_riches_agg_filter_sortable',
							'label'        => __( 'Sortable', 'UCF-WordPress-Theme-child-RICHES' ),
							'name'         => 'sortable',
							'type'         => 'true_false',
							'ui'           => 1,
							'instructions' => __( 'Also offer "Sort by this label" (alphabetical by term).', 'UCF-WordPress-Theme-child-RICHES' ),
						),
					),
				),
				array(
					'key'           => 'field_riches_agg_sorts',
					'label'         => __( 'Sort options', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'          => 'riches_agg_sorts',
					'type'          => 'checkbox',
					'choices'       => array(
						'title'         => __( 'Title (A–Z)', 'UCF-WordPress-Theme-child-RICHES' ),
						'published'     => __( 'Date posted', 'UCF-WordPress-Theme-child-RICHES' ),
						'date_recorded' => __( 'Date recorded', 'UCF-WordPress-Theme-child-RICHES' ),
					),
					'default_value' => array( 'title', 'date_recorded' ),
					'instructions'  => __( 'Which sort keys the live "Sort by" dropdown offers. Sortable filters (above) are added automatically.', 'UCF-WordPress-Theme-child-RICHES' ),
				),
			),
			'location'              => array(
				// Aggregator template pages …
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'page',
					),
					array(
						'param'    => 'page_template',
						'operator' => '==',
						'value'    => RICHES_AGGREGATOR_TEMPLATE,
					),
				),
				// … OR the blog "Posts page" (Settings → Reading), rendered by home.php.
				array(
					array(
						'param'    => 'page_type',
						'operator' => '==',
						'value'    => 'posts_page',
					),
				),
			),
			'position'              => 'acf_after_title',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
		)
	);

	// Group B: the one structured post field used as a sort key.
	acf_add_local_field_group(
		array(
			'key'                   => 'group_riches_aggregator_post',
			'title'                 => __( 'Aggregator metadata', 'UCF-WordPress-Theme-child-RICHES' ),
			'fields'                => array(
				array(
					'key'            => 'field_riches_date_recorded',
					'label'          => __( 'Date recorded', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'           => 'riches_date_recorded',
					'type'           => 'date_picker',
					'display_format' => 'F j, Y',
					'return_format'  => 'Ymd',
					'first_day'      => 0,
					'instructions'   => __( 'When the item was recorded/created. Used as a sort key on aggregator pages.', 'UCF-WordPress-Theme-child-RICHES' ),
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'post',
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
add_action( 'acf/init', 'riches_register_aggregator_fields' );

/**
 * Populate the "Filter source" select with real dropdown choices.
 *
 * Offers, per public taxonomy: a whole-taxonomy option ("All <Label>",
 * value "tax:<name>") and, for every hierarchical term that has children,
 * a nested-parent option ("<Label> › <Term> (children)", value "term:<id>").
 *
 * @param array $field The ACF field being loaded.
 * @return array
 */
function riches_agg_filter_source_choices( $field ) {
	$choices    = array();
	$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
	$exclude    = array( 'post_format' );

	foreach ( $taxonomies as $tax ) {
		if ( in_array( $tax->name, $exclude, true ) ) {
			continue;
		}

		$choices[ 'tax:' . $tax->name ] = sprintf(
			/* translators: %s: taxonomy plural label */
			__( 'All %s', 'UCF-WordPress-Theme-child-RICHES' ),
			$tax->labels->name
		);

		if ( ! $tax->hierarchical ) {
			continue;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $tax->name,
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $terms ) ) {
			continue;
		}
		foreach ( $terms as $term ) {
			$children = get_term_children( $term->term_id, $tax->name );
			if ( empty( $children ) ) {
				continue; // only terms that can scope sub-terms are useful as a parent
			}
			$choices[ 'term:' . $term->term_id ] = sprintf(
				/* translators: 1: taxonomy label, 2: term name */
				__( '%1$s › %2$s (children)', 'UCF-WordPress-Theme-child-RICHES' ),
				$tax->labels->singular_name,
				$term->name
			);
		}
	}

	$field['choices'] = $choices;
	return $field;
}
add_filter( 'acf/load_field/key=field_riches_agg_filter_source', 'riches_agg_filter_source_choices' );

/**
 * Small guarded ACF read helper.
 *
 * @param string $name    Field name.
 * @param int    $post_id Post ID.
 * @return mixed Field value, or '' when ACF is inactive.
 */
function riches_aggregator_field( $name, $post_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}
	return get_field( $name, $post_id );
}

/**
 * Resolve a "Filter source" value into a taxonomy + optional parent scope.
 *
 * @param string $source Stored value: "tax:<name>" or "term:<id>".
 * @return array|null { taxonomy: string, parent_id: int } or null if invalid.
 */
function riches_agg_resolve_source( $source ) {
	$source = (string) $source;

	if ( 0 === strpos( $source, 'tax:' ) ) {
		$tax = substr( $source, 4 );
		return taxonomy_exists( $tax ) ? array(
			'taxonomy'  => $tax,
			'parent_id' => 0,
		) : null;
	}

	if ( 0 === strpos( $source, 'term:' ) ) {
		$term = get_term( (int) substr( $source, 5 ) );
		if ( $term instanceof WP_Term ) {
			return array(
				'taxonomy'  => $term->taxonomy,
				'parent_id' => (int) $term->term_id,
			);
		}
	}

	return null;
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

	if ( ! $q->have_posts() ) {
		wp_reset_postdata();
		return '<p class="text-muted">' . esc_html__( 'No entries yet.', 'UCF-WordPress-Theme-child-RICHES' ) . '</p>';
	}

	// Buffer the cards first so the control bar can list the terms actually present.
	ob_start();
	while ( $q->have_posts() ) :
		$q->the_post();
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
		$yt_id     = riches_youtube_id_from_content( ( $_post_raw instanceof WP_Post ) ? $_post_raw->post_content : '' );
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
				<?php the_post_thumbnail( 'large', array( 'class' => 'card-img-top' ) ); ?>
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
