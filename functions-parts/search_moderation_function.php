<?php

if (! defined ('ABSPATH')){
    exit;
}

function create_custom_tables() {
    global $wpdb;

    // Имя и полный путь к таблицам
    $pending_searches_table = $wpdb->prefix . 'pending_searches';
    $rejected_searches_table = $wpdb->prefix . 'rejected_searches';
    $popular_searches_table =  $wpdb->prefix . 'popular_searches';

    // Charset и Collate для таблиц
    $charset_collate = $wpdb->get_charset_collate();

    // Подключаем файл, необходимый для работы функции dbDelta
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Проверяем, существуют ли таблицы уже
    if ($wpdb->get_var("SHOW TABLES LIKE '{$pending_searches_table}'") != $pending_searches_table) {
        // SQL для создания таблицы pending_searches
        $sql1 = "CREATE TABLE $pending_searches_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            query TEXT NOT NULL,
            count INT DEFAULT 1,
            status VARCHAR(20) DEFAULT 'awaiting_approval'
        ) $charset_collate;";
        // Выполняем SQL запрос с помощью dbDelta
        dbDelta($sql1);
    }

    if ($wpdb->get_var("SHOW TABLES LIKE '{$rejected_searches_table}'") != $rejected_searches_table) {
        // SQL для создания таблицы rejected_searches
        $sql2 = "CREATE TABLE $rejected_searches_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            query TEXT NOT NULL,
            count INT DEFAULT 1
        ) $charset_collate;";
        // Выполняем SQL запрос с помощью dbDelta
        dbDelta($sql2);
    }

    if ($wpdb->get_var("SHOW TABLES LIKE '{$popular_searches_table}'") != $popular_searches_table) {
        // SQL для создания таблицы rejected_searches
        $sql3 = "CREATE TABLE $popular_searches_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            query TEXT NOT NULL,
            count INT DEFAULT 1
        ) $charset_collate;";
        // Выполняем SQL запрос с помощью dbDelta
        dbDelta($sql3);
    }
}

// Добавляем хук для активации темы
add_action('after_switch_theme', 'create_custom_tables');

/* Модерация поиска */ 

function my_search_moderation_page_content() {
    global $wpdb;
    // Получите запросы, ожидающие модерации
    $pending_requests = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pending_searches WHERE status = 'awaiting_approval'");    
    load_template(get_template_directory() . '/search-moderation-template.php', null, array('pending_requests' => $pending_requests));
}

function my_custom_admin_menu() {
    add_menu_page(
        'Модерация поиска',           // Название страницы
        'Модерация поиска',           // Текст в меню
        'manage_options',             // Capability
        'search-moderation',          // Slug страницы
        'my_search_moderation_page_content', // Функция отображения содержимого
        'dashicons-search'            // Иконка (опционально)
    );
}
add_action('admin_menu', 'my_custom_admin_menu');
  
// Функция одобрения запроса
function approve_search_request() {
    global $wpdb;
    $request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

    // Получаем запрос из ожидающих
    $query_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pending_searches WHERE id = %d", $request_id));

    if($query_info) {
        // Проверяем, существует ли уже такой запрос в популярных
        $existing = $wpdb->get_row($wpdb->prepare("SELECT id, query, count FROM {$wpdb->prefix}popular_searches WHERE query = %s", $query_info->query));
        
        if($existing) {
            // Если существует, обновляем запись в popular_searches с сохранением query и увеличиваем счетчик
            $wpdb->update(
                "{$wpdb->prefix}popular_searches",
                array('count' => $existing->count + 1), // Увеличиваем счетчик
                array('id' => $existing->id) // Условие для обновления
            );
        } else {
            // Если нет, добавляем новый запрос с query и count из pending_searches
            $wpdb->insert(
                "{$wpdb->prefix}popular_searches",
                array('query' => $query_info->query, 'count' => $query_info->count),
                array('%s', '%d')
            );
        }

        // Удаляем запрос из ожидающих
        $wpdb->delete("{$wpdb->prefix}pending_searches", array('id' => $request_id));
    }

    wp_redirect(admin_url('admin.php?page=search-moderation')); // Перенаправляем обратно на страницу модерации
    exit;
}


// Функция отклонения запроса
function reject_search_request() {
    global $wpdb;
    $request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

    // Получаем запрос из ожидающих
    $query_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pending_searches WHERE id = %d", $request_id));

    if($query_info) {
        // Добавляем запрос в отклоненные
        $wpdb->insert(
            "{$wpdb->prefix}rejected_searches",
            array('query' => $query_info->query),
            array('%s')
        );

        // Удаляем запрос из ожидающих
        $wpdb->delete("{$wpdb->prefix}pending_searches", array('id' => $request_id));
    }

    wp_redirect(admin_url('admin.php?page=search-moderation')); // Перенаправляем обратно на страницу модерации
    exit;
}

// Добавляем действия к хукам WordPress
add_action('admin_post_approve_search', 'approve_search_request');
add_action('admin_post_reject_search', 'reject_search_request');

function get_popular_searches($limit = 6) {
    global $wpdb;
    $popular_searches = $wpdb->get_results($wpdb->prepare("
        SELECT query, count 
        FROM {$wpdb->prefix}popular_searches 
        ORDER BY count DESC 
        LIMIT %d", 
        $limit
    ));
    return $popular_searches;
}