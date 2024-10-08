<?php
/*
* Template name: Карточка рецепта
*/
$categories = get_query_var('categories');
$post_id = get_query_var('post_id');
$recipe_likes = intval(carbon_get_post_meta($post_id, 'recipe_likes'));
$recipe_dislikes = intval(carbon_get_post_meta($post_id, 'recipe_dislikes'));
$rating_counter = $recipe_likes + $recipe_dislikes;
$rating = calculate_rating($recipe_likes, $recipe_dislikes);
$time = get_query_var('time');
$portions = get_query_var('portions');
$comments = get_query_var('comments');
$description = get_query_var('description');
$tags = get_query_var('tags');

$user_id = is_user_logged_in() ? get_current_user_id() : ($_COOKIE['user_id'] ?? '0');
$is_favorite = is_recipe_favorite($post_id, $user_id);
$favorite_class = $is_favorite ? 'added' : '';

$cook_time = carbon_get_post_meta($post_id, 'recipe_time');
$prep_time = carbon_get_post_meta($post_id, 'recipe_prep');
$cook_time = (int)$cook_time;
$prep_time = (int)$prep_time;
$total_time = $cook_time + $prep_time;
$cook_time_convert = convert_time_to_string($cook_time);
$prep_time_convert = !empty($prep_time) ? convert_time_to_string($prep_time) : null;    
$total_time_convert = convert_time_to_string($total_time);
?>

