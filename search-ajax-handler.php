<?php

add_action('wp_ajax_nopriv_ajax_search', 'my_ajax_search');
add_action('wp_ajax_ajax_search', 'my_ajax_search');

function my_ajax_search() {
    global $wpdb;
    if (function_exists('relevanssi_do_query')) {
        $search_term = sanitize_text_field($_POST['search']);
        $rejected_query = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}rejected_searches WHERE query = %s", $search_term));
        $existing_pending_query = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}pending_searches WHERE query = %s", $search_term));
        
        if ($rejected_query == 0 && $existing_pending_query == 0) {
            $wpdb->insert(
                $wpdb->prefix . 'pending_searches', // Имя таблицы
                array(
                    'query' => $search_term,
                    'status' => 'awaiting_approval'
                ),
                array(
                    '%s', // Формат значения для 'query'
                    '%s'  // Формат значения для 'status'
                )
            );
        }

        // WP_Query с использованием Relevanssi
        $search_query = new WP_Query();
        $search_query->query_vars['s'] = $search_term;
        $search_query->query_vars['posts_per_page'] = 5; // Или любое другое количество
        relevanssi_do_query($search_query);

        if ($search_query->have_posts()) {
            while ($search_query->have_posts()) {
                $search_query->the_post();
                load_template(get_template_directory() . '/template-parts/ajax-search-result-item.php');
            }
        } else {
            echo 'Ничего не найдено.';
        }
    }
    wp_die(); // Завершает PHP-скрипт
}



?>