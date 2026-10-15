<?php
defined( 'ABSPATH' ) || exit;

function filter_recipes() {
	cooklook_verify_ajax_nonce( 'cooklook_catalog' );
	$request = array_merge( $_GET, $_POST );
	$filters = cooklook_recipe_filters( $request );
	$context = array(
		'current_category' => cooklook_request_id( $request, 'current_category' ),
		'current_tag'      => cooklook_request_slug( $request, 'current_tag' ),
	);
	$query = new WP_Query( cooklook_recipe_query_args( $filters, $context ) );
	ob_start();
	cooklook_render_recipes( $query, true );
	$html = ob_get_clean();
	$url = wp_validate_redirect( cooklook_request_url( $request, 'page_url' ), get_post_type_archive_link( 'recipe' ) );
	$url = remove_query_arg( array( 'action', 'nonce', 'paged' ), $url );
	wp_send_json_success( array( 'html' => $html, 'pagination' => cooklook_recipe_pagination( $query, $url ) ) );
}
add_action( 'wp_ajax_filter_recipes', 'filter_recipes' );
add_action( 'wp_ajax_nopriv_filter_recipes', 'filter_recipes' );

function cooklook_get_subcategories() {
	cooklook_verify_ajax_nonce( 'cooklook_catalog' );
	$parent = cooklook_request_id( $_GET, 'category_id' );
	$terms = $parent ? get_terms( array( 'taxonomy' => 'recipe_category', 'parent' => $parent, 'hide_empty' => false ) ) : array();
	if ( is_wp_error( $terms ) ) {
		wp_send_json_error( __( 'Не удалось получить категории.', 'cooklook' ), 500 );
	}
	$options = '<option value="all">' . esc_html__( 'Любое блюдо', 'cooklook' ) . '</option>';
	foreach ( $terms as $term ) {
		$options .= '<option value="' . esc_attr( $term->term_id ) . '">' . esc_html( $term->name ) . '</option>';
	}
	wp_send_json_success( array( 'options' => $options ) );
}
add_action( 'wp_ajax_get_subcategories', 'cooklook_get_subcategories' );
add_action( 'wp_ajax_nopriv_get_subcategories', 'cooklook_get_subcategories' );
