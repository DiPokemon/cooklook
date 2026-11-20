<?php
/** Collection selection, homepage rendering and route lifecycle without a site database. */
require __DIR__ . '/bootstrap.php';
require dirname( __DIR__ ) . '/inc/recipe-helpers.php';
require dirname( __DIR__ ) . '/inc/recipe-collections.php';

class WP_Term {
	public $term_id; public $taxonomy; public $name; public $slug;
	function __construct( $id, $taxonomy = 'recipe_collection' ) {
		$this->term_id = $id; $this->taxonomy = $taxonomy;
		$this->name = 'Рождественская выпечка'; $this->slug = 'christmas-baking';
	}
}
function get_term( $id, $taxonomy ) {
	return $GLOBALS['collection_terms'][ $id ] ?? new WP_Error( 'invalid_term', 'Unknown collection' );
}
function get_term_link( $term ) {
	return ! empty( $GLOBALS['collection_bad_url'] ) ? new WP_Error( 'invalid_term', 'No route' ) : home_url( '/collections/' . $term->slug . '/' );
}
function get_template_directory_uri() { return home_url( '/wp-content/themes/cooklook' ); }
function esc_attr__( $text ) { return esc_attr( $text ); }
function taxonomy_exists( $taxonomy ) { return ! empty( $GLOBALS['collection_registered'] ); }
function get_option( $key ) { return $GLOBALS['collection_options'][ $key ] ?? false; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['collection_options'][ $key ] = $value; }
function delete_option( $key ) { unset( $GLOBALS['collection_options'][ $key ] ); }
function flush_rewrite_rules( $hard = true ) { $GLOBALS['collection_flushes'][] = $hard; }

function collection_fixture( $count = 0 ) {
	$GLOBALS['collection_terms'] = array( 1 => new WP_Term( 1 ), 2 => new WP_Term( 2, 'recipe_category' ) );
	$GLOBALS['collection_bad_url'] = false;
	$GLOBALS['collection_registered'] = true;
	$GLOBALS['collection_options'] = array(); $GLOBALS['collection_flushes'] = array();
	$GLOBALS['test_meta'][100]['_home_collection'] = array( array( 'type' => 'term', 'subtype' => 'recipe_collection', 'id' => 1 ) );
	$GLOBALS['test_posts'] = array(); $GLOBALS['collection_membership'] = array();
	$GLOBALS['collection_queries'] = array(); $GLOBALS['test_rendered'] = array(); $GLOBALS['test_reset'] = false;
	for ( $id = 1; $id <= $count; $id++ ) { collection_add_recipe( $id ); }
	// Model the WordPress boundary against a mixed corpus, including visibility and term membership.
	$GLOBALS['test_query_result'] = function ( $query ) {
		$args = $query->query_vars;
		$posts = array_filter( $GLOBALS['test_posts'], function ( $post ) use ( $args ) {
			if ( $post->post_type !== $args['post_type'] || $post->post_status !== $args['post_status'] ) { return false; }
			if ( isset( $args['has_password'] ) && ( '' !== $post->post_password ) !== $args['has_password'] ) { return false; }
			foreach ( $args['tax_query'] ?? array() as $clause ) {
				if ( ! is_array( $clause ) ) { continue; }
				$member = $GLOBALS['collection_membership'][ $post->ID ][ $clause['taxonomy'] ] ?? array();
				if ( ! array_intersect( (array) $clause['terms'], $member ) ) { return false; }
			}
			return true;
		} );
		$posts = array_values( $posts );
		if ( 'ID' === ( $args['orderby'] ?? '' ) ) {
			usort( $posts, function ( $left, $right ) use ( $args ) { return ( 'DESC' === $args['order'] ? -1 : 1 ) * ( $left->ID <=> $right->ID ); } );
		}
		$posts = array_slice( $posts, 0, $args['posts_per_page'] ?? 10 );
		$GLOBALS['collection_queries'][] = array_column( $posts, 'ID' );
		return $posts;
	};
}
function collection_add_recipe( $id, $status = 'publish', $password = '', $term_id = 1, $post_type = 'recipe' ) {
	$GLOBALS['test_posts'][ $id ] = (object) array(
		'ID' => $id, 'post_type' => $post_type, 'post_status' => $status, 'post_password' => $password,
		'post_title' => 'Recipe ' . $id,
	);
	$GLOBALS['collection_membership'][ $id ] = array( 'recipe_collection' => array( $term_id ) );
}
function collection_home_html() {
	$args = array( 'settings_id' => 100 );
	ob_start();
	try { include dirname( __DIR__ ) . '/template-parts/home-collection.php'; return ob_get_contents(); }
	finally { ob_end_clean(); }
}

