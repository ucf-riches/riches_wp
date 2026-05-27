<?php
/**
 * Sticky navigation bar template.
 *
 * Assign a menu under Appearance → Menus → Sticky Navigation.
 *
 * @package UCF-WordPress-Theme-child-RICHES
 */

$theme_location = richeswp_get_sticky_nav_theme_location();
$title_elem     = function_exists( 'ucfwp_get_nav_title_elem' ) ? ucfwp_get_nav_title_elem() : 'span';
$brand_logo_url = richeswp_get_brand_logo_url();
$brand_alt      = richeswp_get_brand_logo_alt();

$menu = wp_nav_menu(
	array(
		'container'       => 'div',
		'container_class' => 'collapse navbar-collapse align-self-lg-stretch',
		'container_id'    => 'sticky-nav-menu',
		'depth'           => 2,
		'echo'            => false,
		'fallback_cb'     => class_exists( 'bs4Navwalker' ) ? 'bs4Navwalker::fallback' : false,
		'menu_class'      => 'nav navbar-nav ml-md-auto',
		'theme_location'  => $theme_location,
		'walker'          => class_exists( 'bs4Navwalker' ) ? new bs4Navwalker() : null,
	)
);

if ( ! $menu ) {
	return;
}
?>

<nav
	id="riches-sticky-nav"
	class="riches-sticky-nav navbar navbar-toggleable-md navbar-custom navbar-inverse bg-inverse-t-3"
	aria-label="<?php esc_attr_e( 'Sticky site navigation', 'UCF-WordPress-Theme-child-RICHES' ); ?>"
>
	<div class="container d-flex flex-row flex-nowrap justify-content-between">
		<?php if ( $title_elem ) : ?>
		<<?php echo esc_html( $title_elem ); ?> class="mb-0">
			<a class="navbar-brand riches-navbar-brand mr-lg-5" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img
					src="<?php echo esc_url( $brand_logo_url ); ?>"
					alt="<?php echo esc_attr( $brand_alt ); ?>"
					width="1742"
					height="503"
					decoding="async"
				>
			</a>
		</<?php echo esc_html( $title_elem ); ?>>
		<?php endif; ?>

		<button
			class="navbar-toggler ml-auto align-self-start collapsed"
			type="button"
			data-toggle="collapse"
			data-target="#sticky-nav-menu"
			aria-controls="sticky-nav-menu"
			aria-expanded="false"
			aria-label="<?php esc_attr_e( 'Toggle navigation', 'UCF-WordPress-Theme-child-RICHES' ); ?>"
		>
			<span class="navbar-toggler-text"><?php esc_html_e( 'Navigation', 'UCF-WordPress-Theme-child-RICHES' ); ?></span>
			<span class="navbar-toggler-icon"></span>
		</button>

		<?php echo $menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</nav>
