<?php
/** php tools/seo-ingredient-profile.php > docs/seo-ingredient-profile-{before,after}.json
 * Read-only ingredient modal measurements after WP bootstrap; not full-page TTFB.
 */
if ( PHP_SAPI !== 'cli' ) { exit; }
define( 'DISABLE_WP_CRON', true );
define( 'SAVEQUERIES', true );
require ( getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 ) ) . '/wp-load.php';
global $wpdb;
add_filter( 'query', function ( $sql ) {
	if ( ! preg_match( '/^\s*(?:SELECT|SHOW|EXPLAIN)\b/i', $sql ) ) { throw new RuntimeException( 'Only reads are allowed.' ); }
	return $sql;
}, PHP_INT_MAX );
$counts = $wpdb->get_row( "SELECT COUNT(*) AS total, SUM(count > 0) AS nonempty FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'recipe_tags'", ARRAY_A );
$slugs = $wpdb->get_col( "SELECT t.slug FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id WHERE tt.taxonomy = 'recipe_tags' AND tt.count > 0 ORDER BY tt.count DESC, t.term_id ASC LIMIT 3" );
$cases = array( 'blank' => array(), 'selected' => array( 'include_ingredients' => array_slice( $slugs, 0, 2 ), 'exclude_ingredients' => array_slice( $slugs, 2, 1 ) ) );
$results = array();
foreach ( $cases as $name => $request ) {
	$args = array( 'filters' => cooklook_recipe_filters( $request ) );
	$runs = array();
	for ( $pass = 1; $pass <= 3; $pass++ ) {
		$wpdb->queries = array();
		$start = microtime( true );
		ob_start();
		include dirname( __DIR__ ) . '/template-parts/filter-ingridients.php';
		$html = ob_get_clean();
		$runs[] = array( 'pass' => $pass, 'elapsed_ms' => round( ( microtime( true ) - $start ) * 1000, 3 ),
			'bytes' => strlen( $html ), 'options' => substr_count( $html, '<option ' ),
			'sql_count' => count( $wpdb->queries ), 'queries' => array_column( $wpdb->queries, 0 ) );
	}
	$results[ $name ] = array( 'request' => $request, 'runs' => $runs );
}
echo wp_json_encode( array( 'checked_at' => gmdate( 'c' ), 'site' => home_url( '/' ),
	'method' => 'Modal HTML only after WP bootstrap, three calls per case in one process, no external cache flush. First blank pass precedes selected passes. Times are not full-page TTFB.',
	'external_object_cache' => wp_using_ext_object_cache(), 'ingredient_counts' => $counts, 'cases' => $results ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
