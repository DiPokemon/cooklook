jQuery(document).ready(function ($) {
    // Функция для проверки состояния голоса пользователя и присвоения класса кнопке
    function checkUserVote() {
        var recipeID = $('.like_btn').attr('id').split('-')[1];
        $.ajax({
            type: 'POST',
            url: rating.ajax_url,
            data: {
                action: 'check_user_vote',
                recipe_id: recipeID
            },
            success: function(response) {
                if (response.user_vote === 'like') {
                    $('.like_btn').addClass('user_liked');
                } else if (response.user_vote === 'dislike') {
                    $('.dislike_btn').addClass('user_disliked');
                }
            }
        });
    }

    // Вызываем функцию checkUserVote() при загрузке страницы
    checkUserVote();

    // Обработчик клика по кнопке лайка
    $('.like_btn').on('click', function () {
        var recipeID = $(this).attr('id').split('-')[1];
        
        // Проверяем, есть ли у кнопки класс 'user_liked'
        if ($(this).hasClass('user_liked')) {
            // Если есть, отправляем запрос на удаление голоса
            $.ajax({
                type: 'POST',
                url: rating.ajax_url,
                data: {
                    action: 'remove_vote',
                    recipe_id: recipeID
                },
                success: function(response) {
                    $('.like_count').text(response.likes);
                    $('.dislike_count').text(response.dislikes);
                    // Убираем класс 'user_liked' с кнопки
                    $('.like_btn').removeClass('user_liked');
                }
            });
        } else {
            // Если класса нет, отправляем запрос на постановку лайка
            $.ajax({
                type: 'POST',
                url: rating.ajax_url,
                data: {
                    action: 'update_recipe_likes',
                    recipe_id: recipeID
                },
                success: function(response) {
                    $('.like_count').text(response.likes);
                    $('.dislike_count').text(response.dislikes);
                    // Добавляем класс 'user_liked' к кнопке
                    $('.like_btn').addClass('user_liked');
                    // Убираем класс 'user_disliked' с кнопки дизлайка
                    $('.dislike_btn').removeClass('user_disliked');
                }
            });
        }
    });

    // Обработчик клика по кнопке дизлайка
    $('.dislike_btn').on('click', function () {
        var recipeID = $(this).attr('id').split('-')[1];
        
        // Проверяем, есть ли у кнопки класс 'user_disliked'
        if ($(this).hasClass('user_disliked')) {
            // Если есть, отправляем запрос на удаление голоса
            $.ajax({
                type: 'POST',
                url: rating.ajax_url,
                data: {
                    action: 'remove_vote',
                    recipe_id: recipeID
                },
                success: function(response) {
                    $('.like_count').text(response.likes);
                    $('.dislike_count').text(response.dislikes);
                    // Убираем класс 'user_disliked' с кнопки
                    $('.dislike_btn').removeClass('user_disliked');
                }
            });
        } else {
            // Если класса нет, отправляем запрос на постановку дизлайка
            $.ajax({
                type: 'POST',
                url: rating.ajax_url,
                data: {
                    action: 'update_recipe_dislikes',
                    recipe_id: recipeID
                },
                success: function(response) {
                    $('.like_count').text(response.likes);
                    $('.dislike_count').text(response.dislikes);
                    // Добавляем класс 'user_disliked' к кнопке
                    $('.dislike_btn').addClass('user_disliked');
                    // Убираем класс 'user_liked' с кнопки лайка
                    $('.like_btn').removeClass('user_liked');
                }
            });
        }
    });
});
