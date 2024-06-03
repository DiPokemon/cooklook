<?php
function process_image($image_path, $watermark_path, $output_path) {
    try {
        // Создаем объект Imagick для основного изображения
        $image = new Imagick($image_path);

        // Устанавливаем пределы для использования памяти и процессорного времени
        $image->setResourceLimit(Imagick::RESOURCETYPE_MEMORY, 256);
        $image->setResourceLimit(Imagick::RESOURCETYPE_MAP, 256);

        // Отзеркаливаем изображение по горизонтали
        $image->flopImage();

        // Создаем объект Imagick для водяного знака
        $watermark = new Imagick($watermark_path);
        
        // Устанавливаем прозрачный фон для водяного знака
        $watermark->setImageBackgroundColor(new ImagickPixel('none'));

        // Уменьшаем прозрачность водяного знака
        $watermark->evaluateImage(Imagick::EVALUATE_MULTIPLY, 0.1, Imagick::CHANNEL_ALPHA);

        // Получаем размеры основного изображения
        $image_width = $image->getImageWidth();
        $image_height = $image->getImageHeight();

        // Получаем размеры водяного знака
        $watermark_width = $watermark->getImageWidth();
        $watermark_height = $watermark->getImageHeight();

        // Устанавливаем отступы
        $initial_offset_x = 20;
        $initial_offset_y = 20;
        $horizontal_spacing = 100;
        $vertical_spacing = 50;

        // Повторяем водяной знак узором кирпичной стены
        for ($y = $initial_offset_y; $y < $image_height; $y += $watermark_height + $vertical_spacing) {
            for ($x = $initial_offset_x; $x < $image_width; $x += $watermark_width + $horizontal_spacing) {
                $offset_x = ($y % (2 * ($watermark_height + $vertical_spacing))) ? 0 : ($watermark_width + $horizontal_spacing) / 2;
                $image->compositeImage($watermark, Imagick::COMPOSITE_OVER, $x + $offset_x, $y);
            }
        }

        // Слегка изменяем цвета изображения (например, увеличиваем насыщенность)
        $image->modulateImage(100, 110, 100); // (яркость, насыщенность, оттенок)

        // Сохраняем обработанное изображение
        $image->writeImage($output_path);

        // Освобождаем память
        $image->clear();
        $image->destroy();
        $watermark->clear();
        $watermark->destroy();
    } catch (Exception $e) {
        error_log('Ошибка при обработке изображения: ' . $e->getMessage());
    }
}

function custom_process_attachment($attachment_id) {
    // Получаем путь к загруженному изображению
    $image_path = get_attached_file($attachment_id);
    
    // Путь к водяному знаку
    $watermark_path = __DIR__ . '/../static/watermark.png';
    
    // Временный путь для сохранения обработанного изображения
    $temp_output_path = dirname($image_path) . '/processed_' . basename($image_path);

    // Обрабатываем изображение
    process_image($image_path, $watermark_path, $temp_output_path);

    // Замена оригинального изображения на обработанное
    copy($temp_output_path, $image_path);
    unlink($temp_output_path);
}
add_action('add_attachment', 'custom_process_attachment');
