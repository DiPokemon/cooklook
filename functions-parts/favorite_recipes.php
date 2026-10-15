<?php
defined( 'ABSPATH' ) || exit;

function create_favorite_recipes_table() {
	global $wpdb;
	$table = $wpdb->prefix . 'favorite_recipes';
	// Existing tables and rows are deliberately left intact.
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table ) {
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( "CREATE TABLE {$table} (
		id int NOT NULL AUTO_INCREMENT,
		user_id int NOT NULL,
		recipe_id int NOT NULL,
		created_at timestamp DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY user_recipe (user_id,recipe_id)
	) " . $wpdb->get_charset_collate() . ';' );
}
add_action( 'after_switch_theme', 'create_favorite_recipes_table' );

/** Cache once per request instead of one SQL query for every recipe card. */
function cooklook_favorite_ids( $user_id, $refresh = false ) {
	static $cache = array();
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return array();
	}
	if ( $refresh || ! isset( $cache[ $user_id ] ) ) {
		global $wpdb;
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT recipe_id FROM {$wpdb->prefix}favorite_recipes WHERE user_id = %d ORDER BY recipe_id DESC", $user_id ) );
		$cache[ $user_id ] = array_map( 'absint', $ids );
	}
	return $cache[ $user_id ];
}

function is_recipe_favorite( $recipe_id, $user_id ) {
	return in_array( absint( $recipe_id ), cooklook_favorite_ids( $user_id ), true );
}

function add_to_favorites() {
	cooklook_verify_ajax_nonce( 'cooklook_favorites' );
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'Войдите, чтобы сохранить рецепт.', 'cooklook' ), 401 );
	}
	$recipe_id = cooklook_ajax_recipe_id();
	$user_id = get_current_user_id();
	$desired = cooklook_request_text( $_POST, 'favorite' );
	if ( ! in_array( $desired, array( '', '0', '1' ), true ) ) {
		wp_send_json_error( __( 'Недопустимое состояние избранного.', 'cooklook' ), 400 );
	}
	$result = cooklook_with_lock( 'favorites-' . $user_id, function () use ( $recipe_id, $user_id, $desired ) {
		global $wpdb;
		cooklook_favorite_ids( $user_id, true );
		$exists = is_recipe_favorite( $recipe_id, $user_id );
		$add = '' === $desired ? ! $exists : '1' === $desired;
		$saved = true;
		if ( $exists && ! $add ) {
			$saved = $wpdb->delete( $wpdb->prefix . 'favorite_recipes', array( 'user_id' => $user_id, 'recipe_id' => $recipe_id ), array( '%d', '%d' ) );
		} elseif ( ! $exists && $add ) {
			$saved = $wpdb->insert( $wpdb->prefix . 'favorite_recipes', array( 'user_id' => $user_id, 'recipe_id' => $recipe_id ), array( '%d', '%d' ) );
		}
		return false === $saved ? new WP_Error( 'save_failed', __( 'Не удалось сохранить избранное.', 'cooklook' ) ) : array( 'favorite' => $add );
	} );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( $result->get_error_message(), 'busy' === $result->get_error_code() ? 409 : 500 );
	}
	wp_send_json_success( $result );
}
add_action( 'wp_ajax_add_to_favorites', 'add_to_favorites' );
add_action( 'wp_ajax_nopriv_add_to_favorites', 'add_to_favorites' );
