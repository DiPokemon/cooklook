<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package cooklook
 */

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function cooklook_body_classes( $classes ) {
	// Adds a class of hfeed to non-singular pages.
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	// Adds a class of no-sidebar when there is no sidebar present.
	if ( ! is_active_sidebar( 'sidebar-1' ) ) {
		$classes[] = 'no-sidebar';
	}

	return $classes;
}
add_filter( 'body_class', 'cooklook_body_classes' );

/**
 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
 */
function cooklook_pingback_header() {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">', esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'cooklook_pingback_header' );

//убирает ошибку в schema - query-input 
add_filter( 'aioseo_schema_output', 'aioseo_filter_schema_output' );
function aioseo_filter_schema_output( $graphs ) {
	foreach ( $graphs as $index => $graph ) {
	if ( 'WebSite' === $graph['@type'] ) {
	unset( $graphs[ $index ]['potentialAction']["query-input"] );
	}
}
return $graphs;
}


function add_featured_image_column( $columns ) {
	$new = array();
	foreach($columns as $key => $title) {
		if ($key == 'cb') { // или 'cb' для чекбокса
			$new[$key] = $title;
			$new['featured_image'] = __('Миниатюра');
		} else {
			$new[$key] = $title;
		}
	}
	return $new;
}
add_filter('manage_posts_columns', 'add_featured_image_column');

function featured_image_column($column, $post_id) {
    if ($column == 'featured_image') {
        echo get_the_post_thumbnail($post_id, 'thumbnail');
    }
}
add_action('manage_posts_custom_column', 'featured_image_column', 10, 2);

function my_admin_style() {
    echo '
    <style type="text/css">
		.fixed .column-featured_image{
			width: 100px
		}
        .column-featured_image img {
            max-width: 100px; /* или любой другой размер */
            height: auto;
        }
    </style>';
}
add_action('admin_head', 'my_admin_style');

/* функция рассчета рейтинга рецепта */
function calculate_rating($likes, $dislikes) {
    if ($likes + $dislikes == 0) {
        return 0;
    }
    $total_votes = $likes + $dislikes;
    $like_ratio = $likes / $total_votes;
    $rating = $like_ratio * 5;
    return round($rating, 1);
}

/* увеличивает полу recipe_views у рецепта при каждом просмотре (используется для вывода популярных рецептов) */
function increase_post_views() {
    if ( is_single() ) {
        $post_id = get_the_ID();
        $views = carbon_get_post_meta( $post_id, 'recipe_views' );
        $views++;
        carbon_set_post_meta( $post_id, 'recipe_views', $views );
    }
}
add_action( 'wp_head', 'increase_post_views' );


// function process_image_upload($attachment_ID) {
// 		$image_path = get_attached_file($attachment_ID); // Получить путь к изображению
	
// 		// Создание объекта Imagick
// 		$imagick = new Imagick($image_path);
	
// 		// Отзеркаливание изображения
// 		$imagick->flopImage();
	
// 		// Добавление водяного знака
// 		// $watermark = new Imagick('cooklook/wp-content/themes/cooklook/static/watermark.png');
// 		// $imagick->compositeImage($watermark, Imagick::COMPOSITE_OVER, 0, 0);

// 		// Конвертация в WebP
// 		$imagick->setImageFormat('webp');
// 		$imagick->setImageCompressionQuality(80); // Установка качества сжатия
	
// 		// Сохранение изменённого изображения
// 		// Сохранение изменённого изображения
// 		$new_image_path = preg_replace('/\.\w+$/', '.webp', $image_path);
// 		$imagick->writeImage($new_image_path);
// 	}
	
// 	// Подключение функции к хуку WordPress для обработки загруженных изображений
// 	add_action('add_attachment', 'process_image_upload');

function mirror_image_on_upload($attachment_ID) {
    $image_path = get_attached_file($attachment_ID); // Получение пути к загруженному изображению

    // Создание объекта Imagick для обработки изображения
    $imagick = new Imagick($image_path);

    // Отзеркаливание изображения
    $imagick->flopImage();

    // Сохранение изменений, замена оригинального файла
    $imagick->writeImage($image_path);

    // Очистка ресурсов
    $imagick->clear();
    $imagick->destroy();
}

// Добавление функции в WordPress хук, который срабатывает после загрузки изображения
add_action('add_attachment', 'mirror_image_on_upload');


