<?php
defined( 'ABSPATH' ) || exit;

/** Only create missing tables. Existing production tables are never rebuilt. */
function create_custom_tables() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	foreach ( array( 'pending_searches', 'rejected_searches', 'popular_searches' ) as $name ) {
		$table = $wpdb->prefix . $name;
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table ) {
			continue;
		}
		$status = 'pending_searches' === $name ? ", status varchar(20) DEFAULT 'awaiting_approval'" : '';
		dbDelta( "CREATE TABLE {$table} (
			id int NOT NULL AUTO_INCREMENT,
			query text NOT NULL,
			count int DEFAULT 1{$status},
			PRIMARY KEY  (id)
		) " . $wpdb->get_charset_collate() . ';' );
	}
}
add_action( 'after_switch_theme', 'create_custom_tables' );

function cooklook_check_theme_tables() {
	if ( ! current_user_can( 'manage_options' ) || '1' === get_option( 'cooklook_tables_checked' ) ) {
		return;
	}
	create_custom_tables();
	create_favorite_recipes_table();
	global $wpdb;
	foreach ( array( 'pending_searches', 'rejected_searches', 'popular_searches', 'favorite_recipes' ) as $name ) {
		$table = $wpdb->prefix . $name;
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) !== $table ) {
			return;
		}
	}
	update_option( 'cooklook_tables_checked', '1', false );
}
add_action( 'admin_init', 'cooklook_check_theme_tables' );

function my_search_moderation_page_content() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Недостаточно прав.', 'cooklook' ) );
	}
	global $wpdb;
	$page = max( 1, cooklook_request_id( $_GET, 'paged' ) );
	$pending_requests = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pending_searches WHERE status = 'awaiting_approval' ORDER BY id DESC LIMIT 50 OFFSET %d", ( $page - 1 ) * 50 ) );
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pending_searches WHERE status = 'awaiting_approval'" );
	load_template( get_template_directory() . '/search-moderation-template.php', false, array( 'pending_requests' => $pending_requests, 'page' => $page, 'pages' => (int) ceil( $total / 50 ) ) );
}

function my_custom_admin_menu() {
	add_menu_page( 'Модерация поиска', 'Модерация поиска', 'manage_options', 'search-moderation', 'my_search_moderation_page_content', 'dashicons-search' );
}
add_action( 'admin_menu', 'my_custom_admin_menu' );

function cooklook_moderate_search( $approve ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Недостаточно прав.', 'cooklook' ), '', array( 'response' => 403 ) );
	}
	$id = cooklook_request_id( $_GET, 'request_id' );
	if ( ! $id || '' === cooklook_request_text( $_GET, '_wpnonce' ) ) {
		wp_die( esc_html__( 'Недопустимый запрос.', 'cooklook' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'cooklook_moderate_search_' . $id );
	$result = cooklook_with_lock( 'search-moderation', function () use ( $id, $approve ) {
		global $wpdb;
		$pending = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pending_searches WHERE id = %d", $id ) );
		if ( ! $pending ) {
			return true;
		}
		$table = $wpdb->prefix . ( $approve ? 'popular_searches' : 'rejected_searches' );
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE query = %s", $pending->query ) );
		$count = max( 1, (int) $pending->count );
		$saved = $existing
			? $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET count = count + %d WHERE id = %d", $count, $existing ) )
			: $wpdb->insert( $table, array( 'query' => $pending->query, 'count' => $count ), array( '%s', '%d' ) );
		if ( false === $saved ) {
			return new WP_Error( 'save_failed', __( 'Не удалось сохранить решение.', 'cooklook' ) );
		}
		return false !== $wpdb->delete( $wpdb->prefix . 'pending_searches', array( 'id' => $id ), array( '%d' ) );
	} );
	if ( is_wp_error( $result ) || ! $result ) {
		wp_die( esc_html__( 'Не удалось обработать запрос. Повторите попытку.', 'cooklook' ) );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=search-moderation' ) );
	exit;
}
function approve_search_request() { cooklook_moderate_search( true ); }
function reject_search_request() { cooklook_moderate_search( false ); }
add_action( 'admin_post_approve_search', 'approve_search_request' );
add_action( 'admin_post_reject_search', 'reject_search_request' );

function get_popular_searches( $limit = 6 ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( "SELECT query, count FROM {$wpdb->prefix}popular_searches ORDER BY count DESC, id DESC LIMIT %d", min( 50, max( 1, (int) $limit ) ) ) );
}

function cooklook_record_search() {
	if ( ! is_search() || is_paged() || is_feed() || 'GET' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
		return;
	}
	$query = sanitize_text_field( get_search_query( false ) );
	if ( mb_strlen( $query ) < 2 || mb_strlen( $query ) > 120 ) {
		return;
	}
	// Bound public writes; store only a salted hash of the address, not the address.
	$address = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
	$key = 'cooklook_search_' . hash_hmac( 'sha256', $address, wp_salt( 'nonce' ) );
	$recent = get_transient( $key );
	$recent = is_array( $recent ) ? $recent : array();
	if ( count( $recent ) >= 20 || in_array( $query, $recent, true ) ) {
		return;
	}
	$recent[] = $query;
	set_transient( $key, $recent, 10 * MINUTE_IN_SECONDS );
	cooklook_with_lock( 'search-moderation', function () use ( $query ) {
		global $wpdb;
		if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}rejected_searches WHERE query = %s", $query ) ) ) {
			return;
		}
		foreach ( array( 'popular_searches', 'pending_searches' ) as $name ) {
			$table = $wpdb->prefix . $name;
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE query = %s", $query ) );
			if ( $id ) {
				$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET count = count + 1 WHERE id = %d", $id ) );
				return;
			}
		}
		$wpdb->insert( $wpdb->prefix . 'pending_searches', array( 'query' => $query, 'count' => 1, 'status' => 'awaiting_approval' ), array( '%s', '%d', '%s' ) );
	} );
}
add_action( 'template_redirect', 'cooklook_record_search' );
