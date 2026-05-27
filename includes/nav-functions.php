<?php
/**
 * Sticky navigation for WordPress menus.
 *
 * @package UCF-WordPress-Theme-child-RICHES
 */

/**
 * Register the sticky navigation menu location.
 */
function richeswp_register_sticky_nav_menu() {
	register_nav_menus(
		array(
			'sticky-nav' => __( 'Sticky Navigation', 'UCF-WordPress-Theme-child-RICHES' ),
		)
	);
}
add_action( 'after_setup_theme', 'richeswp_register_sticky_nav_menu' );

/**
 * URL for the RICHES brand logo in the sticky nav.
 *
 * @return string
 */
function richeswp_get_brand_logo_url() {
	$url = get_stylesheet_directory_uri() . '/static/images/riches-main-medium.png';

	return apply_filters( 'richeswp_brand_logo_url', $url );
}

/**
 * Accessible alt text for brand logo images.
 *
 * @return string
 */
function richeswp_get_brand_logo_alt() {
	return apply_filters( 'richeswp_brand_logo_alt', __( 'RICHES', 'UCF-WordPress-Theme-child-RICHES' ) );
}

/**
 * Theme location used for the sticky bar (falls back to header-menu).
 *
 * @return string
 */
function richeswp_get_sticky_nav_theme_location() {
	$location = 'sticky-nav';

	if ( ! has_nav_menu( $location ) && has_nav_menu( 'header-menu' ) ) {
		$location = 'header-menu';
	}

	return apply_filters( 'richeswp_sticky_nav_theme_location', $location );
}

/**
 * Returns markup for the sticky navigation bar, or an empty string when no menu is assigned.
 *
 * @return string
 */
function richeswp_get_sticky_nav_markup() {
	if ( ! has_nav_menu( richeswp_get_sticky_nav_theme_location() ) ) {
		return '';
	}

	ob_start();
	get_template_part( 'template-parts/sticky', 'nav' );

	return ob_get_clean();
}

/**
 * Echoes the sticky navigation bar markup.
 */
function richeswp_the_sticky_nav() {
	echo richeswp_get_sticky_nav_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
