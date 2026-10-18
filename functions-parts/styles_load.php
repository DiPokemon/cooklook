<?php
defined( 'ABSPATH' ) || exit;

function cooklook_enqueue_fonts() {
	$path = 'static/css/fonts.css';
	wp_enqueue_style( 'cooklook-fonts', get_template_directory_uri() . '/' . $path, array(), cooklook_asset_version( $path ) );
}

function load_styles() {
	$styles = array(
		'style_min' => 'static/css/frontend.css',
	);
	if ( cooklook_has_recipe_slider() ) {
		$styles['slick'] = 'static/libs/slick/cooklook-slick.css';
	}
	if ( is_page( 'account' ) || is_page_template( 'page-account.php' ) ) {
		$styles['cooklook-account'] = 'static/css/account.css';
	}
	if ( is_post_type_archive( 'recipe' ) || is_tax( array( 'recipe_category', 'recipe_tags' ) ) || is_page_template( 'archive.php' ) ) {
		$styles['select2'] = 'static/libs/select2/select2.min.css';
		$styles['cooklook-catalog'] = 'static/css/catalog.css';
	}
	foreach ( $styles as $handle => $path ) {
		wp_enqueue_style( $handle, get_template_directory_uri() . '/' . $path, array(), cooklook_asset_version( $path ) );
	}
}
add_action( 'wp_enqueue_scripts', 'load_styles' );

/**
 * Let WordPress inline only the small, known render-blocking stylesheets.
 * Core preserves their queue order, rewrites relative URLs and respects its
 * total inline-size limit. Large styles keep their reusable browser cache.
 */
function cooklook_inline_small_styles() {
	if ( version_compare( get_bloginfo( 'version' ), '5.9', '<' ) ) {
		return;
	}
	$styles = wp_styles();
	$files  = array(
		'slick'            => array( get_template_directory(), get_template_directory_uri(), 'static/libs/slick/cooklook-slick.css' ),
		'cooklook-catalog' => array( get_template_directory(), get_template_directory_uri(), 'static/css/catalog.css' ),
		'relevanssi-live-search' => array( WP_PLUGIN_DIR, plugins_url(), 'relevanssi-live-ajax-search/assets/styles/style.css' ),
	);
	foreach ( $files as $handle => $file ) {
		$path = $file[0] . '/' . $file[2];
		$src  = $file[1] . '/' . $file[2];
		// A replacement registered by a child theme/plugin must retain its own CSS.
		if ( isset( $styles->registered[ $handle ] ) && wp_style_is( $handle, 'enqueued' ) && $src === $styles->registered[ $handle ]->src && is_readable( $path ) && filesize( $path ) <= 8192 ) {
			wp_style_add_data( $handle, 'path', $path );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'cooklook_inline_small_styles', 100 );

function load_custom_admin_style( $hook ) {
	if ( ! in_array( $hook, array( 'toplevel_page_recipe-manager', 'toplevel_page_search-moderation' ), true ) ) {
		return;
	}
	$path = 'static/css/admin-styles.css';
	wp_enqueue_style( 'custom_admin_css', get_template_directory_uri() . '/' . $path, array(), cooklook_asset_version( $path ) );
}
add_action( 'admin_enqueue_scripts', 'load_custom_admin_style' );

function custom_login_styles() {
	cooklook_enqueue_fonts();
	$path = 'static/css/style.min.css';
	wp_enqueue_style( 'custom-login', get_template_directory_uri() . '/' . $path, array(), cooklook_asset_version( $path ) );
}
add_action( 'login_enqueue_scripts', 'custom_login_styles' );
