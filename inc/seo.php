<?php
/** Indexing policy. Use the active SEO provider; never print a second meta tag. */
defined( 'ABSPATH' ) || exit;

function cooklook_seo_service_slugs() {
	return array( 'account', 'my-favorites', 'rcl-postedit' );
}

function cooklook_seo_service_templates() {
	return array( 'page-account.php', 'my-favorites.php' );
}

function cooklook_seo_is_service_page( $post ) {
	return $post && 'page' === $post->post_type && (
		in_array( $post->post_name, cooklook_seo_service_slugs(), true ) ||
		in_array( get_page_template_slug( $post->ID ), cooklook_seo_service_templates(), true )
	);
}

function cooklook_seo_is_excluded_page( $post ) {
	if ( cooklook_seo_is_service_page( $post ) ) {
		return true;
	}
	// Keep a repurposed sample-page public; only the verified default demo is excluded.
	return $post && 'page' === $post->post_type && 'sample-page' === $post->post_name &&
		( false !== strpos( $post->post_content, 'Это пример страницы.' ) || false !== strpos( $post->post_content, 'This is an example page.' ) );
}

function cooklook_seo_noindex() {
	if ( is_search() || is_404() || ( is_singular( 'page' ) && cooklook_seo_is_excluded_page( get_queried_object() ) ) ) {
		return true;
	}
	if ( ! cooklook_is_recipe_catalog() ) {
		return false;
	}
	$filters = cooklook_recipe_filters( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only public filters.
	return $filters['category_id'] || $filters['subcategory_id'] || '' !== $filters['region'] || $filters['include_ingredients'] || $filters['exclude_ingredients'] || 'date' !== $filters['sort_by'];
}

function cooklook_seo_yoast_robots( $robots ) {
	if ( cooklook_seo_noindex() ) {
		$robots['index'] = 'noindex';
	}
	return $robots;
}
add_filter( 'wpseo_robots_array', 'cooklook_seo_yoast_robots', 99 );

/** Fallback also preserves an existing nofollow from the development environment. */
function cooklook_seo_wp_robots( $robots ) {
	if ( cooklook_seo_noindex() ) {
		unset( $robots['index'] );
		$robots['noindex'] = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'cooklook_seo_wp_robots', PHP_INT_MAX );

function cooklook_seo_service_headers() {
	if ( is_singular( 'page' ) && cooklook_seo_is_service_page( get_queried_object() ) ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
	}
}
add_action( 'template_redirect', 'cooklook_seo_service_headers', 0 );

function cooklook_seo_service_page_ids() {
	static $ids;
	if ( null === $ids ) {
		$args = array( 'post_type' => 'page', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'no_found_rows' => true );
		$slugs = get_posts( array_merge( $args, array( 'post_name__in' => array_merge( cooklook_seo_service_slugs(), array( 'sample-page' ) ) ) ) );
		$templates = get_posts( array_merge( $args, array( 'meta_query' => array( array( 'key' => '_wp_page_template', 'value' => cooklook_seo_service_templates(), 'compare' => 'IN' ) ) ) ) );
		$ids = array_values( array_unique( array_map( 'absint', array_merge( $slugs, $templates ) ) ) );
		$ids = array_values( array_filter( $ids, function ( $id ) { return cooklook_seo_is_excluded_page( get_post( $id ) ); } ) );
	}
	return $ids;
}

function cooklook_seo_sitemap_exclude( $ids ) {
	return array_values( array_unique( array_merge( $ids, cooklook_seo_service_page_ids() ) ) );
}
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', 'cooklook_seo_sitemap_exclude' );

function cooklook_seo_core_sitemap_query( $args, $post_type ) {
	if ( 'page' === $post_type ) {
		$args['post__not_in'] = cooklook_seo_sitemap_exclude( isset( $args['post__not_in'] ) ? $args['post__not_in'] : array() );
	}
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'cooklook_seo_core_sitemap_query', 10, 2 );
