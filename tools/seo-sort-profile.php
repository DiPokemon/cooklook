<?php
/** Read-only SQL sort profile on the currently active local data/theme.
 * php tools/seo-sort-profile.php > docs/seo-sort-profile-before.json
 * Repeat after the change, saving to seo-sort-profile-after.json.
 * No cache flushing, schema changes or recipe writes. EXPLAIN ANALYZE executes SELECT only.
 */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
define( 'DISABLE_WP_CRON', true );
define( 'SAVEQUERIES', true );
require ( getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 ) ) . '/wp-load.php';
global $wpdb;
add_filter( 'query', function ( $sql ) {
	if ( ! preg_match( '/^\s*(?:SELECT|SHOW|EXPLAIN)\b/i', $sql ) ) { throw new RuntimeException( 'Non-read SQL refused by sort profiler.' ); }
	return $sql;
}, PHP_INT_MAX );
$version = $wpdb->get_var( 'SELECT VERSION()' );
$can_analyze = false === stripos( $version, 'mariadb' ) && version_compare( $version, '8.0.18', '>=' );
$region = $wpdb->get_var( "SELECT pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_recipe_region' AND pm.meta_value <> '' AND p.post_type = 'recipe' AND p.post_status = 'publish' AND p.post_password = '' GROUP BY pm.meta_value ORDER BY COUNT(DISTINCT p.ID) DESC, pm.meta_value LIMIT 1" );
$categories = get_terms( array( 'taxonomy' => 'recipe_category', 'hide_empty' => true, 'number' => 1, 'orderby' => 'count', 'order' => 'DESC' ) );
$ingredients = get_terms( array( 'taxonomy' => 'recipe_tags', 'hide_empty' => true, 'number' => 2, 'orderby' => 'count', 'order' => 'DESC' ) );
$contexts = array( 'catalog' => array(), 'page_2' => array( 'paged' => 2 ) );
if ( $region ) { $contexts['region'] = array( 'region' => $region ); }
if ( ! is_wp_error( $categories ) && $categories ) { $contexts['category'] = array( 'category_id' => $categories[0]->term_id ); }
if ( ! is_wp_error( $ingredients ) && count( $ingredients ) >= 2 ) {
	$contexts['ingredient'] = array( 'include_ingredients' => array( $ingredients[0]->slug ) );
	$contexts['ingredients_and'] = array( 'include_ingredients' => wp_list_pluck( $ingredients, 'slug' ) );
	$contexts['include_exclude'] = array( 'include_ingredients' => array( $ingredients[0]->slug ), 'exclude_ingredients' => array( $ingredients[1]->slug ) );
	if ( $region ) { $contexts['region_ingredient'] = $contexts['region'] + $contexts['ingredient']; }
}
$report = array( 'checked_at' => gmdate( 'c' ), 'site' => home_url( '/' ), 'wordpress' => get_bloginfo( 'version' ), 'database' => $version,
	'database_writes' => false, 'external_object_cache' => wp_using_ext_object_cache(),
	'method' => 'Existing local data, retained DB buffers; no production connection or load simulation. Per scenario: uncached WP_Query generates SQL, then nine direct SELECT executions (median, excluding FOUND_ROWS and EXPLAIN), EXPLAIN and MySQL 8.0.18+ EXPLAIN ANALYZE. Final two WP_Query calls check per-process caching. Not full-page TTFB.',
	'public_recipes' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'recipe' AND post_status = 'publish' AND post_password = ''" ),
	'postmeta_rows' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta}" ),
	'sort_metadata' => $wpdb->get_results( "SELECT meta_key, COUNT(*) AS rows_count, COUNT(DISTINCT post_id) AS posts FROM {$wpdb->postmeta} WHERE meta_key IN ('_recipe_time', '_recipe_views', '_recipe_region') GROUP BY meta_key", ARRAY_A ),
	'postmeta_indexes' => $wpdb->get_results( "SHOW INDEX FROM {$wpdb->postmeta}", ARRAY_A ), 'cases' => array() );
foreach ( array( 'date', 'recipe_time', 'recipe_views' ) as $sort ) {
	foreach ( $contexts as $name => $request ) {
		$request['sort_by'] = $sort;
		$args = cooklook_recipe_query_args( cooklook_recipe_filters( $request ) ) + array( 'fields' => 'ids', 'cache_results' => false );
		$query = new WP_Query( $args );
		if ( $wpdb->last_error ) { throw new RuntimeException( $wpdb->last_error ); }
		$samples = array();
		for ( $pass = 0; $pass < 9; $pass++ ) {
			$start = microtime( true ); $ids = $wpdb->get_col( $query->request );
			$samples[] = round( ( microtime( true ) - $start ) * 1000, 3 );
			if ( $wpdb->last_error || array_map( 'intval', $ids ) !== array_map( 'intval', $query->posts ) ) { throw new RuntimeException( 'SQL error or unstable sort results.' ); }
			if ( (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' ) !== (int) $query->found_posts ) { throw new RuntimeException( 'Found rows changed.' ); }
		}
		$sorted = $samples; sort( $sorted );
		$result = array( 'case' => $sort . '_' . $name, 'request' => $request, 'found' => (int) $query->found_posts,
			'ids' => array_map( 'intval', $query->posts ), 'sql' => $query->request, 'samples_ms' => $samples, 'median_ms' => $sorted[4],
			'explain' => $wpdb->get_results( 'EXPLAIN ' . $query->request, ARRAY_A ),
			'analyze' => $can_analyze ? $wpdb->get_var( 'EXPLAIN ANALYZE ' . $query->request ) : null );
		$args['cache_results'] = true;
		$result['wp_query_sql_counts'] = array();
		for ( $pass = 0; $pass < 2; $pass++ ) {
			$before = $wpdb->num_queries; $cached = new WP_Query( $args );
			$result['wp_query_sql_counts'][] = $wpdb->num_queries - $before;
			if ( $cached->posts !== $query->posts || (int) $cached->found_posts !== (int) $query->found_posts ) { throw new RuntimeException( 'WP cache changed IDs/count.' ); }
		}
		$report['cases'][] = $result;
	}
}
echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
