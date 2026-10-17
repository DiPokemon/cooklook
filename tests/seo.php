<?php
/** P1 regression tests: no database, network, credentials or publication changes. */
require __DIR__ . '/bootstrap.php';
foreach ( array( 'inc/recipe-helpers.php', 'inc/recipe-query.php', 'inc/recipe-seo.php', 'inc/seo-metadata.php', 'inc/seo.php', 'inc/template-functions.php', 'functions-parts/breadcrumbs.php', 'functions-parts/recipe_rating.php', 'template-parts/schema.php', 'filters-handler.php' ) as $file ) {
	require dirname( __DIR__ ) . '/' . $file;
}
class WP_Term {
	public $term_id; public $name; public $taxonomy; public $parent; public $slug;
	function __construct( $id, $parent = 0 ) { $this->term_id = $id; $this->name = 'Категория ' . $id; $this->taxonomy = 'recipe_category'; $this->parent = $parent; $this->slug = 'category-' . $id; }
}
function is_singular( $type = null ) { $current = $GLOBALS['test_conditionals']['singular'] ?? ''; return $type ? $current === $type : (bool) $current; }
function is_post_type_archive() { return ! empty( $GLOBALS['test_conditionals']['archive'] ); }
function is_page_template( $name ) { return ( $GLOBALS['test_conditionals']['template'] ?? '' ) === $name; }
function is_tax( $taxonomies = null ) { $tax = $GLOBALS['test_conditionals']['tax'] ?? ''; return $tax && ( ! $taxonomies || in_array( $tax, (array) $taxonomies, true ) ); }
function is_search() { return ! empty( $GLOBALS['test_conditionals']['search'] ); }
function is_404() { return ! empty( $GLOBALS['test_conditionals']['404'] ); }
function is_front_page() { return ! empty( $GLOBALS['test_conditionals']['front'] ); }
function term_description() { return $GLOBALS['test_term_description'] ?? ''; }
function get_bloginfo() { return 'Cook Look'; }
function get_option( $name ) { return 'page_comments' === $name ? ( $GLOBALS['test_comment_pagination'] ?? true ) : false; }
function carbon_get_term_meta( $id, $key ) { return $GLOBALS['test_term_images'][ $id ][ $key ] ?? ''; }
function attachment_url_to_postid( $url ) { return $GLOBALS['test_image_urls'][ $url ] ?? 0; }
function wp_get_attachment_image( $id, $size, $icon, $attrs ) { $GLOBALS['test_rendered_image'] = array( $id, $size, $attrs ); return '<img data-attachment="' . $id . '">'; }
function is_home() { return false; }
function is_page() { return is_singular( 'page' ); }
function is_attachment() { return false; }
function is_category() { return false; }
function is_tag() { return false; }
function get_query_var( $key ) { return $GLOBALS['test_query_vars'][ $key ] ?? ''; }
function get_queried_object() { return $GLOBALS['test_object'] ?? get_post( 42 ); }
function get_queried_object_id() { return get_queried_object()->ID; }
function get_page_template_slug( $id ) { return get_post_meta( $id, '_wp_page_template' ); }
function get_post_ancestors() { return array(); }
function get_ancestors() { return array(); }
function get_term( $id ) { return array( 1 => new WP_Term( 1 ), 2 => new WP_Term( 2, 1 ), 3 => new WP_Term( 3 ) )[ $id ] ?? null; }
function term_is_ancestor_of( $parent, $child ) { return 1 === $parent && 2 === $child; }
function get_term_link( $term ) { return home_url( '/recipe_category/' . $term->slug . '/' ); }
function get_terms( $args ) { return array_values( array_intersect( $args['slug'], array( 'milk', 'egg', 'nuts', '%d0%bc%d0%be%d0%bb%d0%be%d0%ba%d0%be' ) ) ); }
function get_search_query() { return 'каша'; }
function get_post_type_archive_link() { return home_url( '/recipes/' ); }
function get_pagenum_link() { return $GLOBALS['test_page_link'] ?? home_url( '/recipes/' ); }
function remove_query_arg( $keys, $url ) { $parts = parse_url( $url ); parse_str( $parts['query'] ?? '', $query ); foreach ( (array) $keys as $key ) unset( $query[ $key ] ); return preg_replace( '/\?.*$/', '', $url ) . ( $query ? '?' . http_build_query( $query ) : '' ); }
$GLOBALS['wpdb'] = new class extends Test_DB {
	function get_var( $sql ) { if ( strpos( $sql, 'SELECT 1 FROM' ) === 0 ) return strpos( $sql, "pm.meta_value = 'Русская'" ) !== false ? '1' : null; return parent::get_var( $sql ); }
};

