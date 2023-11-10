<?php
	require_once( get_template_directory().'/functions-parts/theme-settings.php' );
    require_once( get_template_directory().'/functions-parts/breadcrumbs.php' );
    require_once( get_template_directory().'/functions-parts/navmenu.php' );
    require_once( get_template_directory().'/functions-parts/crb_init.php' );
	require_once( get_template_directory().'/functions-parts/widget-areas.php' );


//Cuting excerpt for words number
function custom_excerpt_length( $length ) {
    return 10;
}
add_filter( 'excerpt_length', 'custom_excerpt_length', 999 );

//Add custom logo
add_theme_support( 'custom-logo' );

//Miniatures
add_theme_support('post-thumbnails');

//Register menu areas
register_nav_menus([
	'middle_menu' => 'Top menu',
    'main_menu' => 'Main menu',
	'footer_right_menu' => 'Footer right menu'
]);

//Add class for logo
add_filter( 'get_custom_logo', 'change_logo_class' );
function change_logo_class( $html ) {
		$html = str_replace( 'custom-logo', 'logo_img', $html );
       $html = str_replace( 'custom-logo-link', 'header_logo_link', $html );
    return $html;
};

//Register and load CSS
function load_styles(){
	wp_enqueue_style('fontawesome', get_template_directory_uri().'/static/fontawesome/css/all.css');
  	wp_enqueue_style('style_min', get_template_directory_uri().'/static/css/style.min.css');
  	wp_enqueue_style('slick', get_template_directory_uri().'/static/libs/slick/slick.min.css');
  	wp_enqueue_style('slick_theme', get_template_directory_uri().'/static/libs/slick/slick-theme.min.css');

}; 
add_action('wp_enqueue_scripts', 'load_styles', 10);

//Register and load JS
function load_scripts(){  
  wp_enqueue_script('script', get_template_directory_uri() . '/static/js/script.js', array(), NULL, true);
  wp_deregister_script( 'jquery' );
  wp_register_script( 'jquery', 'https://code.jquery.com/jquery-3.6.4.min.js');
  wp_enqueue_script( 'jquery' , array(), NULL, true);
  wp_enqueue_script('slick', get_template_directory_uri() . '/static/libs/slick/slick.min.js', array('jquery'), NULL, true);
  //wp_enqueue_script('inits', get_template_directory_uri().'/static/js/inits.js', array('slick','masonry','maskedinput'), NULL, true);
  wp_enqueue_script('yamap_api', 'https://api-maps.yandex.ru/2.1/?lang=ru_RU', array(), NULL, true);  
  wp_enqueue_script('map_init', get_template_directory_uri().'/static/js/map_init.js', array('yamap_api'), NULL, true); 
  wp_enqueue_script('spincrement', get_template_directory_uri().'/static/js/spincrement.min.js', array('jquery'), NULL, true); 
  //wp_enqueue_script('masonry', get_template_directory_uri().'/static/js/masonry.js', array('jquery'), NULL, true); 
  wp_enqueue_script('maskedinput', get_template_directory_uri().'/static/js/maskedinput.min.js', array('jquery'), NULL, true); 
  wp_enqueue_script('slick_init', get_template_directory_uri() . '/static/js/slick_init.js', array('slick'), NULL, true);
} 
add_action('wp_enqueue_scripts', 'load_scripts', 10);


add_action('wp_head', 'phone_front', 1); 
function phone_front($phone) {
    $phone = trim($phone);  
    $res = preg_replace(
      array(
        '/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{3})[-|\s]?\)[-|\s]?(\d{3})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
        '/[\+]?([7|8])[-|\s]?(\d{3})[-|\s]?(\d{3})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
        '/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{4})[-|\s]?\)[-|\s]?(\d{2})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
        '/[\+]?([7|8])[-|\s]?(\d{4})[-|\s]?(\d{2})[-|\s]?(\d{2})[-|\s]?(\d{2})/',	
        '/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{4})[-|\s]?\)[-|\s]?(\d{3})[-|\s]?(\d{3})/',
        '/[\+]?([7|8])[-|\s]?(\d{4})[-|\s]?(\d{3})[-|\s]?(\d{3})/',					
      ), 
      array(
        '+7 ($2) $3-$4-$5', 
        '+7 ($2) $3-$4-$5', 
        '+7 ($2) $3-$4-$5', 
        '+7 ($2) $3-$4-$5', 	
        '+7 ($2) $3-$4', 
        '+7 ($2) $3-$4', 
      ), 
      $phone
    );  
    return $res;
}

