<?php
/*
* Template Name: Карточка категории
*/
$current_cat_id = $args['current_cat_id'];
$category = get_term($current_cat_id, 'recipe_category');
if (!$category || is_wp_error($category)) { return; }
$cat_slug = $category->slug;
$cat_link = get_term_link($category);
$cat_name = $category->name;
$cat_image = carbon_get_term_meta( $current_cat_id, 'category_image' );
?>

<a href="<?= esc_url($cat_link) ?>" class="category_item <?= esc_attr($cat_slug) ?> flex">
    <div class="category_item-wrapper flex">  
        <?php if ($cat_image):?>
            <img src="<?= esc_url($cat_image) ?>" alt="<?= esc_attr(sprintf(__('Рецепты в категории %s', 'cooklook'), $cat_name)) ?>">
            <h3 class="category_item-title"><?= esc_html($cat_name) ?></h3>
        <?php else: ?>
            <h3 class="category_item-title"><?= esc_html($cat_name) ?></h3>
        <?php endif ?>        
    </div>
</a>
