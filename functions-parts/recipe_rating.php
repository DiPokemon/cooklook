<?php
// Обновление количества лайков
function update_recipe_likes() {
    $recipe_id = $_POST['recipe_id'];
    $recipe_likes = carbon_get_post_meta($recipe_id, 'recipe_likes');
    $recipe_dislikes = carbon_get_post_meta($recipe_id, 'recipe_dislikes');

    // Увеличиваем количество лайков
    $recipe_likes++;
    carbon_set_post_meta($recipe_id, 'recipe_likes', $recipe_likes);

    // Проверяем, голосовал ли пользователь за эту запись
    $user_vote = get_user_vote($recipe_id);

    // Возвращаем новые значения лайков и дизлайков, а также состояние голоса пользователя
    echo json_encode(array('likes' => $recipe_likes, 'dislikes' => $recipe_dislikes, 'user_vote' => $user_vote));
    wp_die();
}
add_action('wp_ajax_update_recipe_likes', 'update_recipe_likes');

// Обновление количества дизлайков
function update_recipe_dislikes() {
    $recipe_id = $_POST['recipe_id'];
    $recipe_likes = carbon_get_post_meta($recipe_id, 'recipe_likes');
    $recipe_dislikes = carbon_get_post_meta($recipe_id, 'recipe_dislikes');

    // Увеличиваем количество дизлайков
    $recipe_dislikes++;
    carbon_set_post_meta($recipe_id, 'recipe_dislikes', $recipe_dislikes);

    // Проверяем, голосовал ли пользователь за эту запись
    $user_vote = get_user_vote($recipe_id);

    // Возвращаем новые значения лайков и дизлайков, а также состояние голоса пользователя
    echo json_encode(array('likes' => $recipe_likes, 'dislikes' => $recipe_dislikes, 'user_vote' => $user_vote));
    wp_die();
}
add_action('wp_ajax_update_recipe_dislikes', 'update_recipe_dislikes');

// Удаление голоса пользователя
function remove_vote() {
    $recipe_id = $_POST['recipe_id'];
    $recipe_likes = carbon_get_post_meta($recipe_id, 'recipe_likes');
    $recipe_dislikes = carbon_get_post_meta($recipe_id, 'recipe_dislikes');

    // Убираем голос пользователя
    delete_user_vote($recipe_id);

    // Возвращаем новые значения лайков и дизлайков
    echo json_encode(array('likes' => $recipe_likes, 'dislikes' => $recipe_dislikes));
    wp_die();
}
add_action('wp_ajax_remove_vote', 'remove_vote');

// Проверка голоса пользователя
function check_user_vote() {
    $recipe_id = $_POST['recipe_id'];
    $user_vote = get_user_vote($recipe_id);

    // Возвращаем состояние голоса пользователя
    echo json_encode(array('user_vote' => $user_vote));
    wp_die();
}
add_action('wp_ajax_check_user_vote', 'check_user_vote');

// Функция для получения голоса пользователя
function get_user_vote($recipe_id) {
    $user_id = get_current_user_id();
    $user_vote = get_post_meta($recipe_id, 'user_vote_' . $user_id, true);
    return $user_vote ? $user_vote : 'none';
}

// Функция для удаления голоса пользователя
function delete_user_vote($recipe_id) {
    $user_id = get_current_user_id();
    delete_post_meta($recipe_id, 'user_vote_' . $user_id);
}
