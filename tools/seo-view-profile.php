<?php
/** Read-only CLI profile of the legacy pre-HTML view counter; never increments real views.
 * php tools/seo-view-profile.php > docs/seo-view-profile-before.json
 * Repeat after changing the hook, saving to seo-view-profile-after.json.
 */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
define( 'DISABLE_WP_CRON', true );
define( 'SAVEQUERIES', true );
$root = getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 );
require $root . '/wp-load.php';
add_filter( 'query', function ( $sql ) {
	if ( ! preg_match( '/^\s*SELECT\b/i', $sql ) ) { throw new RuntimeException( 'Non-read SQL refused by profiler.' ); }
	return $sql;
}, PHP_INT_MAX );
$GLOBALS['wp_query'] = new WP_Query( array( 'post_type' => 'recipe', 'post_status' => 'publish', 'has_password' => false, 'p' => 9885 ) );
if ( ! have_posts() ) { throw new RuntimeException( 'Local sample recipe 9885 is required.' ); }
the_post();
$_SERVER['REQUEST_METHOD'] = 'GET';
$id = get_queried_object_id();
$writes = 0;
add_filter( 'update_post_metadata', function ( $value, $post_id, $key ) use ( $id, &$writes ) {
	if ( $id === $post_id && '_recipe_views' === $key ) { $writes++; return true; }
	return $value;
}, PHP_INT_MAX, 3 );
$active = false !== has_action( 'template_redirect', 'increase_post_views' );
$runs = array();
global $wpdb;
for ( $pass = 1; $pass <= 3; $pass++ ) {
	$wpdb->queries = array();
	$writes = 0;
	$start = microtime( true );
	if ( $active ) { increase_post_views(); }
	$runs[] = array( 'pass' => $pass, 'sql_count' => count( $wpdb->queries ), 'blocked_metadata_write_attempts' => $writes,
		'elapsed_ms' => round( ( microtime( true ) - $start ) * 1000, 3 ),
		'callers' => array_count_values( array_column( $wpdb->queries, 2 ) ) );
}
echo wp_json_encode( array( 'checked_at' => gmdate( 'c' ), 'site' => home_url( '/' ), 'recipe_id' => $id,
	'database_writes' => false, 'pre_html_counter_active' => $active,
	'method' => 'Only the template_redirect view callback is measured, after WordPress and the single-recipe query. Metadata writes are intercepted before SQL; their time is not measured. Three passes in one process, no external cache flush. Not full-page TTFB.',
	'runs' => $runs ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
