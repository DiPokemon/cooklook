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
	$result = array_values( array_unique( $result ) );
	sort( $result, SORT_STRING );
	return $result;
}

/** With plain permalinks recipe_category is a route slug, not the legacy facet ID. */
function cooklook_recipe_filter_request( $request, $query = null ) {
	global $wp_rewrite, $wp_query;
	$query = $query ?: $wp_query;
	if ( isset( $wp_rewrite ) && ! $wp_rewrite->using_permalinks() && $query && $query->is_tax( 'recipe_category' ) ) {
		unset( $request['recipe_category'] );
	}
	return $request;
}

function cooklook_catalog_route_args() {
	global $wp_rewrite;
	if ( $wp_rewrite->using_permalinks() ) { return array(); }
	if ( is_tax( array( 'recipe_category', 'recipe_tags' ) ) ) {
		$term = get_queried_object();
		return array( $term->taxonomy => $term->slug );
	}
	return is_page_template( 'archive.php' ) ? array( 'page_id' => get_queried_object_id() ) : array( 'post_type' => 'recipe' );
}

function cooklook_recipe_filters( $request ) {
	$request = cooklook_recipe_filter_request( $request );
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

/** Restore accepted deep links even when a selection is outside the usual child list. */
function cooklook_recipe_category_choices( $parent, $selected = 0 ) {
	$terms = null === $parent ? array() : get_terms( array( 'taxonomy' => 'recipe_category', 'parent' => absint( $parent ), 'hide_empty' => false ) );
	$terms = is_array( $terms ) ? $terms : array();
	if ( $selected && ! in_array( (int) $selected, array_map( 'intval', wp_list_pluck( $terms, 'term_id' ) ), true ) ) {
		$term = get_term( $selected, 'recipe_category' );
		if ( $term instanceof WP_Term ) { $terms[] = $term; }
	}
	return $terms;
}

function cooklook_is_recipe_catalog() {
	return is_post_type_archive( 'recipe' ) || is_tax( array( 'recipe_category', 'recipe_tags' ) ) || is_page_template( 'archive.php' );
}

/** Validate before answering either a full-page request or an AJAX request. */
function cooklook_validate_recipe_filters( $request, $filters, $context = array() ) {
	$request = cooklook_recipe_filter_request( $request );
	$invalid = new WP_Error( 'invalid_recipe_filters', __( 'Такое сочетание фильтров недоступно. Выберите существующие категории и ингредиенты.', 'cooklook' ) );
	foreach ( array( 'category_id', 'subcategory_id', 'recipe_category', 'recipe_subcategory', 'paged', 'current_category' ) as $key ) {
		if ( ! isset( $request[ $key ] ) ) {
			continue;
		}
		$value = $request[ $key ];
		if ( ! is_scalar( $value ) || ( ! in_array( (string) $value, array( '', 'all' ), true ) && ! ctype_digit( (string) $value ) ) ) {
			return $invalid;
		}
		if ( 'paged' === $key && ( '' === (string) $value || ! ctype_digit( (string) $value ) || (int) $value < 1 ) ) {
			return $invalid;
		}
	}
	foreach ( array( 'region', 'recipe_region', 'sort_by', 'current_tag' ) as $key ) {
		if ( isset( $request[ $key ] ) && ! is_scalar( $request[ $key ] ) ) {
			return $invalid;
		}
	}
	if ( isset( $request['sort_by'] ) && ! in_array( (string) $request['sort_by'], array( '', 'date', 'recipe_views', 'recipe_time' ), true ) ) {
		return $invalid;
	}
	foreach ( array( 'include_ingredients', 'exclude_ingredients' ) as $key ) {
		$raw = isset( $request[ $key ] ) ? $request[ $key ] : array();
		$raw = is_array( $raw ) ? $raw : explode( ',', (string) $raw );
		if ( count( $raw ) > 20 ) {
			return $invalid;
		}
		foreach ( $raw as $value ) {
			if ( ! is_scalar( $value ) || ( '' !== trim( (string) $value ) && '' === sanitize_title( wp_unslash( (string) $value ) ) ) ) {
				return $invalid;
			}
		}
	}
	$included = $filters['include_ingredients'];
	if ( ! empty( $context['current_tag'] ) ) {
		$included[] = $context['current_tag'];
	}
	if ( array_intersect( $included, $filters['exclude_ingredients'] ) ) {
		return $invalid;
	}
	$slugs = array_unique( array_merge( $included, $filters['exclude_ingredients'] ) );
	if ( $slugs ) {
		$existing = get_terms( array( 'taxonomy' => 'recipe_tags', 'slug' => $slugs, 'fields' => 'slugs', 'hide_empty' => false ) );
		if ( is_wp_error( $existing ) || array_diff( $slugs, $existing ) ) {
			return $invalid;
		}
	}
	$categories = array_unique( array_filter( array( $filters['category_id'], $filters['subcategory_id'], isset( $context['current_category'] ) ? absint( $context['current_category'] ) : 0 ) ) );
	foreach ( $categories as $id ) {
		$term = get_term( $id, 'recipe_category' );
		if ( ! $term || is_wp_error( $term ) ) {
			return $invalid;
		}
	}
	if ( $filters['category_id'] && $filters['subcategory_id'] && ! term_is_ancestor_of( $filters['category_id'], $filters['subcategory_id'], 'recipe_category' ) ) {
		return $invalid;
	}
	if ( '' !== $filters['region'] ) {
		global $wpdb;
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT 1 FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_recipe_region' AND pm.meta_value = %s AND p.post_type = 'recipe' AND p.post_status = 'publish' AND p.post_password = '' LIMIT 1",
			$filters['region']
		) );
		if ( ! $exists ) {
			return $invalid;
		}
	}
	return true;
}

