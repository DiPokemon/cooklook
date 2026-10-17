<?php
/** JSON-LD is encoded as data, never assembled from interpolated HTML. */
defined( 'ABSPATH' ) || exit;

function cooklook_print_schema( $schema ) {
	if ( ! $schema ) {
		return;
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ) . '</script>';
}

function add_dynamic_schema_markup() {
	if ( defined( 'WPSEO_VERSION' ) ) { return; }
	$schema = array(
		'@context' => 'https://schema.org',
		'@type' => 'WebSite',
		'@id' => home_url( '/' ) . '#website',
		'name' => get_bloginfo( 'name' ),
		'url' => home_url( '/' ),
		'potentialAction' => array(
			'@type' => 'SearchAction',
			'target' => home_url( '/?s={search_term_string}' ),
			'query-input' => 'required name=search_term_string',
		),
	);
	cooklook_print_schema( $schema );
}
add_action( 'wp_head', 'add_dynamic_schema_markup' );

function cooklook_recipe_schema( $post_id ) {
	$post = get_post( $post_id );
	$images = cooklook_recipe_images( $post_id );
	if ( ! $post || post_password_required( $post ) || ! $images ) {
		return array();
	}
	$schema = array(
		'@context' => 'https://schema.org',
		'@type' => 'Recipe',
		'@id' => get_permalink( $post_id ) . '#recipe',
		'name' => get_the_title( $post_id ),
		'url' => get_permalink( $post_id ),
		'author' => cooklook_recipe_author(),
		'datePublished' => get_the_date( 'c', $post_id ),
		'dateModified' => get_the_modified_date( 'c', $post_id ),
		'description' => wp_strip_all_tags( cooklook_recipe_summary( $post_id ) ),
		'image' => $images,
	);
	$facts = cooklook_recipe_facts( $post_id );
	$steps = array();
	foreach ( $facts['steps'] as $row ) {
		$step = array( '@type' => 'HowToStep', 'text' => $row['text'], 'url' => get_permalink( $post_id ) . '#' . $row['anchor'] );
		if ( $row['image'] ) {
			$step['image'] = $row['image'];
		}
		$steps[] = $step;
	}
	if ( $steps ) {
		$schema['recipeInstructions'] = $steps;
	}
	$ingredients = array_column( $facts['ingredients'], 'text' );
	if ( $ingredients ) {
		$schema['recipeIngredient'] = $ingredients;
	}
	$cook = $facts['cook'];
	$prep = $facts['prep'];
	if ( $cook ) {
		$schema['cookTime'] = schema_time( $cook );
	}
	if ( $prep ) {
		$schema['prepTime'] = schema_time( $prep );
	}
	if ( $cook + $prep ) {
		$schema['totalTime'] = schema_time( $cook + $prep );
	}
	$portions = $facts['portions'];
	if ( $portions ) {
		$schema['recipeYield'] = (string) $portions;
	}
	$region = $facts['cuisine'];
	if ( $region ) {
		$schema['recipeCuisine'] = $region;
	}
	$categories = wp_list_pluck( cooklook_recipe_terms( $post_id, 'recipe_category' ), 'name' );
	if ( $categories ) {
		$schema['recipeCategory'] = implode( ', ', $categories );
	}
	$tags = wp_list_pluck( cooklook_recipe_terms( $post_id, 'recipe_tags' ), 'name' );
	if ( $tags ) {
		$schema['keywords'] = implode( ', ', $tags );
	}
	// Legacy nutrition is labelled per 100 g, with no verified serving mass.
	// Do not publish it as nutrition per serving or silently recalculate it.
	$rating = cooklook_recipe_rating_data( $post_id );
	if ( $rating['likes'] + $rating['dislikes'] > 0 ) {
		$schema['aggregateRating'] = array( '@type' => 'AggregateRating', 'ratingValue' => $rating['rating'], 'ratingCount' => $rating['likes'] + $rating['dislikes'], 'bestRating' => 5, 'worstRating' => 0 );
	}
	return $schema;
}

/** Keep Yoast as the graph owner, linking Recipe to its actual WebPage ID. */
function cooklook_recipe_schema_graph( $graph ) {
	if ( ! is_singular( 'recipe' ) || ! is_array( $graph ) || ! $graph ) { return $graph; }
	foreach ( $graph as $piece ) {
		if ( in_array( 'Recipe', (array) ( $piece['@type'] ?? array() ), true ) ) { return $graph; }
	}
	$recipe = cooklook_recipe_schema( get_queried_object_id() );
	if ( ! $recipe ) { return $graph; }
	unset( $recipe['@context'] );
	foreach ( $graph as &$piece ) {
		if ( ! empty( $piece['@id'] ) && in_array( 'WebPage', (array) ( $piece['@type'] ?? array() ), true ) ) {
			$recipe['mainEntityOfPage'] = array( '@id' => $piece['@id'] );
			if ( empty( $piece['mainEntity'] ) ) { $piece['mainEntity'] = array( '@id' => $recipe['@id'] ); }
			break;
		}
	}
	unset( $piece );
	$graph[] = $recipe;
	return $graph;
}
add_filter( 'wpseo_schema_graph', 'cooklook_recipe_schema_graph', 20 );

function cooklook_recipe_schema_fallback( $post_id ) {
	if ( ! defined( 'WPSEO_VERSION' ) ) { cooklook_print_schema( cooklook_recipe_schema( $post_id ) ); }
}
