<?php
/** Actual WordPress/MySQL sort regression; all database access is read-only.
 * php tests/recipe-sorting-wordpress.php > docs/seo-sort-integration.json
 * Edge cases use SELECT/UNION fixtures, never inserted posts or metadata.
 */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
define( 'DISABLE_WP_CRON', true );
require ( getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 ) ) . '/wp-load.php';
global $wpdb;
add_filter( 'query', function ( $sql ) {
	if ( ! preg_match( '/^\s*(?:SELECT|SHOW|EXPLAIN)\b/i', $sql ) ) { throw new RuntimeException( 'Non-read SQL refused by sorting test.' ); }
	return $sql;
}, PHP_INT_MAX );
function sort_check( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }
function sort_pages( $request, $expected, $page_size = 11 ) {
	$args = cooklook_recipe_query_args( cooklook_recipe_filters( $request ) );
	$args['fields'] = 'ids'; $args['cache_results'] = false; $args['posts_per_page'] = $page_size;
	$pages = max( 1, (int) ceil( count( $expected ) / $page_size ) ); $all = array();
	for ( $page = 1; $page <= $pages; $page++ ) {
		$args['paged'] = $page; $query = new WP_Query( $args );
		sort_check( '' === $GLOBALS['wpdb']->last_error, 'SQL error in pagination' );
		sort_check( array_slice( $expected, ( $page - 1 ) * $page_size, $page_size ) === array_map( 'intval', $query->posts ), 'Page IDs or stable order changed: ' . wp_json_encode( $request ) );
		if ( $expected ) { sort_check( count( $expected ) === (int) $query->found_posts && $pages === (int) $query->max_num_pages, 'Pagination count changed' ); }
		$all = array_merge( $all, array_map( 'intval', $query->posts ) );
	}
	sort_check( $all === $expected && count( array_unique( $all ) ) === count( $all ), 'Missing or duplicate recipe between pages' );
	$args['paged'] = $pages + 1; $query = new WP_Query( $args );
	sort_check( ! $query->posts, 'Out-of-range page is not empty' );
	return $pages;
}
$baseline = json_decode( file_get_contents( dirname( __DIR__ ) . '/docs/seo-sort-profile-before.json' ), true );
sort_check( isset( $baseline['cases'] ) && $baseline['site'] === home_url( '/' ), 'Matching baseline is required' );
$real_cases = array();
foreach ( $baseline['cases'] as $case ) {
	// Original SQL retains the pre-change Carbon Fields behavior; remove LIMIT to compare all pages.
	$sql = preg_replace( '/\s+LIMIT\s+\d+\s*,\s*\d+\s*$/i', '', $case['sql'] );
	$expected = array_map( 'intval', $wpdb->get_col( $sql ) );
	sort_check( '' === $wpdb->last_error, 'Baseline SQL failed' );
	$pages = sort_pages( $case['request'], $expected );
	$real_cases[] = array( 'case' => $case['case'], 'recipes' => count( $expected ), 'pages' => $pages, 'all_ids_order_counts_equal' => true );
}
// The scalar region path must leave unrelated queries alone and fail closed on invalid custom args.
sort_check( 'WHERE original' === cooklook_recipe_region_where( 'WHERE original', new WP_Query() ), 'Unrelated query changed' );
$bad_query = new WP_Query(); $bad_query->set( 'cooklook_recipe_region', array( 'bad' ) );
sort_check( 'WHERE original AND 1=0' === cooklook_recipe_region_where( 'WHERE original', $bad_query ), 'Invalid region shape broadened results' );
foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $hook ) {
	sort_check( false !== has_action( $hook, 'wp_cache_set_posts_last_changed' ), 'Core query-cache invalidation missing: ' . $hook );
}

