<?php
/**
 * Admin UX: copy-paste shortcode for map embeds.
 *
 * @package UCF-WordPress-Theme-child-RICHES
 */

defined( 'ABSPATH' ) || exit;

/**
 * Meta box: embed shortcode on map edit screen.
 */
function riches_map_add_embed_meta_box() {
	add_meta_box(
		'riches_map_embed',
		__( 'Embed on a page', 'UCF-WordPress-Theme-child-RICHES' ),
		'riches_map_render_embed_meta_box',
		'riches_map',
		'side',
		'high'
	);
}

add_action( 'add_meta_boxes', 'riches_map_add_embed_meta_box' );

/**
 * @param WP_Post $post Map post.
 */
function riches_map_render_embed_meta_box( $post ) {
	$slug = $post->post_name;
	if ( in_array( $post->post_status, array( 'auto-draft', 'draft' ), true ) && ! $slug ) {
		$slug = sanitize_title( $post->post_title );
	}
	$shortcode = riches_map_get_shortcode_string( $slug );
	$height    = function_exists( 'get_field' ) ? (string) get_field( 'map_height', $post->ID ) : '';
	?>
	<p class="description">
		<?php esc_html_e( 'Copy this into any Page or Post using a Shortcode block (block editor) or the shortcode field in the classic editor.', 'UCF-WordPress-Theme-child-RICHES' ); ?>
	</p>
	<p>
		<label for="riches-map-shortcode-field" class="screen-reader-text">
			<?php esc_html_e( 'Map embed shortcode', 'UCF-WordPress-Theme-child-RICHES' ); ?>
		</label>
		<input
			type="text"
			class="widefat code"
			id="riches-map-shortcode-field"
			readonly
			value="<?php echo esc_attr( $shortcode ); ?>"
			onclick="this.select();"
		/>
	</p>
	<p>
		<button type="button" class="button button-secondary" id="riches-map-copy-shortcode">
			<?php esc_html_e( 'Copy shortcode', 'UCF-WordPress-Theme-child-RICHES' ); ?>
		</button>
		<span id="riches-map-copy-status" class="description" style="margin-left:0.5em;" aria-live="polite"></span>
	</p>
	<?php if ( $slug ) : ?>
		<p class="description">
			<?php
			printf(
				/* translators: %s: map slug. */
				esc_html__( 'Map slug: %s', 'UCF-WordPress-Theme-child-RICHES' ),
				'<code>' . esc_html( $slug ) . '</code>'
			);
			?>
		</p>
	<?php else : ?>
		<p class="description">
			<?php esc_html_e( 'Save the map once to generate a slug, then copy the shortcode again.', 'UCF-WordPress-Theme-child-RICHES' ); ?>
		</p>
	<?php endif; ?>
	<?php if ( $height !== '' ) : ?>
		<p class="description">
			<?php
			printf(
				/* translators: %s: default height from ACF. */
				esc_html__( 'Default height from map settings: %s. Override with height="…" in the shortcode if needed.', 'UCF-WordPress-Theme-child-RICHES' ),
				'<code>' . esc_html( $height ) . '</code>'
			);
			?>
		</p>
	<?php endif; ?>
	<script>
	(function () {
		var btn = document.getElementById('riches-map-copy-shortcode');
		var field = document.getElementById('riches-map-shortcode-field');
		var status = document.getElementById('riches-map-copy-status');
		if (!btn || !field) {
			return;
		}
		btn.addEventListener('click', function () {
			field.select();
			field.setSelectionRange(0, 99999);
			var ok = false;
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(field.value).then(function () {
					ok = true;
					if (status) {
						status.textContent = <?php echo wp_json_encode( __( 'Copied!', 'UCF-WordPress-Theme-child-RICHES' ) ); ?>;
					}
				}).catch(function () {
					document.execCommand('copy');
					if (status) {
						status.textContent = <?php echo wp_json_encode( __( 'Copied!', 'UCF-WordPress-Theme-child-RICHES' ) ); ?>;
					}
				});
				return;
			}
			try {
				document.execCommand('copy');
				ok = true;
			} catch (e) {}
			if (status) {
				status.textContent = ok
					? <?php echo wp_json_encode( __( 'Copied!', 'UCF-WordPress-Theme-child-RICHES' ) ); ?>
					: <?php echo wp_json_encode( __( 'Select the field and copy manually.', 'UCF-WordPress-Theme-child-RICHES' ) ); ?>;
			}
		});
	})();
	</script>
	<?php
}

/**
 * List table column: shortcode snippet.
 *
 * @param array $columns Columns.
 * @return array
 */
function riches_map_list_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['riches_map_shortcode'] = __( 'Embed shortcode', 'UCF-WordPress-Theme-child-RICHES' );
		}
	}
	return $new;
}

add_filter( 'manage_riches_map_posts_columns', 'riches_map_list_columns' );

/**
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function riches_map_list_column_content( $column, $post_id ) {
	if ( 'riches_map_shortcode' !== $column ) {
		return;
	}
	$post = get_post( $post_id );
	if ( ! $post ) {
		return;
	}
	$code = riches_map_get_shortcode_string( $post->post_name );
	echo '<code style="user-select:all;">' . esc_html( $code ) . '</code>';
}

add_action( 'manage_riches_map_posts_custom_column', 'riches_map_list_column_content', 10, 2 );

/**
 * Ensure shortcodes run in Text widgets (Customizer / classic widgets).
 */
function riches_map_widget_text_shortcodes() {
	add_filter( 'widget_text', 'do_shortcode', 11 );
	add_filter( 'widget_text_content', 'do_shortcode', 11 );
}

add_action( 'init', 'riches_map_widget_text_shortcodes' );
