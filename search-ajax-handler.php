<?php
defined( 'ABSPATH' ) || exit;

function my_ajax_search() {
	cooklook_verify_ajax_nonce( 'cooklook_search' );
	$term = cooklook_request_text( $_POST, 'search' );
	if ( mb_strlen( $term ) < 2 || mb_strlen( $term ) > 120 ) {
		wp_die();
	}
	$args = array( 's' => $term, 'post_type' => 'recipe', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => 5, 'no_found_rows' => true );
	if ( function_exists( 'relevanssi_do_query' ) ) {
		$query = new WP_Query();
		$query->parse_query( $args );
		relevanssi_do_query( $query );
	} else {
		$query = new WP_Query( $args );
	}
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			// Keep the same public visibility rules even if a search plugin changes the query.
			if ( 'recipe' === get_post_type() && 'publish' === get_post_status() && ! post_password_required() ) {
				get_template_part( 'template-parts/ajax-search-result-item' );
			}
		}
	} else {
		echo '<p>' . esc_html__( 'Рецепты не найдены.', 'cooklook' ) . '</p>';
	}
	wp_reset_postdata();
	// Count complete queries on regular search pages, not each autocomplete keystroke.
	wp_die();
}
add_action( 'wp_ajax_ajax_search', 'my_ajax_search' );
add_action( 'wp_ajax_nopriv_ajax_search', 'my_ajax_search' );
