<?php
/*
*Template name: Строка с результатом поиска в модальном окне
*/
?>

<a href="<?= get_permalink() ?>" class="search_result">
    <?php if(get_the_post_thumbnail_url(get_the_ID(), 'thumbnail') ) : ?>
        <img src="<?= get_the_post_thumbnail_url(get_the_ID(), 'thumbnail') ?>" alt="" class="search_result-image">
    <?php else: ?>
        <img src="<?= get_template_directory_uri() . '/static/img/no_image.png' ?>" alt="" class="search_result-image">
    <?php endif; ?>
    <span class="search_result-title">
        <?= get_the_title() ?>
    </span>
</a>