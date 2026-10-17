<?php
/** Optional integration test: actual WordPress and existing recipes; never writes to the DB. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
define( 'DISABLE_WP_CRON', true );
$root = getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 );
require $root . '/wp-load.php';
function ai_check( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}
function ai_dom( $html ) {
	$dom = new DOMDocument();
	@$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
	return new DOMXPath( $dom );
}
$query = new WP_Query( array( 'post_type' => 'recipe', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => 10, 'meta_key' => '_thumbnail_id', 'orderby' => 'ID', 'order' => 'DESC' ) );
ai_check( 10 === count( $query->posts ), 'Ten existing recipes required' );
$report = array();
foreach ( $query->posts as $post ) {
	$GLOBALS['post'] = $post;
	setup_postdata( $post );
	$id = $post->ID;
	$facts = cooklook_recipe_facts( $id );
	$editorial = cooklook_recipe_editorial( $id );
	$schema = cooklook_recipe_schema( $id );
	ai_check( (bool) $schema, 'Photo missing in sample recipe: ' . $id );
	ob_start();
	get_template_part( 'template-parts/recipe-facts', null, array( 'facts' => $facts, 'editorial' => $editorial, 'post_id' => $id ) );
	get_template_part( 'template-parts/recipe-instructions', null, array( 'facts' => $facts ) );
	$html = ob_get_clean();
	$xpath = ai_dom( $html );
	ai_check( $schema['author']['@type'] === 'Organization' && $schema['author']['name'] === 'CookLook', 'Wrong organization author: ' . $id );
	ai_check( strpos( $xpath->query( '//div[@class="recipe_editorial"]' )->item( 0 )->textContent, 'Автор рецепта: CookLook' ) !== false, 'Visible author differs from schema: ' . $id );
	ai_check( ! isset( $schema['isBasedOn'] ) && $xpath->query( '//div[@class="recipe_editorial"]//a' )->length === 0, 'Source attribution exposed: ' . $id );
	$ingredients = array();
	foreach ( $xpath->query( '//*[@id="ingredients"]//li' ) as $item ) { $ingredients[] = cooklook_recipe_plain_text( $item->textContent ); }
	ai_check( $ingredients === $schema['recipeIngredient'], 'Ingredient text/schema mismatch: ' . $id );
	$steps = $xpath->query( '//*[@id="instructions"]/ol/li' );
	ai_check( count( $schema['recipeInstructions'] ) === $steps->length, 'Steps are not an ordered list: ' . $id );
	foreach ( $schema['recipeInstructions'] as $index => $step ) {
		ai_check( $step['url'] === get_permalink( $id ) . '#' . $steps->item( $index )->getAttribute( 'id' ), 'Lost step anchor: ' . $id );
		$text = $xpath->query( './/div[@class="step_description"]', $steps->item( $index ) )->item( 0 )->textContent;
		ai_check( cooklook_recipe_plain_text( $text ) === $step['text'], 'Visible step/schema text differs: ' . $id );
	}
	foreach ( array( 'prepTime', 'cookTime', 'totalTime' ) as $key ) {
		if ( isset( $schema[ $key ] ) ) {
			ai_check( $xpath->query( '//dl//time[@datetime="' . $schema[ $key ] . '"]' )->length > 0, 'Missing visible duration: ' . $id );
		}
	}
	$report[] = array( 'id' => $id, 'url' => get_permalink( $id ), 'ingredients' => count( $ingredients ), 'steps' => $steps->length );
}
// Exercise missing data and malicious editorial input only in template arguments.
$fixture = $facts;
$fixture['steps'] = array();
ob_start(); get_template_part( 'template-parts/recipe-instructions', null, array( 'facts' => $fixture ) ); $html = ob_get_clean();
ai_check( strpos( $html, 'id="ingredients"' ) !== false && strpos( $html, 'id="instructions"' ) === false, 'Ingredients depend on steps' );
$fixture['steps'] = array( array( 'anchor' => 'step_2', 'content' => '<img src=x onerror="alert(1)"><script>alert(1)</script><b>Mix</b>', 'image' => '' ) );
$fixture['ingredients'] = array();
ob_start(); get_template_part( 'template-parts/recipe-instructions', null, array( 'facts' => $fixture ) ); $html = ob_get_clean();
ai_check( strpos( $html, 'id="step_2"' ) !== false && strpos( $html, 'id="ingredients"' ) === false && strpos( $html, 'onerror=' ) === false && strpos( $html, '<script>' ) === false, 'Unsafe or absent step handling' );
$editorial['reviewer'] = '<script>alert(1)</script>';
$editorial['source_url'] = 'https://example.test/hidden-source/';
$editorial['source_name'] = 'Hidden source';
ob_start(); get_template_part( 'template-parts/recipe-facts', null, array( 'facts' => $facts, 'editorial' => $editorial, 'post_id' => $id ) ); $html = ob_get_clean();
$xpath = ai_dom( $html );
ai_check( $xpath->query( '//script|//a[starts-with(@href,"javascript:")]' )->length === 0, 'Editorial fields are not escaped' );
ai_check( strpos( $html, 'hidden-source' ) === false && strpos( $html, 'Hidden source' ) === false, 'Source unexpectedly published by the template' );
ai_check( cooklook_recipe_schema_graph( array() ) === array(), 'Empty Yoast graph should remain disabled' );
ob_start(); add_dynamic_schema_markup(); cooklook_recipe_schema_fallback( $id ); $html = ob_get_clean();
ai_check( defined( 'WPSEO_VERSION' ) && '' === $html, 'Duplicate fallback schema with Yoast active' );
echo wp_json_encode( array( 'checked_at' => gmdate( 'c' ), 'site' => home_url( '/' ), 'database_writes' => false, 'recipes' => $report, 'fixtures' => 'PASS: ingredients without steps; steps without ingredients; CookLook organization author in HTML/Recipe; source attribution omitted; escaped fields; disabled/Yoast-owned graph' ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
