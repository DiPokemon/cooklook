<?php
/**
 * Plugin Name: Recipe Importer
 * Description: Импорт рецептов из CSV файла в кастомный тип записи 'recipe'.
 * Version: 1.0
 * Author: Ваше имя
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Подключение Carbon Fields
add_action( 'after_setup_theme', 'crb_load' );

function crb_missing_plugin_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e( 'Carbon Fields library is required for the Recipe Importer plugin to work. Please make sure it is installed.', 'recipe-importer' ); ?></p>
    </div>
    <?php
}

// Добавление страницы плагина в админку
function recipe_importer_menu() {
    add_menu_page( 'Recipe Importer', 'Recipe Importer', 'manage_options', 'recipe-importer', 'recipe_importer_page' );
}
add_action( 'admin_menu', 'recipe_importer_menu' );

// Страница плагина
function recipe_importer_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Импорт рецептов из CSV', 'recipe-importer' ); ?></h1>
        <form id="recipe-import-form" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'recipe_import', 'recipe_import_nonce' ); ?>
            <input type="file" name="csv_file" accept=".csv">
            <?php submit_button( __( 'Импортировать', 'recipe-importer' ) ); ?>
        </form>
        <div id="import-progress"></div>
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#recipe-import-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'import_recipes');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#import-progress').html('<p>Импорт завершен.</p>');
                        } else {
                            $('#import-progress').html('<p>Ошибка: ' + response.data + '</p>');
                        }
                    },
                    error: function() {
                        $('#import-progress').html('<p>Произошла ошибка при импорте.</p>');
                    }
                });
            });
        });
    </script>
    <?php
}

// Функция загрузки изображения по URL и установки его как изображение записи
function set_post_thumbnail_from_url( $post_id, $url ) {
    if ( empty( $url ) ) {
        return;
    }

    // Загрузить изображение по URL
    $tmp = download_url( $url );

    // Проверка на ошибку загрузки
    if ( is_wp_error( $tmp ) ) {
        return;
    }

    // Получить имя файла из URL
    $file_array = array(
        'name'     => basename( $url ),
        'tmp_name' => $tmp,
    );

    // Проверка на mime тип файла
    $file_info = wp_check_filetype( $file_array['name'] );
    if ( ! in_array( $file_info['type'], array( 'image/jpeg', 'image/jpg', 'image/gif', 'image/png' ) ) ) {
        @unlink( $tmp );
        return;
    }

    // Загрузить файл в медиа-библиотеку
    $attachment_id = media_handle_sideload( $file_array, $post_id );

    // Проверка на ошибки при загрузке
    if ( is_wp_error( $attachment_id ) ) {
        @unlink( $tmp );
        return;
    }

    // Установить изображение как изображение записи
    set_post_thumbnail( $post_id, $attachment_id );
}

// Функция импорта CSV файла
function ajax_recipe_import_from_csv() {
    if ( ! isset( $_FILES['csv_file']['tmp_name'] ) ) {
        wp_send_json_error( 'No file uploaded.' );
    }

    $file_path = $_FILES['csv_file']['tmp_name'];
    if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
        wp_send_json_error( 'File is not readable or does not exist.' );
    }

    $header = null;

    if ( ( $handle = fopen( $file_path, 'r' ) ) !== false ) {
        while ( ( $row = fgetcsv( $handle, 1000, ',' ) ) !== false ) {
            if ( ! $header ) {
                $header = $row;
            } else {
                if ( count( $header ) === count( $row ) ) {
                    $recipe_data = array_combine( $header, $row );

                    // Проверка на существование рецепта по recipe_id
                    $existing_recipe = new WP_Query( array(
                        'post_type'  => 'recipe',
                        'meta_key'   => '_recipe_id',
                        'meta_value' => sanitize_text_field( $recipe_data['recipe_id'] ),
                        'post_status' => 'any',
                    ) );

                    if ( $existing_recipe->have_posts() ) {
                        $existing_recipe->the_post();
                        $post_id = get_the_ID();

                        // Используем Carbon Fields для сохранения мета-полей
                        //update_recipe_meta_fields($post_id, $recipe_data);

                        // Установить изображение записи
                        if ( ! empty( $recipe_data['image'] ) ) {
                            set_post_thumbnail_from_url( $post_id, esc_url_raw( $recipe_data['image'] ) );
                        }

                        // Импорт тегов ингредиентов
                        // if ( ! empty( $recipe_data['ingredients_names'] ) ) {
                        //     $ingredients_tags = array_map( 'trim', explode( ' | ', $recipe_data['ingredients_names'] ) );
                        //     wp_set_object_terms( $post_id, $ingredients_tags, 'recipe_tags' );
                        // }

                        // Обработка ингредиентов
                        if ( ! empty( $recipe_data['ingredients_names'] ) && ! empty( $recipe_data['ingredients_quantities'] ) ) {
                            $ingredients_names = explode( ' | ', sanitize_text_field( $recipe_data['ingredients_names'] ) );
                            $ingredients_quantities = explode( ' | ', sanitize_text_field( $recipe_data['ingredients_quantities'] ) );
                            $ingredients = array();
                            for ( $i = 0; $i < count( $ingredients_names ); $i++ ) {
                                $ingredients[] = array(
                                    'ingredient_name'  => $ingredients_names[ $i ],
                                    'ingredient_value' => $ingredients_quantities[ $i ],
                                );
                            }
                            carbon_set_post_meta( $post_id, 'ingredients', $ingredients );
                        }

                        // Обработка шагов
                        if ( ! empty( $recipe_data['steps_texts'] ) ) {
                            $steps_texts = explode( ' | ', sanitize_textarea_field( $recipe_data['steps_texts'] ) );
                            $steps = array();
                            for ( $i = 0; $i < count( $steps_texts ); $i++ ) {
                                $steps[] = array(
                                    'recipe_step_text'  => $steps_texts[ $i ],
                                );
                            }
                            carbon_set_post_meta( $post_id, 'recipe_step', $steps );
                        }

                        if ( ! empty( $recipe_data['original_steps_texts'] ) ) {
                            $original_steps_texts = explode( ' | ', sanitize_textarea_field( $recipe_data['original_steps_texts'] ) );
                            $original_steps = array();
                            for ( $i = 0; $i < count( $original_steps_texts ); $i++ ) {
                                $original_steps[] = array(
                                    'original_recipe_step_text'  => $original_steps_texts[ $i ],
                                );
                            }
                            carbon_set_post_meta( $post_id, 'original_recipe_step', $original_steps );
                        }
                    } else {
                        error_log( 'Recipe with recipe_id ' . sanitize_text_field( $recipe_data['recipe_id'] ) . ' does not exist. Skipping row.' );
                    }
                } else {
                    // Обработка строки с несоответствующим количеством элементов
                    error_log( 'CSV row length does not match header length. Skipping row.' );
                }
            }
        }
        fclose( $handle );
    }

    wp_send_json_success( array( 'message' => 'Импорт завершен.' ) );
}

// Функция для обновления мета-полей рецепта
function update_recipe_meta_fields( $post_id, $recipe_data ) {
    $meta_fields = array(
        'recipe_portions' => 'portions',
        'recipe_time' => 'recipe_time',
        'prep_time' => 'prep_time',
        'cook_time' => 'cook_time',
        'recipe_likes' => 'recipe_like',
        'recipe_dislikes' => 'recipe_dislike',
        'recipe_calories' => 'recipe_calories',
        'recipe_protein' => 'recipe_protein',
        'recipe_fat' => 'recipe_fat',
        'recipe_carbs' => 'recipe_carbs',
        'recipe_region' => 'recipe_region',
        'recipe_url' => 'recipe_url'
    );

    foreach ( $meta_fields as $meta_key => $csv_key ) {
        if ( ! empty( $recipe_data[ $csv_key ] ) ) {
            carbon_set_post_meta( $post_id, $meta_key, sanitize_text_field( $recipe_data[ $csv_key ] ) );
        }
    }
}

// Добавление AJAX обработчика
add_action( 'wp_ajax_import_recipes', 'ajax_recipe_import_from_csv' );