<div class="recipe_loop-item">
    <div class="recipe_loop-wrapper">
        <div class="img_block" data-permalink="<?= get_permalink() ?>">
            <?php if (get_the_post_thumbnail_url(null, 'large')) : ?>
               
                    <img loading="lazy" src="<?= get_the_post_thumbnail_url(null, 'large') ?>" alt="<?= __('Рецепт', 'cooklook') ?> <?= the_title() ?> на <?= $portions ?> порций">
                  
            <?php else: ?>
                
                    <img loading="lazy" src="<?= get_template_directory_uri() . '/static/img/no_image.png' ?>" alt="<?= __('Рецепт', 'cooklook') ?> <?= the_title() ?> на <?= $portions ?> порций">
                
            <?php endif; ?>
            <div class="recipe_meta">
                <div class="top_meta">
                    <?php
                        $recipe_categories = get_the_terms(get_the_ID(), 'recipe_category'); // 'recipe_category' - это таксономия для кастомных категорий рецептов

                        if ($recipe_categories && !is_wp_error($recipe_categories)) {
                            $parent_category = get_term($recipe_categories[0]->parent, 'recipe_category'); // Получаем родительскую категорию
                            
                            if ($parent_category && !is_wp_error($parent_category)) {
                                $category_id = $parent_category->term_id;
                                $category_name = $parent_category->name;
                                $category_slug = $parent_category->slug;
                            } else {
                                $category_id = $recipe_categories[0]->term_id;
                                $category_name = $recipe_categories[0]->name;
                                $category_slug = $recipe_categories[0]->slug;
                            } ?>
                            
                            <a class="meta_category <?= esc_attr($category_slug) ?>" href="<?= esc_url(get_term_link($category_id)) ?>"><?= esc_html($category_name) ?></a>
                        
                    <?php } ?>    
                    <?php if (is_user_logged_in()) : ?>                
                        <a href="#" data-recipe-id="<?= get_the_ID(); ?>" class="bookmark <?= $favorite_class ?>">                    
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <title><?= __('Добавить рецепт в избранное', 'cooklook') ?></title>
                                <path d="M16.8203 2H7.18031C5.05031 2 3.32031 3.74 3.32031 5.86V19.95C3.32031 21.75 4.61031 22.51 6.19031 21.64L11.0703 18.93C11.5903 18.64 12.4303 18.64 12.9403 18.93L17.8203 21.64C19.4003 22.52 20.6903 21.76 20.6903 19.95V5.86C20.6803 3.74 18.9503 2 16.8203 2Z"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="bottom_meta">
                    <div class="rating bottom_meta-item">
                        <svg class="bottom_meta-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="19" viewBox="0 0 20 19" fill="none">
                            <title><?= __('Рейтинг рецепта', 'cooklook') ?></title>
                            <path d="M8.23497 1.31224C8.98771 -0.100353 11.0123 -0.10035 11.765 1.31224L13.2313 4.06374C13.5202 4.60593 14.0423 4.98524 14.6472 5.09248L17.7171 5.63669C19.2932 5.91607 19.9188 7.84157 18.808 8.99398L16.6442 11.2387C16.2179 11.681 16.0184 12.2948 16.1034 12.9032L16.5345 15.9911C16.7558 17.5763 15.1179 18.7664 13.6786 18.066L10.8751 16.7018C10.3227 16.433 9.67733 16.433 9.1249 16.7018L6.3214 18.066C4.88212 18.7664 3.2442 17.5763 3.46552 15.9911L3.89661 12.9032C3.98155 12.2948 3.78214 11.681 3.35577 11.2387L1.19202 8.99398C0.0811796 7.84156 0.706812 5.91607 2.28288 5.63669L5.3528 5.09248C5.95773 4.98524 6.47981 4.60593 6.76874 4.06374L8.23497 1.31224Z"/>
                        </svg>
                        <span class="bottom_meta-value"><?= $rating ?></span>
                    </div>                    

                    <div class="persons bottom_meta-item">
                        <svg class="bottom_meta-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <title><?= __('Количество порций', 'cooklook') ?></title>
                            <path d="M12.2807 17.595C15.5237 17.595 18.1585 15.1077 18.2706 12C18.1585 8.8924 15.5237 6.40503 12.2807 6.40503C9.03812 6.40503 6.40262 8.8924 6.29199 12C6.40224 15.1077 9.03774 17.595 12.2807 17.595Z" />
                            <path d="M12.2816 3.75C7.72537 3.75 4.03125 7.44375 4.03125 12C4.03125 16.5559 7.72575 20.25 12.2816 20.25C16.8375 20.25 20.5316 16.5559 20.5316 12C20.5316 7.44375 16.8375 3.75 12.2816 3.75ZM12.2812 18C8.96737 18 6.28087 15.4046 6.28087 12.2021C6.28087 12.1339 6.29025 12.0675 6.2925 12C6.29025 11.9325 6.28087 11.8658 6.28087 11.7979C6.28087 8.59538 8.96775 6 12.2812 6C15.5951 6 18.2812 8.59538 18.2812 11.7979C18.2812 11.8658 18.2734 11.9325 18.2715 12C18.2737 12.0675 18.2812 12.1339 18.2812 12.2021C18.2809 15.4046 15.5951 18 12.2812 18Z"/>
                            <path d="M1.30554 2.78291C1.39554 2.43003 1.57366 2.77878 1.57366 2.77878C1.57366 2.77878 1.63591 6.43129 2.04091 6.47104C2.44591 6.51079 2.13391 2.71278 2.61316 2.64003C3.05866 2.56916 2.80441 6.53141 3.21016 6.49128C3.61441 6.44853 3.68266 2.79754 3.68266 2.79754C3.68266 2.79754 3.86266 2.44916 3.95154 2.79941C5.04954 7.11566 4.16116 8.88004 4.16116 8.88004C4.16116 8.88004 4.01116 9.25091 3.65491 9.25278C3.62154 9.25091 3.64666 19.5038 3.64441 20.8823C3.64516 21.5524 1.58191 21.5318 1.58304 20.8647C1.58454 19.4862 1.62466 9.23441 1.59241 9.23516C1.23504 9.23516 1.08541 8.86466 1.08541 8.86466C1.08541 8.86466 0.20041 7.10141 1.30554 2.78291ZM20.8577 2.62503C21.3219 2.62391 23.2479 4.01253 23.2502 9.17329C23.2524 10.8237 22.352 10.8233 22.3535 11.8557C22.355 12.8862 22.3607 21.0829 22.3607 21.0829C22.3607 21.0829 21.3729 21.7605 20.6387 21.0514L20.6252 2.77166C20.6259 2.77203 20.6233 2.62466 20.8577 2.62503Z" />
                        </svg>
                        <span class="bottom_meta-value"><?= $portions ?></span>
                    </div>

                    <div class="reviews bottom_meta-item">
                        <svg class="bottom_meta-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <title><?= __('Отзывы о рецепте', 'cooklook') ?></title>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M7.268 18.732L5 21V16.843C3.75 15.383 3 13.524 3 11.5C3 6.806 7.03 3 12 3C16.97 3 21 6.806 21 11.5C21 16.194 16.97 20 12 20C10.338 20.0053 8.70464 19.5676 7.268 18.732Z"/>
                        </svg>
                        <span class="bottom_meta-value"><?= $comments ?></span>
                    </div>

                    <div class="cook_time bottom_meta-item">
                        <svg class="bottom_meta-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <title><?= __('Время приготовления', 'cooklook') ?></title>
                            <path d="M6 20C5.16667 20 4.45834 19.7083 3.875 19.125C3.29167 18.5417 3 17.8333 3 17V9C3 8.71667 3.096 8.479 3.288 8.287C3.48 8.095 3.71734 7.99934 4 8H20C20.2833 8 20.521 8.096 20.713 8.288C20.905 8.48 21.0007 8.71734 21 9V17C21 17.8333 20.7083 18.5417 20.125 19.125C19.5417 19.7083 18.8333 20 18 20H6ZM9 5V4C9 3.71667 9.096 3.479 9.288 3.287C9.48 3.095 9.71734 2.99934 10 3H14C14.2833 3 14.521 3.096 14.713 3.288C14.905 3.48 15.0007 3.71734 15 4V5H20C20.2833 5 20.521 5.096 20.713 5.288C20.905 5.48 21.0007 5.71734 21 6C21 6.28334 20.904 6.521 20.712 6.713C20.52 6.905 20.2827 7.00067 20 7H4C3.71667 7 3.479 6.904 3.287 6.712C3.095 6.52 2.99934 6.28267 3 6C3 5.71667 3.096 5.479 3.288 5.287C3.48 5.095 3.71734 4.99934 4 5H9Z" />
                        </svg>
                        <span class="bottom_meta-value">
                            <?= $total_time_convert; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="recipe_loop-content flex">
            <div class="categories flex">
                <?php display_recipe_categories_hierarchy($categories); ?>             
            </div>
            <a href="<?= get_permalink() ?>" class="recipe_title">
                <h3>
                    <?= get_the_title() ?>
                </h3>
            </a>
            <a href="<?= get_permalink() ?>" class="recipe_desc">
                <?= $description ?>
            </a>
            <?php if ($tags) : ?>
                <div class="recipe_ingridients flex">
                    <?= __('Ингридиенты:','cooklook') ?>
                    <?php foreach ($tags as $tag) : ?>
                        <a href="<?= get_tag_link($tag->term_id) ?>" class="recipe_ingridients-item"><?= $tag->name ?></a>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>