/** Reuse the page-template query so validation runs before headers are sent. */
function cooklook_catalog_query() {
	global $wp_query;
	static $page_query;
	if ( ! is_page_template( 'archive.php' ) ) {
		return $wp_query;
	}
	if ( null === $page_query ) {
		$filters = cooklook_recipe_filters( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only public filters.
		$filters['paged'] = max( $filters['paged'], absint( get_query_var( 'paged' ) ), absint( get_query_var( 'page' ) ) );
		$page_query = new WP_Query( cooklook_recipe_query_args( $filters ) );
	}
	return $page_query;
}

function cooklook_validate_catalog_request() {
	if ( ! cooklook_is_recipe_catalog() ) {
		return;
	}
	$request = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only public filters.
	$valid = cooklook_validate_recipe_filters( $request, cooklook_recipe_filters( $request ), cooklook_recipe_context() );
	$query = cooklook_catalog_query();
	if ( is_wp_error( $valid ) || ( (int) $query->get( 'paged' ) > 1 && (int) $query->get( 'paged' ) > (int) $query->max_num_pages ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		remove_action( 'template_redirect', 'redirect_canonical' );
	}
}
add_action( 'template_redirect', 'cooklook_validate_catalog_request', 0 );

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
		$args['cooklook_recipe_region'] = $filters['region'];
	}
	if ( 'date' !== $filters['sort_by'] ) {
		$args['cooklook_sort_meta'] = '_' . $filters['sort_by'];
	}
	return $args;
}

/** Keep this scalar key exact: Carbon Fields expands registered meta_query keys to LIKE. */
function cooklook_recipe_region_where( $where, $query ) {
	$region = $query->get( 'cooklook_recipe_region' );
	if ( '' === $region ) { return $where; }
	if ( ! is_string( $region ) ) { return $where . ' AND 1=0'; }
	global $wpdb;
	// Membership preserves duplicate region rows without multiplying recipes or needing GROUP BY.
	return $where . $wpdb->prepare(
		" AND {$wpdb->posts}.ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_recipe_region' AND meta_value = %s)",
		$region
	);
}
add_filter( 'posts_where', 'cooklook_recipe_region_where', 10, 2 );

function cooklook_recipe_sort_clauses( $clauses, $query ) {
	global $wpdb;
	$key = $query->get( 'cooklook_sort_meta' );
	if ( ! in_array( $key, array( '_recipe_views', '_recipe_time' ), true ) ) {
		return $clauses;
	}
	// Aggregate once by indexed meta_key; a per-recipe join/subquery reads every recipe's metadata.
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
		$filters = cooklook_recipe_filters( cooklook_recipe_filter_request( $_GET, $query ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only public filters.
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

/** Shared by server links and the browser; also accepts an AJAX page URL. */
function cooklook_recipe_pagination_pattern( $base_url = '' ) {
	global $wp_rewrite;
	$url = remove_query_arg( array( 'paged', 'page' ), $base_url ?: get_pagenum_link( 1, false ) );
	$url = preg_replace( '/#.*$/', '', $url );
	if ( ! $wp_rewrite->using_permalinks() ) {
		return array( 'base' => $url . '%_%', 'format' => ( false === strpos( $url, '?' ) ? '?' : '&' ) . 'paged=%#%' );
	}
	$parts = explode( '?', $url, 2 );
	$path = preg_replace( '~/' . preg_quote( $wp_rewrite->pagination_base, '~' ) . '/[0-9]+/?$~', '', $parts[0] );
	$path = user_trailingslashit( $path, 'paged' );
	$format = ( '/' === substr( $path, -1 ) ? '' : '/' ) . user_trailingslashit( $wp_rewrite->pagination_base . '/%#%', 'paged' );
	return array( 'base' => $path . '%_%' . ( isset( $parts[1] ) ? '?' . $parts[1] : '' ), 'format' => $format );
}

function cooklook_recipe_pagination( $query, $base_url = '' ) {
	if ( $query->max_num_pages < 2 ) {
		return '';
	}
	$base_url = $base_url ?: get_pagenum_link( 1, false );
	$pattern = cooklook_recipe_pagination_pattern( $base_url );
	// paginate_links otherwise copies admin-ajax.php query parameters into every link.
	$page_link = function () use ( $base_url ) { return $base_url; };
	add_filter( 'get_pagenum_link', $page_link, PHP_INT_MAX );
	try {
		return paginate_links( array(
			'base'      => $pattern['base'],
			'format'    => $pattern['format'],
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
