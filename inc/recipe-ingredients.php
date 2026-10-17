<?php
/** Bounded ingredient lookup for the catalog's two Select2 fields. */
defined( 'ABSPATH' ) || exit;

/** Only existing selections belong in the initial HTML, including legacy empty terms. */
function cooklook_selected_ingredients( $filters ) {
	$slugs = array_values( array_unique( array_merge(
		cooklook_ingredient_slugs( $filters['include_ingredients'] ),
		cooklook_ingredient_slugs( $filters['exclude_ingredients'] )
	) ) );
	if ( ! $slugs ) { return array(); }
	$terms = get_terms( array(
		'taxonomy' => 'recipe_tags', 'slug' => $slugs, 'hide_empty' => false,
		'number' => count( $slugs ), 'hierarchical' => false, 'update_term_meta_cache' => false,
	) );
	return is_wp_error( $terms ) ? array() : $terms;
}

/** At most 20 choices per page; the extra row avoids a separate COUNT query. */
function cooklook_search_ingredients( $request ) {
	$term = isset( $request['term'] ) ? $request['term'] : '';
	$page = isset( $request['page'] ) ? $request['page'] : '1';
	// Bound both input and offset before running a term query. Refine broad searches after 100 pages.
	if ( ! is_string( $term ) || strlen( $term ) > 400 || ! preg_match( '/^.{0,100}$/us', wp_unslash( $term ) )
		|| ! is_scalar( $page ) || ! preg_match( '/^[1-9][0-9]{0,2}$/D', (string) $page ) || (int) $page > 100 ) {
		return new WP_Error( 'invalid_ingredient_search', __( 'Уточните название ингредиента и повторите поиск.', 'cooklook' ) );
	}
	$term = sanitize_text_field( wp_unslash( $term ) );
	$result = array( 'results' => array(), 'pagination' => array( 'more' => false ) );
	if ( ! preg_match( '/^.{2,}$/u', $term ) ) { return $result; }
	$terms = get_terms( array(
		'taxonomy' => 'recipe_tags', 'hide_empty' => true, 'hierarchical' => false,
		'search' => $term, 'orderby' => 'name', 'order' => 'ASC',
		'number' => 21, 'offset' => ( (int) $page - 1 ) * 20, 'update_term_meta_cache' => false,
	) );
	if ( is_wp_error( $terms ) ) { return $terms; }
	$result['pagination']['more'] = count( $terms ) > 20 && (int) $page < 100;
	foreach ( array_slice( $terms, 0, 20 ) as $ingredient ) {
		$result['results'][] = array( 'id' => $ingredient->slug, 'text' => wp_specialchars_decode( $ingredient->name, ENT_QUOTES ) );
	}
	return $result;
}

function cooklook_search_ingredients_ajax() {
	cooklook_verify_ajax_nonce( 'cooklook_catalog' );
	$request = $_GET;
	// "page" is an admin-screen parameter consumed by plugins before AJAX dispatch.
	$request['page'] = isset( $request['ingredient_page'] ) ? $request['ingredient_page'] : '1';
	$result = cooklook_search_ingredients( $request );
	if ( is_wp_error( $result ) ) {
		$status = 'invalid_ingredient_search' === $result->get_error_code() ? 400 : 500;
		wp_send_json_error( __( 'Не удалось загрузить ингредиенты. Уточните запрос или обновите страницу.', 'cooklook' ), $status );
	}
	wp_send_json_success( $result );
}
add_action( 'wp_ajax_cooklook_search_ingredients', 'cooklook_search_ingredients_ajax' );
add_action( 'wp_ajax_nopriv_cooklook_search_ingredients', 'cooklook_search_ingredients_ajax' );
