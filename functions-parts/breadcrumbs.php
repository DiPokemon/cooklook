<?php
defined( 'ABSPATH' ) || exit;

function cooklook_breadcrumb_terms( $term ) {
	$items = array();
	if ( ! ( $term instanceof WP_Term ) ) {
		return $items;
	}
	foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ) as $id ) {
		$parent = get_term( $id, $term->taxonomy );
		if ( $parent instanceof WP_Term ) {
			$items[] = array( $parent->name, get_term_link( $parent ) );
		}
	}
	$items[] = array( $term->name, get_term_link( $term ) );
	return $items;
}

function breadcrumbs( $page = null ) {
	if ( is_front_page() ) {
		return;
	}
	$items = array( array( __( 'Главная', 'cooklook' ), home_url( '/' ) ) );
	$title = '';
	if ( is_singular( 'recipe' ) || is_tax( array( 'recipe_category', 'recipe_tags' ) ) ) {
		$items[] = array( __( 'Каталог рецептов', 'cooklook' ), get_post_type_archive_link( 'recipe' ) );
	}
	if ( is_singular() ) {
		$post = get_queried_object();
		if ( is_singular( 'recipe' ) ) {
			$terms = cooklook_recipe_terms( $post->ID, 'recipe_category' );
			if ( $terms ) {
				$items = array_merge( $items, cooklook_breadcrumb_terms( $terms[0] ) );
			}
		} elseif ( is_page() || is_attachment() ) {
			foreach ( array_reverse( get_post_ancestors( $post ) ) as $id ) {
				$items[] = array( get_the_title( $id ), get_permalink( $id ) );
			}
		} elseif ( 'post' === $post->post_type ) {
			$terms = get_the_category( $post->ID );
			if ( $terms ) {
				$items = array_merge( $items, cooklook_breadcrumb_terms( $terms[0] ) );
			}
		} else {
			$type = get_post_type_object( $post->post_type );
			if ( $type && $type->has_archive ) {
				$items[] = array( $type->labels->name, get_post_type_archive_link( $type->name ) );
			}
		}
		$title = get_the_title( $post );
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();
		$terms = cooklook_breadcrumb_terms( $term );
		if ( $terms ) {
			$current = array_pop( $terms );
			$items = array_merge( $items, $terms );
			$title = $current[0];
		}
	} elseif ( is_search() ) {
		$title = sprintf( __( 'Результаты поиска: «%s»', 'cooklook' ), get_search_query( false ) );
	} elseif ( is_404() ) {
		$title = __( 'Страница не найдена', 'cooklook' );
	} elseif ( is_post_type_archive( 'recipe' ) ) {
		$title = __( 'Каталог рецептов', 'cooklook' );
	} elseif ( is_home() ) {
		$title = get_the_title( get_option( 'page_for_posts' ) );
	} else {
		$title = wp_strip_all_tags( get_the_archive_title() );
	}
	if ( $title ) {
		$items[] = array( $title, '' );
	}
	$page = null === $page ? cooklook_seo_page_number() : $page;
	if ( $page > 1 ) {
		if ( $title ) {
			$items[ count( $items ) - 1 ][1] = remove_query_arg(
				array( 'paged', 'page', 'cpage' ),
				is_singular() ? get_permalink() : get_pagenum_link( 1, false )
			);
		}
		$items[] = array( sprintf( __( 'Страница %d', 'cooklook' ), $page ), '' );
	}
	echo '<div class="page-header__breadcrumbs" itemscope itemtype="https://schema.org/BreadcrumbList">';
	foreach ( $items as $index => $item ) {
		if ( $index ) {
			echo '<span class="breadcrumbs__separator"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M6.66797 5.33325L9.33464 7.99992L6.66797 10.6666" stroke="#292D32" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';
		}
		echo '<span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
		if ( $item[1] && ! is_wp_error( $item[1] ) ) {
			echo '<a class="breadcrumbs__link" itemprop="item" href="' . esc_url( $item[1] ) . '"><span itemprop="name">' . esc_html( $item[0] ) . '</span></a>';
		} else {
			echo '<span class="breadcrumbs__current" itemprop="name">' . esc_html( $item[0] ) . '</span>';
		}
		echo '<meta itemprop="position" content="' . esc_attr( $index + 1 ) . '"></span>';
	}
	echo '</div>';
}
