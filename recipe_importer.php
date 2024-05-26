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
function crb_load() {
    require_once( 'carbon-fields/vendor/autoload.php' );
    \Carbon_Fields\Carbon_Fields::boot();
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
        <h1>Импорт рецептов из CSV</h1>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv">
            <?php submit_button( 'Импортировать' ); ?>
        </form>
        <?php
        if ( ! empty( $_FILES['csv_file']['tmp_name'] ) ) {
            recipe_import_from_csv( $_FILES['csv_file']['tmp_name'] );
        }
        ?>
    </div>
    <?php
}

// Функция импорта CSV файла
function recipe_import_from_csv( $file_path ) {
    if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
        return false;
    }

    $header = null;
    $data = array();
    if ( ( $handle = fopen( $file_path, 'r' ) ) !== false ) {
        while ( ( $row = fgetcsv( $handle, 1000, ',' ) ) !== false ) {
            if ( ! $header ) {
                $header = $row;
            } else {
                $data[] = array_combine( $header, $row );
            }
        }
        fclose( $handle );
    }

    foreach ( $data as $recipe_data ) {
        $post_id = wp_insert_post( array(
            'post_title'   => $recipe_data['title'],
            'post_content' => $recipe_data['about'],
            'post_type'    => 'recipe',
            'post_status'  => 'publish',
        ) );

        if ( ! is_wp_error( $post_id ) ) {
            // Используем Carbon Fields для сохранения мета-полей
            carbon_set_post_meta( $post_id, 'recipe_portions', $recipe_data['portions'] );
            carbon_set_post_meta( $post_id, 'recipe_time', $recipe_data['recipe_time'] );
            carbon_set_post_meta( $post_id, 'recipe_id', $recipe_data['recipe_id'] );
            carbon_set_post_meta( $post_id, 'recipe_likes', $recipe_data['recipe_like'] );
            carbon_set_post_meta( $post_id, 'recipe_dislikes', $recipe_data['recipe_dislike'] );
            carbon_set_post_meta( $post_id, 'recipe_calories', $recipe_data['recipe_calories'] );
            carbon_set_post_meta( $post_id, 'recipe_protein', $recipe_data['recipe_protein'] );
            carbon_set_post_meta( $post_id, 'recipe_fat', $recipe_data['recipe_fat'] );
            carbon_set_post_meta( $post_id, 'recipe_carbs', $recipe_data['recipe_carbs'] );
            carbon_set_post_meta( $post_id, 'recipe_region', $recipe_data['recipe_region'] );

            // Обработка ингредиентов
            if ( ! empty( $recipe_data['ingredients_names'] ) && ! empty( $recipe_data['ingredients_quantities'] ) ) {
                $ingredients_names = explode( ' | ', $recipe_data['ingredients_names'] );
                $ingredients_quantities = explode( ' | ', $recipe_data['ingredients_quantities'] );
                $ingredients = array();
                for ( $i = 0; $i < count( $ingredients_names ); $i++ ) {
                    $ingredients[] = array(
                        'ingridient_name'  => $ingredients_names[ $i ],
                        'ingridient_value' => $ingredients_quantities[ $i ],
                    );
                }
                carbon_set_post_meta( $post_id, 'ingridients', $ingredients );
            }

            // Обработка шагов
            if ( ! empty( $recipe_data['steps_texts'] ) && ! empty( $recipe_data['steps_images'] ) ) {
                $steps_texts = explode( ' | ', $recipe_data['steps_texts'] );
                $steps_images = explode( ' | ', $recipe_data['steps_images'] );
                $steps = array();
                for ( $i = 0; $i < count( $steps_texts ); $i++ ) {
                    $steps[] = array(
                        'recipe_step_image' => $steps_images[ $i ],
                        'recipe_step_text'  => $steps_texts[ $i ],
                    );
                }
                carbon_set_post_meta( $post_id, 'recipe_step', $steps );
            }
        }
    }

    echo '<div class="updated"><p>Импорт завершен!</p></div>';
}
