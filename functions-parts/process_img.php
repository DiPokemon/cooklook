<?php
function process_recipe_pin_image($image_path, $output_path, $logo_path, $post_title) {
    try {
        // Создаем объект Imagick для исходного изображения
        $image = new Imagick($image_path);

        // Получаем размеры исходного изображения
        $image_width = $image->getImageWidth();
        $image_height = $image->getImageHeight();

        // Создаем новое изображение в формате 2:3
        $aspect_ratio = 2 / 3;
        $new_width = $image_width;
        $new_height = (int)($image_width * $aspect_ratio);

        if ($new_height > $image_height) {
            $new_height = $image_height;
            $new_width = (int)($image_height / $aspect_ratio);
        }

        $new_image = new Imagick();
        $new_image->newImage($new_width, $new_height, new ImagickPixel('white'));
        $new_image->compositeImage($image, Imagick::COMPOSITE_OVER, ($new_width - $image_width) / 2, ($new_height - $image_height) / 2);

        // Добавляем градиент
        $gradient = new Imagick();
        $gradient->newPseudoImage($new_width, $new_height, "gradient:white-transparent-white");

        $new_image->compositeImage($gradient, Imagick::COMPOSITE_OVER, 0, 0);

        // Добавляем логотип
        $logo = new Imagick($logo_path);
        $logo_width = $logo->getImageWidth();
        $logo_height = $logo->getImageHeight();
        $logo_x = ($new_width - $logo_width) / 2;
        $logo_y = $new_height - $logo_height - 20;

        $new_image->compositeImage($logo, Imagick::COMPOSITE_OVER, $logo_x, $logo_y);

        // Добавляем название записи
        $draw = new ImagickDraw();
        $draw->setFontSize(30);
        $draw->setFillColor('black');
        $draw->setTextAlignment(Imagick::ALIGN_CENTER);

        $title_x = $new_width / 2;
        $title_y = 40;

        $new_image->annotateImage($draw, $title_x, $title_y, 0, $post_title);

        // Сохраняем новое изображение
        $new_image->writeImage($output_path);

        // Освобождаем память
        $image->clear();
        $image->destroy();
        $new_image->clear();
        $new_image->destroy();
        $gradient->clear();
        $gradient->destroy();
        $logo->clear();
        $logo->destroy();
    } catch (Exception $e) {
        error_log('Ошибка при обработке изображения: ' . $e->getMessage());
    }
}

function process_image($image_path, $watermark_path, $output_path, $corner_watermark_path) {
    try {
        // Создаем объект Imagick для исходного изображения
        $image = new Imagick($image_path);

        // Отражаем изображение по горизонтали
        $image->flopImage();

        // Изменяем цветовую гамму изображения
        $image->modulateImage(90, 110, 105); // Пример изменения цветовой гаммы

        // Создаем полупрозрачный водяной знак и увеличиваем его прозрачность
        $watermark = new Imagick($watermark_path);
        $watermark->evaluateImage(Imagick::EVALUATE_MULTIPLY, 0.3, Imagick::CHANNEL_ALPHA); // Устанавливаем прозрачность 70%
        $watermark->rotateImage(new ImagickPixel('none'), -45); // Поворачиваем водяной знак на -45 градусов

        // Накладываем водяной знак плиткой по всему изображению
        $draw = new ImagickDraw();
        $image_width = $image->getImageWidth();
        $image_height = $image->getImageHeight();
        $watermark_width = $watermark->getImageWidth();
        $watermark_height = $watermark->getImageHeight();
        $offset = 50;

        for ($x = 0; $x < $image_width; $x += $watermark_width + $offset) {
            for ($y = 0; $y < $image_height; $y += $watermark_height + $offset) {
                $draw->composite($watermark->getImageCompose(), $x, $y, 0, 0, $watermark);
            }
        }

        $image->drawImage($draw);

        // Накладываем водяной знак в угол
        $corner_watermark = new Imagick($corner_watermark_path);
        $corner_x = $image->getImageWidth() - $corner_watermark->getImageWidth() - 10; // С отступом 10 пикселей
        $corner_y = $image->getImageHeight() - $corner_watermark->getImageHeight() - 10; // С отступом 10 пикселей
        $corner_watermark->evaluateImage(Imagick::EVALUATE_MULTIPLY, 0.5, Imagick::CHANNEL_ALPHA); // Устанавливаем прозрачность 50%
        $image->compositeImage($corner_watermark, Imagick::COMPOSITE_OVER, $corner_x, $corner_y);

        // Сохраняем новое изображение
        $image->writeImage($output_path);

        // Освобождаем память
        $image->clear();
        $image->destroy();
        $watermark->clear();
        $watermark->destroy();
        $corner_watermark->clear();
        $corner_watermark->destroy();
    } catch (Exception $e) {
        error_log('Ошибка при обработке изображения: ' . $e->getMessage());
    }
}

function custom_process_attachment($attachment_id) {
    // Получаем путь к загруженному изображению
    $image_path = get_attached_file($attachment_id);

    // Путь к водяному знаку
    $watermark_path = __DIR__ . '/../static/img/watermark.png';

    // Путь к водяному знаку для угла
    $corner_watermark_path = __DIR__ . '/../static/img/watermark_2.png';

    // Получаем название записи
    $post_id = attachment_url_to_postid(wp_get_attachment_url($attachment_id));
    $post_title = get_the_title($post_id);

    // Временный путь для сохранения обработанного изображения
    $temp_output_path = dirname($image_path) . '/processed_' . basename($image_path);

    // Обрабатываем изображение
    process_image($image_path, $watermark_path, $temp_output_path, $corner_watermark_path);

    // Замена оригинального изображения на обработанное
    copy($temp_output_path, $image_path);
    unlink($temp_output_path);

    // Проверяем, является ли пост типом recipe
    if (get_post_type($post_id) == 'recipe') {
        // Путь для сохранения нового изображения в формате 2:3
        $recipe_pin_output_path = dirname($image_path) . '/recipe_pin_' . basename($image_path);

        // Обрабатываем изображение для recipe_pin_img
        process_recipe_pin_image($image_path, $recipe_pin_output_path, $watermark_path, $post_title);

        // Сохраняем путь обработанного изображения в кастомное поле Carbon Fields
        carbon_set_post_meta($post_id, 'recipe_pin_img', wp_get_attachment_url($attachment_id));
    }
}
add_action('add_attachment', 'custom_process_attachment');
?>
