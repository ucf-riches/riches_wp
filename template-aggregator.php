<?php
/**
 * Template Name: Aggregator
 * Template Post Type: page
 *
 * Loads every post in the category chosen via the "Aggregated category" page
 * field and renders them as cards with a filter/sort header. See
 * includes/aggregator.php.
 */
?>
<?php get_header(); the_post(); ?>

<article class="<?php echo $post->post_status; ?> post-list-item">
	<div class="container mt-4 mt-sm-5 mb-5 pb-sm-4">
		<?php the_content(); ?>
	</div>

	<?php
	$term = function_exists( 'get_field' ) ? get_field( 'riches_agg_category' ) : null;
	$slug = ( $term instanceof WP_Term ) ? $term->slug : '';
	echo riches_render_aggregator( array( 'category' => $slug ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped internally
	?>
</article>

<?php get_footer(); ?>
