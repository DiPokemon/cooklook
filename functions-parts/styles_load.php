<?php

//Register and load CSS
function load_styles(){
	wp_enqueue_style('fontawesome', get_template_directory_uri().'/static/fontawesome/css/all.css');
  	wp_enqueue_style('style_min', get_template_directory_uri().'/static/css/style.min.css');
  	wp_enqueue_style('slick', get_template_directory_uri().'/static/libs/slick/slick.min.css');
  	wp_enqueue_style('slick_theme', get_template_directory_uri().'/static/libs/slick/slick-theme.min.css');
	wp_enqueue_style('slick_theme', get_template_directory_uri().'/static/libs/slick/slick-theme.min.css');
}; 
add_action('wp_enqueue_scripts', 'load_styles', 10);