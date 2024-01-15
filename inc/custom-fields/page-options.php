<?php
use Carbon_Fields\Container;
use Carbon_Fields\Field;

Container::make( 'post_meta', __( 'Homepage Settings' ) )
	->where( 'post_type', '=', 'page' )
	->where( 'post_template', '=', 'home.php' )
	->add_tab( __( 'Популярные категории', 'cooklook' ), array(
        Field::make( 'text', 'popular_cats_title', __( 'Заголовок', 'cooklook' ) ),
        Field::make( 'association', 'popular_cats', __( 'Категории' ) )
        ->set_types( array(
            array(
                'type' => 'term',
                'taxonomy' => 'recipe_category',
            )
        ) )
        ->set_duplicates_allowed( false )
        ->set_max( 5 )
    ) )
    ->add_tab( __( 'Новые рецепты', 'cooklook' ), array(
        Field::make( 'text', 'new_recipes_title', __( 'Заголовок', 'cooklook' ) ),        
    ) )
    ->add_tab( __( 'Популярные рецепты', 'cooklook' ), array(
        Field::make( 'text', 'popular_recipes_title', __( 'Заголовок', 'cooklook' ) ),        
    ) )
    ->add_tab( __( 'Категории', 'cooklook' ), array(
        Field::make( 'text', 'recipes_categories_title', __( 'Заголовок', 'cooklook' ) ),        
    ) );







