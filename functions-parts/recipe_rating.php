<?php
// Проверка голоса пользователя
// Проверка голоса пользователя
function check_user_vote() {
    $recipe_id = $_POST['recipe_id'];
    $user_id = get_current_user_id();

    // Получаем голос пользователя для данного рецепта
    $user_vote = get_user_vote_meta($user_id, $recipe_id);

    // Определяем тип кнопки на основе голоса пользователя
    $button_type = 'none';
    if ($user_vote === 'like') {
        $button_type = 'like';
    } elseif ($user_vote === 'dislike') {
        $button_type = 'dislike';
    }

   
    // Возвращаем состояние голоса пользователя и тип кнопки
    echo json_encode(array('user_vote' => $user_vote, 'button_type' => $button_type));
    wp_die();
}


// Функция для определения типа кнопки (лайк или дизлайк) на основе голоса пользователя
function get_button_type($recipe_id, $user_vote) {
    if ($user_vote === 'like') {
        return 'like';
    } elseif ($user_vote === 'dislike') {
        return 'dislike';
    } else {
        // Если пользователь еще не проголосовал, возвращаем пустую строку
        return '';
    }
}

// Функция для получения голоса пользователя
function get_user_vote_meta($user_id, $recipe_id) {
    $user_votes = get_user_meta($user_id, 'user_votes', true);
    return isset($user_votes[$recipe_id]) ? $user_votes[$recipe_id] : 'none';
}

// Обновление голоса пользователя
function update_user_vote() {
    $recipe_id = $_POST['recipe_id'];
    $vote_type = $_POST['vote_type'];
    $user_id = get_current_user_id();

    // Получаем текущее количество лайков и дизлайков
    $recipe_likes = carbon_get_post_meta($recipe_id, 'recipe_likes');
    $recipe_dislikes = carbon_get_post_meta($recipe_id, 'recipe_dislikes');

    if ($vote_type === 'like') {
        // Если пользователь ставит лайк
        $recipe_likes++;
        // Если пользователь уже ставил дизлайк, уменьшаем количество дизлайков
        if (get_user_vote_meta($user_id, $recipe_id) === 'dislike') {
            $recipe_dislikes--;
        }
    } elseif ($vote_type === 'remove_like') {
        // Если пользователь удаляет лайк
        $recipe_likes--;
    } elseif ($vote_type === 'dislike') {
        // Если пользователь ставит дизлайк
        $recipe_dislikes++;
        // Если пользователь уже ставил лайк, уменьшаем количество лайков
        if (get_user_vote_meta($user_id, $recipe_id) === 'like') {
            $recipe_likes--;
        }
    } elseif ($vote_type === 'remove_dislike') {
        // Если пользователь удаляет дизлайк
        $recipe_dislikes--;
    }

    // Обновляем количество лайков и дизлайков в полях Carbon Fields
    carbon_set_post_meta($recipe_id, 'recipe_likes', $recipe_likes);
    carbon_set_post_meta($recipe_id, 'recipe_dislikes', $recipe_dislikes);

    // Обновляем информацию о голосе пользователя в метаданных его профиля
    update_user_vote_meta($user_id, $recipe_id, $vote_type);

    // Возвращаем новые значения лайков и дизлайков
    echo json_encode(array('likes' => $recipe_likes, 'dislikes' => $recipe_dislikes));
    wp_die();
}

// Функция для обновления голоса пользователя
function update_user_vote_meta($user_id, $recipe_id, $vote_type) {
    $user_votes = get_user_meta($user_id, 'user_votes', true) ?: array();
    $user_votes[$recipe_id] = $vote_type;
    update_user_meta($user_id, 'user_votes', $user_votes);
}

add_action('wp_ajax_check_user_vote', 'check_user_vote');
add_action('wp_ajax_update_user_vote', 'update_user_vote');

add_action('wp_ajax_nopriv_check_user_vote', 'check_user_vote');
add_action('wp_ajax_nopriv_update_user_vote', 'update_user_vote');