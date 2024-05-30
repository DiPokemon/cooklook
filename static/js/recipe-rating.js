jQuery(document).ready(function ($) {
    // Функция для проверки состояния голоса пользователя и присвоения класса кнопке
    function checkUserVote() {
        $('.like_btn, .dislike_btn').removeClass('active'); // Убираем класс .active у всех кнопок перед началом проверки
    
        $('.like_btn, .dislike_btn').each(function () {
            var button = $(this);
            var recipeID = button.attr('id').split('-')[1];
            $.ajax({
                type: 'POST',
                url: rating.ajax_url,
                data: {
                    action: 'check_user_vote',
                    recipe_id: recipeID
                },
                success: function(response) {
                    response = JSON.parse(response); // Преобразуем строку в объект
    
                    // Проверяем, есть ли класс .active у кнопки и соответствует ли он голосу пользователя
                    var isActiveButton = button.hasClass('active');
                    var isCorrectVote = (response.user_vote === 'like' && button.hasClass('like_btn')) ||
                                        (response.user_vote === 'dislike' && button.hasClass('dislike_btn'));
    
                    if (isCorrectVote && !isActiveButton) {
                        button.addClass('active'); // Добавляем класс .active только если кнопка соответствует голосу пользователя и не имеет этого класса
                    }
                }
            });
        });
    }

    // Функция для обновления рейтинга рецепта
    function updateRating(recipeID) {
        $.ajax({
            type: 'POST',
            url: rating.ajax_url,
            data: {
                action: 'get_recipe_rating',
                recipe_id: recipeID
            },
            success: function (response) {
                response = JSON.parse(response);
                $('#recipe_rating-' + recipeID).text(response.rating);
            }
        });
    }
    

    // Вызываем функцию checkUserVote() при загрузке страницы
    checkUserVote();

    // Обработчик клика по кнопке лайка
    $('.like_btn').on('click', function () {
        var button = $(this);
        var recipeID = button.attr('id').split('-')[1];
        var userVote = button.hasClass('active') ? 'remove_like' : 'like'; // Проверяем, стоит ли уже лайк от пользователя

        $.ajax({
            type: 'POST',
            url: rating.ajax_url,
            data: {
                action: 'update_user_vote',
                recipe_id: recipeID,
                vote_type: userVote // Передаем тип голоса (лайк, дизлайк или удаление голоса)
            },
            success: function (response) {
                // Убираем класс .active у всех кнопок
                $('.like_btn, .dislike_btn').removeClass('active');
                // Добавляем класс .active только к нажатой кнопке
                if (userVote === 'like') {
                    button.addClass('active');
                }
                updateRating(recipeID);
            }
        });
    });

    // Обработчик клика по кнопке дизлайка
    $('.dislike_btn').on('click', function () {
        var button = $(this);
        var recipeID = button.attr('id').split('-')[1];
        var userVote = button.hasClass('active') ? 'remove_dislike' : 'dislike'; // Проверяем, стоит ли уже дизлайк от пользователя

        $.ajax({
            type: 'POST',
            url: rating.ajax_url,
            data: {
                action: 'update_user_vote',
                recipe_id: recipeID,
                vote_type: userVote // Передаем тип голоса (лайк, дизлайк или удаление голоса)
            },
            success: function(response) {
                // Обновляем отображение количества лайков и дизлайков
                // $('.like_count').text(response.likes);
                // $('.dislike_count').text(response.dislikes);

                // Убираем класс .active у всех кнопок
                $('.like_btn, .dislike_btn').removeClass('active');
                // Добавляем класс .active только к нажатой кнопке
                if (userVote === 'dislike') {
                    button.addClass('active');
                }
                updateRating(recipeID);
            }
        });
    });

});
