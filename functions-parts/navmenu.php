<?php
defined( 'ABSPATH' ) || exit;

function add_additional_class_on_li( $classes, $item, $args ) {
	if ( ! empty( $args->add_li_class ) ) {
		$classes[] = sanitize_html_class( $args->add_li_class );
	}
	return $classes;
}
add_filter( 'nav_menu_css_class', 'add_additional_class_on_li', 10, 3 );

function add_menu_link_class( $attributes, $item, $args ) {
	if ( ! empty( $args->link_class ) ) {
		$attributes['class'] = trim( ( isset( $attributes['class'] ) ? $attributes['class'] : '' ) . ' ' . sanitize_html_class( $args->link_class ) );
	}
	return $attributes;
}
add_filter( 'nav_menu_link_attributes', 'add_menu_link_class', 10, 3 );
// The menu container owns its schema. Do not reparse fragments into nested HTML documents.
