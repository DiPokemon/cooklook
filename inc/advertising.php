<?php
/** Advertising placements shared by templates and the administration screen. */
defined( 'ABSPATH' ) || exit;

function cooklook_ad_slots() {
	return array(
		'main_categories' => array(
			'group' => 'home', 'title' => __( 'Среди популярных категорий', 'cooklook' ),
			'description' => __( 'После третьей категории на главной. Вертикальный баннер, 600 × 976 px.', 'cooklook' ),
			'banner' => 'main_categories', 'url' => 'https://kursy-tut.ru/1637509?erid=2bL9aMPo2e4BA5qnNGCKd2CXSG',
			'sizes' => '(max-width: 767px) 45vw, 260px', 'loading' => 'eager',
		),
		'main_square_1' => array(
			'group' => 'home', 'title' => __( 'Баннерная полоса — первое место', 'cooklook' ),
			'description' => __( 'После новых рецептов на главной. Квадратный баннер, от 600 × 600 px.', 'cooklook' ),
			'banner' => 'main_square_1', 'url' => 'https://kursy-tut.ru/2075290?erid=2bL9aMPo2e4BA5qnNJLpeahqfd',
		),
		'main_square_2' => array(
			'group' => 'home', 'title' => __( 'Баннерная полоса — второе место', 'cooklook' ),
			'description' => __( 'После новых рецептов на главной. Квадратный баннер, от 600 × 600 px.', 'cooklook' ),
			'banner' => 'main_square_2', 'url' => 'https://kursy-tut.ru/1637501?erid=2bL9aMPo2e4BA5qnNGCKd2CXS8',
		),
		'main_square_3' => array(
			'group' => 'home', 'title' => __( 'Баннерная полоса — третье место', 'cooklook' ),
			'description' => __( 'После новых рецептов на главной. Квадратный баннер, от 600 × 600 px.', 'cooklook' ),
			'banner' => 'main_square_3', 'url' => 'https://kursy-tut.ru/2075326?erid=2bL9aMPo2e4BA5qnNJLpeaiAcm',
		),
		'main_bottom' => array(
			'group' => 'home', 'title' => __( 'Внизу главной', 'cooklook' ),
			'description' => __( 'Под списком категорий, перед подвалом. Широкое рекламное место.', 'cooklook' ),
			'yandex_id' => 'R-A-8833792-9', 'sizes' => '(max-width: 1200px) calc(100vw - 40px), 1160px',
		),
		'recipe_archive_1' => array(
			'group' => 'catalog', 'title' => __( 'Среди карточек рецептов', 'cooklook' ),
			'description' => __( 'Повторяется после каждых трёх рецептов в каталоге, категориях и тегах, в том числе после фильтрации. Баннер 542 × 644 px или с такими же пропорциями.', 'cooklook' ),
			'banner' => 'recipe_archive_1', 'url' => 'https://kursy-tut.ru/1637554?erid=2bL9aMPo2e4BA5qnNGCKd2CXpF',
		),
		'recipe_page' => array(
			'group' => 'recipe', 'title' => __( 'Рядом с пищевой ценностью', 'cooklook' ),
			'description' => __( 'В верхней части рецепта, перед инструкцией. Горизонтальный баннер, 1084 × 472 px.', 'cooklook' ),
			'banner' => 'recipe_page', 'url' => 'https://kursy-tut.ru/1637470?erid=2bL9aMPo2e4BA5qnNGCKd2CCV5',
			'sizes' => '(max-width: 1024px) calc(100vw - 40px), 552px',
		),
		'recipe_page_square' => array(
			'group' => 'recipe', 'title' => __( 'Рядом с комментариями', 'cooklook' ),
			'description' => __( 'Под инструкцией рецепта. Вертикальный баннер, 878 × 980 px.', 'cooklook' ),
			'banner' => 'recipe_page_square', 'url' => 'https://kursy-tut.ru/2075248?erid=2bL9aMPo2e4BA5qnNJLpeahqHh',
		),
		'recipe_bottom' => array(
			'group' => 'recipe', 'title' => __( 'Внизу рецепта', 'cooklook' ),
			'description' => __( 'После похожих рецептов, перед подвалом. Широкое рекламное место.', 'cooklook' ),
			'yandex_id' => 'R-A-8833792-7', 'sizes' => '(max-width: 1200px) calc(100vw - 40px), 1160px',
		),
	);
}

function cooklook_ads_defaults() {
	$settings = array( 'enabled' => true, 'banner_enabled' => true, 'yandex_enabled' => true, 'slots' => array() );
	foreach ( cooklook_ad_slots() as $name => $slot ) {
		$settings['slots'][ $name ] = array(
			'type' => isset( $slot['yandex_id'] ) ? 'yandex' : 'banner',
			'image_source' => isset( $slot['banner'] ) ? 'theme' : 'none',
			'image_id' => 0, 'url' => isset( $slot['url'] ) ? $slot['url'] : '',
			'alt' => '', 'new_tab' => false,
			'yandex_id' => isset( $slot['yandex_id'] ) ? $slot['yandex_id'] : '',
		);
	}
	return $settings;
}

