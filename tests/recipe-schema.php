<?php
/** Recipe image regressions, using in-memory metadata only: php tests/recipe-schema.php */
require __DIR__ . '/bootstrap.php';
foreach ( array( 'inc/recipe-helpers.php', 'inc/recipe-seo.php', 'inc/seo-metadata.php', 'inc/template-functions.php', 'functions-parts/recipe_rating.php', 'template-parts/schema.php' ) as $file ) {
	require dirname( __DIR__ ) . '/' . $file;
}
function is_singular( $type = null ) { return ! $type || 'recipe' === $type; }
function get_queried_object_id() { return 42; }

test_case( 'An undersized dish photo keeps Recipe and adds an editorial quality recommendation', function () {
	$GLOBALS['test_images'][100]['full'] = array( 'https://example.test/dish.jpg', 100, 100 );
	$before = $GLOBALS['test_meta'];
	expect( cooklook_recipe_schema( 42 )['image'] === array( 'https://example.test/dish.jpg' ), 'A quality recommendation removed a real dish photo from Recipe' );
	$graph = array( array( '@type' => 'WebPage', '@id' => get_permalink( 42 ) ) );
	$result = cooklook_recipe_schema_graph( $graph );
	expect( count( $result ) === 2 && $result[0]['mainEntity']['@id'] === $result[1]['@id'], 'A small real photo broke the Recipe graph relation' );
	$issues = cooklook_recipe_review_issues( 42 );
	expect( isset( $issues['undersized_dish_image'] ) && ! isset( $issues['missing_dish_image'] ), 'The editor was not told about the small existing photo' );
	expect( $GLOBALS['test_meta'] === $before, 'Image validation changed stored metadata' );
} );

test_case( 'The recommended image area boundary controls diagnostics without removing the original', function () {
	$GLOBALS['test_images'][100]['full'] = array( 'https://example.test/dish.jpg', 250, 199 );
	expect( cooklook_recipe_images( 42 ) === array( 'https://example.test/dish.jpg' ), '49,750 pixel real photo was removed' );
	expect( isset( cooklook_recipe_review_issues( 42 )['undersized_dish_image'] ), 'Photo below the recommended area was not reported' );
	$GLOBALS['test_images'][100]['full'][2] = 200;
	expect( cooklook_recipe_images( 42 ) === array( 'https://example.test/dish.jpg' ), '50,000 pixel photo was rejected' );
	expect( ! isset( cooklook_recipe_review_issues( 42 )['undersized_dish_image'] ), 'Valid minimum area is still reported as too small' );
} );

test_case( 'Legacy photos with unknown dimensions remain available with an explicit review issue', function () {
	foreach ( array( array( 0, 0 ), array( null, null ), array( 'unknown', 1000 ) ) as $dimensions ) {
		$GLOBALS['test_images'][100]['full'] = array_merge( array( 'https://example.test/dish.jpg' ), $dimensions );
		expect( cooklook_recipe_images( 42 ) === array( 'https://example.test/dish.jpg' ), 'Unknown dimensions silently removed a legacy photo' );
		$issues = cooklook_recipe_review_issues( 42 );
		expect( isset( $issues['unknown_image_dimensions'] ) && ! isset( $issues['undersized_dish_image'] ), 'Unknown dimensions were confused with a small photo' );
	}
} );

test_case( 'Placeholders and protected recipes remain excluded with no graph link', function () {
	$graph = array( array( '@type' => 'WebPage', '@id' => get_permalink( 42 ) ) );
	unset( $GLOBALS['test_meta'][42]['_thumbnail_id'] );
	expect( cooklook_recipe_schema_graph( $graph ) === $graph, 'Missing dish photo left a Recipe graph link' );
	$GLOBALS['test_meta'][42]['_thumbnail_id'] = 100;
	$GLOBALS['test_images'][100]['full'] = array( 'https://example.test/no_image.png', 100, 100 );
	$issues = cooklook_recipe_review_issues( 42 );
	expect( isset( $issues['missing_dish_image'] ) && ! isset( $issues['undersized_dish_image'] ), 'Placeholder reported as a real small dish photo' );
	expect( cooklook_recipe_schema_graph( $graph ) === $graph, 'Placeholder created a Recipe graph link' );
	$GLOBALS['test_images'][100]['full'] = array( 'https://example.test/dish.jpg', 1500, 1000 );
	$GLOBALS['test_posts'][42]->post_password = 'private';
	expect( cooklook_recipe_schema_graph( $graph ) === $graph, 'Protected recipe data entered the graph' );
} );

echo "\n{$GLOBALS['test_passed']} passed, {$GLOBALS['test_failed']} failed.\n";
exit( $GLOBALS['test_failed'] ? 1 : 0 );
