<?php
/** Bounded background generation of disposable WebP copies; originals stay untouched. */
defined( 'ABSPATH' ) || exit;

function cooklook_webp_enabled() {
	return (bool) apply_filters( 'cooklook_webp_enabled', true );
}

/** Only local JPEG/PNG attachments inside uploads may enter the image editor. */
function cooklook_webp_source( $id ) {
	$uploads = wp_get_upload_dir();
	$base = realpath( $uploads['basedir'] );
	$file = get_attached_file( $id );
	$file = $file ? realpath( $file ) : false;
	if ( ! $base || ! $file || ! is_file( $file ) || ! is_readable( $file ) ) { return false; }
	$base = trailingslashit( wp_normalize_path( $base ) );
	$file = wp_normalize_path( $file );
	if ( 0 !== strpos( $file, $base ) || ! in_array( get_post_mime_type( $id ), array( 'image/jpeg', 'image/png' ), true ) ) { return false; }
	$bytes = filesize( $file );
	if ( ! $bytes || $bytes > 12 * MB_IN_BYTES ) { return false; }
	$key = substr( hash( 'sha256', 'v1|' . $id . '|' . $file . '|' . filemtime( $file ) . '|' . $bytes ), 0, 20 );
	return array( 'file' => $file, 'key' => $key, 'base' => $base, 'url' => trailingslashit( $uploads['baseurl'] ) );
}

/** Render existing derivatives only. A cache miss never performs image conversion. */
function cooklook_webp_picture( $id, $size, $attributes, $fallback ) {
	if ( ! cooklook_webp_enabled() || ! $fallback ) { return $fallback; }
	$source = cooklook_webp_source( $id );
	if ( ! $source ) { return $fallback; }
	$cached = get_post_meta( $id, '_cooklook_webp_v1', true );
	if ( ! is_array( $cached ) || ( $cached['key'] ?? '' ) !== $source['key'] ) { $cached = array(); }
	if ( ! empty( $cached['sources'] ) ) {
		$original = wp_get_attachment_image_src( $id, $size );
		// Never replace a custom crop with a different composition.
		if ( ! $original || empty( $original[2] ) || abs( $original[1] / $original[2] - $cached['width'] / max( 1, $cached['height'] ) ) > 0.02 ) { return $fallback; }
		$srcset = array();
		foreach ( $cached['sources'] as $width => $name ) {
			if ( ! preg_match( '~^cooklook-webp/[0-9]+-[a-f0-9]{20}-[0-9]+\.webp$~D', $name ) || ! is_file( $source['base'] . $name ) ) { $srcset = array(); break; }
			$srcset[] = $source['url'] . $name . ' ' . (int) $width . 'w';
		}
		if ( $srcset ) {
			return '<picture class="cooklook-picture"><source type="image/webp" srcset="' . esc_attr( implode( ', ', $srcset ) ) . '" sizes="' . esc_attr( $attributes['sizes'] ) . '">' . $fallback . '</picture>';
		}
	}
	if ( empty( $cached['retry_after'] ) || $cached['retry_after'] <= time() ) {
		// Request-local set; shutdown merges it into a small persistent queue once.
		$GLOBALS['cooklook_webp_requested'][ absint( $id ) ] = absint( $id );
	}
	return $fallback;
}

function cooklook_webp_queue_lock() {
	$expires = (int) get_option( 'cooklook_webp_queue_lock', 0 );
	if ( $expires && $expires < time() ) { delete_option( 'cooklook_webp_queue_lock' ); }
	return add_option( 'cooklook_webp_queue_lock', time() + 30, '', false );
}

function cooklook_webp_schedule() {
	if ( ! wp_next_scheduled( 'cooklook_webp_batch' ) ) {
		wp_schedule_single_event( time() + 60, 'cooklook_webp_batch' );
	}
}

function cooklook_webp_enqueue_requested() {
	// CLI audits and administration must remain read-only unless the worker is called explicitly.
	if ( PHP_SAPI === 'cli' || is_admin() || ! cooklook_webp_enabled() || empty( $GLOBALS['cooklook_webp_requested'] ) ) { return; }
	if ( ! cooklook_webp_queue_lock() ) { return; }
	try {
		$queue = array_values( array_unique( array_merge( (array) get_option( 'cooklook_webp_queue', array() ), array_values( $GLOBALS['cooklook_webp_requested'] ) ) ) );
		update_option( 'cooklook_webp_queue', array_slice( array_map( 'absint', $queue ), 0, 40 ), false );
	} finally {
		delete_option( 'cooklook_webp_queue_lock' );
	}
	cooklook_webp_schedule();
}
add_action( 'shutdown', 'cooklook_webp_enqueue_requested' );