add_action('wp_head', 'phone_href', 1); 
function phone_href($phone) {
    $phone = trim($phone); 
	$res = preg_replace(
		array(
			'/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{3})[-|\s]?\)[-|\s]?(\d{3})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
			'/[\+]?([7|8])[-|\s]?(\d{3})[-|\s]?(\d{3})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
			'/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{4})[-|\s]?\)[-|\s]?(\d{2})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
			'/[\+]?([7|8])[-|\s]?(\d{4})[-|\s]?(\d{2})[-|\s]?(\d{2})[-|\s]?(\d{2})/',	
			'/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{4})[-|\s]?\)[-|\s]?(\d{3})[-|\s]?(\d{3})/',
			'/[\+]?([7|8])[-|\s]?(\d{4})[-|\s]?(\d{3})[-|\s]?(\d{3})/',					
		), 
		array(
			'+7$2$3$4$5', 
			'+7$2$3$4$5', 
			'+7$2$3$4$5', 
			'+7$2$3$4$5', 	
			'+7$2$3$4', 
			'+7$2$3$4', 
		), 
		$phone
	); 
	return $res;
}

add_action('wp_head', 'phone_wa', 1); 
function phone_wa($phone) {
    $phone = trim($phone); 
	$res = preg_replace(
		array(
			'/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{3})[-|\s]?\)[-|\s]?(\d{3})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
			'/[\+]?([7|8])[-|\s]?(\d{3})[-|\s]?(\d{3})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
			'/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{4})[-|\s]?\)[-|\s]?(\d{2})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
			'/[\+]?([7|8])[-|\s]?(\d{4})[-|\s]?(\d{2})[-|\s]?(\d{2})[-|\s]?(\d{2})/',	
			'/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{4})[-|\s]?\)[-|\s]?(\d{3})[-|\s]?(\d{3})/',
			'/[\+]?([7|8])[-|\s]?(\d{4})[-|\s]?(\d{3})[-|\s]?(\d{3})/',					
		), 
		array(
			'7$2$3$4$5', 
			'7$2$3$4$5', 
			'7$2$3$4$5', 
			'7$2$3$4$5', 	
			'7$2$3$4', 
			'7$2$3$4', 
		), 
		$phone
	); 
	return $res;
}

add_filter('wpcf7_autop_or_not', '__return_false');

function my_custom_mime_types( $mimes ) {
	$mimes['csv'] = 'text/csv';
	unset( $mimes['exe'] );
	 return $mimes;
	}
	add_filter( 'upload_mimes', 'my_custom_mime_types' );

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



function process_image_upload($attachment_ID) {
		$image_path = get_attached_file($attachment_ID); // Получить путь к изображению
	
		// Создание объекта Imagick
		$imagick = new Imagick($image_path);
	
		// Отзеркаливание изображения
		$imagick->flopImage();
	
		// Добавление водяного знака
		$watermark = new Imagick('cooklook/wp-content/themes/cooklook/static/watermark.png');
		$imagick->compositeImage($watermark, Imagick::COMPOSITE_OVER, 0, 0);

		// Конвертация в WebP
		$imagick->setImageFormat('webp');
		$imagick->setImageCompressionQuality(80); // Установка качества сжатия
	
		// Сохранение изменённого изображения
		// Сохранение изменённого изображения
		$new_image_path = preg_replace('/\.\w+$/', '.webp', $image_path);
		$imagick->writeImage($new_image_path);
	}
	
	// Подключение функции к хуку WordPress для обработки загруженных изображений
	add_action('add_attachment', 'process_image_upload');