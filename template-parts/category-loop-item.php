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
$cat_image = cooklook_category_image( $current_cat_id, ! empty( $args['priority'] ) );
?>

<a href="<?= esc_url($cat_link) ?>" class="category_item <?= esc_attr($cat_slug) ?> flex">
    <div class="category_item-wrapper flex">  
        <?php if ($cat_image):?>
            <?php echo $cat_image; // Generated and escaped by cooklook_category_image(). ?>
            <h3 class="category_item-title"><?= esc_html($cat_name) ?></h3>
        <?php else: ?>
            <h3 class="category_item-title"><?= esc_html($cat_name) ?></h3>
        <?php endif ?>        
    </div>
</a>
