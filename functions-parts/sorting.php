<?php
defined( 'ABSPATH' ) || exit;

/** Compatibility action; the catalog client uses filter_recipes for sorting too. */
function sort_posts_function() {
	filter_recipes();
}
add_action( 'wp_ajax_sort_posts', 'sort_posts_function' );
add_action( 'wp_ajax_nopriv_sort_posts', 'sort_posts_function' );
