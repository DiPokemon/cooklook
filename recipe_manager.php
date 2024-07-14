<?php
/**
 * Plugin Name: Recipe Manager
 * Description: Управление рецептами и их шагами.
 * Version: 1.0
 * Author: Ваше имя
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Добавление страницы управления рецептами в админку
function recipe_manager_menu() {
    add_menu_page( 'Recipe Manager', 'Recipe Manager', 'manage_options', 'recipe-manager', 'recipe_manager_page' );
}
add_action( 'admin_menu', 'recipe_manager_menu' );

// Функция для отображения страницы управления рецептами
function recipe_manager_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Управление рецептами', 'recipe-manager' ); ?></h1>

        <?php
        // Получение количества рецептов со статусом "Черновик" и "Опубликовано"
        $draft_count = wp_count_posts('recipe')->draft;
        $published_count = wp_count_posts('recipe')->publish;
        $total_count = $draft_count + $published_count;
        $published_percentage = ($total_count > 0) ? round(($published_count / $total_count) * 100, 2) : 0;
        ?>
        <style>
            #recipe-manager-container table{
                border-spacing: 0px;
            }
            #recipe-manager-container table tr td{
                border-bottom: 1px solid gray;
                padding: 5px 0px;
            }
            #recipe-manager-container table tr td.original_text{
                padding-right: 50px;
            }

            #recipe-info .recipe_header{
                display: flex;
                flex-direction: row;
                gap: 10px;
            }

            #recipe-info .recipe_header img{
                width: 50px;
                height: 50px;
                border-radius: 50%;
                object-fit: cover;
            }

            #recipe_time{
                display: flex;
                gap: 10px;
            }
        </style>
        <p><?php echo sprintf( __( 'Черновиков: %d | Опубликовано: %d | Процент опубликованных: %.2f%%', 'recipe-manager' ), $draft_count, $published_count, $published_percentage ); ?></p>
        
        <div id="recipe-info">
            <div class="recipe_header">
                <img id="recipe-image" src="" alt="" style="max-width: 100px; display: none;">
                <h2 id="recipe-title"></h2>
            </div>
            <p id="recipe-ingredients"></p>
        </div>

        <form id="editable-steps-form">
            <div id="recipe_time"></div>

            <div id="recipe-manager-container" style="padding: 50px 0px;">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 40%;"><?php //esc_html_e( 'Оригинальные шаги', 'recipe-manager' ); ?></th>
                            <th style="width: 60%;"><?php //esc_html_e( 'Редактируемые шаги', 'recipe-manager' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="steps-table-body">
                        <!-- Steps will be loaded here dynamically -->
                    </tbody>
                </table>
            </div>
            <div id="actions" style="margin-top: 20px;">
                <input type="hidden" id="current-recipe-id" name="recipe_id">
                <button type="button" id="save-steps" class="button button-primary"><?php esc_html_e( 'Сохранить', 'recipe-manager' ); ?></button>
                <button type="button" id="skip-recipe" class="button"><?php esc_html_e( 'Пропустить', 'recipe-manager' ); ?></button>
            </div>
        </form>
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            function adjustTextareaHeight(textarea) {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            }

            function loadRandomRecipe() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_random_draft_recipe'
                    },
                    success: function(response) {
                        if (response.success) {
                            var recipe = response.data;
                            $('#current-recipe-id').val(recipe.ID);
                            $('#recipe-title').html('<a href="' + recipe.edit_link + '" target="_blank">' + recipe.title + '</a>');
                            $('#recipe-ingredients').text('Ингредиенты: ' + recipe.ingredients.join(', '));
                            if (recipe.image) {
                                $('#recipe-image').attr('src', recipe.image).show();
                            } else {
                                $('#recipe-image').hide();
                            }
                            $('#steps-table-body').empty();
                            $('#recipe_time').empty();

                            recipe.original_steps.forEach(function(step, index) {
                                var row = '<tr>';
                                row += '<td class="original_text" style="vertical-align: top;">' + step + '</td>';
                                row += '<td style="vertical-align: top;"><textarea name="steps_texts[]" rows="3" style="width: 100%;" oninput="adjustTextareaHeight(this)">' + (recipe.steps[index] ? recipe.steps[index] : '') + '</textarea></td>';
                                row += '</tr>';
                                $('#steps-table-body').append(row);
                            });

                            if (recipe.time === 0) {
                                $('#recipe_time').append(
                                    '<input placeholder="Время приготовления" type="number" name="recipe_time" min="0">' +
                                    '<input placeholder="Время подготовки" type="number" name="recipe_prep" min="0">' +
                                    '<a href="' + recipe.url + '" target="_blank">Оригинальный рецепт</a>'
                                );
                            }

                            $('textarea').each(function() {
                                adjustTextareaHeight(this);
                            });
                        } else {
                            $('#recipe-title').text('');
                            $('#recipe-ingredients').text('');
                            $('#steps-table-body').html('<tr><td colspan="2">' + response.data + '</td></tr>');
                            $('#recipe_time').empty();
                        }
                    }
                });
            }

            $('#save-steps').on('click', function() {
                var formData = $('#editable-steps-form').serializeArray();
                var stepsEmpty = false;

                formData.forEach(function(item) {
                    if (item.name === 'steps_texts[]' && item.value.trim() === '') {
                        stepsEmpty = true;
                    }
                });

                if (stepsEmpty) {
                    alert('Все шаги должны быть заполнены.');
                    return;
                }

                console.log('Sending data:', formData); // Отладочная информация
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: $.param(formData) + '&action=save_recipe_steps',
                    success: function(response) {
                        console.log('Response:', response); // Отладочная информация
                        if (response.success) {
                            //alert('Шаги рецепта сохранены и рецепт опубликован.');
                            loadRandomRecipe();
                        } else {
                            alert('Ошибка: ' + response.data);
                        }
                    }
                });
            });

            $('#skip-recipe').on('click', function() {
                loadRandomRecipe();
            });

            loadRandomRecipe();
        });

        function adjustTextareaHeight(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        }
    </script>
    <?php
}

// Функция для получения случайного рецепта со статусом "Черновик"
function get_random_draft_recipe() {
    $args = array(
        'post_type' => 'recipe',
        'post_status' => 'draft',
        'posts_per_page' => 1,
        'orderby' => 'rand',
        'meta_query' => array(
            array(
                'key' => '_thumbnail_id',
                'compare' => 'EXISTS'
            )
        )
    );
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        $original_steps = carbon_get_post_meta($post_id, 'original_recipe_step');
        $steps = carbon_get_post_meta($post_id, 'recipe_step');
        $ingredients = wp_get_post_terms($post_id, 'recipe_tags', array('fields' => 'names'));
        $edit_link = get_edit_post_link($post_id);
        $image = get_the_post_thumbnail_url($post_id, 'medium');
        $recipe_time = get_post_meta($post_id, '_recipe_time', true);
        $recipe_prep = get_post_meta($post_id, '_recipe_prep', true);
        $recipe_url = get_post_meta($post_id, '_recipe_url', true);
        $data = array(
            'ID' => $post_id,
            'title' => get_the_title(),
            'original_steps' => wp_list_pluck($original_steps, 'original_recipe_step_text'),
            'steps' => wp_list_pluck($steps, 'recipe_step_text'),
            'ingredients' => $ingredients,
            'edit_link' => $edit_link,
            'image' => $image,
            'time' => $recipe_time ? $recipe_time : 0,
            'prep' => $recipe_prep ? $recipe_prep : 0,
            'url' => $recipe_url
        );
        wp_send_json_success($data);
    } else {
        wp_send_json_error('Нет рецептов со статусом "Черновик".');
    }
    wp_die();
}
add_action('wp_ajax_get_random_draft_recipe', 'get_random_draft_recipe');

// Функция для сохранения шагов рецепта и изменения статуса на "Опубликовано"
function save_recipe_steps() {
    if (isset($_POST['recipe_id']) && isset($_POST['steps_texts'])) {
        $post_id = intval($_POST['recipe_id']);
        $steps_texts = array_map('sanitize_textarea_field', $_POST['steps_texts']);
        $recipe_time = isset($_POST['recipe_time']) ? intval($_POST['recipe_time']) : 0;
        $recipe_prep = isset($_POST['recipe_prep']) ? intval($_POST['recipe_prep']) : 0;

        // Проверка на пустые шаги
        foreach ($steps_texts as $step) {
            if (empty($step)) {
                wp_send_json_error('Шаги рецепта не могут быть пустыми.');
                wp_die();
            }
        }

        // Обновляем шаги рецепта
        $steps = array();
        foreach ($steps_texts as $text) {
            $steps[] = array('recipe_step_text' => $text);
        }
        carbon_set_post_meta($post_id, 'recipe_step', $steps);

        // Обновляем время приготовления и подготовки
        update_post_meta($post_id, '_recipe_time', $recipe_time);
        update_post_meta($post_id, '_recipe_prep', $recipe_prep);

        // Изменяем статус на "Опубликовано"
        wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'publish'
        ));

        wp_send_json_success('Шаги рецепта сохранены и рецепт опубликован.');
    } else {
        wp_send_json_error('Недостаточно данных для сохранения.');
    }
    wp_die();
}
add_action('wp_ajax_save_recipe_steps', 'save_recipe_steps');
