<?php
/*
* Файл для подключение разметки Schema ORG
*/

function add_dynamic_schema_markup() {
    $custom_logo_id = get_theme_mod('custom_logo');
    $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
    ?>
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebSite",
            "name": "<?php echo esc_js(get_bloginfo('name')); ?>",
            "url": "<?php echo esc_url(home_url('/')); ?>",
            "potentialAction": {
                "@type": "SearchAction",
                "target": "<?php echo esc_url(home_url('/')) . '?s={search_term_string}'; ?>",
                "query-input": "required name=search_term_string"
            }
        }
        {
            "@context": "http://schema.org",
            "@type": "WPFooter",
            "name": "<?php echo esc_js(get_bloginfo('name')); ?>",
            "description": "<?php bloginfo('description'); ?>",
            "publisher": {
                "@type": "WebSite",
                "name": "<?php echo esc_js(get_bloginfo('name')); ?>",
                "logo": {
                "@type": "ImageObject",
                "url": "<?php echo esc_url($logo[0]) ?>"
                },
            }
        }
    </script>
    <?php
}

add_action('wp_head', 'add_dynamic_schema_markup');