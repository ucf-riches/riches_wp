<?php
/**
 * Header Related Functions
 **/

/**
 * Registers the parent theme's "Page Header Fields" ACF field group.
 *
 * The UCF parent theme's dynamic per-page header
 * (see UCF-WordPress-Theme/template-parts/header-media.php) reads per-page
 * image / video / title fields via get_field(). The parent ships these fields
 * only as a manual export (dev/acf-export.json) and does not register them in
 * code, so we register the "Page Header Fields" group here. Without it,
 * ucfwp_get_header_images() always returns false and no banner is rendered.
 *
 * @author Kirk Lundblade
 * @since 1.0.0
 **/
function richeswp_register_page_header_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	// dev/acf-export.json lives in the parent (template) theme.
	$export = get_template_directory() . '/dev/acf-export.json';
	if ( ! is_readable( $export ) ) {
		return;
	}

	$groups = json_decode( file_get_contents( $export ), true );
	if ( ! is_array( $groups ) ) {
		return;
	}

	foreach ( $groups as $group ) {
		// "Page Header Fields" group key from the parent theme's export.
		if ( isset( $group['key'] ) && 'group_58f7a73f5fecc' === $group['key'] ) {
			acf_add_local_field_group( $group );
			break;
		}
	}
}
add_action( 'acf/init', 'richeswp_register_page_header_fields' );


/**
 * Returns the Media Library attachment ID for the default header banner
 * (static/images/common-banner.png), importing it into the Media Library
 * once and caching the ID in an option on subsequent calls.
 *
 * The parent theme's header <picture> pipeline
 * (ucfwp_get_media_background_picture_srcs) works off attachment IDs, so the
 * static banner must exist as an attachment to be usable as a fallback.
 *
 * @author Kirk Lundblade
 * @since 1.0.0
 * @return int Attachment ID, or 0 on failure.
 **/
function richeswp_get_common_banner_attachment_id() {
	$cached = (int) get_option( 'richeswp_common_banner_attachment_id', 0 );
	if ( $cached && 'attachment' === get_post_type( $cached ) ) {
		return $cached;
	}

	$src = get_stylesheet_directory() . '/static/images/common-banner.png';
	if ( ! is_readable( $src ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$upload = wp_upload_dir();
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}

	$filename = wp_unique_filename( $upload['path'], 'common-banner.png' );
	$dest     = trailingslashit( $upload['path'] ) . $filename;

	if ( ! @copy( $src, $dest ) ) {
		return 0;
	}

	$filetype = wp_check_filetype( $dest, null );
	$attach_id = wp_insert_attachment(
		array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => 'Common Banner (default page header)',
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$dest
	);

	if ( is_wp_error( $attach_id ) || ! $attach_id ) {
		return 0;
	}

	wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $dest ) );
	update_option( 'richeswp_common_banner_attachment_id', $attach_id, false );

	return $attach_id;
}


/**
 * Falls back to the common-banner.png default header image on any page/term
 * that has no per-page "Page Header Image" (page_header_image) set.
 *
 * Hooks the parent theme's intended override point so the static default flows
 * through the normal header <picture> pipeline. A per-page image, when set,
 * still takes precedence (this only fires when header_image is empty).
 *
 * @author Kirk Lundblade
 * @since 1.0.0
 * @param array $images Assoc. array of header image attachment IDs.
 * @param mixed $obj    The queried object (WP_Post, WP_Term), or null.
 * @return array
 **/
function richeswp_default_header_image( $images, $obj ) {
	if ( empty( $images['header_image'] ) ) {
		$attachment_id = richeswp_get_common_banner_attachment_id();
		if ( $attachment_id ) {
			$images['header_image'] = $attachment_id;
		}
	}

	return $images;
}
add_filter( 'ucfwp_get_header_images_after', 'richeswp_default_header_image', 10, 2 );
