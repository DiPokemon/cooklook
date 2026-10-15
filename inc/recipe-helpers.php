<?php
/** Shared access to the existing recipe model. Field names are intentionally unchanged. */
defined( 'ABSPATH' ) || exit;

/** Simple Carbon Fields values use the standard, preloaded WordPress meta cache. */
function cooklook_recipe_value( $post_id, $key ) {
	$post_id = apply_filters( 'carbon_get_post_meta_post_id', $post_id, $key, '' );
	$value = get_post_meta( $post_id, '_' . $key, true );
	return is_scalar( $value ) ? $value : '';
}

function cooklook_recipe_rows( $post_id, $key ) {
	$value = carbon_get_post_meta( $post_id, $key );
	return is_array( $value ) ? array_values( array_filter( $value, 'is_array' ) ) : array();
}

function cooklook_recipe_terms( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	return is_array( $terms ) ? $terms : array();
}

function cooklook_recipe_description( $post_id, $words = 0 ) {
	$post = get_post( $post_id );
	if ( ! $post || post_password_required( $post ) ) {
		return '';
	}
	$text = $post->post_excerpt ?: $post->post_content;
	if ( '' === trim( $text ) ) {
		$steps = cooklook_recipe_rows( $post_id, 'recipe_step' );
		$text  = isset( $steps[0]['recipe_step_text'] ) ? $steps[0]['recipe_step_text'] : '';
	}
	$text = strip_shortcodes( $text );
	return $words ? wp_trim_words( wp_strip_all_tags( $text ), $words, ' …' ) : $text;
}

/** Reject arrays before passing request values to scalar PHP APIs. */
function cooklook_request_text( $request, $key, $default = '' ) {
	return isset( $request[ $key ] ) && is_scalar( $request[ $key ] )
		? sanitize_text_field( wp_unslash( (string) $request[ $key ] ) ) : $default;
}

function cooklook_request_id( $request, $key ) {
	$value = cooklook_request_text( $request, $key );
	return ctype_digit( $value ) ? absint( $value ) : 0;
}

/** sanitize_text_field removes percent-encoded octets, which are valid in term slugs and URLs. */
function cooklook_request_slug( $request, $key ) {
	return isset( $request[ $key ] ) && is_string( $request[ $key ] )
		? sanitize_title( wp_unslash( $request[ $key ] ) ) : '';
}

function cooklook_request_url( $request, $key ) {
	return isset( $request[ $key ] ) && is_string( $request[ $key ] )
		? esc_url_raw( wp_unslash( $request[ $key ] ), array( 'http', 'https' ) ) : '';
}

function cooklook_verify_ajax_nonce( $action, $field = 'nonce' ) {
	if ( ! isset( $_REQUEST[ $field ] ) || ! is_string( $_REQUEST[ $field ] ) || ! check_ajax_referer( $action, $field, false ) ) {
		wp_send_json_error( __( 'Сессия истекла. Обновите страницу и повторите попытку.', 'cooklook' ), 403 );
	}
}

function cooklook_ajax_recipe_id() {
	$post_id = cooklook_request_id( $_POST, 'recipe_id' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- All callers verify an action-specific nonce first.
	if ( ! $post_id || 'recipe' !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) || post_password_required( $post_id ) ) {
		wp_send_json_error( __( 'Рецепт недоступен.', 'cooklook' ), 404 );
	}
	return $post_id;
}

/** Serialize updates without changing the production table or metadata layout. */
function cooklook_with_lock( $resource, $callback ) {
	global $wpdb;
	$name = 'cooklook_' . md5( $wpdb->prefix . ':' . $resource );
	if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $name ) ) ) {
		return new WP_Error( 'busy', __( 'Запрос уже обрабатывается. Повторите попытку.', 'cooklook' ) );
	}
	try {
		return call_user_func( $callback );
	} finally {
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
	}
}

/** Advertising is an additional item, never a replacement for a recipe. */
function cooklook_render_recipes( $query, $advertising = false ) {
	$position = 0;
	if ( ! $query->have_posts() ) {
		get_template_part( 'template-parts/recipe-loop-nothing' );
	}
	while ( $query->have_posts() ) {
		$query->the_post();
		get_template_part( 'template-parts/recipe-loop-item' );
		$position++;
		if ( $advertising && 0 === $position % 3 ) {
			get_template_part( 'template-parts/recipe-loop-item-adv' );
		}
	}
	wp_reset_postdata();
}
