<?php
/** Optional local integration check: real core/plugins, read-only database, in-memory content fixture. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
define( 'DISABLE_WP_CRON', true );
$root = getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 );
require $root . '/wp-load.php';
function p2_check( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	echo 'PASS: ' . $message . "\n";
}
$query = new WP_Query( array( 'post_type' => 'recipe', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => 1, 'meta_key' => '_thumbnail_id' ) );
p2_check( $query->have_posts(), 'An existing published recipe with an attachment is available' );
$GLOBALS['wp_query'] = $query;
$query->the_post();
$id = get_the_ID();
$original_content = get_post_field( 'post_content', $id );
// Work only on an object clone, never insert/update a post or shared object cache.
$fixture = clone $GLOBALS['post'];
$fixture->post_content = '<!-- wp:paragraph --><p>P2-CONTENT <a href="/recipes/">Useful link</a></p><!-- /wp:paragraph -->[cooklook_p2_fixture]';
$query->post = $fixture;
$GLOBALS['post'] = $fixture;
setup_postdata( $fixture );
add_shortcode( 'cooklook_p2_fixture', function () { return '<span>P2-SHORTCODE</span>'; } );
ob_start(); get_template_part( 'template-parts/content-recipe' ); $html = ob_get_clean();
p2_check( substr_count( $html, 'P2-CONTENT' ) === 1 && strpos( $html, 'href="/recipes/"' ) !== false, 'Full block content and its links render exactly once' );
p2_check( strpos( $html, '<span>P2-SHORTCODE</span>' ) !== false, 'Core the_content processing renders shortcodes' );
p2_check( get_post_field( 'post_content', $id ) === $original_content, 'Stored content is unchanged' );
$dom = new DOMDocument(); @$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
$xpath = new DOMXPath( $dom );
$hero = $xpath->query( '//div[contains(@class,"recipe_img_wrapper")]/img' )->item( 0 );
p2_check( $hero && $hero->getAttribute( 'width' ) && $hero->getAttribute( 'height' ) && $hero->getAttribute( 'srcset' ) && $hero->getAttribute( 'sizes' ), 'Hero has intrinsic dimensions and responsive sources' );
p2_check( 'eager' === $hero->getAttribute( 'loading' ) && 'high' === $hero->getAttribute( 'fetchpriority' ), 'Hero loading priority is explicit' );
foreach ( array( 'recipe_category', 'recipe_tags' ) as $taxonomy ) {
	$terms = cooklook_recipe_terms( $id, $taxonomy );
	$links = $xpath->query( '//div[@data-taxonomy="' . $taxonomy . '"]//a' );
	p2_check( count( $terms ) === $links->length, 'Separate taxonomy links: ' . $taxonomy );
}
$GLOBALS['wp_rewrite']->permalink_structure = '';
$category = get_terms( array( 'taxonomy' => 'recipe_category', 'hide_empty' => true, 'number' => 1 ) )[0];
$tax_query = new WP_Query( array( 'recipe_category' => $category->slug, 'posts_per_page' => 1 ) );
$GLOBALS['wp_query'] = $tax_query;
$request = array( 'recipe_category' => $category->slug, 'paged' => 1 );
$filters = cooklook_recipe_filters( $request );
p2_check( ! $filters['category_id'] && true === cooklook_validate_recipe_filters( $request, $filters, cooklook_recipe_context() ), 'Plain taxonomy route is not mistaken for a numeric facet' );
echo "Integration checks complete.\n";
