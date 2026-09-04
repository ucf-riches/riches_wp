<?php
/**
 * Card link bar renderer.
 *
 * URL and label resolution live in the riches-core plugin
 * (riches_get_omeka_url(), riches_get_omeka_link_text()). This file only
 * renders; it outputs nothing when the plugin is inactive.
 *
 * @package UCF-WordPress-Theme-child-RICHES
 */

defined( 'ABSPATH' ) || exit;

/**
 * Echo the card link bar for a post, if a URL resolves.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function riches_render_omeka_bar( $post_id ) {
	if ( ! function_exists( 'riches_get_omeka_url' ) || ! function_exists( 'riches_get_omeka_link_text' ) ) {
		return;
	}
	$url = riches_get_omeka_url( $post_id );
	if ( '' === $url ) {
		return;
	}
	$text = riches_get_omeka_link_text( $post_id );
	?>
	<div class="riches-omeka-bar">
		<a class="riches-omeka-bar__link btn btn-primary btn-sm" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
			<span class="fas fa-external-link-alt" aria-hidden="true"></span>
			<span><?php echo esc_html( $text ); ?></span>
			<span class="sr-only">: <?php echo esc_html( get_the_title( $post_id ) ); ?> <?php esc_html_e( '(opens in a new tab)', 'UCF-WordPress-Theme-child-RICHES' ); ?></span>
		</a>
	</div>
	<?php
}
