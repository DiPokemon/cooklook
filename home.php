<?php
/*
* Template name: Главная
*/
?>

<?php
if (extension_loaded('imagick')) {
    $imagick = new Imagick();
    echo 'ImageMagick версии ' . $imagick->getVersion() . ' установлен.';
} else {
    echo 'ImageMagick не установлен.';
}
?>