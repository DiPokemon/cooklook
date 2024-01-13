<?php

add_action('wp_ajax_nopriv_ajax_search', 'my_ajax_search');
add_action('wp_ajax_ajax_search', 'my_ajax_search');

function my_ajax_search() {
    global $wpdb;
    if (function_exists('relevanssi_do_query')) {
        $search_term = sanitize_text_field($_POST['search']);
        
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
        
        // Проверяем, есть ли запрос в таблице популярных
        $popular_query = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}popular_searches WHERE query = %s", $search_term));

        if ($popular_query) {
            // Запрос популярный, увеличиваем count
            $wpdb->update(
                "{$wpdb->prefix}popular_searches",
                array('count' => $popular_query->count + 1),
                array('id' => $popular_query->id)
            );
        } else {
            // Проверяем, есть ли запрос в таблице отклоненных
            $rejected_query = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}rejected_searches WHERE query = %s", $search_term));

            if ($rejected_query == 0) {
                // Проверяем, есть ли запрос в таблице ожидающих
                $existing_pending_query = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pending_searches WHERE query = %s", $search_term));

                if ($existing_pending_query) {
                    // Запрос уже находится в ожидающих, увеличиваем count
                    $wpdb->update(
                        "{$wpdb->prefix}pending_searches",
                        array('count' => $existing_pending_query->count + 1),
                        array('id' => $existing_pending_query->id)
                    );
                } else {
                    // Если запрос не находится в ожидающих, добавляем его с count = 1
                    $wpdb->insert(
                        $wpdb->prefix . 'pending_searches',
                        array(
                            'query' => $search_term,
                            'status' => 'awaiting_approval',
                            'count' => 1
                        ),
                        array(
                            '%s', // Формат значения для 'query'
                            '%s', // Формат значения для 'status'
                            '%d'  // Формат значения для 'count'
                        )
                    );
                }
            }
        }
    }
    wp_die(); // Завершает PHP-скрипт
}






?>