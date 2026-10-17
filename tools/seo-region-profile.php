<?php
/** CLI directory profile. Only this directory's cache may be written; recipes are never changed.
 * php tools/seo-region-profile.php legacy
 * php tools/seo-region-profile.php cold
 * php tools/seo-region-profile.php warm   (a separate process verifies persistence)
 */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
$mode = isset( $argv[1] ) ? $argv[1] : 'warm';
if ( ! in_array( $mode, array( 'legacy', 'cold', 'warm' ), true ) ) { throw new RuntimeException( 'Expected legacy, cold or warm.' ); }
define( 'DISABLE_WP_CRON', true );
define( 'SAVEQUERIES', true );
require ( getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 ) ) . '/wp-load.php';
global $wpdb;
add_filter( 'query', function ( $sql ) use ( $wpdb ) {
	if ( ! preg_match( '/^\s*(?:SELECT|SHOW|EXPLAIN)\b/i', $sql ) && ! ( false !== strpos( $sql, $wpdb->options ) && false !== strpos( $sql, 'cooklook_recipe_regions_' ) && preg_match( '/^\s*(?:INSERT|UPDATE|DELETE)\b/i', $sql ) ) ) {
		throw new RuntimeException( 'SQL outside the directory cache refused.' );
	}
	return $sql;
}, PHP_INT_MAX );
if ( 'cold' === $mode ) { cooklook_invalidate_recipe_regions(); }
$runs = array();
for ( $pass = 1; $pass <= 3; $pass++ ) {
	$wpdb->queries = array();
	$start = microtime( true );
	$regions = 'legacy' === $mode
		? $wpdb->get_col( "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_recipe_region' AND p.post_type = 'recipe' AND p.post_status = 'publish' AND p.post_password = '' ORDER BY pm.meta_value" )
		: cooklook_recipe_regions();
	$queries = array_column( $wpdb->queries, 0 );
	$runs[] = array( 'pass' => $pass, 'sql_count' => count( $queries ),
		'directory_sql_count' => count( preg_grep( '/SELECT DISTINCT pm\.meta_value/', $queries ) ),
		'elapsed_ms' => round( ( microtime( true ) - $start ) * 1000, 3 ),
		'queries' => $queries );
}
echo wp_json_encode( array( 'checked_at' => gmdate( 'c' ), 'site' => home_url( '/' ), 'mode' => $mode,
	'external_object_cache' => wp_using_ext_object_cache(), 'recipe_writes' => false,
	'method' => 'Directory only, after WordPress bootstrap; three calls per process. Cold invalidates only this directory before timing. Warm must run in a separate process. Includes cache SQL, not full-page TTFB.',
	'regions' => $regions, 'runs' => $runs ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
