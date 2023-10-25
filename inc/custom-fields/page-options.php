<?php
use Carbon_Fields\Container;
use Carbon_Fields\Field;

Container::make( 'post_meta', __( 'Main page fields', 'cooklook' ) )     
    ->show_on_template('home.php')
    ->add_fields(array(
		Field::make( 'complex', 'main_banners',  __('Main banners', 'cooklook') )
            ->add_fields( 'main_banners_items', __('Banner', 'cooklook'), array(                
                Field::make( 'image', 'main_banner_desk', __( 'Banner for desktop', 'cooklook' ) )
                    ->set_value_type( 'url' )
                    ->set_width(20),  
                Field::make( 'image', 'main_banner_mob', __( 'Banner for mobile', 'cooklook' ) )
                    ->set_value_type( 'url' )
                    ->set_width(20),                   
                Field::make( 'text', 'main_banner_alt', __( 'Alt', 'cooklook' ) )
                    ->set_width(30),
                Field::make( 'text', 'main_banner_title', __( 'Title', 'cooklook' ) )
                    ->set_width(30),
                Field::make( 'text', 'main_banner_url', __( 'URL', 'cooklook' ) )
                    ->set_attribute( 'placeholder', 'https://ks-tehno.ru/example/' )
                    ->set_width(100),
            ) ),
            
        Field::make( 'complex', 'info_blocks',  __('Info blocks', 'cooklook') )
            ->add_fields( 'info_blocks_items', __('Info block', 'cooklook'), array(                
                Field::make( 'image', 'info_block_desk_img', __( 'Info block for desktop', 'cooklook' ) )
                    ->set_value_type( 'url' )
                    ->set_width(20),  
                Field::make( 'image', 'info_block_mob_img', __( 'Info block for mobile', 'cooklook' ) )
                    ->set_value_type( 'url' )
                    ->set_width(20),                   
                Field::make( 'text', 'info_block_img_alt', __( 'Alt', 'cooklook' ) )
                    ->set_width(30),
                Field::make( 'text', 'info_block_img_title', __( 'Title', 'cooklook' ) )
                    ->set_width(30),
                Field::make( 'text', 'info_block_title', __( 'Info block title', 'cooklook' ) )                    
                    ->set_width(50),
                Field::make( 'textarea', 'info_block_text', __( 'Info block text', 'cooklook' ) )   
                    ->set_rows(1)                 
                    ->set_width(50),
                Field::make( 'text', 'info_block_url', __( 'Button URL', 'cooklook' ) )
                    ->set_attribute( 'placeholder', 'https://ks-tehno.ru/example/' )
                    ->set_width(50),
                Field::make( 'text', 'info_block_btn_text', __( 'Button text', 'cooklook' ) )
                    ->set_attribute( 'placeholder', 'https://ks-tehno.ru/example/' )
                    ->set_width(50),
            ) ),

        Field::make( 'complex', 'advantages',  __('Advantages', 'cooklook') )
            ->add_fields( 'advantages_items', __('Advantages items', 'cooklook'), array(
                Field::make( 'text', 'advantage_icon', __( 'Icons code', 'cooklook' ) )
                    ->set_attribute( 'placeholder', '<i class="fa-brands fa-whatsapp"></i>' )
                    ->set_width(50),
                Field::make( 'text', 'advantage_text', __( 'Text', 'cooklook' ) )
                    ->set_width(50),                
            ) ),

        
        Field::make( 'text', 'main_categories_title', __( 'Categories title', 'cooklook' ) )                    
            ->set_width(25),
        Field::make( 'text', 'main_info_title', __( 'Info block title', 'cooklook' ) )                    
            ->set_width(25),
        Field::make( 'text', 'main_advantages_title', __( 'Advantages title', 'cooklook' ) )                    
            ->set_width(25),
        Field::make( 'text', 'main_testimonials_title', __( 'Testimonials title', 'cooklook' ) )                    
            ->set_width(25),

        Field::make( 'text', 'main_opt_title', __( 'Opt title', 'cooklook' ) )                    
            ->set_width(30),
        Field::make( 'image', 'main_opt_image', __( 'Image', 'cooklook' ) )
            ->set_value_type( 'url' )                        
            ->set_width(30),        
        Field::make( 'text', 'main_opt_subtitle', __( 'Info block title', 'cooklook' ) )                    
            ->set_width(40),
        Field::make( 'textarea', 'main_opt_text', __( 'Info block title', 'cooklook' ) )
	));

