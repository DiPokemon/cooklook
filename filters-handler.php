<?php

add_action( 'wp_ajax_get_subcategories', 'get_subcategories_callback');
add_action( 'wp_ajax_nopriv_get_subcategories', 'get_subcategories_callback');

function get_subcategories_callback() {
    $selected_category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

    if ($selected_category_id) {
        // Получите подкатегории на основе выбранной категории
        $subcategories = get_terms(array(
            'taxonomy' => 'recipe_category',
            'hide_empty' => false,
            'parent' => $selected_category_id,
        ));

        $options = '<option value="">'.  __('Любое блюдо', 'cooklook') . '</option>';        
        foreach ($subcategories as $subcategory) {
            $options .= '<option value="' . esc_attr($subcategory->term_id) . '">' . esc_html($subcategory->name) . '</option>';
            
        }
    } else {
        $options = '<option value="">'.  __('Любое блюдо', 'cooklook') . '</option>';
    }

    // Верните данные в формате JSON
    wp_send_json_success(array('options' => $options));
    wp_die();
}

add_action('wp_ajax_filter_recipes', 'filter_recipes');
add_action('wp_ajax_nopriv_filter_recipes', 'filter_recipes');

function filter_recipes() {
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
    $subcategory_id = isset($_GET['subcategory_id']) ? intval($_GET['subcategory_id']) : 0;
    $region = isset($_GET['region']) ? sanitize_text_field($_GET['region']) : '';
    $include_ingredients = isset($_GET['include_ingredients']) ? $_GET['include_ingredients'] : array();
    $exclude_ingredients = isset($_GET['exclude_ingredients']) ? $_GET['exclude_ingredients'] : array();

    $args = array(
        'post_type' => 'recipe',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    );

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

    if ($region) {
        $args['meta_query'] = array(
            array(
                'key' => '_recipe_region', // Замените на ваше кастомное поле
                'value' => $region,
                'compare' => '=',
            ),
        );
    }

    // Фильтрация по выбранным ингредиентам из таксономии "recipe_tags"
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

    wp_send_json_success(array('html' => $html));
    wp_die();
}


?>