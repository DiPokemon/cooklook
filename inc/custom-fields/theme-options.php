<?php
use Carbon_Fields\Container;
use Carbon_Fields\Field;

// Default options page
$basic_options_container = Container::make( 'theme_options', __( 'Theme options', 'cooklook' ) )
    ->set_icon( 'dashicons-welcome-widgets-menus' )    
    ->add_tab( __( 'Additional code', 'cooklook' ), array(
        Field::make( 'header_scripts', 'head_code', __('Code in HEAD', 'cooklook')),
        Field::make( 'footer_scripts', 'footer_code', __('Code in FOOTER', 'cooklook')),
        Field::make( 'textarea', 'text_404', __('Page 404 text', 'cooklook') ),
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
    ) );
    // ->add_tab( __( 'Contacts', 'cooklook' ), array(
    //     Field::make( 'text', 'org_name', __('Organization name', 'cooklook'))
    //         ->set_attribute( 'placeholder', 'ООО "КС-ТЕХНО"' ),
    //     Field::make( 'text', 'main_phone', __( 'main phone', 'cooklook' ) )
    //         ->set_attribute( 'placeholder', '+7 (***) ***-**-**' )            
    //         ->set_width(33),
    //     Field::make( 'text', 'email', __( 'E-mail', 'cooklook' ) )
    //         ->set_attribute( 'placeholder', 'example@example.com' )  
    //         ->set_width(33),
    //     Field::make( 'text', 'second_phone', __( 'Add. phone', 'cooklook' ) )
    //         ->set_attribute( 'placeholder', '+7 (***) ***-**-**' ) 
    //         ->set_width(33),
    //     Field::make( 'text', 'vk', __( 'VKontakte', 'cooklook' ) )
    //         ->set_attribute( 'placeholder', 'vk.com/example' )
    //         ->set_width(33),
    //     Field::make( 'text', 'tg', __( 'Telegram', 'cooklook' ) )
    //         ->set_attribute( 'placeholder', 'example' )
    //         ->set_width(33),
    //     Field::make( 'text', 'wa', __( 'WhatsApp', 'cooklook' ) )
    //         ->set_attribute( 'placeholder', '7**********' )
    //         ->set_width(33),
    //     Field::make( 'text', 'inst', __( 'Instagram', 'cooklook' ) )
    //         ->set_attribute( 'placeholder', 'instagram.com/example' )
    //         ->set_width(33),
    //     Field::make( 'text', 'fb', __( 'Facebook', 'cooklook' ) )
    //         ->set_attribute( 'placeholder', 'facebook.com/example' )
    //         ->set_width(33),        
    // ) )
    // ->add_tab( __( 'Contact form', 'cooklook' ), array(
    //     Field::make( 'text', 'cf_title', __( 'CF Title', 'cooklook' ) )
    //         ->set_width(33),
    //     Field::make( 'textarea', 'cf_subtitle', __( 'CF Subtitle', 'cooklook' ) )
    //         ->set_width(33)
    //         ->set_rows(1),
    //     Field::make( 'text', 'cf_shortcode', __( 'CF Shortcode', 'cooklook' ) )
    //         ->set_width(34)
    //         ->set_attribute( 'placeholder', '[contact-form-7 id="1" title="Contact form 1"]' ),        
    // ) );