Container::make( 'post_meta', __( 'Page fields', 'cooklook' ) )
    ->show_on_template('page.php')
    ->add_fields(array(
		Field::make( 'complex', 'page_images',  __('Page images', 'cooklook') )
            ->add_fields( 'page_image', __('Page image', 'cooklook'), array(
                Field::make( 'image', 'page_image_img', __( 'Page image', 'cooklook' ) )
                    ->set_value_type( 'url' )
                    ->set_width(30),
                Field::make( 'text', 'page_image_alt', __( 'Alt', 'cooklook' ) )
                    ->set_width(35),
                Field::make( 'text', 'page_image_title', __( 'Title', 'cooklook' ) )
                    ->set_width(35)
            ) ),
	));

Container::make( 'post_meta', __( 'About page fields', 'cooklook' ) )
    ->show_on_template('about.php')
    ->add_fields(array(
        Field::make( 'rich_text', 'about_additional_content', __( 'Additional text', 'cooklook' ) ),
        Field::make( 'association', 'about_category', __( 'Categories', 'cooklook' ) )
            ->set_max( 8 )
            ->set_types( array(
                array(
                    'type'      => 'term',
                    'taxonomy' => 'product_cat',
                )
            ) ),
        Field::make( 'complex', 'about_icons',  __('About icons', 'cooklook') )
            ->add_fields( 'about_icons_items', __('About_icons', 'cooklook'), array(
                Field::make( 'text', 'about_icon', __( 'Icon code', 'cooklook' ) )
                    ->set_attribute( 'placeholder', '<i class="fa-brands fa-whatsapp"></i>' )
                    ->set_width(50),
                Field::make( 'text', 'about_icon_text', __( 'Text', 'cooklook' ) )
                    ->set_width(50),                
            ) )
	));

Container::make( 'post_meta', __( 'Opt page fields', 'cooklook' ) )
    ->show_on_template('opt.php')
    ->add_fields(array(        
        Field::make( 'complex', 'opt_images',  __('Opt images', 'cooklook') )
            ->add_fields( 'opt_images_items', __('Opt images', 'cooklook'), array(
                Field::make( 'image', 'opt_image', __( 'Image', 'cooklook' ) )
                    ->set_value_type( 'url' )
                    ->set_width(30),
                Field::make( 'text', 'opt_image_alt', __( 'Alt', 'cooklook' ) )
                    ->set_width(35),
                Field::make( 'text', 'opt_image_title', __( 'Title', 'cooklook' ) )
                    ->set_width(35)              
            ) )     
	));

Container::make( 'post_meta', __( 'Buy and delivery fields', 'cooklook' ) )
    ->show_on_template('buy_delivery.php')
    ->add_fields(array(
        Field::make( 'complex', 'buy_icons',  __('Opt icons', 'cooklook') )
            ->add_fields( 'buy_icons_items', __('buy_icons', 'cooklook'), array(
                Field::make( 'text', 'buy_icon', __( 'Icon code', 'cooklook' ) )
                    ->set_attribute( 'placeholder', '<i class="fa-brands fa-whatsapp"></i>' )
                    ->set_width(50),
                Field::make( 'text', 'buy_icon_text', __( 'Text', 'cooklook' ) )
                    ->set_width(50),                
            ) ),
        Field::make( 'complex', 'buy_texts',  __('Buy texts', 'cooklook') )
            ->add_fields( 'buy_texts', __('Buy text item', 'cooklook'), array(
                Field::make( 'textarea', 'buy_text', __( 'Text', 'cooklook' ) )                              
            ) ), 
        Field::make( 'rich_text', 'buy_additional_text', __( 'Additional text', 'cooklook' ) ),          
	));