/** A worker processes at most two photos per minute, each at no more than 1280 px. */
function cooklook_webp_batch() {
	if ( ! cooklook_webp_enabled() || ! cooklook_webp_queue_lock() ) { return; }
	try {
		$queue = (array) get_option( 'cooklook_webp_queue', array() );
		$batch = array_splice( $queue, 0, 2 );
		update_option( 'cooklook_webp_queue', $queue, false );
	} finally {
		delete_option( 'cooklook_webp_queue_lock' );
	}
	foreach ( $batch as $id ) { cooklook_generate_webp( absint( $id ) ); }
	if ( get_option( 'cooklook_webp_queue', array() ) ) { cooklook_webp_schedule(); }
}
add_action( 'cooklook_webp_batch', 'cooklook_webp_batch' );

function cooklook_generate_webp( $id ) {
	$source = cooklook_webp_source( $id );
	if ( ! $source ) { return false; }
	$cached = get_post_meta( $id, '_cooklook_webp_v1', true );
	if ( is_array( $cached ) && ( $cached['key'] ?? '' ) === $source['key'] && empty( $cached['sources'] ) && ( $cached['retry_after'] ?? 0 ) > time() ) { return false; }
	if ( is_array( $cached ) && ( $cached['key'] ?? '' ) === $source['key'] && ! empty( $cached['sources'] ) ) {
		$complete = true;
		foreach ( $cached['sources'] as $name ) {
			if ( ! preg_match( '~^cooklook-webp/[0-9]+-[a-f0-9]{20}-[0-9]+\.webp$~D', $name ) || ! is_file( $source['base'] . $name ) ) { $complete = false; break; }
		}
		if ( $complete ) { return true; }
	}
	$result = array( 'key' => $source['key'], 'retry_after' => time() + DAY_IN_SECONDS );
	$written = array();
	try {
		$dimensions = wp_getimagesize( $source['file'] );
		if ( ! $dimensions || $dimensions[0] * $dimensions[1] > 12000000 || ! in_array( $dimensions['mime'], array( 'image/jpeg', 'image/png' ), true ) || ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) { return false; }
		$directory = $source['base'] . 'cooklook-webp';
		if ( ! wp_mkdir_p( $directory ) || is_link( $directory ) || ! is_writable( $directory ) ) { return false; }
		$free = disk_free_space( $directory );
		if ( false !== $free && $free < 50 * MB_IN_BYTES ) { return false; }
		$widths = array_unique( array( min( 360, $dimensions[0] ), min( 720, $dimensions[0] ), min( 1280, $dimensions[0] ) ) );
		$sources = array();
		foreach ( $widths as $width ) {
			$editor = wp_get_image_editor( $source['file'] );
			if ( is_wp_error( $editor ) || ( $width < $dimensions[0] && is_wp_error( $editor->resize( $width, null, false ) ) ) || is_wp_error( $editor->set_quality( 80 ) ) ) { return false; }
			$name = 'cooklook-webp/' . $id . '-' . $source['key'] . '-' . $width . '.webp';
			$temporary = $directory . '/' . wp_unique_filename( $directory, $id . '-' . $source['key'] . '-' . $width . '-tmp.webp' );
			$saved = $editor->save( $temporary, 'image/webp' );
			if ( is_wp_error( $saved ) ) { return false; }
			$written[] = $temporary;
			$actual = wp_getimagesize( $temporary );
			if ( ! $actual || 'image/webp' !== $actual['mime'] || (int) $actual[0] !== (int) $width ) { return false; }
			if ( ! rename( $temporary, $source['base'] . $name ) ) { return false; }
			array_pop( $written );
			$written[] = $source['base'] . $name;
			$sources[ $width ] = $name;
			unset( $editor );
		}
		// Do not publish a stale derivative if the source was replaced during processing.
		$fresh = cooklook_webp_source( $id );
		if ( ! $fresh || $fresh['key'] !== $source['key'] ) { return false; }
		$result = array( 'key' => $source['key'], 'width' => $dimensions[0], 'height' => $dimensions[1], 'sources' => $sources );
		return true;
	} catch ( Throwable $error ) {
		return false;
	} finally {
		if ( empty( $result['sources'] ) ) {
			foreach ( $written as $file ) { wp_delete_file( $file ); }
		}
		update_post_meta( $id, '_cooklook_webp_v1', $result );
	}
}
