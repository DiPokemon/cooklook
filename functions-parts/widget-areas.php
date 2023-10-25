<?php

add_action('widgets_init', 'kstehno_widgets_init');
function kstehno_widgets_init(){
    register_sidebar(array(
        'name' => esc_html('Sidebar', 'kstehno'),
        'id'   => 'sidebar-shop',
        'description' => esc_html('Add widget here.', 'kstehno'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="widget-title">',
        'after_title' => '</h3>'
    ) );
}