<?php
/** Real WordPress directory/filter compatibility. Only directory cache writes are allowed.
 * php tests/recipe-regions-wordpress.php > docs/seo-region-integration.json
 */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
define( 'DISABLE_WP_CRON', true );
require ( getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 ) ) . '/wp-load.php';
global $wpdb;
add_filter( 'query', function ( $sql ) use ( $wpdb ) {
	if ( ! preg_match( '/^\s*(?:SELECT|SHOW|EXPLAIN)\b/i', $sql ) && ! ( false !== strpos( $sql, $wpdb->options ) && false !== strpos( $sql, 'cooklook_recipe_regions_' ) && preg_match( '/^\s*(?:INSERT|UPDATE|DELETE)\b/i', $sql ) ) ) {
		throw new RuntimeException( 'SQL outside the directory cache refused.' );
	}
	return $sql;
}, PHP_INT_MAX );
function region_check( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }
$hooks = array(
	'added_post_meta' => array( 'cooklook_recipe_regions_meta_changed', 3 ),
	'updated_post_meta' => array( 'cooklook_recipe_regions_meta_changed', 3 ),
	'deleted_post_meta' => array( 'cooklook_recipe_regions_meta_changed', 3 ),
	'wp_after_insert_post' => array( 'cooklook_recipe_regions_post_saved', 4 ),
	'deleted_post' => array( 'cooklook_recipe_regions_post_deleted', 2 ),
	'after_switch_theme' => array( 'cooklook_invalidate_recipe_regions', 1 ),
);
foreach ( $hooks as $hook => $handler ) {
	region_check( 10 === has_action( $hook, $handler[0] ) && $GLOBALS['wp_filter'][ $hook ]->callbacks[10][ $handler[0] ]['accepted_args'] === $handler[1], 'Missing handler or arguments: ' . $hook );
}
$legacy = $wpdb->get_col( "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_recipe_region' AND p.post_type = 'recipe' AND p.post_status = 'publish' AND p.post_password = '' ORDER BY pm.meta_value" );
region_check( $legacy === cooklook_recipe_regions(), 'Cached directory differs from the original SQL' );
$cases = array();
foreach ( array_filter( $legacy ) as $region ) {
	$expected = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT p.ID, p.post_date FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE pm.meta_key = '_recipe_region' AND pm.meta_value = %s AND p.post_type = 'recipe' AND p.post_status = 'publish' AND p.post_password = '' ORDER BY p.post_date DESC, p.ID DESC", $region ) ) );
	$request = array( 'region' => $region );
	$filters = cooklook_recipe_filters( $request );
	region_check( true === cooklook_validate_recipe_filters( $request, $filters ), 'Existing region rejected: ' . $region );
	$ids = array(); $pages = max( 1, (int) ceil( count( $expected ) / 11 ) );
	for ( $page = 1; $page <= $pages; $page++ ) {
		$filters['paged'] = $page;
		$query = new WP_Query( array_merge( cooklook_recipe_query_args( $filters ), array( 'fields' => 'ids' ) ) );
		$ids = array_merge( $ids, array_map( 'intval', $query->posts ) );
	}
	region_check( $expected === $ids, 'Filtered IDs/order/pagination changed: ' . $region );
	$alias = mb_strtolower( $region, 'UTF-8' );
	$alias_request = array( 'recipe_region' => $alias );
	$alias_filters = cooklook_recipe_filters( $alias_request );
	region_check( true === cooklook_validate_recipe_filters( $alias_request, $alias_filters ), 'Legacy lower-case region link rejected' );
	region_check( in_array( $alias_filters['region'], cooklook_recipe_region_choices( $alias_filters['region'] ), true ), 'Legacy region cannot be restored in the dropdown' );
	$alias_query = new WP_Query( array_merge( cooklook_recipe_query_args( $alias_filters ), array( 'fields' => 'ids', 'posts_per_page' => -1 ) ) );
	region_check( $expected === array_map( 'intval', $alias_query->posts ), 'Database collation compatibility lost' );
	$cases[] = array( 'region' => $region, 'recipes' => count( $ids ), 'pages' => $pages, 'ids_order_and_legacy_alias_match' => true );
}
foreach ( array( array( 'region' => 'Cooklook-Nonexistent-Region-Check' ), array( 'region' => array( 'Русская кухня' ) ) ) as $request ) {
	region_check( is_wp_error( cooklook_validate_recipe_filters( $request, cooklook_recipe_filters( $request ) ) ), 'Invalid region accepted' );
}
// The legacy validator remains a live SQL lookup, independent of the cached dropdown.
$key = cooklook_recipe_regions_cache_key();
add_filter( 'pre_transient_' . $key, '__return_empty_array' );
$request = array( 'region' => reset( $cases )['region'] );
region_check( array() === cooklook_recipe_regions() && true === cooklook_validate_recipe_filters( $request, cooklook_recipe_filters( $request ) ), 'Directory cache controls filter validity' );
remove_filter( 'pre_transient_' . $key, '__return_empty_array' );
echo wp_json_encode( array( 'checked_at' => gmdate( 'c' ), 'site' => home_url( '/' ), 'recipe_writes' => false,
	'method' => 'Real database read comparison and hook registration check. Cache writes only; recipe lifecycle/invalidation/race cases run separately in memory.',
	'raw_directory_equal' => true, 'visible_regions' => count( $cases ), 'registered_hooks' => array_keys( $hooks ),
	'invalid_regions_rejected' => true, 'validation_independent_of_directory_cache' => true, 'cases' => $cases ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