test_case( 'Invalid homepage selections never render cards or query the catalog', function () {
	collection_fixture( 15 );
	$invalid = array(
		array(), array( array( 'id' => 999 ) ), array( array( 'id' => 2 ) ),
		array( array( 'type' => 'post', 'subtype' => 'recipe_collection', 'id' => 1 ) ),
		array( array( 'type' => 'term', 'subtype' => 'recipe_category', 'id' => 1 ) ),
		array( array( 'id' => array( 1 ) ) ), array( array( 'id' => '-1' ) ), array( array( 'id' => '1.5' ) ),
	);
	foreach ( $invalid as $selection ) {
		$GLOBALS['test_meta'][100]['_home_collection'] = $selection;
		expect( null === cooklook_home_collection( 100 ), 'Malformed, foreign or deleted selection resolved as a collection' );
		expect( '' === collection_home_html(), 'Invalid selection displayed a homepage block' );
	}
	expect( ! $GLOBALS['collection_queries'] && ! $GLOBALS['test_rendered'], 'Invalid selection fell back to the full catalog' );
} );

test_case( 'Large collections render ten public recipe cards and link to the complete collection', function () {
	collection_fixture( 16 );
	collection_add_recipe( 1, 'draft' ); collection_add_recipe( 2, 'private' ); collection_add_recipe( 3, 'publish', 'secret' );
	collection_add_recipe( 4, 'publish', '', 9 ); collection_add_recipe( 5, 'publish', '', 1, 'page' );
	$before = serialize( $GLOBALS['collection_membership'] );
	$html = collection_home_html();
	expect( count( $GLOBALS['test_rendered'] ) === 10, 'Homepage did not cap recipe cards at ten' );
	expect( array_unique( $GLOBALS['test_rendered'] ) === array( 'template-parts/recipe-loop-item' ), 'Collection used unexpected card templates' );
	expect( $GLOBALS['collection_queries'][0] === range( 6, 15 ), 'Draft, private, protected, foreign or non-recipe content leaked into the preview' );
	expect( 2 === substr_count( $html, 'href="https://example.test/subsite/collections/christmas-baking/"' ), 'Desktop or mobile button lost the full collection URL' );
	expect( false !== strpos( $html, 'Рождественская выпечка' ) && false !== strpos( $html, 'Смотреть всю подборку' ), 'Default labels missing' );
	expect( $GLOBALS['test_reset'], 'Homepage rendering did not restore post data' );
	expect( $before === serialize( $GLOBALS['collection_membership'] ) && count( $GLOBALS['test_posts'] ) === 16, 'Homepage cap removed members from the complete collection' );
} );

test_case( 'Small collections show every available recipe and empty collections hide the block', function () {
	collection_fixture( 3 );
	expect( false !== strpos( collection_home_html(), 'class="seasonal_collection"' ), 'Small collection was hidden' );
	expect( 3 === count( $GLOBALS['test_rendered'] ), 'Small collection duplicated cards to reach ten' );
	collection_fixture();
	collection_add_recipe( 1, 'draft' ); collection_add_recipe( 2, 'publish', 'secret' );
	expect( '' === collection_home_html() && ! $GLOBALS['test_rendered'], 'Collection with no public recipes rendered an empty block' );
} );

test_case( 'New members preserve existing homepage links and custom labels remain escaped', function () {
	collection_fixture( 12 );
	$first = array_column( cooklook_home_collection_query( cooklook_home_collection( 100 ) )->posts, 'ID' );
	collection_add_recipe( 13 );
	$next = array_column( cooklook_home_collection_query( cooklook_home_collection( 100 ) )->posts, 'ID' );
	expect( $first === range( 1, 10 ) && $next === $first, 'Adding a newer member displaced existing homepage links' );
	$GLOBALS['test_meta'][100]['_home_collection_title'] = '<img src=x onerror=alert(1)>';
	$GLOBALS['test_meta'][100]['_home_collection_button'] = '<script>bad()</script>';
	$html = collection_home_html();
	expect( false !== strpos( $html, '&lt;img' ) && false !== strpos( $html, '&lt;script&gt;' ), 'Custom labels disappeared or were not escaped' );
	expect( false === strpos( $html, '<script>' ) && false === strpos( $html, '<img src=x' ), 'Custom labels became executable HTML' );
	$GLOBALS['collection_bad_url'] = true; $GLOBALS['test_rendered'] = array();
	expect( '' === collection_home_html() && ! $GLOBALS['test_rendered'], 'Invalid collection URL rendered broken buttons' );
} );

test_case( 'Collection routes refresh softly once after registration and once after theme reactivation', function () {
	collection_fixture();
	$GLOBALS['collection_registered'] = false;
	cooklook_refresh_collection_rewrite_rules();
	expect( ! $GLOBALS['collection_flushes'] && ! $GLOBALS['collection_options'], 'Routes refreshed before taxonomy registration' );
	$GLOBALS['collection_registered'] = true;
	cooklook_refresh_collection_rewrite_rules(); cooklook_refresh_collection_rewrite_rules();
	expect( array( false ) === $GLOBALS['collection_flushes'], 'Rewrite refresh repeated or requested server-file modification' );
	cooklook_reset_collection_rewrite_version();
	cooklook_refresh_collection_rewrite_rules(); cooklook_refresh_collection_rewrite_rules();
	expect( array( false, false ) === $GLOBALS['collection_flushes'], 'Reactivation did not refresh exactly once' );
} );

echo "\n{$GLOBALS['test_passed']} passed, {$GLOBALS['test_failed']} failed\n";
exit( $GLOBALS['test_failed'] ? 1 : 0 );
