<?php
/** Shared, site-scoped directory of regions available in public recipes. */
defined( 'ABSPATH' ) || exit;

function cooklook_recipe_regions_cache_key() {
	return 'cooklook_recipe_regions_v1_' . md5( (string) get_option( 'cooklook_recipe_regions_version', '0' ) );
}

function cooklook_recipe_regions() {
	$key = cooklook_recipe_regions_cache_key();
	$regions = get_transient( $key );
	// An empty directory is also a cache hit. Preserve SQL collation, order and legacy values.
	if ( is_array( $regions ) ) { return $regions; }

	global $wpdb;
	$regions = $wpdb->get_col( "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_recipe_region' AND p.post_type = 'recipe' AND p.post_status = 'publish' AND p.post_password = '' ORDER BY pm.meta_value" );
	// wpdb may return an empty array on failure. Do not cache that as a valid empty directory.
	if ( '' !== $wpdb->last_error ) { return array(); }
	set_transient( $key, $regions, HOUR_IN_SECONDS );
	return $regions;
}

/** Keep accepted legacy spellings and recent values selectable after a full-page load. */
function cooklook_recipe_region_choices( $selected = '' ) {
	$regions = array_values( array_filter( cooklook_recipe_regions(), function ( $region ) {
		return is_scalar( $region ) && '' !== (string) $region;
	} ) );
	// SQL uses the site's collation; an accepted lowercase alias may not equal an option.
	// The directory can also predate a newly added value. Do not change its shared cache.
	if ( '' !== $selected && ! in_array( $selected, $regions, true ) ) { $regions[] = $selected; }
	return $regions;
}

function cooklook_invalidate_recipe_regions() {
	$old_key = cooklook_recipe_regions_cache_key();
	// A concurrent builder may still save the old snapshot. Its old key is no longer read.
	update_option( 'cooklook_recipe_regions_version', wp_generate_uuid4(), false );
	delete_transient( $old_key );
}

function cooklook_recipe_regions_meta_changed( $meta_ids, $post_id, $meta_key ) {
	// Also covers bulk metadata deletion (object ID may be 0). Views/ratings never invalidate.
	if ( 0 === strcasecmp( '_recipe_region', $meta_key ) ) { cooklook_invalidate_recipe_regions(); }
}
add_action( 'added_post_meta', 'cooklook_recipe_regions_meta_changed', 10, 3 );
add_action( 'updated_post_meta', 'cooklook_recipe_regions_meta_changed', 10, 3 );
add_action( 'deleted_post_meta', 'cooklook_recipe_regions_meta_changed', 10, 3 );

function cooklook_recipe_region_is_public( $post ) {
	return $post && 'recipe' === $post->post_type && 'publish' === $post->post_status && '' === $post->post_password;
}

function cooklook_recipe_regions_post_saved( $post_id, $post, $update, $post_before ) {
	if ( cooklook_recipe_region_is_public( $post ) !== cooklook_recipe_region_is_public( $post_before ) ) {
		cooklook_invalidate_recipe_regions();
	}
}
add_action( 'wp_after_insert_post', 'cooklook_recipe_regions_post_saved', 10, 4 );

function cooklook_recipe_regions_post_deleted( $post_id, $post ) {
	if ( cooklook_recipe_region_is_public( $post ) ) { cooklook_invalidate_recipe_regions(); }
}
add_action( 'deleted_post', 'cooklook_recipe_regions_post_deleted', 10, 2 );
add_action( 'after_switch_theme', 'cooklook_invalidate_recipe_regions' );
