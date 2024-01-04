<?php
/*
* Template name: Элемент списка категорий
*/
$category = $args['category'];
$index = $args['index'];
$visibility_class = $index < 5 ? 'visible' : 'hidden';
?>


<div class="category_item <?= $visibility_class ?>">
    <a href="<?= get_category_link($category->term_id) ?>" class="category_name">
        <h3><?= $category->name ?></h3>
    </a>
    <span class="category_post_count">
        <?= $category->count ?>
    </span>
</div>
