<?php
require __DIR__ . '/bootstrap.php';
foreach ( array( 'inc/recipe-helpers.php', 'inc/recipe-query.php', 'functions-parts/recipe_rating.php', 'functions-parts/favorite_recipes.php', 'recipe_manager.php', 'recipe_importer.php', 'inc/template-functions.php', 'template-parts/schema.php' ) as $file ) {
	require dirname( __DIR__ ) . '/' . $file;
}
test_case( 'Filter aliases, malformed inputs and bounded ingredient lists', function () {
	$filters = cooklook_recipe_filters( array( 'recipe_category' => '12', 'recipe_subcategory' => '21', 'region' => array( 'bad' ), 'paged' => '-2', 'sort_by' => 'SQL', 'include_ingredients' => array( 'milk', array(), 'milk', 'egg' ) ) );
	expect( $filters['category_id'] === 12 && $filters['subcategory_id'] === 21, 'Legacy aliases lost' );
	expect( $filters['region'] === '' && $filters['paged'] === 1 && $filters['sort_by'] === 'date', 'Malformed scalar input accepted' );
	expect( $filters['include_ingredients'] === array( 'milk', 'egg' ), 'Ingredient normalization failed' );
	expect( count( cooklook_ingredient_slugs( range( 1, 100 ) ) ) === 20, 'Ingredient limit missing' );
} );
test_case( 'Sorting retains category, tag, region, ingredients and page', function () {
	foreach ( array( 'date', 'recipe_views', 'recipe_time' ) as $sort ) {
		$args = cooklook_recipe_query_args( cooklook_recipe_filters( array( 'category_id' => 1, 'subcategory_id' => 2, 'region' => 'Русская', 'include_ingredients' => 'milk,egg', 'exclude_ingredients' => array( 'nuts' ), 'paged' => 3, 'sort_by' => $sort ) ), array( 'current_category' => 9, 'current_tag' => 'soup' ) );
		expect( $args['paged'] === 3 && $args['post_status'] === 'publish' && $args['has_password'] === false, 'Page or visibility lost' );
		expect( count( $args['tax_query'] ) === 7 && $args['meta_query'][0]['value'] === 'Русская', 'Filters lost by sort' );
		expect( $args['tax_query'][3]['terms'] === 'soup' && $args['tax_query'][4]['operator'] === 'AND', 'Tag context or ingredient AND lost' );
	}
} );
test_case( 'Numeric ordering retains recipes with missing metadata', function () {
	$query = new WP_Query( array( 'cooklook_sort_meta' => '_recipe_time' ) );
	$clauses = cooklook_recipe_sort_clauses( array( 'join' => '', 'orderby' => '' ), $query );
	expect( strpos( $clauses['join'], 'LEFT JOIN' ) !== false && strpos( $clauses['orderby'], 'ASC' ) !== false, 'Missing values excluded or wrong order' );
} );
test_case( 'All cards survive advertising and post data is restored', function () {
	$query = new WP_Query(); $query->posts = range( 1, 11 ); $GLOBALS['test_rendered'] = array(); $GLOBALS['test_reset'] = false;
	cooklook_render_recipes( $query, true );
	$counts = array_count_values( $GLOBALS['test_rendered'] );
	expect( $counts['template-parts/recipe-loop-item'] === 11 && $counts['template-parts/recipe-loop-item-adv'] === 3, 'Ad replaced a recipe' );
	expect( $GLOBALS['test_reset'], 'Post data not restored' );
} );
test_case( 'Empty recipes produce safe descriptions and arrays', function () {
	expect( cooklook_recipe_description( 42 ) === '' && cooklook_recipe_rows( 42, 'recipe_step' ) === array(), 'Empty recipe not safe' );
	$GLOBALS['test_meta'][42]['_recipe_step'] = array( array( 'recipe_step_text' => '<b>First</b> second third' ) );
	expect( cooklook_recipe_description( 42, 2 ) === 'First second …', 'Step fallback failed' );
} );
test_case( 'Vote transitions are idempotent and never become negative', function () {
	expect( cooklook_vote_transition( 'like', 'like', 10, 2 )['likes'] === 10, 'Duplicate like increments counter' );
	expect( cooklook_vote_transition( 'none', 'remove_like', 10, 2 )['likes'] === 10, 'Removing absent vote changes counter' );
	$vote = cooklook_vote_transition( 'like', 'dislike', 10, 2 );
	expect( $vote['likes'] === 9 && $vote['dislikes'] === 3, 'Switching votes corrupts totals' );
	expect( cooklook_vote_transition( 'like', 'remove_like', 0, 0 )['likes'] === 0, 'Negative counter' );
} );
test_case( 'AJAX repeated vote, switch and removal preserve other votes', function () {
	$GLOBALS['test_users'][7]['user_votes'] = array( 99 => 'like' );
	$_POST = array( 'recipe_id' => '42', 'vote_type' => 'like' );
	expect( response_from( 'update_user_vote' )->success, 'Vote failed' );
	expect( response_from( 'update_user_vote' )->data['likes'] === 11, 'Repeat was not idempotent' );
	$_POST['vote_type'] = 'dislike'; $reply = response_from( 'update_user_vote' );
	expect( $reply->data['likes'] === 10 && $reply->data['dislikes'] === 3, 'Switch failed' );
	$_POST['vote_type'] = 'remove_dislike'; $reply = response_from( 'update_user_vote' );
	expect( $reply->data['user_vote'] === 'none' && $reply->data['dislikes'] === 2, 'Remove failed' );
	expect( get_user_meta( 7, 'user_votes', true )[99] === 'like' && ! $GLOBALS['wpdb']->locks, 'Other votes lost or lock leaked' );
} );
test_case( 'Guests, invalid nonces and non-recipes cannot vote', function () {
	$_POST = array( 'recipe_id' => 42, 'vote_type' => 'like' );
	$GLOBALS['test_user_id'] = 0; expect( response_from( 'update_user_vote' )->status === 401, 'Guest vote allowed' );
	$GLOBALS['test_user_id'] = 7; $_REQUEST['nonce'] = array( 'bad' ); expect( response_from( 'update_user_vote' )->status === 403, 'Array nonce accepted' );
	$_REQUEST['nonce'] = 'test-valid'; $GLOBALS['test_posts'][42]->post_type = 'page'; expect( response_from( 'update_user_vote' )->status === 404, 'Non-recipe vote allowed' );
} );
test_case( 'Vote database failure rolls back and releases locks', function () {
	$_POST = array( 'recipe_id' => 42, 'vote_type' => 'like' ); $GLOBALS['test_fail_meta'] = '_recipe_likes';
	expect( response_from( 'update_user_vote' )->status === 500, 'Failure reported as success' );
	expect( get_user_vote_meta( 7, 42 ) === 'none' && get_post_meta( 42, '_recipe_likes' ) === 10 && ! $GLOBALS['wpdb']->locks, 'Rollback failed' );
	$GLOBALS['wpdb']->busy = true; expect( response_from( 'update_user_vote' )->status === 409, 'Busy write not rejected' );
} );
test_case( 'Favorites add/remove is idempotent and isolated by account', function () {
	$GLOBALS['wpdb']->favorites = array();
	$_POST = array( 'recipe_id' => 42, 'favorite' => '1' );
	response_from( 'add_to_favorites' ); response_from( 'add_to_favorites' );
	expect( count( $GLOBALS['wpdb']->favorites ) === 1, 'Duplicate favorite created' );
	$GLOBALS['test_user_id'] = 8; $_POST['favorite'] = '0'; response_from( 'add_to_favorites' );
	expect( count( $GLOBALS['wpdb']->favorites ) === 1, 'Other account favorite removed' );
	$GLOBALS['test_user_id'] = 7; response_from( 'add_to_favorites' );
	expect( ! $GLOBALS['wpdb']->favorites, 'Favorite not removed' );
	expect( cooklook_favorite_ids( 0 ) === array(), 'Guest favorites exposed' );
} );
test_case( 'Step editing preserves images, row types and omitted times', function () {
	$GLOBALS['test_posts'][42]->post_status = 'draft';
	$GLOBALS['test_meta'][42]['_recipe_time'] = 45; $GLOBALS['test_meta'][42]['_recipe_prep'] = 15;
	$GLOBALS['test_meta'][42]['_recipe_step'] = array( array( '_type' => '_', 'recipe_step_text' => 'old', 'recipe_step_image' => 'https://example.test/a.jpg' ) );
	$_POST = array( 'recipe_id' => 42, 'steps_texts' => array( 'New text' ) );
	expect( response_from( 'save_recipe_steps' )->success, 'Save failed' );
	$step = carbon_get_post_meta( 42, 'recipe_step' )[0];
	expect( $step['recipe_step_image'] === 'https://example.test/a.jpg' && $step['_type'] === '_', 'Step image or type lost' );
	expect( carbon_get_post_meta( 42, 'recipe_time' ) === 45 && carbon_get_post_meta( 42, 'recipe_prep' ) === 15, 'Omitted time erased' );
	expect( get_post_status( 42 ) === 'publish', 'Recipe not published' );
} );
test_case( 'Manager validates all fields before writing and blocks stale drafts', function () {
	$GLOBALS['test_posts'][42]->post_status = 'draft';
	$_POST = array( 'recipe_id' => 42, 'steps_texts' => array( 'New text' ), 'recipe_time' => '-1' );
	expect( response_from( 'save_recipe_steps' )->status === 400 && carbon_get_post_meta( 42, 'recipe_step' ) === '', 'Invalid time caused partial write' );
	$GLOBALS['test_caps']['manage_options'] = false; expect( response_from( 'get_random_draft_recipe' )->status === 403, 'Draft disclosed to subscriber' );
	$GLOBALS['test_caps']['manage_options'] = true; $_POST['recipe_time'] = '15'; $GLOBALS['test_posts'][42]->post_status = 'publish'; expect( response_from( 'save_recipe_steps' )->status === 409, 'Published recipe overwritten' );
} );
test_case( 'CSV parses BOM, quoted commas and rows longer than 1000 bytes', function () {
	$path = tempnam( sys_get_temp_dir(), 'cooklook-test-' );
	try {
		$text = str_repeat( 'Длинный шаг, ', 200 );
		$handle = fopen( $path, 'wb' ); fwrite( $handle, "\xEF\xBB\xBF" ); fputcsv( $handle, array( 'recipe_id', 'steps_texts' ), ',', '"', '' ); fputcsv( $handle, array( 'external-42', $text ), ',', '"', '' ); fclose( $handle );
		$rows = cooklook_read_recipe_csv( $path );
		expect( ! is_wp_error( $rows ) && $rows[0]['steps_texts'] === $text, 'Long quoted row truncated' );
		file_put_contents( $path, "recipe_id,recipe_id\n42,42\n" ); expect( is_wp_error( cooklook_read_recipe_csv( $path ) ), 'Duplicate header accepted' );
		file_put_contents( $path, "recipe_id,steps_texts\n42,text,extra\n" ); expect( is_wp_error( cooklook_read_recipe_csv( $path ) ), 'Malformed row accepted' );
	} finally { unlink( $path ); }
} );
test_case( 'CSV uses production ingredient keys and preserves step images', function () {
	$GLOBALS['test_meta'][42]['_recipe_step'] = array( array( 'recipe_step_image' => 'https://example.test/step.jpg', 'recipe_step_text' => 'old' ) );
	$result = cooklook_import_recipe_row( array( 'recipe_id' => 'external-42', 'ingredients_names' => 'Milk | Egg', 'ingredients_quantities' => '100 ml | 1', 'steps_texts' => 'Mix' ) );
	expect( $result === 42, 'Import failed' );
	expect( carbon_get_post_meta( 42, 'ingridients' )[0]['ingridient_name'] === 'Milk' && carbon_get_post_meta( 42, 'ingredients' ) === '', 'Wrong ingredient schema' );
	expect( carbon_get_post_meta( 42, 'recipe_step' )[0]['recipe_step_image'] === 'https://example.test/step.jpg', 'Image erased on import' );
	$before = $GLOBALS['test_meta'];
	$result = cooklook_import_recipe_row( array( 'recipe_id' => 'external-42', 'ingredients_names' => 'Milk | Egg', 'ingredients_quantities' => '1' ) );
	expect( is_wp_error( $result ) && $GLOBALS['test_meta'] === $before, 'Invalid row partially saved' );
} );
test_case( 'Unauthorized import fails before accessing uploaded files', function () {
	$GLOBALS['test_caps']['manage_options'] = false;
	expect( response_from( 'ajax_recipe_import_from_csv' )->status === 403, 'Unauthorized upload accepted' );
} );
test_case( 'Recipe schema is valid JSON and cannot close the script element', function () {
	$GLOBALS['test_meta'][42]['_recipe_prep'] = 15;
	$GLOBALS['test_meta'][42]['_recipe_step'] = array( array( 'recipe_step_text' => 'Mix "well"\n</script><script>alert(1)</script>' ) );
	$schema = cooklook_recipe_schema( 42 );
	ob_start(); cooklook_print_schema( $schema ); $html = ob_get_clean();
	expect( substr_count( $html, '</script>' ) === 1, 'Unsafe script terminator' );
	$json = substr( $html, strlen( '<script type="application/ld+json">' ), -strlen( '</script>' ) );
	$decoded = json_decode( $json, true );
	expect( json_last_error() === JSON_ERROR_NONE && $decoded['prepTime'] === 'PT15M', 'Invalid schema or literal prepTime' );
	expect( $decoded['aggregateRating']['ratingCount'] === 12, 'Votes missing from schema' );
	$GLOBALS['test_meta'][42]['_recipe_likes'] = 0; $GLOBALS['test_meta'][42]['_recipe_dislikes'] = 0;
	expect( ! isset( cooklook_recipe_schema( 42 )['aggregateRating'] ), 'Fake zero-vote rating included' );
} );
test_case( 'Pending comments are not presented as published and cookie consent is respected', function () {
	$GLOBALS['test_comment'] = (object) array( 'comment_ID' => 5, 'comment_approved' => '0' );
	$response = response_from( 'handle_ajax_comment' );
	expect( $response->success && ! $response->data['approved'] && $response->data['url'] === '', 'Pending comment exposed as published' );
	expect( $GLOBALS['test_actions']['set_comment_cookies'][2] === false, 'Cookies set without consent' );
	$_POST['wp-comment-cookies-consent'] = 'yes'; $GLOBALS['test_comment']->comment_approved = '1';
	$response = response_from( 'handle_ajax_comment' );
	expect( $response->data['approved'] && strpos( $response->data['url'], '#comment-5' ) !== false, 'Approved reply link missing' );
	expect( $GLOBALS['test_actions']['set_comment_cookies'][2] === true, 'Consent ignored' );
} );
echo $GLOBALS['test_passed'] . " passed, " . $GLOBALS['test_failed'] . " failed.\n";
exit( $GLOBALS['test_failed'] ? 1 : 0 );
