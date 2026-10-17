<?php
/** Carbon Fields reads backed by the standard WordPress post metadata cache. */
defined( 'ABSPATH' ) || exit;

class Cooklook_Cached_Post_Meta_Datastore extends \Carbon_Fields\Datastore\Post_Meta_Datastore {

	/**
	 * Reuse metadata primed in bulk by WP_Query instead of querying each complex field.
	 * Carbon Fields still decodes its own keys, groups, values and defaults. Inherited
	 * writes use the WordPress metadata API, which invalidates this same cache.
	 *
	 * @param \Carbon_Fields\Field\Field $field Field being loaded.
	 * @param array                     $storage_key_patterns Carbon Fields key patterns.
	 * @return \stdClass[]
	 */
	protected function get_storage_array( \Carbon_Fields\Field\Field $field, $storage_key_patterns ) {
		// With no key, WordPress returns raw values, including serialized strings.
		$meta          = get_post_meta( $this->get_object_id() );
		$storage_array = array();
		if ( is_array( $meta ) ) {
			ksort( $meta, SORT_STRING );
			foreach ( $meta as $key => $values ) {
				if ( ! $this->key_toolset->storage_key_matches_any_pattern( (string) $key, $storage_key_patterns ) ) {
					continue;
				}
				foreach ( $values as $value ) {
					$storage_array[] = (object) array( 'key' => $key, 'value' => $value );
				}
			}
		}
		return apply_filters( 'carbon_fields_datastore_storage_array', $storage_array, $this, $storage_key_patterns );
	}
}
