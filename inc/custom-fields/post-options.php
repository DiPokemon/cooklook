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
            ->set_default_value(0),
        Field::make( 'text', 'recipe_rating', __( 'Rating', 'cooklook' ) )   
            ->set_attribute('type', 'number')         
            ->set_attribute('min', 1)
            ->set_attribute('max', 5)
            ->set_attribute('step', 0.1),
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
    ) ) ;   