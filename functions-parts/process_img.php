<?php
function process_recipe_pin_image($image_path, $output_path, $logo_path, $post_title) {
    if (!class_exists('Imagick') || !is_readable($image_path) || !is_readable($logo_path)) { return false; }
    try {
        // Создаем объект Imagick для исходного изображения
        $image = new Imagick($image_path);

        // Получаем размеры исходного изображения
        $image_width = $image->getImageWidth();
        $image_height = $image->getImageHeight();

        // Создаем новое изображение в формате 2:3
        $aspect_ratio = 2 / 3;
        $new_width = $image_width;
        $new_height = (int)($image_width / $aspect_ratio);

        if ($new_height > $image_height) {
            $new_height = $image_height;
            $new_width = (int)($image_height * $aspect_ratio);
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
        return true;
    } catch (Exception $e) {
        error_log('Cooklook: image processing failed.');
        return false;
    }
}

function process_image($image_path, $watermark_path, $output_path, $corner_watermark_path) {
    if (!class_exists('Imagick') || !is_readable($image_path) || !is_readable($watermark_path) || !is_readable($corner_watermark_path)) { return false; }
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
        return true;
    } catch (Exception $e) {
        error_log('Cooklook: image processing failed.');
        return false;
    }
}

function custom_process_attachment($attachment_id) {
    $attachment = get_post($attachment_id);
    if (!$attachment || 'recipe' !== get_post_type($attachment->post_parent) || !wp_attachment_is_image($attachment_id) || !class_exists('Imagick')) {
        return;
    }
    $image_path = get_attached_file($attachment_id);
    if (!$image_path || !is_readable($image_path) || !is_writable($image_path)) { return; }
    $watermark = get_template_directory() . '/static/img/watermark.png';
    $corner = get_template_directory() . '/static/img/watermark_2.png';
    $output = dirname($image_path) . '/' . wp_unique_filename(dirname($image_path), 'processed_' . basename($image_path));
    if (process_image($image_path, $watermark, $output, $corner) && is_file($output) && filesize($output) > 0 && wp_getimagesize($output)) {
        // Replace only a fully generated, decodable image; a failed conversion leaves the source intact.
        rename($output, $image_path);
    }
    if (is_file($output)) { wp_delete_file($output); }
}
add_action('add_attachment', 'custom_process_attachment');
