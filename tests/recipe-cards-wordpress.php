<?php
/** CLI integration test against actual WordPress/Carbon Fields; no database writes.
 * php tests/recipe-cards-wordpress.php
 */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
define( 'DISABLE_WP_CRON', true );
$root = getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 );
require $root . '/wp-load.php';
add_filter( 'query', function ( $sql ) {
	if ( ! preg_match( '/^\s*(?:SELECT|SHOW|EXPLAIN)\b/i', $sql ) ) {
		throw new RuntimeException( 'Non-read SQL refused by integration test.' );
	}
	return $sql;
}, PHP_INT_MAX );

function card_check( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}

/** Compare visible card text, links and order without WP's per-request image heuristics. */
function card_snapshot( $query ) {
	$query->rewind_posts();
	ob_start(); cooklook_render_recipes( $query ); $html = ob_get_clean();
	$dom = new DOMDocument();
	@$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
	$xpath = new DOMXPath( $dom );
	$cards = array();
	foreach ( $xpath->query( '//a[@class="recipe_title" or @class="recipe_desc"]' ) as $link ) {
		$cards[] = array( $link->getAttribute( 'class' ), $link->getAttribute( 'href' ), trim( $link->textContent ) );
	}
	card_check( count( $cards ) === 2 * $query->post_count, 'Missing card titles/descriptions' );
	return $cards;
}

global $wpdb;
$field = \Carbon_Fields\Helper\Helper::get_field( 'post_meta', '', 'recipe_step' );
$cached_store = $field->get_datastore();
card_check( $cached_store instanceof Cooklook_Cached_Post_Meta_Datastore, 'Steps must use cached metadata reads' );
$query = new WP_Query( array( 'post_type' => 'recipe', 'post_status' => 'publish', 'has_password' => false,
	'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'no_found_rows' => true ) );
card_check( $query->post_count > 0, 'Existing recipes required' );
$descriptions = array();
$rows = array();
$sql_counts = array();
// WP_Query has primed post metadata; even the first read must not issue per-card SQL.
for ( $pass = 0; $pass < 2; $pass++ ) {
	$before = $wpdb->num_queries;
	foreach ( $query->posts as $recipe ) {
		$descriptions[ $recipe->ID ] = cooklook_recipe_description( $recipe->ID, 15 );
		$rows[ $recipe->ID ] = carbon_get_post_meta( $recipe->ID, 'recipe_step' );
	}
	$sql_counts[] = $wpdb->num_queries - $before;
	card_check( 0 === end( $sql_counts ), 'Primed descriptions/steps issued SQL on pass ' . ( $pass + 1 ) );
}

// Compare every stored row (including images) and description against Carbon's SQL reader.
try {
	$field->set_datastore( new \Carbon_Fields\Datastore\Post_Meta_Datastore() );
	foreach ( $query->posts as $recipe ) {
		card_check( $rows[ $recipe->ID ] === carbon_get_post_meta( $recipe->ID, 'recipe_step' ), 'Changed steps: ' . $recipe->ID );
		card_check( $descriptions[ $recipe->ID ] === cooklook_recipe_description( $recipe->ID, 15 ), 'Changed description: ' . $recipe->ID );
	}
	$old_cards = card_snapshot( $query );
} finally {
	$field->set_datastore( $cached_store );
}
card_check( $old_cards === card_snapshot( $query ), 'Changed rendered card text, links or order' );

// Synthetic metadata is supplied only through a temporary read filter, never persisted.
$fixture_id = PHP_INT_MAX;
$group = $field->get_group_names()[0];
$fixture = array( '_original_recipe_step|original_recipe_step_text|0|0|value' => array( 'Do not include' ) );
$expected_steps = array();
// Insert in reverse order, including two-digit indices: Carbon must restore numeric order.
for ( $index = 10; $index >= 0; $index-- ) {
	$fixture[ '_recipe_step|||' . $index . '|value' ] = array( $group );
	$fixture[ '_recipe_step|recipe_step_text|' . $index . '|0|value' ] = array( '<p>Step ' . $index . '</p>' );
	$expected_steps[ $index ] = '<p>Step ' . $index . '</p>';
}
ksort( $expected_steps );
$metadata_filter = function ( $value, $id, $key ) use ( $fixture_id, &$fixture ) {
	return $id === $fixture_id && '' === $key ? $fixture : $value;
};
add_filter( 'get_post_metadata', $metadata_filter, 10, 3 );
try {
	$before = $wpdb->num_queries;
	$steps = cooklook_recipe_rows( $fixture_id, 'recipe_step' );
	card_check( array_column( $steps, 'recipe_step_text' ) === $expected_steps, 'Numeric step order or field isolation changed' );
	card_check( carbon_get_post_meta( $fixture_id, 'recipe_step[2]/recipe_step_text' ) === '<p>Step 2</p>', 'Nested Carbon field path changed' );
	$fixture['_recipe_step|recipe_step_text|0|0|value'] = array( 'Updated step' );
	card_check( cooklook_recipe_rows( $fixture_id, 'recipe_step' )[0]['recipe_step_text'] === 'Updated step', 'Stale data after metadata changed' );
	$fixture = array();
	card_check( array() === cooklook_recipe_rows( $fixture_id, 'recipe_step' ), 'Missing steps must be empty' );
	card_check( array() === cooklook_recipe_rows( $fixture_id, 'recipe_step' ), 'Repeated missing steps changed' );
	card_check( $before === $wpdb->num_queries, 'Fixture reads issued SQL' );
} finally {
	remove_filter( 'get_post_metadata', $metadata_filter, 10 );
}

$mapped_id = $query->posts[0]->ID;
$id_filter = function ( $id, $key ) use ( $fixture_id, $mapped_id ) {
	return $fixture_id === $id && 'recipe_step' === $key ? $mapped_id : $id;
};
add_filter( 'carbon_get_post_meta_post_id', $id_filter, 10, 2 );
try {
	card_check( $rows[ $mapped_id ] === carbon_get_post_meta( $fixture_id, 'recipe_step' ), 'Carbon post-ID mapping ignored' );
} finally {
	remove_filter( 'carbon_get_post_meta_post_id', $id_filter, 10 );
}
$filter_seen = false;
$storage_filter = function ( $storage, $store, $patterns ) use ( &$filter_seen, $mapped_id ) {
	if ( $store instanceof Cooklook_Cached_Post_Meta_Datastore && $store->get_object_id() === $mapped_id ) {
		$filter_seen = ! empty( $patterns );
		return array();
	}
	return $storage;
};
add_filter( 'carbon_fields_datastore_storage_array', $storage_filter, 10, 3 );
try {
	card_check( array() === cooklook_recipe_rows( $mapped_id, 'recipe_step' ) && $filter_seen, 'Carbon storage filter ignored' );
} finally {
	remove_filter( 'carbon_fields_datastore_storage_array', $storage_filter, 10 );
}

echo wp_json_encode( array( 'checked_at' => gmdate( 'c' ), 'site' => home_url( '/' ), 'database_writes' => false,
	'recipes_compared' => $query->post_count, 'description_and_steps_sql_per_pass' => $sql_counts,
	'result' => 'PASS: original rows, images, descriptions and rendered card order/links preserved; numeric group order, nested paths, changing/missing metadata and Carbon filters verified.' ),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
