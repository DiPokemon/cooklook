<?php
if (! defined ('ABSPATH')){
    exit;
}

// Carbon Fields initialization
add_action( 'after_setup_theme', 'crb_load' );
function crb_load() {
    require_once( get_template_directory().'/vendor/autoload.php' );
    \Carbon_Fields\Carbon_Fields::boot();
    new \Carbon_Fields_Yoast\Carbon_Fields_Yoast;
}

add_action( 'carbon_fields_register_fields', 'register_custom_fields' );
function register_custom_fields() {
	require get_template_directory() . '/inc/custom-fields/theme-options.php';
	require get_template_directory() . '/inc/custom-fields/post-options.php';
    require get_template_directory() . '/inc/custom-fields/page-options.php';
    //require get_template_directory() . '/inc/custom-fields/category-options.php';
}

add_action( 'admin_enqueue_scripts', 'crb_enqueue_admin_scripts' );
function crb_enqueue_admin_scripts() {
	wp_enqueue_script( 'crb-admin', get_stylesheet_directory_uri() . '/static/js/admin.js', array( 'carbon-fields-yoast' ) );
}