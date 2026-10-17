<?php
/** Verify actual WordPress URL behavior without bootstrapping the site or database. */
if ( PHP_SAPI !== 'cli' ) { exit; }
$core = getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 );
if ( ! is_file( $core . '/wp-includes/functions.php' ) ) {
	fwrite( STDERR, "Set COOKLOOK_WP_ROOT to a WordPress checkout. No wp-config.php is loaded.\n" );
	exit( 1 );
}
define( 'ABSPATH', rtrim( $core, '/\\' ) . '/' );
define( 'WPINC', 'wp-includes' );
require ABSPATH . WPINC . '/plugin.php';
require ABSPATH . WPINC . '/functions.php';
require ABSPATH . WPINC . '/formatting.php';
require ABSPATH . WPINC . '/compat.php';
// WordPress 6.9+ moved UTF-8 helpers out of formatting.php.
foreach ( array( 'compat-utf8.php', 'utf8.php' ) as $file ) {
	if ( is_file( ABSPATH . WPINC . '/' . $file ) ) {
		require ABSPATH . WPINC . '/' . $file;
	}
}
require ABSPATH . WPINC . '/kses.php';
require ABSPATH . WPINC . '/class-wp-error.php';
require ABSPATH . WPINC . '/general-template.php';
add_filter( 'pre_option', function ( $pre, $name ) {
	if ( 'blog_charset' === $name ) { return 'UTF-8'; }
	throw new RuntimeException( 'Unexpected option access: ' . $name );
}, 10, 2 );
function __( $text ) { return $text; }
function _x( $text ) { return $text; }
function absint( $value ) { return abs( (int) $value ); }
function user_trailingslashit( $url ) { return $GLOBALS['wp_rewrite']->use_trailing_slashes ? rtrim( $url, '/' ) . '/' : rtrim( $url, '/' ); }
function get_query_var( $name ) { return $GLOBALS['wp_query']->get( $name ); }
function get_pagenum_link( $page = 1, $escape = true ) {
	return apply_filters( 'get_pagenum_link', 'https://example.test/subsite/wp-admin/admin-ajax.php?action=filter_recipes&nonce=SECRET&paged=2&current_tag=milk', $page );
}
$GLOBALS['wp_locale'] = (object) array( 'number_format' => array( 'decimal_point' => '.', 'thousands_sep' => ',' ) );
$GLOBALS['wp_rewrite'] = new class {
	public $pagination_base = 'page';
	public $pretty = false;
	public $use_trailing_slashes = true;
	function using_permalinks() { return $this->pretty; }
	function using_index_permalinks() { return false; }
};
$GLOBALS['wp_query'] = new class {
	public $max_num_pages = 8;
	function is_tax() { return false; }
	function get( $name ) { return 'paged' === $name ? 2 : ''; }
};
require dirname( __DIR__ ) . '/inc/recipe-query.php';
require dirname( __DIR__ ) . '/inc/recipe-helpers.php';
add_filter( 'sanitize_title', 'sanitize_title_with_dashes', 10, 3 );
$slug = strtolower( rawurlencode( 'молоко' ) );
$tag_url = 'https://example.test/subsite/recipe_tags/' . $slug . '/?region=' . rawurlencode( 'Русская кухня' );
$request = array( 'current_tag' => $slug, 'page_url' => $tag_url );
$context = array( 'current_tag' => cooklook_request_slug( $request, 'current_tag' ) );
$args = cooklook_recipe_query_args( cooklook_recipe_filters( array() ), $context );
if ( $args['tax_query'][0]['terms'] !== $slug || cooklook_request_url( $request, 'page_url' ) !== $tag_url ) {
	throw new RuntimeException( 'Cyrillic archive context or URL was stripped by input sanitization' );
}
if ( cooklook_request_slug( array( 'tag' => array( $slug ) ), 'tag' ) !== '' || cooklook_request_url( array( 'url' => 'javascript:alert(1)' ), 'url' ) !== '' ) {
	throw new RuntimeException( 'Malformed slug or unsafe URL accepted' );
}
$before = get_pagenum_link();
$html = cooklook_recipe_pagination( $GLOBALS['wp_query'], 'https://example.test/subsite/recipes/?region=%D0%A0%D1%83%D1%81%D1%81%D0%BA%D0%B0%D1%8F&include_ingredients%5B0%5D=milk' );
foreach ( array( 'admin-ajax', 'SECRET', 'action=', 'nonce=', 'current_tag=', '%25%23%25' ) as $forbidden ) {
	if ( strpos( $html, $forbidden ) !== false ) { throw new RuntimeException( 'Pagination leaked: ' . $forbidden ); }
}
if ( strpos( $html, 'paged=3' ) === false || strpos( $html, 'region=' ) === false || strpos( $html, 'include_ingredients' ) === false ) {
	throw new RuntimeException( 'Pagination lost page or filter state: ' . $html );
}
if ( get_pagenum_link() !== $before ) { throw new RuntimeException( 'Temporary pagination filter leaked' ); }
$tag_html = cooklook_recipe_pagination( $GLOBALS['wp_query'], cooklook_request_url( $request, 'page_url' ) );
if ( strpos( $tag_html, '/recipe_tags/' . $slug . '/' ) === false ) {
	throw new RuntimeException( 'Cyrillic archive pagination URL was corrupted' );
}
foreach ( array( false, true ) as $pretty ) {
	$GLOBALS['wp_rewrite']->pretty = $pretty;
	foreach ( array( false, true ) as $trailing ) {
		$GLOBALS['wp_rewrite']->use_trailing_slashes = $trailing;
		foreach ( array( 'https://example.test/subsite/recipes', 'https://example.test/subsite/index.php/recipe_tags/' . $slug ) as $base ) {
			$first = $base . ( $trailing ? '/' : '' ) . '?region=Russian&include_ingredients%5B0%5D=milk';
			$input = $pretty ? $base . '/page/2/' . '?region=Russian&include_ingredients%5B0%5D=milk' : $first . '&paged=2';
			$links = cooklook_recipe_pagination( $GLOBALS['wp_query'], $input );
			$dom = new DOMDocument(); @$dom->loadHTML( $links );
			$hrefs = array(); foreach ( $dom->getElementsByTagName( 'a' ) as $a ) { $hrefs[] = $a->getAttribute( 'href' ); }
			if ( ! in_array( $first, $hrefs, true ) ) { throw new RuntimeException( 'Page 1 is not the clean URL: ' . $links ); }
			$third = $pretty ? $base . '/page/3' . ( $trailing ? '/' : '' ) . '?region=Russian&include_ingredients%5B0%5D=milk' : $first . '&paged=3';
			if ( ! in_array( $third, $hrefs, true ) ) { throw new RuntimeException( 'Page 3 lost permalink settings: ' . $links ); }
			if ( strpos( $links, 'paged=1' ) !== false || strpos( $links, 'SECRET' ) !== false ) { throw new RuntimeException( 'Unwanted pagination parameters' ); }
		}
	}
}
echo "PASS: WordPress pagination preserves plain/pretty URLs, trailing slash settings, index.php, subdirectories, Cyrillic and filters.\n";
