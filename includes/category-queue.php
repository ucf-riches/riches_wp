<?php
/**
 * Shared renderer for category-based post queues (card decks).
 *
 * @package UCF-WordPress-Theme-child-RICHES
 */

/**
 * Whether a shortcode attribute enables "reduced" layout (no category title row / extra links).
 *
 * @param mixed $value Raw attribute value.
 * @return bool
 */
function riches_shortcode_reduced_flag( $value ) {
	if ( null === $value || '' === $value || false === $value ) {
		return false;
	}
	if ( true === $value || 1 === $value || '1' === $value ) {
		return true;
	}
	if ( is_string( $value ) ) {
		return in_array( strtolower( trim( $value ) ), array( '1', 'true', 'yes', 'on', 'reduced' ), true );
	}
	return (bool) $value;
}

/**
 * Parse a truthy/falsy shortcode attribute, returning $default when unset/blank.
 *
 * Accepts 1/true/yes/on (true) and 0/false/no/off (false), case-insensitive.
 *
 * @param mixed $value   Raw attribute value.
 * @param bool  $default Value to use when the attribute is absent or empty.
 * @return bool
 */
function riches_shortcode_bool_flag( $value, $default = true ) {
	if ( null === $value || '' === $value ) {
		return $default;
	}
	if ( is_bool( $value ) ) {
		return $value;
	}
	if ( is_string( $value ) ) {
		$v = strtolower( trim( $value ) );
		if ( in_array( $v, array( '0', 'false', 'no', 'off' ), true ) ) {
			return false;
		}
		if ( in_array( $v, array( '1', 'true', 'yes', 'on' ), true ) ) {
			return true;
		}
	}
	return (bool) $value;
}

/**
 * Render a category queue as a card deck (and optional section heading).
 *
 * @param array $args {
 *     @type string $category        Category slug (required).
 *     @type string $label           Display label for heading / tabs.
 *     @type int    $posts_per_page  Number of posts. Default 3.
 *     @type bool   $show_heading     If true, output h2 + prominent "View all" archive link. Default true (ignored when reduced is true).
 *     @type bool   $reduced          If true, omit heading row; show a compact archive link after the cards instead.
 *     @type bool   $wrap_collections If true, wrap in .riches-collections (standalone rows). Default true.
 *     @type bool   $include_container If true, wrap inner markup in .container. Default true (set false for tab panels).
 * }
 * @return string HTML fragment.
 */
function riches_render_category_queue( $args ) {
	$defaults = array(
		'category'          => '',
		'label'             => '',
		'posts_per_page'    => 3,
		'show_heading'      => true,
		'reduced'           => false,
		'wrap_collections'  => true,
		'include_container' => true,
		'link_titles'       => true,
	);

	$args = wp_parse_args( $args, $defaults );

	$slug = sanitize_key( $args['category'] );
	if ( ! $slug ) {
		return '';
	}

	$label_plain    = $args['label'] !== '' ? (string) $args['label'] : $slug;
	$posts_per_page = absint( $args['posts_per_page'] );
	if ( $posts_per_page < 1 ) {
		$posts_per_page = 3;
	}

	$reduced            = ! empty( $args['reduced'] );
	$show_heading       = $reduced ? false : (bool) $args['show_heading'];
	$wrap_collections   = (bool) $args['wrap_collections'];
	$include_container  = (bool) $args['include_container'];
	$link_titles        = (bool) $args['link_titles'];
	$cat                = get_category_by_slug( $slug );
	$cat_link           = $cat ? get_category_link( $cat->term_id ) : null;

	$q = new WP_Query(
		array(
			'post_type'      => 'post',
			'category_name'  => $slug,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'posts_per_page' => $posts_per_page,
		)
	);
	update_post_thumbnail_cache( $q );

	ob_start();

	if ( $wrap_collections ) {
		$section_classes = array( 'riches-collections' );
		if ( $reduced ) {
			$section_classes[] = 'riches-collections--reduced';
		}
		echo '<div class="' . esc_attr( implode( ' ', $section_classes ) ) . '">';
	}
	if ( $include_container ) {
		echo '<div class="container mt-4 mt-sm-5 mb-5 pb-sm-4">';
	}
	?>
		<?php if ( $show_heading ) : ?>
		<h2 class="d-flex align-items-baseline justify-content-between flex-wrap">
			<span class="riches-queue-archive__heading-text"><?php echo esc_html( $label_plain ); ?></span>
			<?php if ( $cat_link ) : ?>
				<a href="<?php echo esc_url( $cat_link ); ?>" class="riches-queue-archive riches-queue-archive--full riches-queue-archive--full-inline h6 ml-md-3 mt-2 mt-md-0 mb-0"><?php esc_html_e( 'View all', 'UCF-WordPress-Theme-child-RICHES' ); ?> <span aria-hidden="true">&rarr;</span></a>
			<?php endif; ?>
		</h2>
		<?php endif; ?>
		<?php if ( $q->have_posts() ) : ?>
			<div class="card-deck mb-3">
				<?php
				while ( $q->have_posts() ) :
					$q->the_post();
					/*
					 * Use raw post_content for YouTube detection — not get_the_content().
					 * get_the_content() returns only the teaser when <!--more--> exists and
					 * $more is 0, which is typical in secondary loops (e.g. tab panels): the
					 * queried object is the parent page, not the post in the loop, so URLs
					 * below the fold never appear and the thumbnail branch is skipped.
					 */
					$_post_raw = get_post();
					$yt_id     = riches_youtube_id_from_content( ( $_post_raw instanceof WP_Post ) ? $_post_raw->post_content : '' );
					?>
					<div class="card">
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
								<p class="card-text text-muted"><?php the_time( 'F j, Y' ); ?></p>
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
								<p class="card-text text-muted"><?php the_time( 'F j, Y' ); ?></p>
							</div>
						<?php endif; ?>
					</div>
				<?php endwhile; ?>
			</div>
		<?php else : ?>
			<p class="text-muted"><?php esc_html_e( 'No posts yet.', 'UCF-WordPress-Theme-child-RICHES' ); ?></p>
		<?php endif; ?>
		<?php if ( $reduced && $cat_link ) : ?>
			<p class="riches-queue-archive riches-queue-archive--reduced mb-0">
				<a href="<?php echo esc_url( $cat_link ); ?>">
					<?php
					printf(
						/* translators: %s: Category or section display name. */
						esc_html__( 'View all in %s', 'UCF-WordPress-Theme-child-RICHES' ),
						esc_html( $label_plain )
					);
					?>
					<span aria-hidden="true">&rarr;</span>
				</a>
			</p>
		<?php elseif ( ! $show_heading && $cat_link && ! $reduced ) : ?>
			<p class="riches-queue-archive riches-queue-archive--full riches-queue-archive--full-block mb-0">
				<a href="<?php echo esc_url( $cat_link ); ?>">
					<?php
					printf(
						/* translators: %s: Category or section display name. */
						esc_html__( 'View all in %s', 'UCF-WordPress-Theme-child-RICHES' ),
						esc_html( $label_plain )
					);
					?>
					<span aria-hidden="true">&rarr;</span>
				</a>
			</p>
		<?php endif; ?>
	<?php
	if ( $include_container ) {
		echo '</div>';
	}
	if ( $wrap_collections ) {
		echo '</div>';
	}

	wp_reset_postdata();

	return ob_get_clean();
}
