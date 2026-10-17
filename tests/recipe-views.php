<?php
/** View counter boundaries with in-memory data only. */
require __DIR__ . '/bootstrap.php';
require dirname( __DIR__ ) . '/inc/recipe-helpers.php';
require dirname( __DIR__ ) . '/inc/recipe-views.php';
function nocache_headers() { $GLOBALS['view_nocache'] = true; }
function wp_create_nonce( $action ) { return $action . '-valid'; }
function wp_verify_nonce( $nonce, $action ) { return $nonce === wp_create_nonce( $action ); }
function is_singular( $type ) { return ( $GLOBALS['test_conditionals']['singular'] ?? '' ) === $type; }
function is_preview() { return ! empty( $GLOBALS['test_conditionals']['preview'] ); }
function is_feed() { return ! empty( $GLOBALS['test_conditionals']['feed'] ); }
function is_embed() { return ! empty( $GLOBALS['test_conditionals']['embed'] ); }
function get_queried_object() { return get_post( 42 ); }
function admin_url( $path ) { return 'https://example.test/subsite/wp-admin/' . $path; }
function cooklook_enqueue_script( $handle, $path, $deps ) { $GLOBALS['view_script'] = array( $handle, $path, $deps ); }
function wp_localize_script( $handle, $name, $data ) { $GLOBALS['view_config'] = $data; }
function view_request( $data = array(), $server = array() ) {
	$_POST = array_merge( array( 'recipe_id' => '42', 'nonce' => wp_create_nonce( 'cooklook_recipe_view_42' ) ), $data );
	$_SERVER = array_merge( array( 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'https://example.test', 'HTTP_USER_AGENT' => 'Mozilla/5.0 Browser' ), $server );
	$GLOBALS['view_nocache'] = false;
	return response_from( 'cooklook_record_recipe_view' );
}
test_case( 'Views count public recipes for guests and signed-in readers without changing other metadata', function () {
	$before = $GLOBALS['test_meta'][42];
	foreach ( array( 0, 7 ) as $user ) {
		$GLOBALS['test_user_id'] = $user;
		$response = view_request();
		expect( $response->success && $response->data['counted'] && $GLOBALS['view_nocache'], 'Valid view failed or response cacheable' );
	}
	expect( 2 === $GLOBALS['test_meta'][42]['_recipe_views'], 'Views did not increment once per request' );
	unset( $GLOBALS['test_meta'][42]['_recipe_views'] );
	expect( $before === $GLOBALS['test_meta'][42] && ! $GLOBALS['wpdb']->locks, 'Unrelated data changed or lock leaked' );
} );
test_case( 'GET, HEAD and foreign/malformed origins cannot mutate the counter', function () {
	$before = $GLOBALS['test_meta'];
	foreach ( array( 'GET', 'HEAD' ) as $method ) { expect( view_request( array(), array( 'REQUEST_METHOD' => $method ) )->status === 405, 'Non-POST accepted' ); }
	foreach ( array( '', 'null', 'https://evil.test', 'https://example.test.evil.test', 'http://example.test', 'https://example.test:444', 'https://user@example.test', 'https://example.test/path', 'https://example.test#fragment' ) as $origin ) {
		expect( view_request( array(), array( 'HTTP_ORIGIN' => $origin ) )->status === 403, 'Bad origin accepted: ' . $origin );
	}
	expect( $before === $GLOBALS['test_meta'], 'Invalid request wrote data' );
	expect( cooklook_recipe_view_same_origin( 'HTTPS://EXAMPLE.TEST:443' ), 'Equivalent origin rejected' );
} );
test_case( 'Expired, malformed and other-recipe nonces refresh without counting', function () {
	$before = $GLOBALS['test_meta'];
	foreach ( array( '', 'expired', array( 'bad' ), wp_create_nonce( 'cooklook_recipe_view_43' ) ) as $nonce ) {
		$response = view_request( array( 'nonce' => $nonce ) );
		expect( $response->status === 403 && $response->data['code'] === 'invalid_nonce' && $response->data['nonce'] === wp_create_nonce( 'cooklook_recipe_view_42' ), 'Invalid nonce not refreshed safely' );
		expect( $before === $GLOBALS['test_meta'], 'Nonce refresh counted a view' );
	}
	expect( view_request( array( 'nonce' => $response->data['nonce'] ) )->success && get_post_meta( 42, '_recipe_views' ) === 1, 'Refreshed nonce did not count once' );
} );
test_case( 'Drafts, private/password recipes, other post types and invalid IDs cannot be counted', function () {
	$before = $GLOBALS['test_meta'];
	foreach ( array( 'draft', 'private', 'trash' ) as $status ) {
		$GLOBALS['test_posts'][42]->post_status = $status;
		expect( view_request()->status === 404, 'Non-public recipe counted' );
	}
	$GLOBALS['test_posts'][42]->post_status = 'publish';
	$GLOBALS['test_posts'][42]->post_password = 'secret';
	expect( view_request()->status === 404, 'Password recipe counted' );
	$GLOBALS['test_posts'][42]->post_password = '';
	$GLOBALS['test_posts'][42]->post_type = 'page';
	expect( view_request()->status === 404, 'Other post type counted' );
	$GLOBALS['test_posts'][42]->post_type = 'recipe';
	foreach ( array( 0, -42, '42 OR 1=1', array( 42 ), 999 ) as $id ) { expect( view_request( array( 'recipe_id' => $id ) )->status === 404, 'Invalid ID accepted' ); }
	expect( $before === $GLOBALS['test_meta'], 'Unavailable recipe wrote data' );
} );
test_case( 'Known robots and empty User-Agent are ignored without locks or writes', function () {
	$before = $GLOBALS['test_meta'];
	$GLOBALS['wpdb']->busy = true; // A bot must return before trying to acquire a lock.
	foreach ( array( '', 'Googlebot/2.1', 'YandexBot', 'Google-InspectionTool/1.0', 'bingpreview', 'GPTBot', 'OAI-SearchBot', 'Claude-User', 'Perplexity-User', 'ChatGPT-User', 'HeadlessChrome', 'facebookexternalhit/1.1' ) as $agent ) {
		$response = view_request( array(), array( 'HTTP_USER_AGENT' => $agent ) );
		expect( $response->success && false === $response->data['counted'], 'Robot counted: ' . $agent );
	}
	expect( $before === $GLOBALS['test_meta'] && ! $GLOBALS['wpdb']->locks, 'Bot wrote data or acquired a lock' );
} );
test_case( 'Busy locks, failed writes and integer overflow do not claim success or leak locks', function () {
	$GLOBALS['test_meta'][42]['_recipe_views'] = 20;
	$GLOBALS['wpdb']->busy = true;
	expect( view_request()->status === 409 && get_post_meta( 42, '_recipe_views' ) === 20, 'Busy lock changed count' );
	$GLOBALS['wpdb']->busy = false;
	$GLOBALS['test_fail_meta'] = '_recipe_views';
	expect( view_request()->status === 500 && get_post_meta( 42, '_recipe_views' ) === 20 && ! $GLOBALS['wpdb']->locks, 'Failed write accepted or lock leaked' );
	$GLOBALS['test_meta'][42]['_recipe_views'] = PHP_INT_MAX;
	expect( view_request()->status === 500 && get_post_meta( 42, '_recipe_views' ) === PHP_INT_MAX, 'Counter overflowed' );
	$GLOBALS['test_meta'][42]['_recipe_views'] = -1;
	expect( view_request()->success && get_post_meta( 42, '_recipe_views' ) === 1, 'Negative legacy counter not normalized' );
} );
test_case( 'Only normal public recipe pages enqueue the view script, without writes', function () {
	$before = $GLOBALS['test_meta'];
	foreach ( array( 'none', 'preview', 'feed', 'embed', 'draft', 'password', 'valid' ) as $case ) {
		$GLOBALS['view_script'] = null;
		$GLOBALS['test_conditionals'] = array( 'singular' => $case === 'none' ? '' : 'recipe', $case => true );
		$GLOBALS['test_posts'][42]->post_status = $case === 'draft' ? 'draft' : 'publish';
		$GLOBALS['test_posts'][42]->post_password = $case === 'password' ? 'secret' : '';
		cooklook_enqueue_recipe_views();
		expect( (bool) $GLOBALS['view_script'] === ( 'valid' === $case ), 'Wrong script scope: ' . $case );
	}
	expect( $GLOBALS['view_config']['recipe_id'] === 42 && $GLOBALS['view_config']['nonce'] === wp_create_nonce( 'cooklook_recipe_view_42' ), 'Wrong recipe configuration' );
	expect( $before === $GLOBALS['test_meta'], 'Rendering script configuration changed views' );
} );
echo $GLOBALS['test_passed'] . ' passed, ' . $GLOBALS['test_failed'] . " failed.\n";
exit( $GLOBALS['test_failed'] ? 1 : 0 );
