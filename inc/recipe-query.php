<?php
/** One query definition for archives, filtering, sorting and pagination. */
defined( 'ABSPATH' ) || exit;

function cooklook_ingredient_slugs( $value ) {
	$values = is_array( $value ) ? $value : explode( ',', (string) $value );
	$result = array();
	foreach ( array_slice( $values, 0, 20 ) as $slug ) {
		if ( is_scalar( $slug ) ) {
			$slug = sanitize_title( wp_unslash( (string) $slug ) );
			if ( '' !== $slug ) {
				$result[] = $slug;
			}
		}
	}
	return array_values( array_unique( $result ) );
}

function cooklook_recipe_filters( $request ) {
	$sort = cooklook_request_text( $request, 'sort_by', 'date' );
	return array(
		'category_id'         => cooklook_request_id( $request, isset( $request['category_id'] ) ? 'category_id' : 'recipe_category' ),
		'subcategory_id'      => cooklook_request_id( $request, isset( $request['subcategory_id'] ) ? 'subcategory_id' : 'recipe_subcategory' ),
		'region'             => cooklook_request_text( $request, isset( $request['region'] ) ? 'region' : 'recipe_region' ),
		'include_ingredients' => cooklook_ingredient_slugs( isset( $request['include_ingredients'] ) ? $request['include_ingredients'] : array() ),
		'exclude_ingredients' => cooklook_ingredient_slugs( isset( $request['exclude_ingredients'] ) ? $request['exclude_ingredients'] : array() ),
		'sort_by'            => in_array( $sort, array( 'date', 'recipe_views', 'recipe_time' ), true ) ? $sort : 'date',
		'paged'              => max( 1, cooklook_request_id( $request, 'paged' ) ),
	);
}

function cooklook_recipe_context() {
	return array(
		'current_category' => is_tax( 'recipe_category' ) ? get_queried_object_id() : 0,
		'current_tag'      => is_tax( 'recipe_tags' ) ? get_queried_object()->slug : '',
	);
}

function cooklook_recipe_query_args( $filters, $context = array() ) {
	$args = array(
		'post_type'           => 'recipe',
		'post_status'         => 'publish',
		'has_password'        => false,
		'posts_per_page'      => 11,
		'paged'               => $filters['paged'],
		'orderby'             => array( 'date' => 'DESC', 'ID' => 'DESC' ),
		'ignore_sticky_posts' => true,
	);
	$tax_query = array( 'relation' => 'AND' );
	$categories = array( $filters['category_id'], $filters['subcategory_id'], isset( $context['current_category'] ) ? absint( $context['current_category'] ) : 0 );
	foreach ( array_unique( array_filter( $categories ) ) as $category ) {
		$tax_query[] = array( 'taxonomy' => 'recipe_category', 'field' => 'term_id', 'terms' => $category );
	}
	if ( ! empty( $context['current_tag'] ) ) {
		$tax_query[] = array( 'taxonomy' => 'recipe_tags', 'field' => 'slug', 'terms' => $context['current_tag'] );
	}
	foreach ( array( 'include_ingredients' => 'AND', 'exclude_ingredients' => 'NOT IN' ) as $key => $operator ) {
		if ( $filters[ $key ] ) {
			$tax_query[] = array( 'taxonomy' => 'recipe_tags', 'field' => 'slug', 'terms' => $filters[ $key ], 'operator' => $operator );
		}
	}
	if ( count( $tax_query ) > 1 ) {
		$args['tax_query'] = $tax_query;
	}
	if ( '' !== $filters['region'] ) {
		$args['meta_query'][] = array( 'key' => '_recipe_region', 'value' => $filters['region'] );
	}
	if ( 'date' !== $filters['sort_by'] ) {
		$args['cooklook_sort_meta'] = '_' . $filters['sort_by'];
	}
	return $args;
}

function cooklook_recipe_sort_clauses( $clauses, $query ) {
	global $wpdb;
	$key = $query->get( 'cooklook_sort_meta' );
	if ( ! in_array( $key, array( '_recipe_views', '_recipe_time' ), true ) ) {
		return $clauses;
	}
	// Keep older recipes without metadata and tolerate duplicate legacy rows.
	$clauses['join'] .= $wpdb->prepare(
		" LEFT JOIN (SELECT post_id, MAX(CAST(meta_value AS DECIMAL(20,4))) AS value FROM {$wpdb->postmeta} WHERE meta_key = %s GROUP BY post_id) AS cooklook_sort ON {$wpdb->posts}.ID = cooklook_sort.post_id",
		$key
	);
	$direction = '_recipe_time' === $key ? 'ASC' : 'DESC';
	$clauses['orderby'] = "COALESCE(cooklook_sort.value, 0) {$direction}, {$wpdb->posts}.post_date DESC, {$wpdb->posts}.ID DESC";
	return $clauses;
}
add_filter( 'posts_clauses', 'cooklook_recipe_sort_clauses', 10, 2 );

function cooklook_prepare_recipe_archive( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_post_type_archive( 'recipe' ) || $query->is_tax( array( 'recipe_category', 'recipe_tags' ) ) ) {
		$filters = cooklook_recipe_filters( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only public filters.
		$filters['paged'] = max( $filters['paged'], absint( $query->get( 'paged' ) ) );
		foreach ( cooklook_recipe_query_args( $filters ) as $key => $value ) {
			$query->set( $key, $value );
		}
	}
	if ( $query->is_search() ) {
		$query->set( 'post_type', 'recipe' );
		$query->set( 'post_status', 'publish' );
		$query->set( 'has_password', false );
	}
}
add_action( 'pre_get_posts', 'cooklook_prepare_recipe_archive' );

function cooklook_recipe_pagination( $query, $base_url = '' ) {
	if ( $query->max_num_pages < 2 ) {
		return '';
	}
	$base_url = $base_url ?: get_pagenum_link( 1, false );
	// paginate_links otherwise copies admin-ajax.php query parameters into every link.
	$page_link = function () use ( $base_url ) { return $base_url; };
	add_filter( 'get_pagenum_link', $page_link, PHP_INT_MAX );
	try {
		return paginate_links( array(
			'base'      => add_query_arg( 'paged', '%#%', $base_url ),
			'format'    => '',
			'current'   => max( 1, (int) $query->get( 'paged' ) ),
			'total'     => (int) $query->max_num_pages,
			'mid_size'  => 2,
			'prev_text' => __( 'Назад', 'cooklook' ),
			'next_text' => __( 'Далее', 'cooklook' ),
			'add_args'  => false,
		) );
	} finally {
		remove_filter( 'get_pagenum_link', $page_link, PHP_INT_MAX );
	}
}
