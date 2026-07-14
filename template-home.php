<?php
/**
 * Template Name: Home
 * Template Post Type: page
 */
?>
<?php get_header(); the_post(); ?>

<article class="<?php echo $post->post_status; ?> post-list-item">
	<div class="container mt-4 mt-sm-5 mb-5 pb-sm-4">
		<?php the_content(); ?>
	</div>
</article>
<div class="riches-collections">
	<div class="container mt-4 mt-sm-5 mb-5 pb-sm-4">
		<h2>Recently-Added Collections</h2>
		<div class='card-deck mb-3'>
		<?php
		/*
		$gallery_shortcode = '[gallery id="' . intval( $post->post_parent ) . '"]';
			print apply_filters( 'the_content', $gallery_shortcode );
		*/
		$temp= get_stylesheet_directory_uri() . '/static/images/riches-header.png';
		$args = array(
			'post_type' => 'post' ,
			'orderby' => 'date' ,
			'order' => 'DESC' ,
			'posts_per_page' => 3,
			'paged' => get_query_var('paged')
		);
		$q = new WP_Query($args);
		$i=0;

		if ( $q->have_posts() && $i <= 3) {
			while ( $q->have_posts() ) {
				$q->the_post();
				// your loop ?>
				<div class='card'>
					<!--<img class='card-img-top' src='<?php print $temp ?>' alt='featured collection'>-->
					<?php $sizing = array(350, 350);
					the_post_thumbnail($sizing); ?>
					<?php riches_render_omeka_bar( get_the_ID() ); ?>
					<div class='card-block'>
						<h4 class='card-title'><?php the_title(); ?></h4>
						<p class='card text'><?php the_excerpt(); ?></p>
						<p class='card text'><?php the_time( 'F j, Y' ); ?></p>
					</div>
				</div>

			<?php $i++;
			}
		}
		?>
		</div>
	</div>
</div>
<?php get_footer(); ?>
