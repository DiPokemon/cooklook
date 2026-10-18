<?php
/** Responsive images without moving or rewriting existing media URLs. */
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/media-webp.php';

function cooklook_post_image( $post_id, $size = 'medium_large', $attributes = array() ) {
	static $placeholder_size;
	$id = get_post_thumbnail_id( $post_id );
	$attributes = array_merge( array( 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 768px) calc(100vw - 40px), (max-width: 1024px) calc((100vw - 60px) / 2), 360px' ), $attributes );
	if ( $id && wp_attachment_is_image( $id ) ) {
		$attributes['alt'] = get_post_meta( $id, '_wp_attachment_image_alt', true ) ?: get_the_title( $post_id );
		return cooklook_webp_picture( $id, $size, $attributes, wp_get_attachment_image( $id, $size, false, $attributes ) );
	}
	// A placeholder is decorative and must not claim to depict the dish.
	$path = '/static/img/no_image.png';
	if ( null === $placeholder_size ) { $placeholder_size = wp_getimagesize( get_template_directory() . $path ); }
	return '<img src="' . esc_url( get_template_directory_uri() . $path ) . '" alt="" width="' . (int) $placeholder_size[0] . '" height="' . (int) $placeholder_size[1] . '" loading="' . esc_attr( $attributes['loading'] ) . '" decoding="async">';
}

function cooklook_category_image( $term_id, $priority = false ) {
	$id = absint( carbon_get_term_meta( $term_id, 'category_image_id' ) );
	$url = carbon_get_term_meta( $term_id, 'category_image' );
	if ( ! $id && $url ) { $id = attachment_url_to_postid( $url ); }
	$attributes = array( 'alt' => '', 'loading' => $priority ? 'eager' : 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 767px) 40vw, 220px' );
	if ( $id && wp_attachment_is_image( $id ) ) {
		return wp_get_attachment_image( $id, 'medium', false, $attributes );
	}
	// External/legacy URL-only images remain supported; CSS reserves their display box.
	return $url ? '<img src="' . esc_url( $url ) . '" alt="" loading="' . esc_attr( $attributes['loading'] ) . '" decoding="async">' : '';
}

/** Static banner geometry and optional prebuilt WebP variants; JPEG stays as fallback. */
function cooklook_banner( $name, $sizes = '(max-width: 1024px) calc(100vw - 40px), 360px', $loading = 'lazy' ) {
	static $manifest;
	if ( null === $manifest ) {
		$file = get_template_directory() . '/static/img/adv/variants.json';
		$manifest = is_file( $file ) ? json_decode( file_get_contents( $file ), true ) : array();
	}
	$name = sanitize_key( $name );
	$path = '/static/img/adv/' . $name . '.jpg';
	$dimensions = wp_getimagesize( get_template_directory() . $path );
	if ( ! $dimensions ) { return ''; }
	$html = '<picture>';
	if ( ! empty( $manifest[ $name ]['sources'] ) ) {
		$sources = array();
		foreach ( $manifest[ $name ]['sources'] as $width => $file ) {
			$sources[] = get_template_directory_uri() . '/static/img/adv/' . $file . ' ' . (int) $width . 'w';
		}
		$html .= '<source type="image/webp" srcset="' . esc_attr( implode( ', ', $sources ) ) . '" sizes="' . esc_attr( $sizes ) . '">';
	}
	return $html . '<img src="' . esc_url( get_template_directory_uri() . $path ) . '" alt="" width="' . (int) $dimensions[0] . '" height="' . (int) $dimensions[1] . '" loading="' . esc_attr( $loading ) . '" decoding="async"></picture>';
}