test_case( 'Facts normalize quantities safely and preserve historical step anchors', function () {
	expect( cooklook_recipe_plain_text( '<p>Mix.</p><p>Bake<br>until done.</p>' ) === 'Mix. Bake until done.', 'Paragraph boundaries were lost' );
	$GLOBALS['test_meta'][42] += array( '_recipe_time' => '65', '_recipe_prep' => ' 05 ', '_recipe_portions' => '4' );
	$GLOBALS['test_meta'][42]['_ingridients'] = array( array( 'ingridient_name' => ' <b>Соль</b> ', 'ingridient_value' => "по\xC2\xA0вкусу" ), array( 'ingridient_name' => array( 'bad' ) ) );
	$GLOBALS['test_meta'][42]['_recipe_step'] = array( array( 'recipe_step_text' => '<p> </p>' ), array( 'recipe_step_text' => '<b>Смешайте</b>  и подайте.' ) );
	$before = $GLOBALS['test_meta'];
	$facts = cooklook_recipe_facts( 42 );
	expect( $facts['cook'] === 65 && $facts['prep'] === 5 && $facts['total'] === 70 && $facts['portions'] === 4, 'Time or yield changed meaning' );
	expect( array_column( $facts['ingredients'], 'text' ) === array( 'Соль по вкусу' ), 'Ingredient amount was guessed or malformed row included' );
	$schema = cooklook_recipe_schema( 42 );
	expect( $schema['totalTime'] === 'PT1H10M' && $schema['recipeYield'] === '4', 'HTML model and schema facts differ' );
	expect( count( $schema['recipeInstructions'] ) === 1 && $schema['recipeInstructions'][0]['url'] === get_permalink( 42 ) . '#step_2', 'Empty row changed an existing anchor' );
	expect( $schema['recipeIngredient'] === array_column( $facts['ingredients'], 'text' ) && $GLOBALS['test_meta'] === $before, 'Schema differs from visible ingredients or metadata mutated' );
	foreach ( array( '-5', '30min', '1.5', '1e3', array(), '99999999999999999999999' ) as $bad ) {
		expect( cooklook_recipe_positive_integer( $bad ) === 0, 'Invalid fact was silently coerced' );
	}
} );
test_case( 'CookLook is the organization author and source metadata stays unpublished', function () {
	$GLOBALS['test_meta'][42]['_recipe_url'] = 'https://example.test/private-import';
	$GLOBALS['test_meta'][42]['_recipe_public_author'] = 'Автор';
	$GLOBALS['test_meta'][42]['_recipe_public_adaptation_author'] = '<b>Редактор</b>';
	$GLOBALS['test_meta'][42]['_recipe_public_source_url'] = 'https://example.test/original/';
	$GLOBALS['test_meta'][42]['_recipe_public_source_name'] = 'Original source';
	$before = $GLOBALS['test_meta'];
	$schema = cooklook_recipe_schema( 42 );
	$editorial = cooklook_recipe_editorial( 42 );
	expect( $schema['author'] === array( '@type' => 'Organization', 'name' => 'CookLook', 'url' => home_url( '/' ) ) && $editorial['author'] === 'CookLook', 'Approved author differs between HTML and schema' );
	expect( ! isset( $schema['isBasedOn'] ) && ! isset( $editorial['source_url'] ) && ! isset( $editorial['source_name'] ), 'Source metadata exposed' );
	expect( ! isset( cooklook_recipe_review_issues( 42 )['unconfirmed_author'] ), 'Approved authorship is still flagged as missing' );
	expect( $GLOBALS['test_meta'] === $before, 'Legacy metadata was overwritten' );
} );
test_case( 'Recipe joins the existing Yoast graph without duplicate entities', function () {
	$GLOBALS['test_conditionals']['singular'] = 'recipe';
	$graph = array( array( '@type' => 'WebSite', '@id' => 'https://example.test/#website' ), array( '@type' => array( 'WebPage' ), '@id' => 'https://example.test/custom-page-id' ) );
	$result = cooklook_recipe_schema_graph( $graph );
	expect( count( $result ) === 3 && $result[2]['mainEntityOfPage']['@id'] === $graph[1]['@id'], 'Recipe did not reference the real WebPage' );
	expect( $result[1]['mainEntity']['@id'] === get_permalink( 42 ) . '#recipe' && ! isset( $result[2]['@context'] ), 'Graph relation or context incorrect' );
	expect( cooklook_recipe_schema_graph( $result ) === $result, 'Recipe duplicated' );
	$GLOBALS['test_posts'][42]->post_password = 'protected';
	expect( cooklook_recipe_schema_graph( $graph ) === $graph, 'Protected facts entered graph' );
} );
test_case( 'Recipe image belongs to the finished dish, step images stay with visible steps', function () {
	$GLOBALS['test_meta'][42]['_recipe_step'] = array( array( 'recipe_step_text' => 'Mix', 'recipe_step_image' => 'https://example.test/step.jpg' ), array( 'recipe_step_text' => '', 'recipe_step_image' => 'https://example.test/hidden.jpg' ) );
	$schema = cooklook_recipe_schema( 42 );
	expect( $schema['image'] === array( 'https://example.test/dish.jpg' ), 'A step leaked into the dish image' );
	expect( count( $schema['recipeInstructions'] ) === 1 && $schema['recipeInstructions'][0]['image'] === 'https://example.test/step.jpg', 'Step image missing or hidden step exposed' );
	expect( cooklook_recipe_step_image( array( 'recipe_step_text' => '', 'recipe_step_image' => 'https://example.test/hidden.jpg' ) ) === '', 'An image without step text was exposed' );
} );
test_case( 'Missing photos and placeholders do not emit incomplete Recipe markup', function () {
	unset( $GLOBALS['test_meta'][42]['_thumbnail_id'] );
	$GLOBALS['test_meta'][42]['_recipe_step'] = array( array( 'recipe_step_text' => 'Mix', 'recipe_step_image' => 'https://example.test/step.jpg' ) );
	expect( cooklook_recipe_schema( 42 ) === array(), 'Step image substituted for missing finished dish' );
	ob_start(); cooklook_print_schema( cooklook_recipe_schema( 42 ) ); $html = ob_get_clean();
	expect( $html === '', 'Empty JSON-LD script emitted' );
	expect( isset( cooklook_recipe_review_issues( 42 )['missing_dish_image'] ), 'Missing photo is absent from editorial report' );
	foreach ( array( 'javascript:alert(1)', '//example.test/image.jpg', 'https://user:secret@example.test/a.jpg', 'https://example.test/no_image.png', 'https://example.test/no_image-150x150.png' ) as $url ) {
		expect( cooklook_recipe_image_url( $url ) === '', 'Unsafe image or placeholder accepted' );
	}
} );
test_case( 'Only existing, correctly proportioned crops enter Recipe.image', function () {
	foreach ( cooklook_recipe_image_sizes() as $name => $size ) {
		$GLOBALS['test_image_metadata'][100]['sizes'][ $name ] = array( 'width' => $size[0], 'height' => $size[1] );
		$GLOBALS['test_images'][100][ $name ] = array( 'https://example.test/' . $name . '.jpg', $size[0], $size[1] );
	}
	expect( count( cooklook_recipe_images( 42 ) ) === 4, 'Generated crops were not included' );
	$GLOBALS['test_image_metadata'][100]['sizes']['cooklook-recipe-wide']['height'] = 1200;
	expect( count( cooklook_recipe_images( 42 ) ) === 3, 'Incorrect crop aspect ratio accepted' );
} );
test_case( 'Unverified nutrition is omitted without overwriting imported data', function () {
	$GLOBALS['test_meta'][42]['_recipe_calories'] = '951';
	$GLOBALS['test_meta'][42]['_recipe_portions'] = '4';
	$before = $GLOBALS['test_meta'];
	expect( ! isset( cooklook_recipe_schema( 42 )['nutrition'] ), 'Legacy nutrition per 100 g published as per serving' );
	expect( isset( cooklook_recipe_review_issues( 42 )['nutrition_unverified'] ), 'Nutrition review task missing' );
	expect( $GLOBALS['test_meta'] === $before && get_post_status( 42 ) === 'publish', 'Report altered recipe data or status' );
} );
test_case( 'Noindex applies with an indexable site, preserves existing restrictions and normal archives', function () {
	$GLOBALS['test_conditionals']['archive'] = true;
	expect( ! cooklook_seo_noindex(), 'Plain archive closed' );
	$_GET = array( 'paged' => 2, 'sort_by' => 'date', 'utm_source' => 'test' );
	expect( ! cooklook_seo_noindex(), 'Pagination/default sorting/tracking closed' );
	foreach ( array( array( 'sort_by' => 'recipe_time' ), array( 'region' => 'Русская' ), array( 'include_ingredients' => array( 'milk' ) ), array( 'category_id' => '1' ) ) as $request ) {
		$_GET = $request;
		$robots = cooklook_seo_yoast_robots( array( 'index' => 'index', 'follow' => 'nofollow' ) );
		expect( $robots['index'] === 'noindex' && $robots['follow'] === 'nofollow', 'Yoast restrictions not preserved' );
		$robots = cooklook_seo_wp_robots( array( 'index' => true, 'nofollow' => true ) );
		expect( $robots['noindex'] && $robots['nofollow'] && ! isset( $robots['index'] ), 'Core fallback creates contradictory robots' );
	}
} );
test_case( 'Private templates, service slugs and default demo are excluded from both sitemaps', function () {
	foreach ( array( 51 => 'account', 52 => 'my-favorites', 53 => 'rcl-postedit', 54 => 'renamed-profile', 55 => 'sample-page', 56 => 'about' ) as $id => $slug ) {
		$GLOBALS['test_posts'][ $id ] = (object) array( 'ID' => $id, 'post_type' => 'page', 'post_name' => $slug, 'post_content' => 55 === $id ? 'Это пример страницы.' : '' );
	}
	$GLOBALS['test_meta'][54]['_wp_page_template'] = 'page-account.php';
	expect( cooklook_seo_sitemap_exclude( array( 99 ) ) === array( 99, 51, 52, 53, 55, 54 ), 'Yoast exclusion loses existing rules or service pages' );
	$args = cooklook_seo_core_sitemap_query( array( 'post__not_in' => array( 99 ) ), 'page' );
	expect( $args['post__not_in'] === cooklook_seo_sitemap_exclude( array( 99 ) ), 'Core fallback differs from Yoast' );
	$GLOBALS['test_conditionals']['singular'] = 'page';
	$GLOBALS['test_object'] = get_post( 54 );
	expect( cooklook_seo_noindex(), 'Renaming a private template makes it indexable' );
	$GLOBALS['test_posts'][55]->post_content = 'Собственная полезная страница';
	expect( ! cooklook_seo_is_excluded_page( get_post( 55 ) ), 'Repurposed sample-page automatically closed' );
	unset( $GLOBALS['test_object'] );
} );
test_case( 'Invalid, contradictory and malformed facets are rejected; valid Cyrillic survives', function () {
	foreach ( array(
		array( 'region' => 'unknown' ), array( 'region' => array( 'x' ) ), array( 'category_id' => 'bad' ), array( 'category_id' => 9 ),
		array( 'category_id' => 1, 'subcategory_id' => 3 ), array( 'category_id' => 2, 'subcategory_id' => 1 ), array( 'include_ingredients' => array( 'unknown' ) ),
		array( 'include_ingredients' => array( 'milk' ), 'exclude_ingredients' => array( 'milk' ) ),
		array( 'include_ingredients' => array( array( 'milk' ) ) ), array( 'include_ingredients' => array_fill( 0, 21, 'milk' ) ),
		array( 'sort_by' => 'bad' ), array( 'paged' => '-1' ), array( 'paged' => '0' )
	) as $request ) {
		expect( is_wp_error( cooklook_validate_recipe_filters( $request, cooklook_recipe_filters( $request ) ) ), 'Invalid request accepted: ' . json_encode( $request ) );
	}
	$request = array( 'category_id' => 1, 'subcategory_id' => 2, 'region' => 'Русская', 'include_ingredients' => array( '%d0%bc%d0%be%d0%bb%d0%be%d0%ba%d0%be' ) );
	expect( true === cooklook_validate_recipe_filters( $request, cooklook_recipe_filters( $request ) ), 'Valid Cyrillic request rejected' );
	expect( true === cooklook_validate_recipe_filters( $request, cooklook_recipe_filters( $request ), array( 'current_category' => 3 ) ), 'Recipes may belong to multiple archive categories' );
	$request = array( 'exclude_ingredients' => array( 'milk' ) );
	expect( is_wp_error( cooklook_validate_recipe_filters( $request, cooklook_recipe_filters( $request ), array( 'current_tag' => 'milk' ) ) ), 'Current ingredient archive can be excluded by its own filter' );
} );
test_case( 'AJAX returns 404 for invalid facets and out-of-range pages', function () {
	$_GET = array( 'region' => 'unknown' );
	expect( response_from( 'filter_recipes' )->status === 404, 'AJAX accepted unknown region' );
	$_GET = array( 'paged' => '4' );
	expect( response_from( 'filter_recipes' )->status === 404, 'AJAX accepted an absent page' );
} );
test_case( 'Every intermediate breadcrumb has a URL on archives and comment pagination', function () {
	foreach ( array( 'archive', 'recipe', 'tax', 'search' ) as $kind ) {
		$GLOBALS['test_conditionals'] = array();
		$GLOBALS['test_query_vars'] = array( 'paged' => 2 );
		$GLOBALS['test_page_link'] = home_url( '/recipes/?region=Русская&paged=2' );
		if ( 'archive' === $kind ) $GLOBALS['test_conditionals']['archive'] = true;
		if ( 'search' === $kind ) $GLOBALS['test_conditionals']['search'] = true;
		if ( 'tax' === $kind ) { $GLOBALS['test_conditionals']['tax'] = 'recipe_category'; $GLOBALS['test_object'] = new WP_Term( 1 ); }
		if ( 'recipe' === $kind ) { $GLOBALS['test_conditionals']['singular'] = 'recipe'; $GLOBALS['test_query_vars'] = array( 'cpage' => 2 ); }
		ob_start(); breadcrumbs(); $html = ob_get_clean();
		$dom = new DOMDocument(); @$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
		$xpath = new DOMXPath( $dom );
		$items = $xpath->query( '//*[@itemprop="itemListElement"]' );
		foreach ( $items as $index => $item ) {
			expect( $xpath->evaluate( 'string(.//*[@itemprop="position"]/@content)', $item ) === (string) ( $index + 1 ), 'Nonsequential breadcrumb positions' );
			if ( $index < $items->length - 1 ) expect( $xpath->query( './/a[@itemprop="item"][@href]', $item )->length === 1, 'Intermediate breadcrumb without URL: ' . $kind );
		}
		if ( 'recipe' === $kind ) expect( strpos( $html, 'https://example.test/subsite/recipe/42/' ) !== false, 'Comment breadcrumb does not link to original recipe' );
		unset( $GLOBALS['test_object'] );
	}
} );
test_case( 'Metadata uses dish summaries and preserves individual Yoast overrides', function () {
	$GLOBALS['test_conditionals']['singular'] = 'recipe';
	$GLOBALS['test_meta'][42]['_recipe_step'] = array( array( 'recipe_step_text' => 'FIRST STEP SHOULD NOT BE A DESCRIPTION' ) );
	$GLOBALS['test_posts'][42]->post_excerpt = 'Описание блюда с <b>сыром</b>.';
	expect( cooklook_seo_description( 'Old step' ) === 'Описание блюда с сыром.', 'Excerpt not used in description' );
	expect( strpos( cooklook_seo_title( 'Old ingredient list' ), 'пошаговый рецепт' ) !== false, 'Recipe title missing' );
	$GLOBALS['test_meta'][42]['_yoast_wpseo_title'] = 'Edited title';
	$GLOBALS['test_meta'][42]['_yoast_wpseo_metadesc'] = 'Edited description';
	expect( cooklook_seo_title( 'Edited title' ) === 'Edited title', 'Manual title overwritten' );
	expect( cooklook_seo_description( 'Edited description' ) === 'Edited description', 'Manual description overwritten' );
	$GLOBALS['test_posts'][42]->post_excerpt = '';
	expect( strpos( cooklook_recipe_summary( 42 ), 'FIRST STEP' ) === false, 'First instruction leaked into summary' );
} );

