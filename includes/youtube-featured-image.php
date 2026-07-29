<?php
/**
 * Derive a post's featured image from an embedded YouTube video.
 *
 * When a post has a YouTube URL in its content but no featured image, the video's
 * thumbnail is sideloaded into the Media Library once (on save) and set as the
 * featured image. A real attachment — rather than a render-time hotlink to
 * img.youtube.com — is what the standard card, Open Graph tags, REST, image
 * sizes, and the_post_thumbnail() all expect.
 *
 * Best-practice notes:
 *   - Sideload ONCE on save, never on a front-end GET (blocking HTTP + a write on read).
 *   - maxresdefault.jpg only exists for HD/newer uploads; hqdefault.jpg always
 *     exists. YouTube returns a true 404 for a missing maxres (with a JPEG body,
 *     which defeats client-side img.onerror but is a non-issue server-side), so
 *     wp_remote_head() detects it reliably here.
 *
 * @package UCF-WordPress-Theme-child-RICHES
 */

defined( 'ABSPATH' ) || exit;

/**
 * Extract the first YouTube video ID from arbitrary content.
 *
 * Single source of truth for YouTube detection — shared by the featured-image
 * sideloader and the card renderers (aggregator.php, category-queue.php).
 *
 * @param string $content Raw post content (use post_content, not the filtered/teased body).
 * @return string|null 11-char video ID, or null if none found.
 */
function riches_youtube_id_from_content( $content ) {
	if ( ! is_string( $content ) || '' === $content ) {
		return null;
	}
	if ( preg_match(
		'/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
		$content,
		$m
	) ) {
		return $m[1];
	}
	return null;
}

/**
 * Best available thumbnail URL for a video: maxresdefault if present, else hqdefault.
 *
 * @param string $video_id YouTube video ID.
 * @return string Thumbnail URL, or '' for an invalid ID.
 */
function riches_youtube_thumb_url( $video_id ) {
	$video_id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $video_id );
	if ( '' === $video_id ) {
		return '';
	}

	$maxres = 'https://i.ytimg.com/vi/' . $video_id . '/maxresdefault.jpg';
	$resp   = wp_remote_head( $maxres, array(
		'timeout'     => 8,
		'redirection' => 2,
	) );
	if ( ! is_wp_error( $resp ) && 200 === (int) wp_remote_retrieve_response_code( $resp ) ) {
		return $maxres;
	}

	// hqdefault always exists (albeit 4:3); the card crops with object-fit: cover.
	return 'https://i.ytimg.com/vi/' . $video_id . '/hqdefault.jpg';
}

/**
 * Post types eligible for a YouTube-derived featured image.
 *
 * @return string[]
 */
function riches_yt_featured_post_types() {
	return (array) apply_filters( 'riches_yt_featured_post_types', array( 'post' ) );
}

/**
 * If a post embeds YouTube and lacks a featured image, sideload the video
 * thumbnail and set it as the featured image. Idempotent and non-destructive.
 *
 * @param int          $post_id Post ID.
 * @param WP_Post|null $post    Post object (as passed by save_post); fetched if null.
 * @return int|false Attachment ID set, or false when nothing was done.
 */
function riches_set_youtube_featured_image( $post_id, $post = null ) {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return false;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return false;
	}

	$post = $post instanceof WP_Post ? $post : get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return false;
	}
	if ( ! in_array( $post->post_type, riches_yt_featured_post_types(), true ) ) {
		return false;
	}
	// Only worth doing for live-ish content.
	if ( ! in_array( $post->post_status, array( 'publish', 'private' ), true ) ) {
		return false;
	}
	// Respect a manually-set (or already-derived) featured image.
	if ( has_post_thumbnail( $post_id ) ) {
		return false;
	}

	$video_id = riches_youtube_id_from_content( $post->post_content );
	if ( ! $video_id ) {
		return false;
	}
	// Already handled this exact video for this post — don't refetch.
	if ( get_post_meta( $post_id, '_riches_yt_thumb', true ) === $video_id ) {
		return false;
	}

	$url = riches_youtube_thumb_url( $video_id );
	if ( '' === $url ) {
		return false;
	}

	// Not loaded on the front-end or during cron/CLI — required or the call fatals.
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$desc          = get_the_title( $post_id );
	$attachment_id = media_sideload_image( $url, $post_id, $desc, 'id' );
	if ( is_wp_error( $attachment_id ) ) {
		return false;
	}

	set_post_thumbnail( $post_id, $attachment_id );
	if ( '' !== $desc ) {
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $desc );
	}
	// Marker so we never refetch this video for this post.
	update_post_meta( $post_id, '_riches_yt_thumb', $video_id );

	return (int) $attachment_id;
}
add_action( 'save_post', 'riches_set_youtube_featured_image', 20, 2 );

/**
 * Backfill YouTube-derived featured images across existing posts.
 *
 * Only fills posts that lack a featured image; never overwrites. Safe to re-run.
 *
 * @return array{processed:int,set:int,skipped:int} Tally.
 */
function riches_backfill_youtube_featured_images() {
	$q = new WP_Query(
		array(
			'post_type'      => riches_yt_featured_post_types(),
			'post_status'    => array( 'publish', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	$tally = array(
		'processed' => 0,
		'set'       => 0,
		'skipped'   => 0,
	);
	foreach ( $q->posts as $pid ) {
		$tally['processed']++;
		$result = riches_set_youtube_featured_image( $pid );
		if ( $result ) {
			$tally['set']++;
		} else {
			$tally['skipped']++;
		}
	}
	return $tally;
}

/**
 * WP-CLI: `wp riches yt-thumbs` — backfill featured images from YouTube thumbnails.
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'riches yt-thumbs',
		function () {
			$t = riches_backfill_youtube_featured_images();
			WP_CLI::success( sprintf( 'Processed %d, set %d, skipped %d.', $t['processed'], $t['set'], $t['skipped'] ) );
		}
	);
}
