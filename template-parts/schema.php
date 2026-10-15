<?php
/** JSON-LD is encoded as data, never assembled from interpolated HTML. */
defined( 'ABSPATH' ) || exit;

function cooklook_print_schema( $schema ) {
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ) . '</script>';
}

function add_dynamic_schema_markup() {
	$schema = array(
		'@context' => 'https://schema.org',
		'@type' => 'WebSite',
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
	$schema = array(
		'@context' => 'https://schema.org',
		'@type' => 'Recipe',
		'name' => get_the_title( $post_id ),
		'url' => get_permalink( $post_id ),
		'author' => array( '@type' => 'Person', 'name' => get_the_author_meta( 'display_name', $post->post_author ) ),
		'datePublished' => get_the_date( 'c', $post_id ),
		'dateModified' => get_the_modified_date( 'c', $post_id ),
		'description' => wp_strip_all_tags( cooklook_recipe_description( $post_id ) ),
	);
	$images = array();
	$image = get_the_post_thumbnail_url( $post_id, 'full' );
	if ( $image ) {
		$images[] = $image;
	}
	$steps = array();
	foreach ( cooklook_recipe_rows( $post_id, 'recipe_step' ) as $index => $row ) {
		if ( empty( $row['recipe_step_text'] ) ) {
			continue;
		}
		$step = array( '@type' => 'HowToStep', 'text' => wp_strip_all_tags( $row['recipe_step_text'] ), 'url' => get_permalink( $post_id ) . '#step_' . ( $index + 1 ) );
		if ( ! empty( $row['recipe_step_image'] ) && esc_url_raw( $row['recipe_step_image'] ) ) {
			$step['image'] = esc_url_raw( $row['recipe_step_image'] );
			$images[] = $step['image'];
		}
		$steps[] = $step;
	}
	if ( $images ) {
		$schema['image'] = array_values( array_unique( $images ) );
	}
	if ( $steps ) {
		$schema['recipeInstructions'] = $steps;
	}
	$ingredients = array();
	foreach ( cooklook_recipe_rows( $post_id, 'ingridients' ) as $row ) {
		if ( ! empty( $row['ingridient_name'] ) ) {
			$ingredients[] = trim( $row['ingridient_name'] . ' ' . ( isset( $row['ingridient_value'] ) ? $row['ingridient_value'] : '' ) );
		}
	}
	if ( $ingredients ) {
		$schema['recipeIngredient'] = $ingredients;
	}
	$cook = max( 0, (int) cooklook_recipe_value( $post_id, 'recipe_time' ) );
	$prep = max( 0, (int) cooklook_recipe_value( $post_id, 'recipe_prep' ) );
	if ( $cook ) {
		$schema['cookTime'] = schema_time( $cook );
	}
	if ( $prep ) {
		$schema['prepTime'] = schema_time( $prep );
	}
	if ( $cook + $prep ) {
		$schema['totalTime'] = schema_time( $cook + $prep );
	}
	$portions = cooklook_recipe_value( $post_id, 'recipe_portions' );
	if ( $portions ) {
		$schema['recipeYield'] = (string) $portions;
	}
	$region = cooklook_recipe_value( $post_id, 'recipe_region' );
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
	$nutrition = array( '@type' => 'NutritionInformation', 'servingSize' => '100 g' );
	foreach ( array( 'recipe_calories' => 'calories', 'recipe_protein' => 'proteinContent', 'recipe_fat' => 'fatContent', 'recipe_carbs' => 'carbohydrateContent' ) as $key => $property ) {
		$value = cooklook_recipe_value( $post_id, $key );
		if ( is_numeric( $value ) && $value >= 0 ) {
			$nutrition[ $property ] = $value . ( 'calories' === $property ? ' kcal' : ' g' );
		}
	}
	if ( count( $nutrition ) > 2 ) {
		$schema['nutrition'] = $nutrition;
	}
	$rating = cooklook_recipe_rating_data( $post_id );
	if ( $rating['likes'] + $rating['dislikes'] > 0 ) {
		$schema['aggregateRating'] = array( '@type' => 'AggregateRating', 'ratingValue' => $rating['rating'], 'ratingCount' => $rating['likes'] + $rating['dislikes'], 'bestRating' => 5, 'worstRating' => 0 );
	}
	return $schema;
}
