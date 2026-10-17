<?php
/** Directory freshness and cache failure/race cases, with no database or filesystem writes. */
require __DIR__ . '/bootstrap.php';
require dirname( __DIR__ ) . '/inc/recipe-regions.php';
define( 'HOUR_IN_SECONDS', 3600 );
function get_option( $name, $default = false ) { return $GLOBALS['region_options'][ $GLOBALS['region_site'] ][ $name ] ?? $default; }
function update_option( $name, $value, $autoload = null ) { $GLOBALS['region_options'][ $GLOBALS['region_site'] ][ $name ] = $value; return true; }
function wp_generate_uuid4() { return 'generation-' . ++$GLOBALS['region_generation']; }
function get_transient( $key ) {
	$entry = $GLOBALS['region_cache'][ $GLOBALS['region_site'] ][ $key ] ?? null;
	return $entry && $entry['expires'] > $GLOBALS['region_now'] ? $entry['value'] : false;
}
function set_transient( $key, $value, $ttl ) {
	if ( $GLOBALS['region_cache_failure'] ) { return false; }
	$GLOBALS['region_cache'][ $GLOBALS['region_site'] ][ $key ] = array( 'value' => $value, 'expires' => $GLOBALS['region_now'] + $ttl );
	return true;
}
function delete_transient( $key ) { unset( $GLOBALS['region_cache'][ $GLOBALS['region_site'] ][ $key ] ); return true; }
class Region_Test_DB {
	public $postmeta = 'wp_postmeta'; public $posts = 'wp_posts'; public $last_error = '';
	public $rows = array(); public $reads = 0; public $during_read;
	function get_col( $sql ) {
		++$this->reads;
		$rows = $this->rows;
		if ( $this->during_read ) { $callback = $this->during_read; $this->during_read = null; $callback(); }
		return $rows;
	}
}
function region_fixture( $rows = array( 'Русская кухня', 'Японская кухня' ) ) {
	$GLOBALS['region_options'] = array(); $GLOBALS['region_cache'] = array(); $GLOBALS['region_site'] = 1;
	$GLOBALS['region_generation'] = 0; $GLOBALS['region_now'] = 100; $GLOBALS['region_cache_failure'] = false;
	$GLOBALS['wpdb'] = new Region_Test_DB(); $GLOBALS['wpdb']->rows = $rows;
}

