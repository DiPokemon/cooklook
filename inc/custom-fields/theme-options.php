<?php
use Carbon_Fields\Container;
use Carbon_Fields\Field;

// Default options page
$basic_options_container = Container::make( 'theme_options', __( 'Theme options', 'cooklook' ) )
    ->set_icon( 'dashicons-welcome-widgets-menus' )    
    ->add_tab( __( 'Additional code', 'cooklook' ), array(
        Field::make( 'header_scripts', 'head_code', __('Code in HEAD', 'cooklook')),
        Field::make( 'footer_scripts', 'footer_code', __('Code in FOOTER', 'cooklook')),
        
        Field::make( 'text', 'html_sitemap_link', __('HTML Sitemap link', 'cooklook') )
            ->set_attribute( 'placeholder', 'https://cook-look.ru/example/' )
            ->set_width(50),
        Field::make( 'text', 'html_sitemap_text', __('HTML sitemap label', 'cooklook') )
            ->set_width(50),
        Field::make( 'text', 'policy_privacy_link', __('Policy privacy link', 'cooklook') )
            ->set_attribute( 'placeholder', 'https://cook-look.ru/example/' )
            ->set_width(50),
        Field::make( 'text', 'policy_privacy_text', __('Policy privacy label', 'cooklook') )
            ->set_width(50),
        Field::make( 'text', 'copyright', __('Copyright', 'cooklook') )
            ->set_attribute( 'placeholder', 'Все права защищены...' ),
    ) )
    ->add_tab( __( 'Contacts', 'cooklook' ), array(
        Field::make( 'text', 'email', __( 'E-mail', 'cooklook' ) )
            ->set_attribute( 'placeholder', 'example@example.com' )  
             ->set_width(50),
        Field::make( 'text', 'ads_email', __( 'Рекламный E-mail', 'cooklook' ) )
             ->set_attribute( 'placeholder', 'example@example.com' )  
              ->set_width(50),       
    ) )

    ->add_tab( __( '404', 'cooklook' ), array(
        
        Field::make( 'text', 'title_404', __( 'Title', 'cooklook' ) )             
            ->set_width(50),
        Field::make( 'text', 'subtitle_404', __( 'Subtitle', 'cooklook' ) ) 
            ->set_width(50),
        Field::make( 'textarea', 'text_404', __('Page 404 text', 'cooklook') ),
        Field::make( 'image', 'image_404', __( 'Image', 'cooklook' ) )  
            ->set_value_type( 'url' )                  
            ->set_width(50),   
        Field::make( 'text', 'btn_404', __( 'Title', 'cooklook' ) )             
            ->set_width(50),   
    ) );

Container::make( 'term_meta', __( 'Category Meta' ) )
    ->where( 'term_taxonomy', '=', 'recipe_category' ) // Применить только к таксономии 'category'
    ->add_fields( array(
        Field::make( 'image', 'category_image', __( 'Миниатюра' ) )
            ->set_value_type( 'url' )
    ));