test_case( 'Home, catalog and term metadata are descriptive and pagination is distinct', function () {
	$GLOBALS['test_conditionals']['front'] = true;
	expect( cooklook_seo_description( '' ) === cooklook_home_intro(), 'Homepage description missing' );
	$GLOBALS['test_conditionals'] = array( 'archive' => true );
	$GLOBALS['test_query_vars']['paged'] = 3;
	expect( strpos( cooklook_seo_title( '' ), 'Страница 3' ) !== false && strpos( cooklook_seo_description( '' ), 'Страница 3' ) === 0, 'Paginated metadata missing page' );
	$GLOBALS['test_conditionals'] = array( 'tax' => 'recipe_category' );
	$GLOBALS['test_query_vars'] = array();
	$GLOBALS['test_object'] = new WP_Term( 1 );
	$GLOBALS['test_term_description'] = '<p>Отредактированное описание категории.</p>';
	expect( cooklook_seo_description( '' ) === 'Отредактированное описание категории.', 'Term description lost' );
	unset( $GLOBALS['test_object'], $GLOBALS['test_term_description'] );
} );

test_case( 'Yoast presentation shares defaults with the graph and preserves explicit editorial values', function () {
	$GLOBALS['test_conditionals']['singular'] = 'recipe';
	$GLOBALS['test_posts'][42]->post_excerpt = 'Описание блюда.';
	$presentation = (object) array( 'title' => 'Old template title', 'meta_description' => 'Old first step', 'open_graph_title' => 'Social title', 'open_graph_description' => 'Social description', 'twitter_title' => 'X title', 'robots' => array( 'index' => 'noindex' ), 'canonical' => '' );
	$before = clone $presentation;
	expect( cooklook_seo_presentation( $presentation ) === $presentation, 'Yoast presentation replaced' );
	expect( $presentation->title === cooklook_seo_title( '' ) && $presentation->meta_description === cooklook_seo_description( '' ), 'Graph and social fallbacks retain the old SEO templates' );
	foreach ( array( 'open_graph_title', 'open_graph_description', 'twitter_title', 'robots', 'canonical' ) as $property ) {
		expect( $presentation->$property === $before->$property, 'Explicit Yoast value overwritten: ' . $property );
	}
	$GLOBALS['test_meta'][42]['_yoast_wpseo_title'] = 'Edited title';
	$GLOBALS['test_meta'][42]['_yoast_wpseo_metadesc'] = 'Edited description';
	$presentation->title = 'Edited title';
	$presentation->meta_description = 'Edited description';
	cooklook_seo_presentation( $presentation );
	expect( $presentation->title === 'Edited title' && $presentation->meta_description === 'Edited description', 'Manual SEO metadata overwritten in presentation' );
	$GLOBALS['test_conditionals'] = array( 'search' => true );
	expect( cooklook_seo_presentation( clone $before ) == $before, 'Unmanaged search presentation changed' );
} );

