<?php
/**
 * Blog posts index (home.php).
 *
 * Renders the WordPress "Posts page" with the aggregator carding + filter/sort UI
 * instead of the default post list. The posts page cannot take a page template —
 * core routes is_home() through home.php/index.php and ignores the page's own
 * template — so this file is how the aggregator design reaches the posts index.
 *
 * Pools ALL published posts. The filter/sort config is read from the Posts page's
 * own ACF fields, which are surfaced there via the group's "Posts page" location
 * rule (see includes/aggregator.php).
 *
 * @package UCF-WordPress-Theme-child-RICHES
 */

get_header();

$posts_page_id = (int) get_option( 'page_for_posts' );
$posts_page    = $posts_page_id ? get_post( $posts_page_id ) : null;
?>

<article class="post-list-item">
	<?php if ( $posts_page instanceof WP_Post && '' !== trim( (string) $posts_page->post_content ) ) : ?>
		<div class="container mt-4 mt-sm-5 mb-5 pb-sm-4">
			<?php echo apply_filters( 'the_content', $posts_page->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core the_content filters ?>
		</div>
	<?php elseif ( $posts_page instanceof WP_Post ) : ?>
		<div class="container mt-4 mt-sm-5 mb-5 pb-sm-4">
			<h1 class="mb-0"><?php echo esc_html( get_the_title( $posts_page_id ) ); ?></h1>
		</div>
	<?php endif; ?>

	<?php
	echo riches_render_aggregator( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped internally
		array(
			'all_posts'      => true,
			'config_post_id' => $posts_page_id,
		)
	);
	?>
</article>

<?php get_footer(); ?>
