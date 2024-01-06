<?php

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
  wp_enqueue_script('ajax-search', get_template_directory_uri() . '/static/js/ajax-search.js', array(), NULL, true);
} 
add_action('wp_enqueue_scripts', 'load_scripts', 10);