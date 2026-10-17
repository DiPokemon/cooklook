<?php
/** Yoast defaults; individual editorial metadata always takes precedence. */
defined( 'ABSPATH' ) || exit;

function cooklook_home_intro() {
	return __( 'Выбирайте блюда по категориям, кухне и ингредиентам. В рецептах — список продуктов, время приготовления и пошаговые инструкции.', 'cooklook' );
}

function cooklook_catalog_intro() {
	return __( 'Найдите рецепт по ингредиентам, категории блюда и кухне. Используйте фильтры, чтобы включить нужные продукты или исключить неподходящие.', 'cooklook' );
}

/** Use only pagination variables that actually select content in this context. */
function cooklook_seo_page_number() {
	if ( is_404() ) { return 1; }
	if ( is_page_template( 'archive.php' ) ) {
		return max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	}
	if ( is_singular() ) {
		return max( 1, (int) get_query_var( 'page' ), get_option( 'page_comments' ) ? (int) get_query_var( 'cpage' ) : 0 );
	}
	return max( 1, (int) get_query_var( 'paged' ) );
}

/** Describe the dish rather than repeating its first instruction. */
function cooklook_recipe_summary( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || post_password_required( $post ) ) { return ''; }
	$text = cooklook_recipe_plain_text( $post->post_excerpt ?: $post->post_content );
	if ( '' !== $text ) { return wp_trim_words( $text, 40, '…' ); }
	$text = sprintf( __( 'Рецепт «%s».', 'cooklook' ), get_the_title( $post_id ) );
	if ( cooklook_recipe_rows( $post_id, 'ingridients' ) && cooklook_recipe_rows( $post_id, 'recipe_step' ) ) {
		$text .= ' ' . __( 'Ингредиенты и пошаговое приготовление.', 'cooklook' );
	}
	return wp_strip_all_tags( $text );
}

function cooklook_seo_metadata_defaults( $page = null ) {
	$title = ''; $description = '';
	if ( is_404() || is_search() ) { return array(); }
	if ( is_front_page() || is_page_template( 'home.php' ) ) {
		$title = __( 'Кулинарные рецепты с пошаговыми инструкциями', 'cooklook' );
		$description = cooklook_home_intro();
	} elseif ( is_singular( 'recipe' ) && ! post_password_required() ) {
		$title = get_the_title( get_queried_object_id() ) . ' — ' . __( 'пошаговый рецепт', 'cooklook' );
		$description = cooklook_recipe_summary( get_queried_object_id() );
	} elseif ( is_tax( array( 'recipe_category', 'recipe_tags' ) ) ) {
		$term = get_queried_object();
		$title = 'recipe_tags' === $term->taxonomy ? sprintf( __( 'Рецепты с ингредиентом «%s»', 'cooklook' ), $term->name ) : $term->name . ' — ' . __( 'рецепты', 'cooklook' );
		$description = wp_trim_words( cooklook_recipe_plain_text( term_description( $term->term_id, $term->taxonomy ) ), 40, '…' );
		if ( '' === trim( $description ) ) {
			$description = $title . '. ' . __( 'Выберите блюдо, посмотрите состав и пошаговый способ приготовления.', 'cooklook' );
		}
	} elseif ( is_post_type_archive( 'recipe' ) || is_page_template( 'archive.php' ) ) {
		$title = __( 'Каталог рецептов по ингредиентам и категориям', 'cooklook' );
		$description = cooklook_catalog_intro();
	}
	if ( ! $title ) { return array(); }
	$page = null === $page ? cooklook_seo_page_number() : $page;
	if ( '%page%' === $page || $page > 1 ) {
		$label = sprintf( __( 'Страница %s', 'cooklook' ), $page );
		$title .= ' — ' . $label;
		$description = $label . '. ' . $description;
	}
	return array( 'title' => wp_strip_all_tags( $title ) . ' | ' . get_bloginfo( 'name' ), 'description' => $description );
}

function cooklook_seo_has_manual_metadata( $field ) {
	if ( is_singular() ) {
		return '' !== trim( (string) get_post_meta( get_queried_object_id(), '_yoast_wpseo_' . $field, true ) );
	}
	if ( is_tax() && class_exists( 'WPSEO_Taxonomy_Meta' ) ) {
		$term = get_queried_object();
		return '' !== trim( (string) WPSEO_Taxonomy_Meta::get_term_meta( $term, $term->taxonomy, 'metadesc' === $field ? 'desc' : $field ) );
	}
	return false;
}

function cooklook_seo_title( $title ) {
	$defaults = cooklook_seo_metadata_defaults();
	return isset( $defaults['title'] ) && ! cooklook_seo_has_manual_metadata( 'title' ) ? $defaults['title'] : $title;
}
add_filter( 'wpseo_title', 'cooklook_seo_title' );

function cooklook_seo_description( $description ) {
	$defaults = cooklook_seo_metadata_defaults();
	return isset( $defaults['description'] ) && ! cooklook_seo_has_manual_metadata( 'metadesc' ) ? $defaults['description'] : $description;
}
add_filter( 'wpseo_metadesc', 'cooklook_seo_description' );

/**
 * Set defaults before Yoast derives WebPage and social metadata from them.
 * Presenter-only filters leave the graph and Open Graph on the old templates.
 * Yoast still owns explicit social values, robots, canonicals and serialization.
 */
function cooklook_seo_presentation( $presentation ) {
	$defaults = cooklook_seo_metadata_defaults();
	if ( isset( $defaults['title'] ) && ! cooklook_seo_has_manual_metadata( 'title' ) ) {
		$presentation->title = $defaults['title'];
	}
	if ( isset( $defaults['description'] ) && ! cooklook_seo_has_manual_metadata( 'metadesc' ) ) {
		$presentation->meta_description = $defaults['description'];
	}
	return $presentation;
}
add_filter( 'wpseo_frontend_presentation', 'cooklook_seo_presentation' );

/** AJAX navigation keeps the displayed page context in sync without a second SEO owner. */
function cooklook_catalog_browser_metadata() {
	$data = array( 'first' => cooklook_seo_metadata_defaults( 1 ), 'paged' => cooklook_seo_metadata_defaults( '%page%' ), 'page_label' => __( 'Страница %s', 'cooklook' ) );
	foreach ( array( 'title' => 'title', 'description' => 'metadesc' ) as $key => $field ) {
		if ( cooklook_seo_has_manual_metadata( $field ) || ! defined( 'WPSEO_VERSION' ) ) {
			unset( $data['first'][ $key ], $data['paged'][ $key ] );
		}
	}
	foreach ( array( 'first' => 1, 'paged' => 2 ) as $key => $page ) {
		ob_start(); breadcrumbs( $page ); $data[ $key ]['breadcrumbs'] = ob_get_clean();
	}
	return $data;
}
