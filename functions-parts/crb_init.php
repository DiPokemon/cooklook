<?php
if (! defined ('ABSPATH')){
    exit;
}

// Carbon Fields initialization
add_action( 'after_setup_theme', 'crb_load' );
function crb_load() {
    require_once( get_template_directory().'/vendor/autoload.php' );
    \Carbon_Fields\Carbon_Fields::boot();
}

add_action( 'carbon_fields_register_fields', 'register_custom_fields' );
function register_custom_fields() {
	require get_template_directory() . '/inc/custom-fields/theme-options.php';
	require get_template_directory() . '/inc/custom-fields/post-options.php';
    require get_template_directory() . '/inc/custom-fields/page-options.php';
    //require get_template_directory() . '/inc/custom-fields/category-options.php';
}