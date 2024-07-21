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
        user_id VARCHAR(255),
        recipe_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'create_favorite_recipes_table');

// AJAX обработчик для добавления страницы в избранное
add_action('wp_ajax_add_to_favorites', 'add_to_favorites');
add_action('wp_ajax_nopriv_add_to_favorites', 'add_to_favorites'); // Для анонимных пользователей

function add_to_favorites() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'favorite_recipes';
    $recipe_id = $_POST['recipe_id'];
    $user_id = get_current_user_id();

    // Проверяем, есть ли уже такая запись
    $exists = is_recipe_favorite($recipe_id, $user_id);

    if ($exists) {
        // Удаление из избранного
        $wpdb->delete($table_name, ['user_id' => $user_id, 'recipe_id' => $recipe_id]);
        echo 'removed';
    } else {
        // Добавление в избранное
        $wpdb->insert($table_name, ['user_id' => $user_id, 'recipe_id' => $recipe_id]);
        echo 'added';
    }
    wp_die();
}



function is_recipe_favorite($recipe_id, $user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'favorite_recipes';
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE recipe_id = %d AND user_id = %s",
        $recipe_id,
        $user_id
    ));

    return ($exists > 0);
}
