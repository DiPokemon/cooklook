<?php
/*
* Template Name: Карточка категории
*/
$current_cat_id = $args['current_cat_id'];
$category = $category = get_term($current_cat_id, 'recipe_category');
$cat_slug = $category->slug;
$cat_link = get_term_link($category);
$cat_name = $category->name;
$cat_image = carbon_get_term_meta( $current_cat_id, 'category_image' );
?>

<a href="<?= $cat_link ?>" class="category_item <?= $cat_slug ?> flex">
    <div class="category_item-wrapper flex">  
        <?php if ($cat_image):?>
            <img src="<?= $cat_image  ?>" alt="<?= __('Рецепты в категории', 'cooklook') ?> <?= $cat_name ?>">
            <h3 class="category_item-title"><?= $cat_name ?></h3>
        <?php else: ?>
            <h3 class="category_item-title"><?= $cat_name ?></h3>
        <?php endif ?>        
    </div>
</a>
