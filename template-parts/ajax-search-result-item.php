<?php
/*
*Template name: Строка с результатом поиска в модальном окне
*/
?>

<a href="<?= get_permalink() ?>" class="search_result">
    <img src="<?= get_the_post_thumbnail_url(get_the_ID(), 'thumbnail') ?>" alt="" class="search_result-image">
    <span class="search_result-title">
        <?= get_the_title() ?>
    </span>
</a>