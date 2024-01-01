<?php
/*
* Template name: Карточка рецепта
*/
?>

<a href="" class="recipe_loop-item">
    <div class="recipe_loop-wrapper">
        <div class="img_block">
            <img src="<?= get_the_post_thumbnail_url(null, 'medium') ?>" alt="<?= __('Новый рецепт', 'cooklook') ?> <?= the_title() ?>">
            <div class="recipe_meta">
                <div class="top_meta">
                    <a href="" class="meta_category "></a>
                </div>
                <div class="bottom_meta"></div>
            </div>
        </div>
        <div class="recipe_loop-content">
            <div class="categories"></div>
            <h3 class="recipe_title"></h3>
            <div class="recipe_desc"></div>
            <div class="recipe_ingridients"></div>
        </div>
    </div>
</a>