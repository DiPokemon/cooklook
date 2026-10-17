<?php
/**
 * Review/apply the source-backed local excerpt batch. Never infers recipe facts.
 * php tools/apply-recipe-summaries.php         (read-only preflight)
 * php tools/apply-recipe-summaries.php --apply (local database only; backup + transaction)
 */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }

function editorial_check( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}

function editorial_source( $id ) {
	$post = get_post( $id );
	editorial_check( $post && 'recipe' === $post->post_type && 'publish' === $post->post_status && '' === $post->post_password, 'Public recipe required: ' . $id );
	return array(
		'url' => get_permalink( $id ), 'title' => $post->post_title, 'content' => $post->post_content,
		'time' => cooklook_recipe_value( $id, 'recipe_time' ), 'prep' => cooklook_recipe_value( $id, 'recipe_prep' ),
		'facts' => cooklook_recipe_facts( $id ),
	);
}

function editorial_validate( $entry ) {
	$id = $entry['id'];
	editorial_check( editorial_source( $id ) === $entry['source'], 'Recipe changed since review; review again: ' . $id );
	$excerpt = get_post_field( 'post_excerpt', $id );
	editorial_check( $excerpt === $entry['previous_excerpt'] || $excerpt === $entry['excerpt'], 'Excerpt edited independently: ' . $id );
	return $excerpt === $entry['excerpt'];
}