test_case( 'Directory preserves legacy values/order and serves repeated reads without SQL', function () {
	$rows = array( null, '', '0', 'Авторская кухня', 'Русская кухня', 'Японская кухня' );
	region_fixture( $rows );
	for ( $pass = 0; $pass < 3; $pass++ ) { expect( cooklook_recipe_regions() === $rows, 'Values or order changed' ); }
	expect( 1 === $GLOBALS['wpdb']->reads, 'Warm directory queried metadata again' );
} );
test_case( 'Dropdown preserves accepted aliases and uncached selections without changing the shared directory', function () {
	$rows = array( null, '', '0', 'Русская кухня', 'Японская кухня' );
	region_fixture( $rows );
	expect( cooklook_recipe_region_choices() === array( '0', 'Русская кухня', 'Японская кухня' ), 'Empty choices or a valid zero value were lost' );
	foreach ( array( 'русская кухня', 'Новая кухня' ) as $selected ) {
		$choices = cooklook_recipe_region_choices( $selected );
		expect( in_array( $selected, $choices, true ), 'Accepted value cannot be selected after reload' );
		expect( cooklook_recipe_regions() === $rows, 'A visitor selection polluted the cached directory' );
	}
	expect( count( cooklook_recipe_region_choices( 'Русская кухня' ) ) === 3, 'Existing selection duplicated' );
	expect( 1 === $GLOBALS['wpdb']->reads, 'Restoring selections queried metadata again' );
} );
test_case( 'Empty directories are cached and expiration rebuilds from current data', function () {
	region_fixture( array() );
	expect( array() === cooklook_recipe_regions() && array() === cooklook_recipe_regions(), 'Empty result changed' );
	expect( 1 === $GLOBALS['wpdb']->reads, 'Empty cache treated as a miss' );
	$GLOBALS['wpdb']->rows = array( 'Новая кухня' );
	$GLOBALS['region_now'] += HOUR_IN_SECONDS;
	expect( array( 'Новая кухня' ) === cooklook_recipe_regions() && 2 === $GLOBALS['wpdb']->reads, 'Expired directory not refreshed' );
} );
test_case( 'SQL errors do not poison the cache and unavailable cache storage still serves data', function () {
	region_fixture( array() );
	$GLOBALS['wpdb']->last_error = 'Test database failure';
	expect( array() === cooklook_recipe_regions() && ! $GLOBALS['region_cache'], 'Failed query was cached' );
	$GLOBALS['wpdb']->last_error = ''; $GLOBALS['wpdb']->rows = array( 'Русская кухня' );
	$GLOBALS['region_cache_failure'] = true;
	for ( $i = 0; $i < 2; $i++ ) { expect( array( 'Русская кухня' ) === cooklook_recipe_regions(), 'Cache failure hid recipes' ); }
	expect( 3 === $GLOBALS['wpdb']->reads && ! $GLOBALS['region_cache'], 'Failed cache write was treated as success' );
} );
test_case( 'Region metadata changes and bulk deletion refresh the list; views/ratings do not', function () {
	region_fixture(); cooklook_recipe_regions();
	$key = cooklook_recipe_regions_cache_key();
	foreach ( array( '_recipe_views', '_recipe_likes', '_recipe_time', '_recipe_step|0|description' ) as $meta_key ) {
		cooklook_recipe_regions_meta_changed( 1, 42, $meta_key );
	}
	expect( $key === cooklook_recipe_regions_cache_key() && is_array( get_transient( $key ) ), 'Unrelated metadata flushed directory' );
	foreach ( array( array( 1, 42, '_recipe_region' ), array( 2, 42, '_RECIPE_REGION' ), array( array( 1, 2 ), 0, '_recipe_region' ), array( array( 1, 2 ), 42, '_recipe_region' ) ) as $event ) {
		$key = cooklook_recipe_regions_cache_key();
		call_user_func_array( 'cooklook_recipe_regions_meta_changed', $event );
		$GLOBALS['wpdb']->rows = array( 'Регион ' . $GLOBALS['region_generation'] );
		expect( $key !== cooklook_recipe_regions_cache_key() && false === get_transient( $key ), 'Metadata mutation did not invalidate' );
		expect( $GLOBALS['wpdb']->rows === cooklook_recipe_regions(), 'Directory did not rebuild after mutation' );
	}
} );
test_case( 'Publication, trash/restore, password and post type changes invalidate in both directions', function () {
	region_fixture();
	$public = clone $GLOBALS['test_posts'][42];
	$hidden = array( null );
	foreach ( array( 'draft', 'future', 'private', 'trash' ) as $status ) { $post = clone $public; $post->post_status = $status; $hidden[] = $post; }
	$post = clone $public; $post->post_password = 'secret'; $hidden[] = $post;
	$post = clone $public; $post->post_type = 'post'; $hidden[] = $post;
	foreach ( $hidden as $before ) {
		foreach ( array( array( $before, $public ), array( $public, $before ) ) as $pair ) {
			cooklook_recipe_regions(); $key = cooklook_recipe_regions_cache_key();
			cooklook_recipe_regions_post_saved( 42, $pair[1], null !== $pair[0], $pair[0] );
			expect( $key !== cooklook_recipe_regions_cache_key() && false === get_transient( $key ), 'Visibility change did not invalidate' );
		}
	}
	$key = cooklook_recipe_regions_cache_key();
	cooklook_recipe_regions_post_saved( 42, clone $public, true, $public );
	cooklook_recipe_regions_post_saved( 42, $hidden[1], true, $hidden[2] );
	expect( $key === cooklook_recipe_regions_cache_key(), 'Unchanged membership flushed directory' );
} );
test_case( 'Permanent deletion invalidates a public recipe even when its post object is no longer cached', function () {
	region_fixture(); cooklook_recipe_regions(); $key = cooklook_recipe_regions_cache_key();
	$post = clone $GLOBALS['test_posts'][42]; unset( $GLOBALS['test_posts'][42] );
	cooklook_recipe_regions_post_deleted( 42, $post );
	expect( $key !== cooklook_recipe_regions_cache_key() && false === get_transient( $key ), 'Deleted recipe left a stale directory' );
	$key = cooklook_recipe_regions_cache_key(); $post->post_status = 'trash';
	cooklook_recipe_regions_post_deleted( 42, $post );
	expect( $key === cooklook_recipe_regions_cache_key(), 'Already hidden recipe flushed directory' );
} );
test_case( 'An in-flight old builder cannot make its stale snapshot current after invalidation', function () {
	region_fixture( array( 'Старый регион' ) );
	$GLOBALS['wpdb']->during_read = function () { cooklook_invalidate_recipe_regions(); $GLOBALS['wpdb']->rows = array( 'Новый регион' ); };
	expect( array( 'Старый регион' ) === cooklook_recipe_regions(), 'Unexpected in-flight snapshot' );
	expect( array( 'Новый регион' ) === cooklook_recipe_regions(), 'Stale concurrent fill became current' );
	expect( array( 'Новый регион' ) === cooklook_recipe_regions() && 2 === $GLOBALS['wpdb']->reads, 'Fresh fill not reusable' );
} );
test_case( 'Site-scoped options/transients remain isolated when switching sites in one request', function () {
	region_fixture( array( 'Первый сайт' ) ); cooklook_recipe_regions();
	$GLOBALS['region_site'] = 2; $GLOBALS['wpdb']->rows = array( 'Второй сайт' );
	expect( array( 'Второй сайт' ) === cooklook_recipe_regions(), 'Another site reused the directory' );
	cooklook_invalidate_recipe_regions();
	$GLOBALS['region_site'] = 1;
	expect( array( 'Первый сайт' ) === cooklook_recipe_regions() && 2 === $GLOBALS['wpdb']->reads, 'Other site invalidated this directory' );
} );
echo $GLOBALS['test_passed'] . ' passed, ' . $GLOBALS['test_failed'] . " failed.\n";
exit( $GLOBALS['test_failed'] ? 1 : 0 );
