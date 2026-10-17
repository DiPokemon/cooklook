<?php
/** Ingredient request/HTML boundaries without loading WordPress or writing data. */
require __DIR__ . '/bootstrap.php';
require dirname( __DIR__ ) . '/inc/recipe-helpers.php';
require dirname( __DIR__ ) . '/inc/recipe-query.php';
require dirname( __DIR__ ) . '/inc/recipe-ingredients.php';
function wp_specialchars_decode( $value, $flags ) { return htmlspecialchars_decode( $value, $flags ); }
function esc_attr__( $text ) { return esc_attr( $text ); }
function get_terms( $args ) {
	$GLOBALS['ingredient_queries'][] = $args;
	return $GLOBALS['ingredient_result'];
}
function ingredient_fixture( $count = 0 ) {
	$GLOBALS['ingredient_queries'] = array(); $GLOBALS['ingredient_result'] = array();
	for ( $i = 0; $i < $count; $i++ ) {
		$GLOBALS['ingredient_result'][] = (object) array( 'slug' => 'ingredient-' . $i, 'name' => 'Ingredient ' . $i );
	}
}

test_case( 'Empty and single-character searches never query the directory', function () {
	ingredient_fixture();
	foreach ( array( '', ' ', 'я', ' a ', '<b>я</b>' ) as $term ) {
		$result = cooklook_search_ingredients( array( 'term' => $term ) );
		expect( array() === $result['results'] && false === $result['pagination']['more'], 'Short search returned results' );
	}
	expect( ! $GLOBALS['ingredient_queries'], 'Short search read all terms' );
} );
test_case( 'Malformed searches and unbounded pages are rejected before SQL', function () {
	ingredient_fixture();
	foreach ( array( array( 'term' => array( 'milk' ) ), array( 'term' => str_repeat( 'я', 101 ) ), array( 'term' => "\xff\xfe" ),
		array( 'page' => array( 1 ) ), array( 'page' => '0' ), array( 'page' => '-1' ), array( 'page' => '1.5' ), array( 'page' => '101' ), array( 'page' => '999999999999999' ) ) as $input ) {
		expect( is_wp_error( cooklook_search_ingredients( $input + array( 'term' => 'milk' ) ) ), 'Invalid search accepted' );
	}
	expect( ! $GLOBALS['ingredient_queries'], 'Invalid request reached SQL' );
} );
test_case( 'Search returns 20 safe text choices and a next-page flag without a count', function () {
	ingredient_fixture( 21 );
	$GLOBALS['ingredient_result'][0]->name = 'Oil &amp; &lt;img src=x onerror=alert(1)&gt;';
	$result = cooklook_search_ingredients( array( 'term' => '  <b>Молоко</b>  ', 'page' => '2', 'number' => 999999 ) );
	$query = $GLOBALS['ingredient_queries'][0];
	expect( 20 === count( $result['results'] ) && $result['pagination']['more'], 'Page boundary incorrect' );
	expect( 'Oil & <img src=x onerror=alert(1)>' === $result['results'][0]['text'], 'Name should be text for Select2 escaping' );
	expect( 'Молоко' === $query['search'] && 21 === $query['number'] && 20 === $query['offset'], 'Search or fixed limits changed' );
	expect( $query['hide_empty'] && false === $query['update_term_meta_cache'] && count( $GLOBALS['ingredient_queries'] ) === 1, 'Extra lookup or empty-term lookup' );
	expect( ! cooklook_search_ingredients( array( 'term' => 'milk', 'page' => '100' ) )['pagination']['more'], 'Pagination exceeds offset bound' );
	ingredient_fixture( 20 );
	expect( ! cooklook_search_ingredients( array( 'term' => 'milk' ) )['pagination']['more'], 'Exact final page advertised more' );
} );
test_case( 'Initial HTML fetches only selected terms with a fixed upper bound', function () {
	ingredient_fixture();
	expect( array() === cooklook_selected_ingredients( cooklook_recipe_filters( array() ) ) && ! $GLOBALS['ingredient_queries'], 'Blank form loaded the whole directory' );
	$filters = array( 'include_ingredients' => range( 1, 80 ), 'exclude_ingredients' => range( 81, 160 ) );
	cooklook_selected_ingredients( $filters );
	$query = $GLOBALS['ingredient_queries'][0];
	expect( 40 === count( $query['slug'] ) && 40 === $query['number'], 'Selected lookup not bounded' );
	expect( false === $query['hide_empty'], 'Saved links with existing empty terms cannot be restored' );
} );
test_case( 'Selected HTML restores each field and escapes untrusted term text and slugs', function () {
	ingredient_fixture( 2 );
	$GLOBALS['ingredient_result'][0]->name = '<img src=x onerror=alert(1)>';
	$GLOBALS['ingredient_result'][1]->slug = 'unused" onclick="alert(1)';
	$args = array( 'filters' => cooklook_recipe_filters( array( 'include_ingredients' => array( 'ingredient-0' ) ) ) );
	ob_start(); include dirname( __DIR__ ) . '/template-parts/filter-ingridients.php'; $html = ob_get_clean();
	expect( 1 === substr_count( $html, '<option ' ), 'Unselected terms leaked into the form' );
	expect( false === strpos( $html, '<img' ) && false === strpos( $html, '" onclick="' ), 'Term text became executable HTML' );
	expect( false !== strpos( $html, 'value="ingredient-0" selected' ), 'Saved selection missing' );
} );
test_case( 'Expired or forged nonce stops ingredient requests before SQL', function () {
	ingredient_fixture(); $_GET = array( 'term' => 'milk' ); $_REQUEST['nonce'] = 'bad';
	$result = response_from( 'cooklook_search_ingredients_ajax' );
	expect( 403 === $result->status && ! $GLOBALS['ingredient_queries'], 'Invalid nonce reached directory' );
} );
test_case( 'Endpoint returns errors without exposing database details and accepts valid guest searches', function () {
	ingredient_fixture( 1 ); $GLOBALS['test_user_id'] = 0; $_GET = array( 'term' => 'milk' );
	$result = response_from( 'cooklook_search_ingredients_ajax' );
	expect( $result->success && 1 === count( $result->data['results'] ), 'Public lookup unavailable' );
	$_GET['ingredient_page'] = array( 1 );
	expect( 400 === response_from( 'cooklook_search_ingredients_ajax' )->status, 'Bad pagination was not rejected' );
	unset( $_GET['ingredient_page'] ); $GLOBALS['ingredient_result'] = new WP_Error( 'db_error', 'private SQL details' );
	$result = response_from( 'cooklook_search_ingredients_ajax' );
	expect( 500 === $result->status && false === strpos( $result->data, 'private' ), 'Database error exposed or ignored' );
} );
echo "\n{$GLOBALS['test_passed']} passed, {$GLOBALS['test_failed']} failed\n";
exit( $GLOBALS['test_failed'] ? 1 : 0 );
