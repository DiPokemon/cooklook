<?php
defined( 'ABSPATH' ) || exit;

function load_styles() {
	$styles = array(
		'style_min' => 'static/css/style.min.css',
		'cooklook-layout' => 'static/css/layout.css',
		'slick' => 'static/libs/slick/slick.min.css',
		'slick_theme' => 'static/libs/slick/slick-theme.min.css',
	);
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

function load_custom_admin_style() {
	$path = 'static/css/admin-styles.css';
	wp_enqueue_style( 'custom_admin_css', get_template_directory_uri() . '/' . $path, array(), cooklook_asset_version( $path ) );
}
add_action( 'admin_enqueue_scripts', 'load_custom_admin_style' );

function custom_login_styles() {
	$path = 'static/css/style.min.css';
	wp_enqueue_style( 'custom-login', get_template_directory_uri() . '/' . $path, array(), cooklook_asset_version( $path ) );
}
add_action( 'login_enqueue_scripts', 'custom_login_styles' );
