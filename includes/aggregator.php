<?php
/**
 * Aggregator page template support.
 *
 * Provides:
 *   - An ACF page field (per aggregator page) to choose which category to aggregate.
 *   - Four descriptive post custom fields (date recorded, location recorded,
 *     collection, name) used to filter/sort aggregated entries.
 *   - riches_render_aggregator(): loads ALL posts in the chosen category as cards
 *     carrying data-* attributes, preceded by an accessible filter/sort header.
 *   - Conditional enqueue of the client-side filter/sort script on the template.
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
 * Register the ACF field groups: the page category selector and the post fields.
 *
 * No-op if ACF is inactive. Mirrors includes/omeka-link.php and includes/map-pins-acf.php.
 */
function riches_register_aggregator_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	// Group A: which category this aggregator page pulls from (page-scoped).
	acf_add_local_field_group(
		array(
			'key'                   => 'group_riches_aggregator_page',
			'title'                 => __( 'Aggregator', 'UCF-WordPress-Theme-child-RICHES' ),
			'fields'                => array(
				array(
					'key'           => 'field_riches_agg_category',
					'label'         => __( 'Aggregated category', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'          => 'riches_agg_category',
					'type'          => 'taxonomy',
					'taxonomy'      => 'category',
					'field_type'    => 'select',
					'add_term'      => 0,
					'save_terms'    => 0, // do NOT make the page a member of the category
					'load_terms'    => 0,
					'return_format' => 'object',
					'allow_null'    => 1,
					'required'      => 1,
					'instructions'  => __( 'Choose the category whose posts this page aggregates.', 'UCF-WordPress-Theme-child-RICHES' ),
				),
			),
			'location'              => array(
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
			),
			'position'              => 'acf_after_title',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
		)
	);

	// Group B: descriptive fields on posts, used to sort/filter aggregated cards.
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
					'instructions'   => __( 'When the item was recorded/created. Used to sort aggregated entries.', 'UCF-WordPress-Theme-child-RICHES' ),
				),
				array(
					'key'   => 'field_riches_name',
					'label' => __( 'Name', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'  => 'riches_name',
					'type'  => 'text',
				),
				array(
					'key'          => 'field_riches_collection',
					'label'        => __( 'Collection', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'         => 'riches_collection',
					'type'         => 'text',
					'instructions' => __( 'Free text. Entries sharing a value are grouped in the Collection filter.', 'UCF-WordPress-Theme-child-RICHES' ),
				),
				array(
					'key'   => 'field_riches_location_recorded',
					'label' => __( 'Location recorded', 'UCF-WordPress-Theme-child-RICHES' ),
					'name'  => 'riches_location_recorded',
					'type'  => 'text',
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
 * Small guarded ACF read helper.
 *
 * @param string $name    Field name.
 * @param int    $post_id Post ID.
 * @return string
 */
function riches_aggregator_field( $name, $post_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}
	return (string) get_field( $name, $post_id );
}

/**
 * Render the aggregator: a filter/sort header + all posts in a category as cards.
 *
 * Does NOT reuse riches_render_category_queue() — that clamps posts_per_page to a
 * minimum of 1, so it cannot load all posts (posts_per_page => -1).
 *
 * @param array $args {
 *     @type string $category Category slug (required).
 * }
 * @return string HTML fragment (already escaped internally).
 */
function riches_render_aggregator( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'category' => '',
		)
	);

	$slug = sanitize_key( $args['category'] );
	if ( '' === $slug ) {
		if ( current_user_can( 'edit_pages' ) ) {
			return '<p class="text-muted">' . esc_html__( 'Aggregator: choose a category in the page editor (Aggregated category field).', 'UCF-WordPress-Theme-child-RICHES' ) . '</p>';
		}
		return '';
	}

	$q = new WP_Query(
		array(
			'post_type'      => 'post',
			'category_name'  => $slug,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	if ( ! $q->have_posts() ) {
		wp_reset_postdata();
		return '<p class="text-muted">' . esc_html__( 'No entries yet.', 'UCF-WordPress-Theme-child-RICHES' ) . '</p>';
	}

	$collections = array();
	$locations   = array();
	$link_titles = true;

	// Buffer the cards first so the header can list the distinct collection/location values.
	ob_start();
	while ( $q->have_posts() ) :
		$q->the_post();
		$pid   = get_the_ID();
		$f_date = riches_aggregator_field( 'riches_date_recorded', $pid );      // 'Ymd' or ''
		$f_name = riches_aggregator_field( 'riches_name', $pid );
		$f_coll = riches_aggregator_field( 'riches_collection', $pid );
		$f_loc  = riches_aggregator_field( 'riches_location_recorded', $pid );

		if ( '' !== trim( $f_coll ) ) {
			$collections[ $f_coll ] = true;
		}
		if ( '' !== trim( $f_loc ) ) {
			$locations[ $f_loc ] = true;
		}

		// Human-readable date: reformat Ymd, else fall back to the published date.
		$date_human = '';
		if ( '' !== $f_date ) {
			$dt = DateTime::createFromFormat( 'Ymd', $f_date );
			if ( $dt instanceof DateTime ) {
				$date_human = $dt->format( 'F j, Y' );
			}
		}

		// YouTube detection (parity with the standard card renderer).
		$_post_raw  = get_post();
		$raw_for_yt = ( $_post_raw instanceof WP_Post ) ? $_post_raw->post_content : '';
		preg_match(
			'/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
			$raw_for_yt,
			$yt_match
		);
		$yt_id = isset( $yt_match[1] ) ? $yt_match[1] : null;
		?>
		<div class="card riches-agg-card"
			data-name="<?php echo esc_attr( $f_name ); ?>"
			data-collection="<?php echo esc_attr( $f_coll ); ?>"
			data-location-recorded="<?php echo esc_attr( $f_loc ); ?>"
			data-date-recorded="<?php echo esc_attr( $f_date ); ?>"
			data-title="<?php echo esc_attr( get_the_title() ); ?>">
			<?php if ( $yt_id ) : ?>
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
						<?php if ( $link_titles ) : ?>
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						<?php else : ?>
							<?php the_title(); ?>
						<?php endif; ?>
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

	$collections = array_keys( $collections );
	$locations   = array_keys( $locations );
	sort( $collections, SORT_NATURAL | SORT_FLAG_CASE );
	sort( $locations, SORT_NATURAL | SORT_FLAG_CASE );

	ob_start();
	?>
	<div class="riches-aggregator">
		<div class="riches-aggregator__controls container" role="group" aria-label="<?php esc_attr_e( 'Filter and sort entries', 'UCF-WordPress-Theme-child-RICHES' ); ?>">
			<div class="riches-aggregator__field">
				<label for="riches-agg-search"><?php esc_html_e( 'Search by name or title', 'UCF-WordPress-Theme-child-RICHES' ); ?></label>
				<input type="search" id="riches-agg-search" class="riches-aggregator__search" autocomplete="off" placeholder="<?php esc_attr_e( 'Search…', 'UCF-WordPress-Theme-child-RICHES' ); ?>">
			</div>
			<div class="riches-aggregator__field">
				<label for="riches-agg-collection"><?php esc_html_e( 'Collection', 'UCF-WordPress-Theme-child-RICHES' ); ?></label>
				<select id="riches-agg-collection" class="riches-aggregator__filter" data-filter="collection">
					<option value=""><?php esc_html_e( 'All collections', 'UCF-WordPress-Theme-child-RICHES' ); ?></option>
					<?php foreach ( $collections as $c ) : ?>
						<option value="<?php echo esc_attr( $c ); ?>"><?php echo esc_html( $c ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="riches-aggregator__field">
				<label for="riches-agg-location"><?php esc_html_e( 'Location', 'UCF-WordPress-Theme-child-RICHES' ); ?></label>
				<select id="riches-agg-location" class="riches-aggregator__filter" data-filter="location">
					<option value=""><?php esc_html_e( 'All locations', 'UCF-WordPress-Theme-child-RICHES' ); ?></option>
					<?php foreach ( $locations as $l ) : ?>
						<option value="<?php echo esc_attr( $l ); ?>"><?php echo esc_html( $l ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="riches-aggregator__field">
				<label for="riches-agg-sort"><?php esc_html_e( 'Sort by', 'UCF-WordPress-Theme-child-RICHES' ); ?></label>
				<select id="riches-agg-sort" class="riches-aggregator__sort">
					<option value="date" selected><?php esc_html_e( 'Date recorded', 'UCF-WordPress-Theme-child-RICHES' ); ?></option>
					<option value="name"><?php esc_html_e( 'Name', 'UCF-WordPress-Theme-child-RICHES' ); ?></option>
					<option value="collection"><?php esc_html_e( 'Collection', 'UCF-WordPress-Theme-child-RICHES' ); ?></option>
					<option value="location"><?php esc_html_e( 'Location', 'UCF-WordPress-Theme-child-RICHES' ); ?></option>
					<option value="title"><?php esc_html_e( 'Title', 'UCF-WordPress-Theme-child-RICHES' ); ?></option>
				</select>
			</div>
			<div class="riches-aggregator__field">
				<label for="riches-agg-dir"><?php esc_html_e( 'Direction', 'UCF-WordPress-Theme-child-RICHES' ); ?></label>
				<select id="riches-agg-dir" class="riches-aggregator__dir">
					<option value="desc" selected><?php esc_html_e( 'Descending', 'UCF-WordPress-Theme-child-RICHES' ); ?></option>
					<option value="asc"><?php esc_html_e( 'Ascending', 'UCF-WordPress-Theme-child-RICHES' ); ?></option>
				</select>
			</div>
			<p class="riches-aggregator__status" role="status" aria-live="polite"></p>
		</div>

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
	if ( ! is_page_template( RICHES_AGGREGATOR_TEMPLATE ) ) {
		return;
	}
	wp_enqueue_script( 'riches-aggregator-filter' );
}
add_action( 'wp_enqueue_scripts', 'riches_aggregator_enqueue_assets', 20 );
