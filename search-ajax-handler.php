<?php

add_action('wp_ajax_nopriv_ajax_search', 'my_ajax_search');
add_action('wp_ajax_ajax_search', 'my_ajax_search');

function my_ajax_search() {
    global $wpdb;
    if (function_exists('relevanssi_do_query')) {
        $search_term = sanitize_text_field($_POST['search']);
        
        
        $search_query = new WP_Query();
        $search_query->query_vars['s'] = $search_term;
        $search_query->query_vars['posts_per_page'] = 5;
        relevanssi_do_query($search_query);

        if ($search_query->have_posts()) {
            while ($search_query->have_posts()) {
                $search_query->the_post();
                get_template_part('/template-parts/ajax-search-result-item');
            }
        } else {
            echo 'Nothing.';
        }        
    
        $popular_query = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}popular_searches WHERE query = %s", $search_term));

        if ($popular_query) {
            
            $wpdb->update(
                "{$wpdb->prefix}popular_searches",
                array('count' => $popular_query->count + 1),
                array('id' => $popular_query->id)
            );
        } else {
            
            $rejected_query = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}rejected_searches WHERE query = %s", $search_term));

            if ($rejected_query == 0) {
                
                $existing_pending_query = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pending_searches WHERE query = %s", $search_term));

                if ($existing_pending_query) {
                    
                    $wpdb->update(
                        "{$wpdb->prefix}pending_searches",
                        array('count' => $existing_pending_query->count + 1),
                        array('id' => $existing_pending_query->id)
                    );
                } else {
                    
                    $wpdb->insert(
                        $wpdb->prefix . 'pending_searches',
                        array(
                            'query' => $search_term,
                            'status' => 'awaiting_approval',
                            'count' => 1
                        ),
                        array(
                            '%s',
                            '%s',
                            '%d'
                        )
                    );
                }
            }
        }
    }
    wp_die(); 
}






?>