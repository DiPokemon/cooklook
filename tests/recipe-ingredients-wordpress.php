<?php
/** Read-only real WordPress ingredient search/pagination and selected-HTML checks.
 * php tests/recipe-ingredients-wordpress.php > docs/seo-ingredient-integration.json
 */
if ( PHP_SAPI !== 'cli' ) { exit; }
define( 'DISABLE_WP_CRON', true );
define( 'SAVEQUERIES', true );
require ( getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 ) ) . '/wp-load.php';
global $wpdb;
add_filter( 'query', function ( $sql ) {
	if ( ! preg_match( '/^\s*(?:SELECT|SHOW|EXPLAIN)\b/i', $sql ) ) { throw new RuntimeException( 'Only read queries allowed.' ); }
	return $sql;
}, PHP_INT_MAX );
function ingredient_check( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }
$all = get_terms( array( 'taxonomy' => 'recipe_tags', 'hide_empty' => false, 'update_term_meta_cache' => false ) );
$bigrams = array();
foreach ( $all as $term ) {
	if ( ! $term->count ) { continue; }
	preg_match_all( '/./u', mb_strtolower( $term->name ), $chars ); $seen = array();
	for ( $i = 1; $i < count( $chars[0] ); $i++ ) { $seen[ $chars[0][ $i - 1 ] . $chars[0][ $i ] ] = true; }
	foreach ( array_keys( $seen ) as $pair ) {
		if ( preg_match( '/^\p{L}{2}$/u', $pair ) ) { $bigrams[ $pair ] = isset( $bigrams[ $pair ] ) ? $bigrams[ $pair ] + 1 : 1; }
	}
}
arsort( $bigrams );
$searches = array_unique( array_merge( array_slice( array_keys( $bigrams ), 0, 3 ), array( 'мол', 'МОЛ', 'лук', 'соль', 'несуществующийингредиент', '%_', "' OR 1=1 --" ) ) );
$cases = array();
foreach ( $searches as $search ) {
	// This unbounded reference query is restricted to this read-only test.
	$expected = get_terms( array( 'taxonomy' => 'recipe_tags', 'hide_empty' => true, 'hierarchical' => false, 'search' => $search, 'orderby' => 'name', 'order' => 'ASC', 'update_term_meta_cache' => false ) );
	$expected = wp_list_pluck( $expected, 'slug' ); $actual = array(); $queries = array(); $times = array();
	$pages = max( 1, (int) ceil( count( $expected ) / 20 ) );
	for ( $page = 1; $page <= $pages; $page++ ) {
		$wpdb->queries = array(); $start = microtime( true );
		$result = cooklook_search_ingredients( array( 'term' => $search, 'page' => (string) $page ) );
		$times[] = round( ( microtime( true ) - $start ) * 1000, 3 );
		ingredient_check( ! is_wp_error( $result ) && '' === $wpdb->last_error, 'Search query failed' );
		ingredient_check( count( $result['results'] ) <= 20, 'Page too large' );
		ingredient_check( ( $page < $pages ) === $result['pagination']['more'], 'Incorrect next-page flag: ' . $search );
		$actual = array_merge( $actual, wp_list_pluck( $result['results'], 'id' ) );
		$queries = array_merge( $queries, array_column( $wpdb->queries, 0 ) );
	}
	ingredient_check( $expected === $actual && count( $actual ) === count( array_unique( $actual ) ), 'Search pages lost or duplicated a term' );
	ingredient_check( ! cooklook_search_ingredients( array( 'term' => $search, 'page' => (string) ( $pages + 1 ) ) )['results'], 'Out-of-range page not empty' );
	$cases[] = array( 'term' => $search, 'matches' => count( $actual ), 'pages' => $pages, 'elapsed_ms' => $times, 'queries' => $queries );
}
$wpdb->queries = array();
cooklook_search_ingredients( array( 'term' => 'я' ) ); cooklook_search_ingredients( array( 'term' => '' ) );
ingredient_check( ! $wpdb->queries, 'Short search queried the database' );
$empty = array_values( array_filter( $all, function ( $term ) { return ! $term->count; } ) );
$selected = array( $all[0]->slug, $all[1]->slug );
if ( $empty ) { $selected[] = $empty[0]->slug; }
$args = array( 'filters' => cooklook_recipe_filters( array( 'include_ingredients' => $selected, 'exclude_ingredients' => array( $all[2]->slug ) ) ) );
$restored = cooklook_selected_ingredients( $args['filters'] );
ingredient_check( count( $restored ) === count( array_unique( array_merge( $selected, array( $all[2]->slug ) ) ) ), 'Selected terms, including empty, not restored' );
ob_start(); include dirname( __DIR__ ) . '/template-parts/filter-ingridients.php'; $html = ob_get_clean();
ingredient_check( substr_count( $html, '<option ' ) === count( $selected ) + 1, 'Unexpected SSR options' );
foreach ( array_merge( $selected, array( $all[2]->slug ) ) as $slug ) {
	ingredient_check( false !== strpos( $html, 'value="' . esc_attr( $slug ) . '" selected' ), 'Missing SSR selection' );
}
echo wp_json_encode( array( 'checked_at' => gmdate( 'c' ), 'site' => home_url( '/' ), 'database_writes' => false,
	'method' => 'Search pages compared with complete reference queries on the same DB; SQL logging after bootstrap. No cache flush. Counts/times describe local data only.',
	'cases' => $cases, 'short_search_sql' => 0, 'selected_options' => count( $selected ) + 1, 'empty_selected_restored' => count( $empty ) > 0,
	'http_cases' => array( 'include_ingredients' => $selected, 'exclude_ingredients' => array( $all[2]->slug ), 'empty' => $empty ? $empty[0]->slug : null ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
