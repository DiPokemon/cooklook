<?php
/** Editorial collections are independent of dish categories and ingredients. */
defined( 'ABSPATH' ) || exit;

/** Resolve the single optional Carbon Fields term association on the homepage. */
function cooklook_home_collection( $settings_id ) {
	$selected = cooklook_recipe_rows( $settings_id, 'home_collection' );
	$row = $selected[0] ?? array();
	if ( ! isset( $row['id'] ) || ! is_scalar( $row['id'] )
		|| ( isset( $row['type'] ) && 'term' !== $row['type'] )
		|| ( isset( $row['subtype'] ) && 'recipe_collection' !== $row['subtype'] ) ) {
		return null;
	}
	$term_id = cooklook_recipe_positive_integer( $row['id'] );
	if ( ! $term_id ) { return null; }
	$term = get_term( $term_id, 'recipe_collection' );
	return $term instanceof WP_Term && 'recipe_collection' === $term->taxonomy ? $term : null;
}

/** The homepage limit never changes the collection's membership or archive size. */
function cooklook_home_collection_query( $term ) {
	$term_id = $term instanceof WP_Term && 'recipe_collection' === $term->taxonomy ? $term->term_id : 0;
	return new WP_Query( array(
		'post_type' => 'recipe',
		'post_status' => 'publish',
		'has_password' => false,
		'posts_per_page' => 10,
		'no_found_rows' => true,
		'ignore_sticky_posts' => true,
		// Adding newer recipes keeps the existing links on the homepage stable.
		'orderby' => 'ID',
		'order' => 'ASC',
		'tax_query' => array( array(
			'taxonomy' => 'recipe_collection',
			'field' => 'term_id',
			'terms' => array( (int) $term_id ),
			'include_children' => false,
		) ),
	) );
}

/** A code deployment needs the new routes once, without rewriting server files. */
function cooklook_refresh_collection_rewrite_rules() {
	$version = '1';
	if ( ! taxonomy_exists( 'recipe_collection' ) || $version === get_option( 'cooklook_collection_rewrite_version' ) ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( 'cooklook_collection_rewrite_version', $version, false );
}
add_action( 'wp_loaded', 'cooklook_refresh_collection_rewrite_rules' );

function cooklook_reset_collection_rewrite_version() {
	delete_option( 'cooklook_collection_rewrite_version' );
}
add_action( 'after_switch_theme', 'cooklook_reset_collection_rewrite_version' );
