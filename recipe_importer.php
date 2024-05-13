<?php
require_once('wp-load.php');

$csv = fopen('EXAMPLE.csv', 'r');
$headers = fgetcsv($csv);

while ($row = fgetcsv($csv)) {
    $data = array_combine($headers, $row);

    $post_id = wp_insert_post([
        'post_title'    => $data['title'],
        'post_content'  => $data['about'],
        'post_status'   => 'publish',
        'post_type'     => 'recipe',
        'meta_input'    => [
            'recipe_portions' => $data['portions'],
            'recipe_time'     => $data['recipe_time'],
            'recipe_id'       => $data['recipe_id'],
            'recipe_likes'    => $data['recipe_like'],
            'recipe_dislikes' => $data['recipe_dislike'],
            'recipe_views'    => $data['recipe_views'],
            'recipe_rating'   => $data['recipe_rating'],
            'recipe_calories' => $data['recipe_calories'],
            'recipe_protein'  => $data['recipe_protein'],
            'recipe_fat'      => $data['recipe_fat'],
            'recipe_carbs'    => $data['recipe_carbs'],
            'recipe_region'   => $data['recipe_region'],
        ]
    ]);

    if ($post_id) {
        // Импорт изображения
        if (!empty($data['image'])) {
            $image_id = media_sideload_image($data['image'], $post_id, null, 'id');
            if (!is_wp_error($image_id)) {
                set_post_thumbnail($post_id, $image_id);
            }
        }

        // Добавление категории
        if (!empty($data['categories'])) {
            wp_set_object_terms($post_id, explode('>', $data['categories']), 'recipe_category');
        }

        // Добавление тегов
        if (!empty($data['ingredients_tags'])) {
            wp_set_object_terms($post_id, explode(',', $data['ingredients_tags']), 'recipe_tags');
        }

        // Импорт ингредиентов
        $ingredients = [];
        for ($i = 1; isset($data["ingridient_name_$i"]); $i++) {
            if (!empty($data["ingridient_name_$i"]) && !empty($data["ingridient_value_$i"])) {
                $ingredients[] = [
                    'ingridient_name'  => $data["ingridient_name_$i"],
                    'ingridient_value' => $data["ingridient_value_$i"]
                ];
            }
        }
        carbon_set_post_meta($post_id, 'ingridients', $ingredients);

        // Импорт шагов приготовления
        $steps = [];
        for ($i = 1; $i <= 26; $i++) {
            if (!empty($data["recipe_step_text_list_$i"])) {
                $steps[] = [
                    'recipe_step_text'  => $data["recipe_step_text_list_$i"],
                    'recipe_step_image' => $data["recipe_step_image_url_list_$i"]
                ];
            }
        }
        carbon_set_post_meta($post_id, 'recipe_step', $steps);
    }
}

fclose($csv);
?>
