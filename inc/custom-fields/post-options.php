<?php
use Carbon_Fields\Container;
use Carbon_Fields\Field;

Container::make( 'post_meta', 'Receipies info' )
    ->where( 'post_type', '=', 'recipe' )
    ->add_tab( __( 'Meta info', 'cooklook' ), array(
        Field::make( 'text', 'recipe_portions', __( 'Portions', 'cooklook' ) )
            ->set_attribute('type', 'number')
            ->set_width(50),
        Field::make( 'text', 'recipe_time', __( 'Cooking time', 'cooklook' ) )
            ->set_attribute('type', 'number')
            ->set_width(50),
        Field::make( 'text', 'recipe_prep', __( 'Prepare time', 'cooklook' ) )
            ->set_attribute('type', 'number')
            ->set_width(50),
        Field::make( 'text', 'recipe_id', __( 'ID', 'cooklook' ) )
            ->set_attribute('type', 'number')
            ->set_width(34),
        Field::make( 'text', 'recipe_likes', __( 'Likes', 'cooklook' ) )
            ->set_attribute('type', 'number')
            ->set_width(33),
        Field::make( 'text', 'recipe_dislikes', __( 'Dislikes', 'cooklook' ) )
            ->set_attribute('type', 'number')
            ->set_width(33),
        Field::make( 'text', 'recipe_views', __( 'Views', 'cooklook' ) )
            ->set_attribute('type', 'number')
            ->set_default_value(0)
            ->set_width(33),
        Field::make( 'text', 'recipe_rating', __( 'Rating', 'cooklook' ) )   
            ->set_attribute('type', 'number')         
            ->set_attribute('min', 1)
            ->set_attribute('max', 5)
            ->set_attribute('step', 0.1)
            ->set_width(33),
        Field::make( 'text', 'recipe_url', __( 'Recipe URL', 'cooklook' ) )
            ->set_attribute('type', 'text')
            ->set_width(34),
        Field::make( 'image', 'recipe_pin_img', __( 'Pinterest image', 'cooklook' ) )  
            ->set_value_type( 'url' )                  
            ->set_width(20),
    ) )
    ->add_tab( __( 'Energy value', 'cooklook' ), array(
        Field::make( 'text', 'recipe_calories', __( 'Calories', 'cooklook' ) )
            ->set_attribute('type', 'number')
            ->set_width(20),
        Field::make( 'text', 'recipe_protein', __( 'Protein', 'cooklook' ) )
            ->set_attribute('type', 'number')
            ->set_width(20),
        Field::make( 'text', 'recipe_fat', __( 'Fat', 'cooklook' ) )
            ->set_attribute('type', 'number')
            ->set_width(20),
        Field::make( 'text', 'recipe_carbs', __( 'Carbohydrates', 'cooklook' ) )
            ->set_attribute('type', 'number')
            ->set_width(20),
        Field::make( 'text', 'recipe_region', __( 'Region', 'cooklook' ) )            
            ->set_width(20),
    ) )    
    ->add_tab( __( 'Ingridients', 'cooklook' ), array(
            Field::make( 'complex', 'ingridients', __('Ingridients', 'cooklook') )
            ->add_fields( array(
                Field::make( 'text', 'ingridient_name', __( 'Ingridient', 'cooklook' ) )                               
                    ->set_width(50),            
                Field::make( 'text', 'ingridient_value', __('Ingridient value', 'cooklook') )
                    ->set_width(50), 
            ) )            
    ) )
    ->add_tab( __( 'Cooking steps', 'cooklook' ), array(
        Field::make( 'complex', 'recipe_step',  __('Step', 'cooklook') )
            ->add_fields( array(
                Field::make( 'image', 'recipe_step_image', __( 'Step image', 'cooklook' ) )  
                    ->set_value_type( 'url' )                  
                    ->set_width(20),
                Field::make( 'textarea', 'recipe_step_text', __( 'Step text', 'cooklook' ) )
                    ->set_width(80),                
            ) ),
    ) )    
    ->add_tab( __( 'Original Cooking steps', 'cooklook' ), array(
        Field::make( 'complex', 'original_recipe_step',  __('Original Step', 'cooklook') )
            ->add_fields( array(                
                Field::make( 'textarea', 'original_recipe_step_text', __( 'Original Step text', 'cooklook' ) )
                    ->set_width(80),                
            ) ),
    ) ) ; 



// Регистрация кастомной переменной для Yoast SEO
add_action('wpseo_register_extra_replacements', 'register_yoast_custom_replacements');
function register_yoast_custom_replacements() {
    wpseo_register_var_replacement('%%recipe_step_text%%', 'replace_recipe_step_text', 'advanced', 'First recipe step text');
    wpseo_register_var_replacement('%%recipe_tags%%', 'replace_recipe_tags', 'advanced', 'Recipe tags');
}

// Замена переменной на значение кастомного поля
function replace_recipe_step_text() {
    if (is_singular('recipe')) {
        $recipe_steps = carbon_get_the_post_meta('recipe_step');
        if (!empty($recipe_steps) && isset($recipe_steps[0]['recipe_step_text'])) {
            return $recipe_steps[0]['recipe_step_text'];
        }
    }
    return '';
}

// Добавление переменной в список доступных переменных Yoast
add_filter('wpseo_replacements', 'add_custom_yoast_replacement_variables');
function add_custom_yoast_replacement_variables($replacements) {
    if (is_singular('recipe')) {
        $recipe_steps = carbon_get_the_post_meta('recipe_step');
        if (!empty($recipe_steps) && isset($recipe_steps[0]['recipe_step_text'])) {
            $replacements['%%recipe_step_text%%'] = $recipe_steps[0]['recipe_step_text'];
        } else {
            $replacements['%%recipe_step_text%%'] = '';
        }
    }
    return $replacements;
}

// Замена переменной на значение кастомных тегов
function replace_recipe_tags() {
    if (is_singular('recipe')) {
        $tags = get_the_terms(get_the_ID(), 'recipe_tags');
        if (!empty($tags) && !is_wp_error($tags)) {
            $tag_names = wp_list_pluck($tags, 'name');
            return implode(', ', $tag_names);
        }
    }
    return '';
}

// Добавление переменной в список доступных переменных Yoast
add_filter('wpseo_replacements', 'add_custom_yoast_tag_replacement_variables');
function add_custom_yoast_tag_replacement_variables($replacements) {
    if (is_singular('recipe')) {
        $tags = get_the_terms(get_the_ID(), 'recipe_tags');
        if (!empty($tags) && !is_wp_error($tags)) {
            $tag_names = wp_list_pluck($tags, 'name');
            $replacements['%%recipe_tags%%'] = implode(', ', $tag_names);
        } else {
            $replacements['%%recipe_tags%%'] = '';
        }
    }
    return $replacements;
}
