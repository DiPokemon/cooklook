<?php
/** Approximate browser page views; counting never runs in the HTML request. */
defined( 'ABSPATH' ) || exit;

function cooklook_enqueue_recipe_views() {
	if ( ! is_singular( 'recipe' ) || is_preview() || is_feed() || is_embed() ) { return; }
	$post = get_queried_object();
	if ( ! $post || 'publish' !== $post->post_status || '' !== $post->post_password ) { return; }
	cooklook_enqueue_script( 'cooklook-recipe-views', 'static/js/recipe-views.js', array() );
	wp_localize_script( 'cooklook-recipe-views', 'cooklookRecipeView', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ), 'recipe_id' => $post->ID,
		'nonce' => wp_create_nonce( 'cooklook_recipe_view_' . $post->ID ),
	) );
}
add_action( 'wp_enqueue_scripts', 'cooklook_enqueue_recipe_views' );

/** Origin is a browser CSRF boundary, not proof of a human visitor. */
function cooklook_recipe_view_same_origin( $origin ) {
	$source = wp_parse_url( $origin );
	$site = wp_parse_url( home_url( '/' ) );
	if ( ! is_array( $source ) || empty( $source['scheme'] ) || empty( $source['host'] ) ||
		array_intersect( array_keys( $source ), array( 'user', 'pass', 'path', 'query', 'fragment' ) ) ) { return false; }
	foreach ( array( 'scheme', 'host' ) as $part ) {
		if ( strtolower( $source[ $part ] ) !== strtolower( $site[ $part ] ) ) { return false; }
	}
	$default_port = 'https' === strtolower( $site['scheme'] ) ? 443 : 80;
	return ( $source['port'] ?? $default_port ) === ( $site['port'] ?? $default_port );
}

/** Known crawlers/previews are excluded heuristically; no IP or visitor ID is stored. */
function cooklook_recipe_view_is_bot( $user_agent ) {
	return '' === $user_agent || (bool) preg_match( '/bot|spider|crawl|slurp|facebookexternalhit|bingpreview|google-inspectiontool|headlesschrome|chatgpt-user|claude-user|perplexity-user/i', $user_agent );
}

function cooklook_record_recipe_view() {
	nocache_headers();
	if ( 'POST' !== cooklook_request_text( $_SERVER, 'REQUEST_METHOD' ) ) {
		wp_send_json_error( array( 'code' => 'method_not_allowed' ), 405 );
	}
	if ( ! cooklook_recipe_view_same_origin( cooklook_request_text( $_SERVER, 'HTTP_ORIGIN' ) ) ) {
		wp_send_json_error( array( 'code' => 'invalid_origin' ), 403 );
	}
	if ( cooklook_recipe_view_is_bot( cooklook_request_text( $_SERVER, 'HTTP_USER_AGENT' ) ) ) {
		wp_send_json_success( array( 'counted' => false ) );
	}
	$post_id = cooklook_request_id( $_POST, 'recipe_id' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Read-only validation and nonce scope; all writes below require that recipe's nonce.
	$post = $post_id ? get_post( $post_id ) : null;
	if ( ! $post || 'recipe' !== $post->post_type || 'publish' !== $post->post_status || '' !== $post->post_password ) {
		wp_send_json_error( array( 'code' => 'recipe_unavailable' ), 404 );
	}
	$nonce = cooklook_request_text( $_POST, 'nonce' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reading the token to verify it immediately below.
	if ( ! wp_verify_nonce( $nonce, 'cooklook_recipe_view_' . $post_id ) ) {
		// Cached HTML can contain an expired or different-session nonce. Refresh only;
		// this branch never counts a view. The client may retry it once, before any write.
		wp_send_json_error( array( 'code' => 'invalid_nonce', 'nonce' => wp_create_nonce( 'cooklook_recipe_view_' . $post_id ) ), 403 );
	}
	$result = cooklook_with_lock( 'views-' . $post_id, function () use ( $post_id ) {
		wp_cache_delete( $post_id, 'post_meta' );
		$views = max( 0, (int) get_post_meta( $post_id, '_recipe_views', true ) );
		if ( PHP_INT_MAX === $views || ! update_post_meta( $post_id, '_recipe_views', $views + 1 ) ) {
			return new WP_Error( 'view_save_failed', 'View could not be saved.' );
		}
		return true;
	} );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'code' => $result->get_error_code() ), 'busy' === $result->get_error_code() ? 409 : 500 );
	}
	wp_send_json_success( array( 'counted' => true ) );
}
add_action( 'wp_ajax_cooklook_record_recipe_view', 'cooklook_record_recipe_view' );
add_action( 'wp_ajax_nopriv_cooklook_record_recipe_view', 'cooklook_record_recipe_view' );
