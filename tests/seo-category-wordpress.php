<?php
/** Read-only verification of category metadata against the real local taxonomy. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
define( 'DISABLE_WP_CRON', true );
require __DIR__ . '/support/readonly-wordpress.php';
require ( getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 ) ) . '/wp-load.php';
if ( 'http://cooklook/' !== home_url( '/' ) || 'cooklook' !== get_template() ) {
	throw new RuntimeException( 'Use the local Cooklook site.' );
}
$terms = get_terms( array( 'taxonomy' => 'recipe_category', 'hide_empty' => false ) );
if ( is_wp_error( $terms ) || ! $terms ) { throw new RuntimeException( 'Category taxonomy is unavailable.' ); }
$GLOBALS['wp_query'] = new WP_Query();
$GLOBALS['wp_query']->is_tax = true;
$GLOBALS['wp_query']->is_archive = true;
$by_name = array(); $by_title = array(); $report = array();
foreach ( $terms as $term ) {
	$GLOBALS['wp_query']->queried_object = $term;
	$GLOBALS['wp_query']->queried_object_id = $term->term_id;
	$metadata = cooklook_seo_metadata_defaults( 1 );
	if ( ! $metadata['title'] || ! $metadata['description'] ) { throw new RuntimeException( 'Empty metadata: ' . $term->slug ); }
	if ( $term->parent ) {
		$parent = get_term( $term->parent, 'recipe_category' );
		if ( ! ( $parent instanceof WP_Term ) || false === strpos( $metadata['title'], wp_strip_all_tags( $parent->name ) ) ) {
			throw new RuntimeException( 'Missing parent context: ' . $term->slug );
		}
	}
	$by_name[ $term->name ][] = $term->slug;
	$by_title[ $metadata['title'] ][] = $term->slug;
	$report[] = array( 'slug' => $term->slug, 'name' => $term->name, 'parent' => $term->parent, 'metadata' => $metadata );
}
$duplicates = array_filter( $by_title, function ( $members ) { return count( $members ) > 1; } );
$homonyms = array_filter( $by_name, function ( $members ) { return count( $members ) > 1; } );
if ( $duplicates ) { throw new RuntimeException( 'Default category titles still duplicate: ' . wp_json_encode( $duplicates ) ); }
$result = array( 'checkedAt' => gmdate( 'c' ), 'site' => home_url( '/' ), 'databaseWrites' => false, 'categories' => count( $terms ), 'homonymousGroups' => count( $homonyms ), 'duplicateDefaultTitlesAfter' => count( $duplicates ), 'homonyms' => $homonyms, 'pages' => $report );
file_put_contents( dirname( __DIR__ ) . '/docs/seo-category-metadata-check.json', wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n" );
echo 'PASS: ' . count( $terms ) . ' categories, ' . count( $homonyms ) . " homonymous groups, no duplicate default titles. No database writes.\n";
