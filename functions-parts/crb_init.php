<?php
if (! defined ('ABSPATH')){
    exit;
}

// Carbon Fields initialization
add_action( 'after_setup_theme', 'cooklook_load_carbon_fields' );
function cooklook_load_carbon_fields() {
    require_once( get_template_directory().'/vendor/autoload.php' );
    \Carbon_Fields\Carbon_Fields::boot();
    new \Carbon_Fields_Yoast\Carbon_Fields_Yoast;
}

add_action( 'carbon_fields_register_fields', 'cooklook_register_custom_fields' );
function cooklook_register_custom_fields() {
	require get_template_directory() . '/inc/custom-fields/theme-options.php';
	require get_template_directory() . '/inc/custom-fields/post-options.php';
    require get_template_directory() . '/inc/custom-fields/page-options.php';
    //require get_template_directory() . '/inc/custom-fields/category-options.php';
}

add_action( 'admin_enqueue_scripts', 'cooklook_carbon_admin_scripts' );
function cooklook_carbon_admin_scripts() {
	cooklook_enqueue_script( 'crb-admin', 'static/js/admin.js', array( 'jquery', 'carbon-fields-yoast' ) );
}