/** Defaults are read-only: installing the theme does not rewrite site options. */
function cooklook_ads_settings() {
	$settings = cooklook_ads_defaults();
	$saved = get_option( 'cooklook_ads', array() );
	if ( ! is_array( $saved ) ) { return $settings; }
	foreach ( array( 'enabled', 'banner_enabled', 'yandex_enabled' ) as $key ) {
		if ( isset( $saved[ $key ] ) ) { $settings[ $key ] = in_array( $saved[ $key ], array( true, 1, '1' ), true ); }
	}
	foreach ( $settings['slots'] as $name => $defaults ) {
		if ( isset( $saved['slots'][ $name ] ) && is_array( $saved['slots'][ $name ] ) ) {
			$settings['slots'][ $name ] = array_merge( $defaults, array_intersect_key( $saved['slots'][ $name ], $defaults ) );
		}
	}
	return $settings;
}

function cooklook_ad_valid_yandex_id( $id ) {
	return is_string( $id ) && (bool) preg_match( '/^R-[AI]-[0-9]+-[0-9]+$/D', $id );
}

/** HTTP(S) only; preserve campaign query strings and advertiser identifiers. */
function cooklook_ad_url( $url ) {
	if ( ! is_string( $url ) || ! preg_match( '~^https?://~i', trim( $url ) ) ) { return ''; }
	$url = esc_url_raw( trim( $url ), array( 'http', 'https' ) );
	return wp_parse_url( $url, PHP_URL_HOST ) ? $url : '';
}

function cooklook_ad_type( $name, $settings ) {
	if ( empty( $settings['enabled'] ) || ! isset( $settings['slots'][ $name ] ) ) { return 'off'; }
	$type = $settings['slots'][ $name ]['type'];
	return in_array( $type, array( 'banner', 'yandex' ), true ) && ! empty( $settings[ $type . '_enabled' ] ) ? $type : 'off';
}

/** Return the entire placement, so a disabled/empty ad leaves no empty grid cell. */
function cooklook_ad( $name, $classes = '' ) {
	$slots = cooklook_ad_slots();
	if ( ! isset( $slots[ $name ] ) ) { return ''; }
	$settings = cooklook_ads_settings();
	$type = cooklook_ad_type( $name, $settings );
	$ad = $settings['slots'][ $name ];
	$slot = $slots[ $name ];
	if ( 'off' === $type ) { return ''; }
	if ( 'yandex' === $type ) {
		if ( ! cooklook_ad_valid_yandex_id( $ad['yandex_id'] ) ) { return ''; }
		$content = '<div class="cooklook-ad-yandex" data-cooklook-yandex="' . esc_attr( $ad['yandex_id'] ) . '"></div>';
	} else {
		$attributes = array(
			'alt' => is_string( $ad['alt'] ) ? $ad['alt'] : '', 'decoding' => 'async',
			'sizes' => isset( $slot['sizes'] ) ? $slot['sizes'] : '(max-width: 1024px) calc(100vw - 40px), 360px',
			'loading' => isset( $slot['loading'] ) ? $slot['loading'] : 'lazy',
		);
		$image = '';
		if ( 'theme' === $ad['image_source'] && isset( $slot['banner'] ) ) {
			$image = cooklook_banner( $slot['banner'], $attributes['sizes'], $attributes['loading'], $attributes['alt'] );
		} elseif ( 'media' === $ad['image_source'] && is_numeric( $ad['image_id'] ) && wp_attachment_is_image( (int) $ad['image_id'] ) && 'trash' !== get_post_status( (int) $ad['image_id'] ) ) {
			$image = wp_get_attachment_image( (int) $ad['image_id'], 'full', false, $attributes );
		}
		if ( ! $image ) { return ''; }
		$url = cooklook_ad_url( $ad['url'] );
		$content = $url ? '<a class="adv_block" href="' . esc_url( $url ) . '" rel="sponsored' . ( $ad['new_tab'] ? ' noopener noreferrer' : '' ) . '"' . ( $ad['new_tab'] ? ' target="_blank"' : '' ) . ' aria-label="' . esc_attr( $attributes['alt'] ?: __( 'Реклама', 'cooklook' ) ) . '">' . $image . '</a>' : '<div class="adv_block">' . $image . '</div>';
	}
	return '<div class="cooklook-ad cooklook-ad--' . esc_attr( $type ) . ' ' . esc_attr( $classes ) . '" data-ad-slot="' . esc_attr( $name ) . '">' . $content . '</div>';
}

/** Enqueue on eligible pages even if their first catalog response contains no cards. */
function cooklook_ads_enqueue() {
	$group = '';
	if ( is_front_page() || is_home() || is_page_template( 'home.php' ) ) { $group = 'home'; }
	if ( is_post_type_archive( 'recipe' ) || is_tax( array( 'recipe_category', 'recipe_tags' ) ) || is_page_template( 'archive.php' ) ) { $group = 'catalog'; }
	if ( is_singular( 'recipe' ) && ! post_password_required() ) { $group = 'recipe'; }
	if ( ! $group ) { return; }
	$settings = cooklook_ads_settings();
	foreach ( cooklook_ad_slots() as $name => $slot ) {
		if ( $group === $slot['group'] && 'yandex' === cooklook_ad_type( $name, $settings ) && cooklook_ad_valid_yandex_id( $settings['slots'][ $name ]['yandex_id'] ) ) {
			cooklook_enqueue_script( 'cooklook-advertising', 'static/js/advertising.js', array( 'jquery' ) );
			return;
		}
	}
}
add_action( 'wp_enqueue_scripts', 'cooklook_ads_enqueue' );
