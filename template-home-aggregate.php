<?php
/**
 * Template Name: Home Aggregate
 * Template Post Type: page
 */
?>
<?php get_header(); the_post(); ?>

<article class="<?php echo $post->post_status; ?> post-list-item">
	<div class="container mt-4 mt-sm-5 mb-5 pb-sm-4">
		<?php the_content(); ?>
	</div>
</article>

<?php
/*
Example (also works in page content):

echo do_shortcode( '[riches_queue_tabs default="history-harvests"]
[riches_category_row category="history-harvests" label="History Harvests"]
[riches_category_row category="oral-history" label="Oral Histories"]
[riches_category_row category="collections-exhibits" label="Collections & Exhibits"]
[riches_category_row category="digital-projects" label="Digital Projects"]
[/riches_queue_tabs]' );

Tabs: add reduced="1" on the wrapper to drop the in-panel category footer link on every tab (rows without their own reduced= attribute).

Standalone row without h2 / footer link:
[riches_category_row category="oral-history" label="Oral Histories" reduced="1"]

If you render [riches_queue_tabs] only via do_shortcode in PHP (not in post content), add for body_class:
add_filter( 'riches_queue_tabs_force_enqueue', '__return_true' );
*/
?>

<?php get_footer(); ?>