// ASCII fixture SQL avoids wpdb's physical-table collation probe on a derived UNION table.
// Real Cyrillic values are covered by the local-data comparisons above.
$region = "Cuisine _% 'test'"; $post_rows = array(); $meta_rows = array();
$values = array( 102 => array( '' ), 103 => array( '0' ), 104 => array( '2' ), 105 => array( '10' ), 106 => array( '2', '100' ), 107 => array( '2.5' ), 108 => array( '-5' ), 109 => array( 'bad' ), 110 => array( null ), 111 => array( '3' ) );
for ( $id = 101; $id <= 117; $id++ ) {
	$status = array( 112 => 'private', 115 => 'draft', 116 => 'future', 117 => 'trash' );
	$post_rows[] = $wpdb->prepare( 'SELECT %d AS ID, %s AS post_type, %s AS post_status, %s AS post_password, %s AS post_date', $id,
		114 === $id ? 'post' : 'recipe', isset( $status[ $id ] ) ? $status[ $id ] : 'publish', 113 === $id ? 'secret' : '',
		in_array( $id, array( 102, 103 ), true ) ? '2026-01-02 00:00:00' : '2026-01-01 00:00:00' );
	$meta_rows[] = $wpdb->prepare( 'SELECT %d AS post_id, %s AS meta_key, %s AS meta_value', $id, '_recipe_region', 111 === $id ? 'Other cuisine' : $region );
	foreach ( array( '_recipe_time', '_recipe_views' ) as $key ) {
		foreach ( isset( $values[ $id ] ) ? $values[ $id ] : ( 101 === $id ? array() : array( '999' ) ) as $value ) {
			$meta_rows[] = null === $value ? $wpdb->prepare( 'SELECT %d AS post_id, %s AS meta_key, NULL AS meta_value', $id, $key ) : $wpdb->prepare( 'SELECT %d AS post_id, %s AS meta_key, %s AS meta_value', $id, $key, $value );
		}
	}
}
$meta_rows[] = $wpdb->prepare( 'SELECT 106 AS post_id, %s AS meta_key, %s AS meta_value', '_recipe_region', $region );
$meta_rows[] = $wpdb->prepare( 'SELECT 111 AS post_id, %s AS meta_key, %s AS meta_value', 'XrecipeYregion', $region );
$fixture = function ( $sql ) use ( $wpdb, $post_rows, $meta_rows ) {
	return str_replace( array( "FROM {$wpdb->posts} ", "FROM {$wpdb->postmeta} " ),
		array( 'FROM (' . implode( ' UNION ALL ', $post_rows ) . ") AS {$wpdb->posts} ", 'FROM (' . implode( ' UNION ALL ', $meta_rows ) . ") AS {$wpdb->postmeta} " ), $sql );
};
$orders = array(
	'date' => array( 103, 102, 111, 110, 109, 108, 107, 106, 105, 104, 101 ),
	'recipe_time' => array( 108, 103, 102, 110, 109, 101, 104, 107, 111, 105, 106 ),
	'recipe_views' => array( 106, 105, 111, 107, 104, 103, 102, 110, 109, 101, 108 ),
);
$fixture_cases = array();
add_filter( 'posts_request', $fixture, PHP_INT_MAX );
try {
	foreach ( $orders as $sort => $order ) {
		foreach ( array( '', $region ) as $filter ) {
			$expected = '' === $filter ? $order : array_values( array_diff( $order, array( 111 ) ) );
			$pages = sort_pages( array( 'sort_by' => $sort, 'region' => $filter ), $expected, 3 );
			$fixture_cases[] = array( 'sort' => $sort, 'region_filter' => '' !== $filter, 'expected_ids' => $expected, 'pages' => $pages, 'passed' => true );
		}
	}
} finally { remove_filter( 'posts_request', $fixture, PHP_INT_MAX ); }
echo wp_json_encode( array( 'checked_at' => gmdate( 'c' ), 'site' => home_url( '/' ), 'database_writes' => false,
	'method' => 'Current WP_Query vs original SQL on all local result pages; SELECT/UNION-only fixtures for numeric/visibility/duplicate/escaping edge cases. No stored test recipes.',
	'real_cases' => $real_cases, 'fixtures' => $fixture_cases,
	'fixture_coverage' => array( 'missing/empty/zero/null/nonnumeric/negative/decimal sort values', 'MAX of duplicate numeric rows', 'stable date/ID ties across pages', 'duplicate regions without duplicate recipes', 'exact region key excludes XrecipeYregion', 'quotes and LIKE wildcards in region value', 'draft/private/future/trash/password/other-post-type excluded' ),
	'unrelated_queries_unchanged' => true, 'invalid_region_shape_fails_closed' => true, 'core_metadata_cache_invalidation_registered' => true ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
