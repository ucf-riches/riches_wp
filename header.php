<!DOCTYPE html>
<html lang="en-us">

<head>
	<?php wp_head(); ?>
</head>

<body ontouchstart <?php body_class(); ?>>
	<a class="skip-navigation bg-complementary text-inverse box-shadow-soft" href="#content">Skip to main content</a>
	<div id="ucfhb"></div>

	<?php do_action( 'after_body_open' ); ?>
	<div id="banner-holder">
		<div class="bannerarea" style="height: 500px; background-position: center; background-repeat: no-repeat; background-size: cover; 
		background-image: url( <?php $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' ); 
		$bgimage=get_stylesheet_directory_uri() . '/static/images/common-banner.png';
		if ( get_page_template_slug( $post->ID )=="template-home.php" && has_post_thumbnail() ) { echo $image[0]; } else { echo $bgimage; } ?> )"></div>
	</div>
	<header class="site-header">
		<?php richeswp_the_sticky_nav(); ?>
	</header>

	<main class="site-main">
		<div class="site-content" id="content" tabindex="-1">
