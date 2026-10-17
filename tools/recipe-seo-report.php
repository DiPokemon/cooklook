<?php
/** Read-only inventory: php tools/recipe-seo-report.php > recipe-review.json */
if ( PHP_SAPI !== 'cli' ) {
	http_response_code( 404 );
	exit;
}
$root = getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 );
if ( ! is_file( $root . '/wp-load.php' ) ) {
	fwrite( STDERR, "Set COOKLOOK_WP_ROOT to the WordPress installation.\n" );
	exit( 1 );
}
define( 'WP_USE_THEMES', false );
define( 'DISABLE_WP_CRON', true );
require $root . '/wp-load.php';
if ( ! function_exists( 'cooklook_recipe_review_issues' ) ) {
	fwrite( STDERR, "Activate the Cooklook theme before running this report.\n" );
	exit( 1 );
}
$report = array( 'generated_at' => gmdate( 'c' ), 'site' => home_url( '/' ), 'checked' => 0, 'issue_counts' => array(), 'recipes' => array() );
$page = 1;
do {
	$query = new WP_Query( array( 'post_type' => 'recipe', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => 100, 'paged' => $page++, 'orderby' => 'ID', 'order' => 'ASC', 'fields' => 'ids', 'no_found_rows' => true ) );
	update_meta_cache( 'post', $query->posts );
	foreach ( $query->posts as $id ) {
		$report['checked']++;
		$issues = array_keys( cooklook_recipe_review_issues( $id ) );
		if ( ! $issues ) {
			continue;
		}
		foreach ( $issues as $issue ) {
			$report['issue_counts'][ $issue ] = isset( $report['issue_counts'][ $issue ] ) ? $report['issue_counts'][ $issue ] + 1 : 1;
		}
		$report['recipes'][] = array( 'id' => $id, 'url' => get_permalink( $id ), 'title' => get_the_title( $id ), 'issues' => $issues );
	}
} while ( count( $query->posts ) === 100 );
echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
