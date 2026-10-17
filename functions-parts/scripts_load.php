<?php
defined( 'ABSPATH' ) || exit;

function cooklook_asset_version( $path ) {
	$file = get_template_directory() . '/' . $path;
	return is_file( $file ) ? (string) filemtime( $file ) : _S_VERSION;
}

function cooklook_enqueue_script( $handle, $path, $dependencies = array( 'jquery' ) ) {
	wp_enqueue_script( $handle, get_template_directory_uri() . '/' . $path, $dependencies, cooklook_asset_version( $path ), true );
}

function cooklook_has_recipe_slider() {
	return is_front_page() || is_page_template( 'home.php' ) || ( is_singular( 'recipe' ) && ! post_password_required() );
}

function load_scripts() {
	// WordPress owns jQuery and its noConflict mode.
	cooklook_enqueue_script( 'script', 'static/js/script.js' );
	if ( cooklook_has_recipe_slider() ) {
		cooklook_enqueue_script( 'slick', 'static/libs/slick/slick.min.js' );
		cooklook_enqueue_script( 'slick_init', 'static/js/slick_init.js', array( 'jquery', 'slick' ) );
	}
	cooklook_enqueue_script( 'ajax-search', 'static/js/ajax-search.js', array() );
	wp_localize_script( 'ajax-search', 'cooklookSearch', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'cooklook_search' ),
	) );
	cooklook_enqueue_script( 'search_modal', 'static/js/search_modal.js', array() );

	if ( is_post_type_archive( 'recipe' ) || is_tax( array( 'recipe_category', 'recipe_tags' ) ) || is_page_template( 'archive.php' ) ) {
		cooklook_enqueue_script( 'select2', 'static/libs/select2/select2.full.min.js' );
		cooklook_enqueue_script( 'filter-ingridients', 'static/js/filter-ingridients.js', array( 'jquery', 'select2' ) );
		wp_localize_script( 'filter-ingridients', 'cooklookIngredients', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'cooklook_catalog' ),
		) );
		cooklook_enqueue_script( 'filter', 'static/js/filters.js', array( 'jquery', 'select2', 'filter-ingridients' ) );
		wp_localize_script( 'filter', 'filter_obj', array_merge( cooklook_recipe_context(), array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'cooklook_catalog' ),
			'filters' => cooklook_recipe_filters( $_GET ),
			'base_url' => remove_query_arg( array( 'paged', 'page' ), get_pagenum_link( 1, false ) ),
			'pagination' => cooklook_recipe_pagination_pattern(),
			'pagination_base' => $GLOBALS['wp_rewrite']->pagination_base,
			'route_query' => cooklook_catalog_route_args(),
			'page_metadata' => cooklook_catalog_browser_metadata(),
		) ) );
	}
	if ( is_user_logged_in() ) {
		cooklook_enqueue_script( 'favorite', 'static/js/favorite_recipes.js' );
		wp_localize_script( 'favorite', 'favorite_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'cooklook_favorites' ) ) );
	}
	if ( is_singular( 'recipe' ) && is_user_logged_in() ) {
		cooklook_enqueue_script( 'recipe-rating-script', 'static/js/recipe-rating.js' );
		wp_localize_script( 'recipe-rating-script', 'rating', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'cooklook_rating' ) ) );
	}
	if ( is_singular() && comments_open() ) {
		cooklook_enqueue_script( 'ajax-comment', 'static/js/ajax-comment.js' );
		wp_localize_script( 'ajax-comment', 'ajax_comment_params', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'cooklook_comment' ),
		) );
	}
}
add_action( 'wp_enqueue_scripts', 'load_scripts' );
