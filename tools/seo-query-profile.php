<?php
/** CLI-only query/card profile against existing recipes. Never clears caches or edits data.
 * php tools/seo-query-profile.php > docs/seo-query-profile.json
 * First runs share the bootstrapped process; they are NOT a cold DB/cache benchmark.
 */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
define( 'DISABLE_WP_CRON', true );
define( 'SAVEQUERIES', true );
$root = getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 );
require $root . '/wp-load.php';
if ( ! function_exists( 'cooklook_recipe_query_args' ) ) { throw new RuntimeException( 'Cooklook must be active.' ); }
// Measured code is read-only; fail if a future template starts persisting anything.
add_filter( 'query', function ( $sql ) {
	if ( ! preg_match( '/^\s*(?:SELECT|SHOW|EXPLAIN)\b/i', $sql ) ) { throw new RuntimeException( 'Non-read SQL refused by profiler.' ); }
	return $sql;
}, PHP_INT_MAX );
global $wpdb;
$categories = get_terms( array( 'taxonomy' => 'recipe_category', 'hide_empty' => true, 'number' => 1 ) );
$ingredients = get_terms( array( 'taxonomy' => 'recipe_tags', 'hide_empty' => true, 'number' => 2, 'orderby' => 'count', 'order' => 'DESC' ) );
$cases = array(
	'catalog' => array(),
	'page_2' => array( 'paged' => 2 ),
	'sort_time' => array( 'sort_by' => 'recipe_time' ),
	'sort_views' => array( 'sort_by' => 'recipe_views' ),
);
if ( ! is_wp_error( $categories ) && $categories ) { $cases['category'] = array( 'category_id' => $categories[0]->term_id ); }
if ( ! is_wp_error( $ingredients ) && count( $ingredients ) >= 2 ) {
	$cases['ingredient'] = array( 'include_ingredients' => array( $ingredients[0]->slug ) );
	$cases['ingredients_and'] = array( 'include_ingredients' => wp_list_pluck( $ingredients, 'slug' ) );
	$cases['include_exclude'] = array( 'include_ingredients' => array( $ingredients[0]->slug ), 'exclude_ingredients' => array( $ingredients[1]->slug ) );
}
$report = array(
	'checked_at' => gmdate( 'c' ), 'site' => home_url( '/' ), 'php' => PHP_VERSION, 'wordpress' => get_bloginfo( 'version' ),
	'database_writes' => false, 'external_object_cache' => (bool) wp_using_ext_object_cache(),
	'method' => 'Three sequential query + 11-card render passes per case after WP bootstrap, in one process. Existing WP/DB caches retained. SQL excludes bootstrap and EXPLAIN; not full-page TTFB or production load capacity.',
	'published_recipes' => (int) wp_count_posts( 'recipe' )->publish,
	'postmeta_rows_all_post_types' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta}" ),
	'cases' => array(),
);
foreach ( $cases as $name => $request ) {
	$result = array( 'case' => $name, 'request' => $request, 'runs' => array() );
	for ( $run = 0; $run < 3; $run++ ) {
		$wpdb->queries = array();
		$start = microtime( true );
		$query = new WP_Query( cooklook_recipe_query_args( cooklook_recipe_filters( $request ) ) );
		$query_ms = ( microtime( true ) - $start ) * 1000;
		$query_sql_count = count( $wpdb->queries );
		$render_start = microtime( true );
		ob_start(); cooklook_render_recipes( $query, true ); $html = ob_get_clean();
		$render_ms = ( microtime( true ) - $render_start ) * 1000;
		$measured = $wpdb->queries;
		$sql_ms = 0; $slowest_ms = 0; $callers = array();
		foreach ( $measured as $statement ) {
			$sql_ms += $statement[1] * 1000; $slowest_ms = max( $slowest_ms, $statement[1] * 1000 );
			$caller = isset( $statement[2] ) ? $statement[2] : 'unknown';
			$callers[ $caller ] = isset( $callers[ $caller ] ) ? $callers[ $caller ] + 1 : 1;
		}
		$result['runs'][] = array( 'run' => $run + 1, 'found' => (int) $query->found_posts, 'rendered' => (int) $query->post_count,
			'query_ms' => round( $query_ms, 3 ), 'render_ms' => round( $render_ms, 3 ), 'sql_count' => count( $measured ),
			'query_sql_count' => $query_sql_count, 'render_sql_count' => count( $measured ) - $query_sql_count,
			'sql_ms' => round( $sql_ms, 3 ), 'slowest_sql_ms' => round( $slowest_ms, 3 ), 'html_bytes' => strlen( $html ) );
		if ( 2 === $run ) { $result['repeat_sql_callers'] = $callers; }
		if ( 0 === $run && preg_match( '/^\s*SELECT\b/i', $query->request ) ) {
			// Only the optimizer plan, never SQL values, credentials or private records.
			$result['explain'] = $wpdb->get_results( 'EXPLAIN ' . $query->request, ARRAY_A );
		}
	}
	$report['cases'][] = $result;
}
echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
