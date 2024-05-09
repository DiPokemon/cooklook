<?php
if (! defined ('ABSPATH')){
    exit;
}

// Создание таблицы базы данных при активации темы
function create_favorite_recipes_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'favorite_recipes';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        recipe_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES wp_users(ID),
        FOREIGN KEY (page_id) REFERENCES wp_posts(ID)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
add_action( 'after_switch_theme', 'create_favorite_recipes_table' );

// AJAX обработчик для добавления страницы в избранное
add_action('wp_ajax_add_to_favorites', 'add_to_favorites');
add_action('wp_ajax_nopriv_add_to_favorites', 'add_to_favorites'); // Для анонимных пользователей
function add_to_favorites() {
    $recipe_id = $_POST['recipe_id'];

    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
    } else {
        $user_id = 0; // ID анонимного пользователя
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'favorite_recipes';

    $wpdb->insert($table_name, array(
        'user_id' => $user_id,
        'recipe_id' => $recipe_id
    ));

    echo 'success';
    wp_die();
}
