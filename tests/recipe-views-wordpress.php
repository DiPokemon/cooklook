<?php
/** Actual WordPress/AJAX integration; counter writes are intercepted in memory.
 * php tests/recipe-views-wordpress.php
 */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
define( 'DISABLE_WP_CRON', true );
$root = getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 );
require $root . '/wp-load.php';
define( 'DOING_AJAX', true );
add_filter( 'query', function ( $sql ) {
	if ( ! preg_match( '/^\s*SELECT\b/i', $sql ) ) { throw new RuntimeException( 'Non-read SQL refused by integration test.' ); }
	return $sql;
}, PHP_INT_MAX );
class Cooklook_View_Test_Exit extends RuntimeException {}
add_filter( 'wp_die_ajax_handler', function () {
	return function () { throw new Cooklook_View_Test_Exit(); };
} );
function view_check( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }
function view_response( $nonce, $method = 'POST', $agent = 'Mozilla/5.0 Cooklook integration test' ) {
	$_POST = array( 'recipe_id' => 9885, 'nonce' => $nonce );
	$_SERVER['REQUEST_METHOD'] = $method;
	$_SERVER['HTTP_USER_AGENT'] = $agent;
	http_response_code( 200 );
	ob_start();
	try { cooklook_record_recipe_view(); } catch ( Cooklook_View_Test_Exit $response ) {
		return array( 'status' => http_response_code(), 'body' => json_decode( ob_get_contents(), true, 512, JSON_THROW_ON_ERROR ) );
	} finally { ob_end_clean(); }
	throw new RuntimeException( 'No AJAX response' );
}
$GLOBALS['wp_query'] = new WP_Query( array( 'post_type' => 'recipe', 'p' => 9885, 'post_status' => 'publish', 'has_password' => false ) );
view_check( have_posts(), 'Local sample recipe 9885 is required' );
the_post();
view_check( false === has_action( 'template_redirect', 'increase_post_views' ), 'Counter still runs before HTML' );
view_check( false !== has_action( 'wp_ajax_cooklook_record_recipe_view', 'cooklook_record_recipe_view' ) && false !== has_action( 'wp_ajax_nopriv_cooklook_record_recipe_view', 'cooklook_record_recipe_view' ), 'Missing AJAX handlers' );
$site = wp_parse_url( home_url( '/' ) );
$_SERVER['HTTP_ORIGIN'] = $site['scheme'] . '://' . $site['host'] . ( isset( $site['port'] ) ? ':' . $site['port'] : '' );
$counter = 20;
$writes = 0;
add_filter( 'get_post_metadata', function ( $value, $id, $key ) use ( &$counter ) {
	return 9885 === $id && '_recipe_views' === $key ? $counter : $value;
}, PHP_INT_MAX, 3 );
add_filter( 'update_post_metadata', function ( $value, $id, $key, $next ) use ( &$counter, &$writes ) {
	if ( 9885 === $id && '_recipe_views' === $key ) { $counter = $next; $writes++; return true; }
	return $value;
}, PHP_INT_MAX, 4 );
cooklook_enqueue_recipe_views();
$script = wp_scripts()->registered['cooklook-recipe-views'] ?? null;
view_check( $script && ! $script->deps && strpos( $script->extra['data'], 'cooklookRecipeView' ) !== false, 'Missing standalone script/configuration' );
view_check( 0 === $writes, 'Enqueueing script counted a view' );
$response = view_response( 'expired' );
view_check( 403 === $response['status'] && $response['body']['data']['code'] === 'invalid_nonce' && 0 === $writes, 'Expired token counted a view' );
$nonce = $response['body']['data']['nonce'];
view_check( (bool) wp_verify_nonce( $nonce, 'cooklook_recipe_view_9885' ), 'Refreshed token invalid' );
$response = view_response( $nonce );
view_check( 200 === $response['status'] && $response['body']['data']['counted'] && 1 === $writes && 21 === $counter, 'Valid view not counted once' );
foreach ( array( 'GET', 'HEAD' ) as $method ) { view_check( 405 === view_response( $nonce, $method )['status'], 'Wrong method accepted' ); }
global $wpdb;
$before = $wpdb->num_queries;
$response = view_response( $nonce, 'POST', 'Googlebot/2.1' );
view_check( 200 === $response['status'] && false === $response['body']['data']['counted'] && $before === $wpdb->num_queries, 'Bot used DB or was counted' );
$_SERVER['HTTP_ORIGIN'] = 'https://foreign.example';
view_check( 403 === view_response( $nonce )['status'], 'Foreign origin accepted' );
view_check( 1 === $writes && 21 === $counter, 'Rejected requests changed views' );
echo wp_json_encode( array( 'checked_at' => gmdate( 'c' ), 'site' => home_url( '/' ), 'recipe_id' => 9885,
	'database_writes' => false, 'simulated_counter_writes' => $writes, 'pre_html_counter_active' => false,
	'result' => 'PASS: actual WordPress hooks/configuration, nonce refresh, guest view, GET/HEAD rejection, zero-SQL bot rejection and foreign-origin rejection; real metadata writes intercepted.' ),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
