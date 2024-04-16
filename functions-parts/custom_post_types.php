<?php
function custom_recipe_taxonomy() {
    $args = array(
        'labels' => array(
            'name' => __('Категории рецептов', 'cooklook'),
            'singular_name' => __('Категория рецепта', 'cooklook'),
        ),
        'public' => true,
        'hierarchical' => true, // Включить иерархию, как у стандартных категорий
    );
    register_taxonomy('recipe_category', 'recipe', $args);
}
add_action('init', 'custom_recipe_taxonomy');

function custom_recipe_tags_taxonomy() {
    $args = array(
        'labels' => array(
            'name' => 'Теги рецептов',
            'singular_name' => 'Тег рецепта',
        ),
        'public' => true,
        'hierarchical' => false, // Теги не имеют иерархии, они плоские
    );
    register_taxonomy('recipe_tags', 'recipe', $args);
}
add_action('init', 'custom_recipe_tags_taxonomy');


function custom_recipe_post_type() {
    $args = array(
        'labels' => array(
            'name' => 'Рецепты',
            'singular_name' => 'Рецепт',
        ),
        'public' => true,
        'has_archive' => 'recipes',
        'show_ui' => true,
        'menu_icon' => 'dashicons-carrot', // Иконка для меню
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments', 'revisions', 'page-attributes', 'post-formats'),
        'taxonomies' => array('recipe_category', 'recipe_tags'), // Включаем стандартные категории и нашу кастомную
    );
    register_post_type('recipe', $args);
    //add_rewrite_rule('^recipes/?$', 'index.php?post_type=recipe', 'top');
}
add_action('init', 'custom_recipe_post_type');


function custom_blog_post_type() {
    $args = array(
        'labels' => array(
            'name' => __('Блог', 'cooklook'),
            'singular_name' => __('Блоговая запись', 'cooklook'),
        ),
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-media-text', // Иконка для меню
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments', 'revisions', 'custom-fields', 'page-attributes', 'post-formats', 'tags', 'categories'),
    );
    register_post_type('blog_post', $args);
}
add_action('init', 'custom_blog_post_type');