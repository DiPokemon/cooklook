<?php
use Carbon_Fields\Container;
use Carbon_Fields\Field;

// Default options page
$basic_options_container = Container::make( 'theme_options', __( 'Theme options', 'kstehno' ) )
    ->set_icon( 'dashicons-welcome-widgets-menus' )    
    ->add_tab( __( 'Additional code', 'kstehno' ), array(
        Field::make( 'header_scripts', 'head_code', __('Code in HEAD', 'kstehno')),
        Field::make( 'footer_scripts', 'footer_code', __('Code in FOOTER', 'kstehno')),
        Field::make( 'textarea', 'text_404', __('Page 404 text', 'kstehno') ),
        Field::make( 'text', 'html_sitemap_link', __('HTML Sitemap link', 'kstehno') )
            ->set_attribute( 'placeholder', 'https://cook-look.ru/example/' )
            ->set_width(50),
        Field::make( 'text', 'html_sitemap_text', __('HTML sitemap label', 'kstehno') )
            ->set_width(50),
        Field::make( 'text', 'policy_privacy_link', __('Policy privacy link', 'kstehno') )
            ->set_attribute( 'placeholder', 'https://cook-look.ru/example/' )
            ->set_width(50),
        Field::make( 'text', 'policy_privacy_text', __('Policy privacy label', 'kstehno') )
            ->set_width(50),
        Field::make( 'text', 'copyright', __('Copyright', 'kstehno') )
            ->set_attribute( 'placeholder', 'Все права защищены...' ),
    ) )
    ->add_tab( __( 'Contacts', 'kstehno' ), array(
        Field::make( 'text', 'org_name', __('Organization name', 'kstehno'))
            ->set_attribute( 'placeholder', 'ООО "КС-ТЕХНО"' ),
        Field::make( 'text', 'main_phone', __( 'main phone', 'kstehno' ) )
            ->set_attribute( 'placeholder', '+7 (***) ***-**-**' )            
            ->set_width(33),
        Field::make( 'text', 'email', __( 'E-mail', 'kstehno' ) )
            ->set_attribute( 'placeholder', 'example@example.com' )  
            ->set_width(33),
        Field::make( 'text', 'second_phone', __( 'Add. phone', 'kstehno' ) )
            ->set_attribute( 'placeholder', '+7 (***) ***-**-**' ) 
            ->set_width(33),
        Field::make( 'text', 'vk', __( 'VKontakte', 'kstehno' ) )
            ->set_attribute( 'placeholder', 'vk.com/example' )
            ->set_width(33),
        Field::make( 'text', 'tg', __( 'Telegram', 'kstehno' ) )
            ->set_attribute( 'placeholder', 'example' )
            ->set_width(33),
        Field::make( 'text', 'wa', __( 'WhatsApp', 'kstehno' ) )
            ->set_attribute( 'placeholder', '7**********' )
            ->set_width(33),
        Field::make( 'text', 'inst', __( 'Instagram', 'kstehno' ) )
            ->set_attribute( 'placeholder', 'instagram.com/example' )
            ->set_width(33),
        Field::make( 'text', 'fb', __( 'Facebook', 'kstehno' ) )
            ->set_attribute( 'placeholder', 'facebook.com/example' )
            ->set_width(33),        
    ) )
    // ->add_tab( __( 'Address', 'kstehno' ), array(
    //     Field::make( 'text', 'address_city', __( 'City', 'kstehno' ) )
    //         ->set_width(50),
    //     Field::make( 'text', 'address_street', __( 'Street', 'kstehno' ) )
    //         ->set_width(50),
    //     Field::make( 'text', 'address_build', __( 'Building', 'kstehno' ) )
    //         ->set_width(50),
    //     Field::make( 'text', 'address_index', __( 'ZIP-Code', 'kstehno' ) )
    //         ->set_width(50),
    //     Field::make( 'text', 'address_latitude', __( 'Latitude (for maps)', 'kstehno' ) )
    //         ->set_width(50),
    //     Field::make( 'text', 'address_longitude', __( 'Longitude (for maps)', 'kstehno' ) )
    //         ->set_width(50),
    // ) )
    ->add_tab( __( 'Contact form', 'kstehno' ), array(
        Field::make( 'text', 'cf_title', __( 'CF Title', 'kstehno' ) )
            ->set_width(33),
        Field::make( 'textarea', 'cf_subtitle', __( 'CF Subtitle', 'kstehno' ) )
            ->set_width(33)
            ->set_rows(1),
        Field::make( 'text', 'cf_shortcode', __( 'CF Shortcode', 'kstehno' ) )
            ->set_width(34)
            ->set_attribute( 'placeholder', '[contact-form-7 id="1" title="Contact form 1"]' ),        
    ) );

