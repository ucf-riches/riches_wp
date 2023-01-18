<?php
/**
 * Footer Related Functions
 **/

/**
 * Returns markup for the site footer. Will return an empty string if all
 * footer sidebars are empty.
 *
 * @author Jo Dickson
 * @since 1.0.0
 * @return string Footer HTML markup
 **/
if ( !function_exists( 'richeswp_get_footer_markup' ) ) {
	function richeswp_get_footer_markup() {
		ob_start();
		$uri = get_stylesheet_directory_uri() . '/static/images/sponsors.png';
	?>
		<!--<footer class="site-footer bg-inverse pt-4 py-md-5">-->
		<footer class="riches-footer site-footer pt-4 py-md-5">
			<div class="container mt-4">
				<div class="row">
					<section class="col-12 col-lg">
						
						<h2 class="riches-title">RICHES of Central Florida</h2>
						<p class="riches-subtitle">Regional Initiative for Collecting the History, Experiences and Stories</p>
						<ul>
							<li><a href="https://www.facebook.com/RICHESMosaicInterface/" class=""><span class="fab fa-facebook-square"></span></a></li>
							<li><a href="https://twitter.com/RichesMI" class=""><span class="fab fa-twitter-square"></span></a></li>
							<li><a href="https://www.youtube.com/channel/UCLgfZLqLwE6M2yUyeOp0IQA" class=""><span class="fab fa-youtube-square"></span></a></li>
							<!--<li><a href="" class=""><span class="fab fa-pinterest-square"></span></a></li>-->
						</ul>
						<img src="<?php echo $uri ?>" style="display: inline-block; padding-bottom: 10px;" width='850px'>
						<p class="address">Department of History, Trevor Colburn Hall Suite XX | 4000 Central Florida Blvd. Orlando, FL 32816-1350 | Phone: 407-823-0242 | Fax: 407-823-3184</p>

					</section>
				</div>
			</div>

		</footer>
	<?php
		return ob_get_clean();
	}
}
?>