test_case( 'Recipe and taxonomy descriptions preserve block boundaries and decode entities once', function () {
	$GLOBALS['test_posts'][42]->post_excerpt = '<p>Первый &amp; второй.</p><p>Третий<br>четвёртый.</p>';
	expect( cooklook_recipe_summary( 42 ) === 'Первый & второй. Третий четвёртый.', 'Summary merges blocks or exposes encoded entities' );
	$GLOBALS['test_conditionals'] = array( 'tax' => 'recipe_category' );
	$GLOBALS['test_object'] = new WP_Term( 1 );
	$GLOBALS['test_term_description'] = '<p>Первая часть.</p><p>Вторая &amp; третья.</p>';
	expect( cooklook_seo_description( '' ) === 'Первая часть. Вторая & третья.', 'Taxonomy description merges block text' );
	unset( $GLOBALS['test_object'], $GLOBALS['test_term_description'] );
} );

test_case( 'Metadata and breadcrumbs ignore pagination variables that do not select content', function () {
	$GLOBALS['test_conditionals'] = array( 'archive' => true );
	$GLOBALS['test_query_vars'] = array( 'page' => 999, 'cpage' => 888 );
	expect( cooklook_seo_page_number() === 1 && strpos( cooklook_seo_title( '' ), 'Страница' ) === false, 'Archive query parameters invent a page number' );
	ob_start(); breadcrumbs(); $html = ob_get_clean();
	expect( strpos( $html, 'Страница' ) === false, 'Archive breadcrumbs invent a page number' );
	$GLOBALS['test_query_vars']['paged'] = 3;
	expect( cooklook_seo_page_number() === 3, 'Real archive pagination lost' );
	$GLOBALS['test_conditionals'] = array( 'singular' => 'recipe' );
	$GLOBALS['test_query_vars'] = array( 'paged' => 99, 'cpage' => 88 );
	$GLOBALS['test_comment_pagination'] = false;
	expect( cooklook_seo_page_number() === 1, 'Disabled comment pagination invents a page' );
	$GLOBALS['test_comment_pagination'] = true;
	expect( cooklook_seo_page_number() === 88, 'Enabled comment pagination lost' );
	$GLOBALS['test_query_vars'] = array( 'page' => 2 );
	expect( cooklook_seo_page_number() === 2, 'Multipage recipe lost' );
	$GLOBALS['test_conditionals'] = array( 'singular' => 'page', 'template' => 'archive.php' );
	$GLOBALS['test_query_vars'] = array( 'page' => 2, 'paged' => 3, 'cpage' => 88 );
	expect( cooklook_seo_page_number() === 3, 'Custom catalog page no longer matches its query' );
	$GLOBALS['test_conditionals'] = array( '404' => true );
	expect( cooklook_seo_page_number() === 1, 'Missing page appends an invalid breadcrumb' );
	unset( $GLOBALS['test_comment_pagination'] );
} );

