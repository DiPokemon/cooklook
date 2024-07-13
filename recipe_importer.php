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
                formData.append('action', 'start_import');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            importBatch(response.data.batch_size, response.data.total_batches, 1);
                        } else {
                            $('#import-progress').html('<p>Ошибка: ' + response.data + '</p>');
                        }
                    },
                    error: function() {
                        $('#import-progress').html('<p>Произошла ошибка при импорте.</p>');
                    }
                });
            });

            function importBatch(batch_size, total_batches, current_batch) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'import_recipes_batch',
                        batch_size: batch_size,
                        current_batch: current_batch
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#import-progress').html('<p>Импортировано батч ' + current_batch + ' из ' + total_batches + '.</p>');
                            if (current_batch < total_batches) {
                                importBatch(batch_size, total_batches, current_batch + 1);
                            } else {
                                $('#import-progress').html('<p>Импорт завершен.</p>');
                            }
                        } else {
                            $('#import-progress').html('<p>Ошибка: ' + response.data + '</p>');
                        }
                    },
                    error: function() {
                        $('#import-progress').html('<p>Произошла ошибка при импорте.</p>');
                    }
                });
            }
        });
    </script>
    <?php
}

// Функция загрузки изображения по URL и установки его как изображение записи
function set_post_thumbnail_from_url( $post_id, $url ) {
    if ( empty( $url ) ) {
        return;
    }

    $tmp = download_url( $url );

    if ( is_wp_error( $tmp ) ) {
        return;
    }

    $file_array = array(
        'name'     => basename( $url ),
        'tmp_name' => $tmp,
    );

    $file_info = wp_check_filetype( $file_array['name'] );
    if ( ! in_array( $file_info['type'], array( 'image/jpeg', 'image/jpg', 'image/gif', 'image/png' ) ) ) {
        @unlink( $tmp );
        return;
    }

    $attachment_id = media_handle_sideload( $file_array, $post_id );

    if ( is_wp_error( $attachment_id ) ) {
        @unlink( $tmp );
        return;
    }

    set_post_thumbnail( $post_id, $attachment_id );
}

// Начало импорта
function ajax_start_import() {
    if ( ! isset( $_FILES['csv_file']['tmp_name'] ) ) {
        wp_send_json_error( 'No file uploaded.' );
    }

    $file_path = $_FILES['csv_file']['tmp_name'];
    if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
        wp_send_json_error( 'File is not readable or does not exist.' );
    }

    $header = null;
    $rows = array();

    if ( ( $handle = fopen( $file_path, 'r' ) ) !== false ) {
        while ( ( $row = fgetcsv( $handle, 1000, ',' ) ) !== false ) {
            if ( ! $header ) {
                $header = $row;
            } else {
                if ( count( $header ) === count( $row ) ) {
                    $rows[] = array_combine( $header, $row );
                }
            }
        }
        fclose( $handle );
    }

    // Сохранить данные в временное хранилище (например, транзиент)
    $batch_size = 100;
    $total_batches = ceil( count( $rows ) / $batch_size );
    set_transient( 'recipe_import_rows', $rows, 3600 );

    wp_send_json_success( array( 'batch_size' => $batch_size, 'total_batches' => $total_batches ) );
}

// Импорт батча
function ajax_recipe_import_batch() {
    $batch_size = intval( $_POST['batch_size'] );
    $current_batch = intval( $_POST['current_batch'] );
    $rows = get_transient( 'recipe_import_rows' );

    if ( ! $rows ) {
        wp_send_json_error( 'No data to import.' );
    }

    $start = ( $current_batch - 1 ) * $batch_size;
    $end = $start + $batch_size;
    $rows_to_import = array_slice( $rows, $start, $end );

    foreach ( $rows_to_import as $recipe_data ) {
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
            //carbon_set_post_meta( $post_id, 'recipe_portions', sanitize_text_field( $recipe_data['portions'] ) );
            //carbon_set_post_meta( $post_id, 'prep_time', sanitize_text_field( $recipe_data['prep_time'] ) );
            //carbon_set_post_meta( $post_id, 'cook_time', sanitize_text_field( $recipe_data['cook_time'] ) );
            //carbon_set_post_meta( $post_id, 'recipe_likes', sanitize_text_field( $recipe_data['recipe_like'] ) );
            //carbon_set_post_meta( $post_id, 'recipe_dislikes', sanitize_text_field( $recipe_data['recipe_dislike'] ) );
            //carbon_set_post_meta( $post_id, 'recipe_calories', sanitize_text_field( $recipe_data['recipe_calories'] ) );
            //carbon_set_post_meta( $post_id, 'recipe_protein', sanitize_text_field( $recipe_data['recipe_protein'] ) );
            //carbon_set_post_meta( $post_id, 'recipe_fat', sanitize_text_field( $recipe_data['recipe_fat'] ) );
            //carbon_set_post_meta( $post_id, 'recipe_carbs', sanitize_text_field( $recipe_data['recipe_carbs'] ) );
            //carbon_set_post_meta( $post_id, 'recipe_region', sanitize_text_field( $recipe_data['recipe_region'] ) );
            //carbon_set_post_meta( $post_id, 'recipe_url', esc_url_raw( $recipe_data['recipe_url'] ) );

            if ( ! empty( $recipe_data['image'] ) ) {
                set_post_thumbnail_from_url( $post_id, esc_url_raw( $recipe_data['image'] ) );
            }

            if ( ! empty( $recipe_data['ingredients_names'] ) && ! empty( $recipe_data['ingredients_quantities'] ) ) {
                $ingredients_names = explode( ' | ', sanitize_text_field( $recipe_data['ingredients_names'] ) );
                $ingredients_quantities = explode( ' | ', sanitize_text_field( $recipe_data['ingredients_quantities'] ) );
                $ingredients = array();
                for ( $i = 0; $i < count( $ingredients_names ); $i++ ) {
                    $ingredients[] = array(
                        'ingridient_name'  => $ingredients_names[ $i ],
                        'ingridient_value' => $ingredients_quantities[ $i ],
                    );
                }
                carbon_set_post_meta( $post_id, 'ingridients', $ingredients );
            }

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
    }

    wp_send_json_success( array( 'message' => 'Batch ' . $current_batch . ' imported.' ) );
}

// Добавление AJAX обработчиков
add_action( 'wp_ajax_start_import', 'ajax_start_import' );
add_action( 'wp_ajax_import_recipes_batch', 'ajax_recipe_import_batch' );
