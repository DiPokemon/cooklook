<?php
/*
* Template Name: Карточка категории
*/
$current_cat_id = $args['current_cat_id'];
$slug = get_category($current_cat_id)->slug;
$category_image = carbon_get_term_meta( $current_cat_id, 'category_image' );
?>

<a href="<?= get_category_link($current_cat_id) ?>" class="category_item <?= $slug ?> flex">
    <div class="category_item-wrapper flex">        
        <img src="<?= $category_image  ?>" alt="<?= __('Рецепты в категории', 'cooklook') ?> <?= get_cat_name( $current_cat_id ) ?>">
        <h3 class="category_item-title"><?= get_cat_name( $current_cat_id ) ?></h3>
    </div>
</a>
