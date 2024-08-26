<?php
add_action('wp_ajax_filter_recipes', 'filter_recipes');
add_action('wp_ajax_nopriv_filter_recipes', 'filter_recipes');

function filter_recipes() {
    $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;

    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
    $subcategory_id = isset($_GET['subcategory_id']) ? intval($_GET['subcategory_id']) : 0;
    $region = isset($_GET['region']) ? sanitize_text_field($_GET['region']) : '';
    $include_ingredients = isset($_GET['include_ingredients']) ? $_GET['include_ingredients'] : array();
    $exclude_ingredients = isset($_GET['exclude_ingredients']) ? $_GET['exclude_ingredients'] : array();

    $current_category = isset($_GET['current_category']) ? intval($_GET['current_category']) : 0;
    $current_tag = isset($_GET['current_tag']) ? sanitize_text_field($_GET['current_tag']) : '';

    $args = array(
        'post_status' => 'publish',
        'post_type' => 'recipe',
        'posts_per_page' => 11,
        'paged' => $paged,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    if ($category_id && $category_id !== 'all') {
        $args['tax_query'][] = array(
            'taxonomy' => 'recipe_category',
            'field' => 'id',
            'terms' => $category_id,
        );
    } elseif ($current_category) {
        $args['tax_query'][] = array(
            'taxonomy' => 'recipe_category',
            'field' => 'id',
            'terms' => $current_category,
        );
    }

    if ($subcategory_id && $subcategory_id !== 'all') {
        $args['tax_query'][] = array(
            'taxonomy' => 'recipe_category',
            'field' => 'id',
            'terms' => $subcategory_id,
        );
    }

    if ($region) {
        $args['meta_query'][] = array(
            'key' => '_recipe_region',
            'value' => $region,
            'compare' => '=',
        );
    }

    if (!empty($include_ingredients)) {
        $args['tax_query'][] = array(
            'taxonomy' => 'recipe_tags',
            'field' => 'slug',
            'terms' => $include_ingredients,
        );
    }

    if (!empty($exclude_ingredients)) {
        $args['tax_query'][] = array(
            'taxonomy' => 'recipe_tags',
            'field' => 'slug',
            'terms' => $exclude_ingredients,
            'operator' => 'NOT IN',
        );
    }

    if ($current_tag) {
        $args['tax_query'][] = array(
            'taxonomy' => 'recipe_tags',
            'field' => 'slug',
            'terms' => $current_tag,
        );
    }

    $recipe_query = new WP_Query($args);

    ob_start();

    if ($recipe_query->have_posts()) {
        while ($recipe_query->have_posts()) {
            $recipe_query->the_post();
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
            
            get_template_part('template-parts/recipe-loop-item');
        }
    } else {
        get_template_part('template-parts/recipe-loop-nothing');
    }

    $html = ob_get_clean();

    ob_start();
    the_posts_pagination(array(
        'mid_size' => 2,
        'prev_text' => __('', 'cooklook'),
        'next_text' => __('', 'cooklook'),
        'screen_reader_text' => __('Пагинация', 'cooklook'),
        'format' => '?paged=%#%',
    ));
    $pagination = ob_get_clean();

    wp_send_json_success(array('html' => $html, 'pagination' => $pagination));
    wp_die();
}