test_case( 'Category images prefer attachment IDs while preserving legacy URLs', function () {
	require_once dirname( __DIR__ ) . '/inc/media.php';
	$GLOBALS['test_term_images'][1] = array( 'category_image' => 'https://example.test/legacy.jpg' );
	$GLOBALS['test_image_urls'] = array( 'https://example.test/legacy.jpg' => 100 );
	expect( strpos( cooklook_category_image( 1 ), 'data-attachment="100"' ) !== false, 'Legacy media URL did not resolve to an attachment' );
	expect( $GLOBALS['test_rendered_image'][2]['alt'] === '', 'Decorative category image repeats its label' );
	$GLOBALS['test_term_images'][1] = array( 'category_image_id' => 100, 'category_image' => 'https://external.test/old.jpg' );
	cooklook_category_image( 1, true );
	expect( $GLOBALS['test_rendered_image'][0] === 100 && $GLOBALS['test_rendered_image'][2]['loading'] === 'eager', 'New attachment ID or first-screen loading lost' );
	$GLOBALS['test_term_images'][1] = array( 'category_image' => 'https://external.test/old.jpg' );
	expect( strpos( cooklook_category_image( 1 ), 'https://external.test/old.jpg' ) !== false, 'Unresolved legacy URL lost' );
	$GLOBALS['test_term_images'] = array();
	expect( cooklook_category_image( 1 ) === '', 'Empty category invented an image' );
} );

test_case( 'Recipe introduction avoids duplicates without changing the full body', function () {
	$post = $GLOBALS['test_posts'][42];
	$post->post_excerpt = '<p>Общее вступление.</p>';
	$post->post_content = '<!-- wp:paragraph --><p>Общее вступление.</p><!-- /wp:paragraph --><p>Полный текст с <a href="/recipes/">ссылкой</a>.</p>[example]';
	$body = $post->post_content;
	expect( cooklook_recipe_intro( 42 ) === '', 'Introduction duplicated' );
	expect( $post->post_content === $body, 'Body, blocks or shortcode changed' );
	$post->post_excerpt = '<p>Отдельный анонс.</p>';
	expect( cooklook_recipe_intro( 42 ) === $post->post_excerpt, 'Distinct excerpt lost' );
	$post->post_excerpt = '';
	expect( cooklook_recipe_intro( 42 ) === '', 'Full body repeated as intro' );
} );

echo $GLOBALS['test_passed'] . " passed, " . $GLOBALS['test_failed'] . " failed.\n";
exit( $GLOBALS['test_failed'] ? 1 : 0 );
