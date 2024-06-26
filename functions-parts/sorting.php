<?php
if (! defined ('ABSPATH')){
    exit;
}

add_action('wp_ajax_sort_posts', 'sort_posts_function');
add_action('wp_ajax_nopriv_sort_posts', 'sort_posts_function');

function sort_posts_function() {
    $sort_by = $_POST['sort_by'];
    $args = array(
        'post_type' => 'recipe',
        'orderby' => 'date',
        'order' => 'DESC'
    );

    $category_id = isset($_GET['recipe_category']) ? intval($_GET['recipe_category']) : 0;
    $subcategory_id = isset($_GET['recipe_subcategory']) ? intval($_GET['recipe_subcategory']) : 0;

    if ($category_id) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'recipe_category',
                'field' => 'id',
                'terms' => $category_id,
            ),
        );
    }

    if ($subcategory_id) {
        $args['tax_query'][] = array(
            'taxonomy' => 'recipe_category',
            'field' => 'id',
            'terms' => $subcategory_id,
        );
    }

    switch ($sort_by) {
        case 'recipe_views':
            $args = array(
                'post_type' => 'recipe',
                'meta_key' => '_recipe_views',
                'orderby'   => 'meta_value_num',
                'order'     => 'DESC',
            );
            break;
        case 'recipe_time':
            $args = array(
                'post_type' => 'recipe',
                'meta_key' => '_recipe_time',
                'orderby'   => 'meta_value_num',
                'order'     => 'DESC',
            );
            // $args['meta_key'] = '_recipe_time';
            // $args['orderby'] = 'meta_value_num';
            break;
        case 'date':
            $args = array(
                'post_type' => 'recipe',
                'orderby' => 'date',
                'order' => 'DESC'
            );
            break;
    }

    $post_counter = 0;

    $query = new WP_Query($args);

    if($query->have_posts()) {
        while($query->have_posts()) {
            $query->the_post();
            $post_counter++;
            $categories = get_the_terms(get_the_ID(), 'recipe_category');
            $post_id = get_the_ID();
            $recipe_likes = carbon_get_post_meta($post_id, 'recipe_likes');
            $recipe_dislikes = carbon_get_post_meta($post_id, 'recipe_likes');
            $rating = calculate_rating($recipe_likes, $recipe_dislikes);
            $time = carbon_get_post_meta($post_id, 'recipe_time');
            $portions = carbon_get_post_meta($post_id, 'recipe_portions');
            $comments = get_comments_number();
            $recipe_steps = carbon_get_post_meta($post_id, 'recipe_step');
            $tags = get_the_terms($post_id, 'recipe_tags');

            if(get_the_excerpt()){
                $description = get_the_excerpt();
            }
            else if(get_the_content()){
                $description = get_the_content();
            }
            else {
                $description = $recipe_steps[0]['recipe_step_text'];
            }
                                
            $words = explode(' ', $description);
            $first_fifteen_words = array_slice($words, 0, 15);
            $description = implode(' ', $first_fifteen_words);
            $description .= ' ...';

            set_query_var('categories', $categories);
            set_query_var('post_id', $post_id);
            set_query_var('rating', $rating);
            set_query_var('time', $time);
            set_query_var('portions', $portions);
            set_query_var('comments', $comments);
            set_query_var('description', $description);
            set_query_var('tags', $tags);
            if ($post_counter % 4 == 0) {
                get_template_part('template-parts/recipe-loop-item-adv');
            }
            else {
                get_template_part('template-parts/recipe-loop-item');
            }
        }
    } else {
        get_template_part('template-parts/recipe-loop-nothing');
    }
    wp_die(); // Это необходимо для завершения AJAX запроса
}

// Подключение и локализация скриптов

