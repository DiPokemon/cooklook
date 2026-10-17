<?php
/** Read-only local cohort inventory: php tools/seo-cohort-snapshot.php > build/seo-cohort-inventory.json */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
define( 'DISABLE_WP_CRON', true );
// Exclude unrelated import housekeeping and keep transients in this CLI process only.
$GLOBALS['wp_filter']['option_active_plugins'][ PHP_INT_MAX ][] = array( 'function' => function ( $plugins ) {
	return array_values( array_filter( $plugins, function ( $plugin ) { return 0 !== strpos( $plugin, 'wp-import-export-lite/' ); } ) );
}, 'accepted_args' => 1 );
$GLOBALS['wp_filter']['muplugins_loaded'][ PHP_INT_MIN ][] = array( 'function' => function () {
	if ( wp_using_ext_object_cache() ) { throw new RuntimeException( 'Use a local copy without persistent object cache.' ); }
	wp_using_ext_object_cache( true );
}, 'accepted_args' => 0 );
$GLOBALS['wp_filter']['query'][ PHP_INT_MAX ][] = array( 'function' => function ( $sql ) {
	if ( ! preg_match( '/^\s*(?:SELECT|SHOW|EXPLAIN|DESCRIBE)\b/i', $sql ) ) { throw new RuntimeException( 'Only read SQL is allowed.' ); }
	return $sql;
}, 'accepted_args' => 1 );
$GLOBALS['wp_filter']['pre_wp_mail'][ PHP_INT_MAX ][] = array( 'function' => function () { return false; }, 'accepted_args' => 1 );
$GLOBALS['wp_filter']['pre_http_request'][ PHP_INT_MAX ][] = array( 'function' => function () { return new WP_Error( 'readonly_cohort', 'External requests disabled.' ); }, 'accepted_args' => 1 );
require ( getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 ) ) . '/wp-load.php';
if ( 'http://cooklook/' !== home_url( '/' ) || 'cooklook' !== get_template() ) { throw new RuntimeException( 'This inventory is for the local Cooklook copy.' ); }

function cohort_hash( $value ) {
	return hash( 'sha256', wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}

$inventory = array();
$page = 1;
do {
	$query = new WP_Query( array( 'post_type' => 'recipe', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => 100, 'paged' => $page++, 'orderby' => 'ID', 'order' => 'ASC', 'no_found_rows' => true ) );
	foreach ( $query->posts as $post ) {
		$facts = cooklook_recipe_facts( $post->ID );
		$categories = array(); $tags = array();
		foreach ( cooklook_recipe_terms( $post->ID, 'recipe_category' ) as $term ) {
			$categories[] = array( 'id' => $term->term_id, 'parent' => $term->parent, 'name' => $term->name, 'slug' => $term->slug );
		}
		usort( $categories, function ( $a, $b ) { return $a['id'] <=> $b['id']; } );
		foreach ( cooklook_recipe_terms( $post->ID, 'recipe_tags' ) as $term ) { $tags[] = $term->slug; }
		sort( $tags, SORT_STRING );
		$steps = array_column( $facts['steps'], 'text' );
		$ingredients = array_column( $facts['ingredients'], 'text' );
		$instructions = implode( ' ', $steps );
		$excerpt = cooklook_recipe_plain_text( $post->post_excerpt );
		$images = cooklook_recipe_images( $post->ID );
		$source = array( 'url' => get_permalink( $post ), 'title' => $post->post_title, 'content' => $post->post_content,
			'time' => cooklook_recipe_value( $post->ID, 'recipe_time' ), 'prep' => cooklook_recipe_value( $post->ID, 'recipe_prep' ), 'facts' => $facts );
		$inventory[] = array(
			'id' => $post->ID, 'url' => $source['url'], 'title' => $post->post_title,
			'published_gmt' => $post->post_date_gmt, 'modified_gmt' => $post->post_modified_gmt,
			'categories' => $categories, 'ingredient_tags' => $tags, 'ingredient_names' => array_column( $facts['ingredients'], 'name' ),
			'ingredient_count' => count( $ingredients ), 'step_count' => count( $steps ),
			'instruction_words' => '' === $instructions ? 0 : count( preg_split( '/\s+/u', $instructions ) ),
			'portions' => $facts['portions'], 'cuisine' => $facts['cuisine'], 'time_present' => $facts['total'] > 0,
			'image_count' => count( $images ), 'image_urls_sha256' => cohort_hash( $images ),
			'public_editorial_sha256' => cohort_hash( cooklook_recipe_editorial( $post->ID ) ),
			'recipe_schema_present' => (bool) cooklook_recipe_schema( $post->ID ),
			'excerpt_present' => '' !== $excerpt, 'excerpt_sha256' => hash( 'sha256', $post->post_excerpt ),
			'source_sha256' => cohort_hash( $source ),
			'recipe_body_sha256' => cohort_hash( array( 'ingredients' => $ingredients, 'steps' => $steps ) ),
			'matching_features_sha256' => cohort_hash( array( $categories, $tags, $facts['ingredients'], $steps ) ),
		);
	}
} while ( count( $query->posts ) === 100 );
echo wp_json_encode( array( 'generated_at' => gmdate( 'c' ), 'site' => home_url( '/' ), 'wordpress' => get_bloginfo( 'version' ), 'yoast' => defined( 'WPSEO_VERSION' ) ? WPSEO_VERSION : null,
	'php' => PHP_VERSION, 'database_writes' => false, 'search_metrics' => null,
	'method' => 'Read-only local inventory. Source hashes exclude excerpts, dates, ratings and view counters. Import housekeeping and external requests are excluded; transients stay in the default in-memory cache. No production observations or traffic estimates.',
	'recipes' => $inventory ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
