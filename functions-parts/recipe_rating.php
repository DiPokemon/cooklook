<?php
function handle_recipe_like() {
    // Получаем ID записи и тип лайка
    $post_id = $_POST['post_id'];
    $like_type = $_POST['like_type'];

    // Получаем ID пользователя
    $user_id = get_current_user_id();

    // Проверяем, оценивал ли уже пользователь эту запись
    $user_likes = get_user_meta($user_id, 'user_recipe_likes', true);
    $user_dislikes = get_user_meta($user_id, 'user_recipe_dislikes', true);

    if ($like_type === 'like') {
        if (!in_array($post_id, $user_likes)) {
            // Если пользователь еще не поставил лайк, но поставил дизлайк - убираем дизлайк
            if (in_array($post_id, $user_dislikes)) {
                $recipe_dislikes = carbon_get_post_meta($post_id, 'recipe_dislikes');
                carbon_set_post_meta($post_id, 'recipe_dislikes', $recipe_dislikes - 1);
                $user_dislikes = array_diff($user_dislikes, array($post_id));
                update_user_meta($user_id, 'user_recipe_dislikes', $user_dislikes);
            }

            // Добавляем лайк
            $recipe_likes = carbon_get_post_meta($post_id, 'recipe_likes');
            carbon_set_post_meta($post_id, 'recipe_likes', $recipe_likes + 1);
            $user_likes[] = $post_id;
            update_user_meta($user_id, 'user_recipe_likes', $user_likes);
        }
    } elseif ($like_type === 'dislike') {
        if (!in_array($post_id, $user_dislikes)) {
            // Если пользователь еще не поставил дизлайк, но поставил лайк - убираем лайк
            if (in_array($post_id, $user_likes)) {
                $recipe_likes = carbon_get_post_meta($post_id, 'recipe_likes');
                carbon_set_post_meta($post_id, 'recipe_likes', $recipe_likes - 1);
                $user_likes = array_diff($user_likes, array($post_id));
                update_user_meta($user_id, 'user_recipe_likes', $user_likes);
            }

            // Добавляем дизлайк
            $recipe_dislikes = carbon_get_post_meta($post_id, 'recipe_dislikes');
            carbon_set_post_meta($post_id, 'recipe_dislikes', $recipe_dislikes + 1);
            $user_dislikes[] = $post_id;
            update_user_meta($user_id, 'user_recipe_dislikes', $user_dislikes);
        }
    }

    // Возвращаем обновленные значения лайков и дизлайков
    $response = array(
        'likes' => carbon_get_post_meta($post_id, 'recipe_likes'),
        'dislikes' => carbon_get_post_meta($post_id, 'recipe_dislikes')
    );

    wp_send_json($response);
    wp_die();
}

