<?php
/*
*Template name: Строка с результатом поиска в модальном окне
*/
?>

<a href="<?= esc_url(get_permalink()) ?>" class="search_result">
    <?php if(get_the_post_thumbnail_url(get_the_ID(), 'thumbnail') ) : ?>
        <img src="<?= esc_url(get_the_post_thumbnail_url(get_the_ID(), 'thumbnail')) ?>" alt="" class="search_result-image">
    <?php else: ?>
        <img src="<?= get_template_directory_uri() . '/static/img/no_image.png' ?>" alt="" class="search_result-image">
    <?php endif; ?>
    <span class="search_result-title">
        <?= esc_html(get_the_title()) ?>
    </span>
</a>