$editorial_write_allowed = false;
$editorial_transaction = false;
try {
	$options = getopt( '', array( 'apply' ) );
	$editorial_apply = array_key_exists( 'apply', $options );
	$batch = json_decode( file_get_contents( dirname( __DIR__ ) . '/docs/seo-editorial-batch.json' ), true, 512, JSON_THROW_ON_ERROR );
	editorial_check( 1 === $batch['version'] && 'http://cooklook/' === $batch['site'] && count( $batch['recipes'] ) > 0, 'This batch is restricted to the local Cooklook copy.' );
	$ids = array();
	foreach ( $batch['recipes'] as $entry ) {
		editorial_check( is_int( $entry['id'] ) && $entry['id'] > 0 && ! isset( $ids[ $entry['id'] ] ), 'Duplicate or invalid recipe ID.' );
		editorial_check( is_string( $entry['excerpt'] ) && '' !== trim( $entry['excerpt'] ) && strlen( $entry['excerpt'] ) <= 1200 && ! preg_match( '/[<>\x00-\x1F]/u', $entry['excerpt'] ), 'A short plain-text excerpt is required.' );
		editorial_check( '' === $entry['previous_excerpt'], 'This batch fills missing excerpts only.' );
		$ids[ $entry['id'] ] = $entry['id'];
	}
	define( 'DISABLE_WP_CRON', true );
	// No activation/settings changes; exclude unrelated import housekeeping only in this process.
	$GLOBALS['wp_filter']['option_active_plugins'][ PHP_INT_MAX ][] = array( 'function' => function ( $plugins ) {
		return array_values( array_filter( $plugins, function ( $plugin ) { return 0 !== strpos( $plugin, 'wp-import-export-lite/' ); } ) );
	}, 'accepted_args' => 1 );
	$GLOBALS['wp_filter']['muplugins_loaded'][ PHP_INT_MIN ][] = array( 'function' => function () {
		editorial_check( ! wp_using_ext_object_cache(), 'Use a local copy without persistent object cache.' );
		wp_using_ext_object_cache( true ); // Default process-local cache keeps transients out of wp_options.
	}, 'accepted_args' => 0 );
	$GLOBALS['wp_filter']['query'][ PHP_INT_MAX ][] = array( 'function' => function ( $sql ) use ( &$editorial_write_allowed ) {
		editorial_check( $editorial_write_allowed || preg_match( '/^\s*(?:SELECT|SHOW|EXPLAIN|DESCRIBE)\b/i', $sql ), 'Non-read SQL refused outside the apply transaction.' );
		return $sql;
	}, 'accepted_args' => 1 );
	$GLOBALS['wp_filter']['pre_wp_mail'][ PHP_INT_MAX ][] = array( 'function' => function () { return false; }, 'accepted_args' => 1 );
	$GLOBALS['wp_filter']['pre_http_request'][ PHP_INT_MAX ][] = array( 'function' => function () { return new WP_Error( 'local_editorial_batch', 'External requests disabled during local editorial work.' ); }, 'accepted_args' => 1 );
	require ( getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 ) ) . '/wp-load.php';
	editorial_check( $batch['site'] === home_url( '/' ) && 'cooklook' === get_template(), 'Wrong site/theme; no changes allowed.' );
	global $wpdb;
	$pending = array(); $already = array();
	foreach ( $batch['recipes'] as $entry ) {
		if ( editorial_validate( $entry ) ) { $already[] = $entry['id']; } else { $pending[] = $entry; }
	}
	$backup = null;
	if ( $editorial_apply && $pending ) {
		foreach ( array( $wpdb->posts, $wpdb->postmeta ) as $table ) {
			$engine = $wpdb->get_var( $wpdb->prepare( 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $table ) );
			editorial_check( 'INNODB' === strtoupper( (string) $engine ), 'Transactional post tables required.' );
		}
		$build = realpath( dirname( __DIR__ ) . '/build' );
		editorial_check( $build && str_replace( '\\', '/', $build ) === str_replace( '\\', '/', realpath( dirname( __DIR__ ) ) ) . '/build', 'A real local build directory is required for the backup.' );
		$backup = 'build/editorial-backup-' . gmdate( 'Ymd\THis\Z' ) . '-' . bin2hex( random_bytes( 3 ) ) . '.json';
		$before = array();
		foreach ( $pending as $entry ) {
			$post = get_post( $entry['id'] );
			$before[] = array( 'ID' => $post->ID, 'post_excerpt' => $post->post_excerpt, 'post_modified' => $post->post_modified, 'post_modified_gmt' => $post->post_modified_gmt );
		}
		editorial_check( false !== file_put_contents( dirname( __DIR__ ) . '/' . $backup, wp_json_encode( array( 'site' => $batch['site'], 'created_at' => gmdate( 'c' ), 'posts' => $before ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n", LOCK_EX ), 'Cannot save backup; no changes made.' );
		$editorial_write_allowed = true;
		editorial_check( false !== $wpdb->query( 'START TRANSACTION' ), 'Cannot start transaction.' );
		$editorial_transaction = true;
		// Lock reviewed posts/meta, then refresh caches so a stale preflight cannot overwrite newer edits.
		$id_list = implode( ',', array_map( 'intval', $ids ) );
		$wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE ID IN ($id_list) FOR UPDATE" );
		editorial_check( '' === $wpdb->last_error, 'Cannot lock recipe posts.' );
		$wpdb->get_col( "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id IN ($id_list) FOR UPDATE" );
		editorial_check( '' === $wpdb->last_error, 'Cannot lock recipe metadata.' );
		foreach ( $pending as $entry ) {
			clean_post_cache( $entry['id'] );
			editorial_check( ! editorial_validate( $entry ), 'Excerpt was applied concurrently; rerun preflight.' );
		}
		foreach ( $pending as $entry ) {
			$result = wp_update_post( wp_slash( array( 'ID' => $entry['id'], 'post_excerpt' => $entry['excerpt'] ) ), true );
			editorial_check( ! is_wp_error( $result ) && $result === $entry['id'] && '' === $wpdb->last_error, 'Cannot save recipe excerpt: ' . $entry['id'] );
			editorial_check( editorial_validate( $entry ), 'Saved excerpt mismatch: ' . $entry['id'] );
		}
		editorial_check( false !== $wpdb->query( 'COMMIT' ), 'Cannot commit changes.' );
		$editorial_transaction = false;
		$editorial_write_allowed = false;
	}
	echo wp_json_encode( array(
		'checked_at' => gmdate( 'c' ), 'site' => home_url( '/' ), 'mode' => $editorial_apply ? 'apply' : 'preflight',
		'pending_ids' => $editorial_apply ? array() : array_column( $pending, 'id' ),
		'applied_ids' => $editorial_apply ? array_column( $pending, 'id' ) : array(), 'already_applied_ids' => $already,
		'backup' => $backup, 'recipe_source_facts_unchanged' => true,
		'method' => 'Reviewed excerpts only, via wp_update_post. Existing source facts checked before and after; local site restriction, backup and transaction. Import housekeeping, external requests and mail disabled in this process.',
	), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
} catch ( Throwable $error ) {
	if ( $editorial_transaction ) { $wpdb->query( 'ROLLBACK' ); }
	fwrite( STDERR, $error->getMessage() . "\n" );
	exit( 1 );
}
