<?php
/*
* Template name: Блок со списком категорий
*/
$parent_cat = get_query_var('parent_cat');
$child_cats = get_query_var('child_cats');
?>

<div class="category_list">
    <div class="parent_category">
        <?php get_template_part('template-parts/category-list-item', null, array('category' => $parent_cat, 'index' => 1)); ?>
    </div>
    <div class="child_categories">
        <?php foreach ($child_cats as $index => $child_cat) : ?>
            <?php get_template_part('template-parts/category-list-item', null, array('category' => $child_cat, 'index' => $index)); ?>   
        <?php endforeach ?>
        <?php if (count($child_cats) > 5) : ?>
                <span class="show_more">
                    <?= __('Показать еще', 'cooklook') ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M3.72437 6.39067C3.4744 6.64071 3.33398 6.97979 3.33398 7.33334C3.33398 7.68689 3.4744 8.02597 3.72437 8.27601L8.00037 12.5527L12.2764 8.27601C12.537 8.01601 12.667 7.67467 12.667 7.33334C12.667 6.99201 12.537 6.65067 12.2764 6.39067C12.0263 6.14071 11.6873 6.00029 11.3337 6.00029C10.9801 6.00029 10.6411 6.14071 10.391 6.39067L8.00037 8.78067L5.6097 6.39067C5.35966 6.14071 5.02058 6.00029 4.66703 6.00029C4.31348 6.00029 3.9744 6.14071 3.72437 6.39067Z" fill="#2DBE64"/>
                    </svg>
                </span>
            <?php endif ?>
    </div>    
</div>