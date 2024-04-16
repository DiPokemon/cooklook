<?php
    include 'variables.php';
    $post_id = get_the_ID();
    $recipe_likes = carbon_get_post_meta($post_id, 'recipe_likes');
    $recipe_dislikes = carbon_get_post_meta($post_id, 'recipe_dislikes');
    $rating = calculate_rating($recipe_likes, $recipe_dislikes);
    $time = carbon_get_post_meta($post_id, 'recipe_time');
    $portions = carbon_get_post_meta($post_id, 'recipe_portions');
    $comments_count = get_comments_number();
    $recipe_steps = carbon_get_post_meta($post_id, 'recipe_step');
    $recipe_ingridients = carbon_get_post_meta($post_id, 'ingridients');
    $tags = get_the_terms($post_id, 'recipe_tags');
    $recipe_protein = carbon_get_post_meta($post_id, 'recipe_protein');
    $recipe_carbs = carbon_get_post_meta($post_id, 'recipe_carbs');
    $recipe_fat = carbon_get_post_meta($post_id, 'recipe_fat');
    $recipe_calories = carbon_get_post_meta($post_id, 'recipe_calories');    

    if (has_excerpt()) {
        $description = get_the_excerpt();
    } else {
        $description = get_the_content();
    }
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

    <section>
        <div class="container recipe_main-info flex">
            <div class="left_side">
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
                </div>

                <h1> <?= get_the_title() ?> </h1>                
               
                <?php if ($description) : ?>
                    <div class="recipe_description">                        
                        <?= $description ?>
                    </div>
                <?php endif; ?>

                <div class="recipe_meta">
                    <div class="rating meta-item">
                        <svg class="meta-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="19" viewBox="0 0 20 19" fill="none">
                            <title><?= __('Рейтинг рецепта', 'cooklook') ?></title>
                            <path d="M8.23497 1.31224C8.98771 -0.100353 11.0123 -0.10035 11.765 1.31224L13.2313 4.06374C13.5202 4.60593 14.0423 4.98524 14.6472 5.09248L17.7171 5.63669C19.2932 5.91607 19.9188 7.84157 18.808 8.99398L16.6442 11.2387C16.2179 11.681 16.0184 12.2948 16.1034 12.9032L16.5345 15.9911C16.7558 17.5763 15.1179 18.7664 13.6786 18.066L10.8751 16.7018C10.3227 16.433 9.67733 16.433 9.1249 16.7018L6.3214 18.066C4.88212 18.7664 3.2442 17.5763 3.46552 15.9911L3.89661 12.9032C3.98155 12.2948 3.78214 11.681 3.35577 11.2387L1.19202 8.99398C0.0811796 7.84156 0.706812 5.91607 2.28288 5.63669L5.3528 5.09248C5.95773 4.98524 6.47981 4.60593 6.76874 4.06374L8.23497 1.31224Z"/>
                        </svg>
                        <span class="meta-value"><?= $rating ?></span>
                    </div>

                    <div class="reviews meta-item">
                        <svg class="meta-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <title><?= __('Отзывы о рецепте', 'cooklook') ?></title>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M7.268 18.732L5 21V16.843C3.75 15.383 3 13.524 3 11.5C3 6.806 7.03 3 12 3C16.97 3 21 6.806 21 11.5C21 16.194 16.97 20 12 20C10.338 20.0053 8.70464 19.5676 7.268 18.732Z"/>
                        </svg>
                        <span class="meta-value"><?= $comments_count ?></span>
                    </div>

                    <div class="cook_time meta-item">
                        <svg class="meta-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <title><?= __('Время приготовления', 'cooklook') ?></title>
                            <path d="M6 20C5.16667 20 4.45834 19.7083 3.875 19.125C3.29167 18.5417 3 17.8333 3 17V9C3 8.71667 3.096 8.479 3.288 8.287C3.48 8.095 3.71734 7.99934 4 8H20C20.2833 8 20.521 8.096 20.713 8.288C20.905 8.48 21.0007 8.71734 21 9V17C21 17.8333 20.7083 18.5417 20.125 19.125C19.5417 19.7083 18.8333 20 18 20H6ZM9 5V4C9 3.71667 9.096 3.479 9.288 3.287C9.48 3.095 9.71734 2.99934 10 3H14C14.2833 3 14.521 3.096 14.713 3.288C14.905 3.48 15.0007 3.71734 15 4V5H20C20.2833 5 20.521 5.096 20.713 5.288C20.905 5.48 21.0007 5.71734 21 6C21 6.28334 20.904 6.521 20.712 6.713C20.52 6.905 20.2827 7.00067 20 7H4C3.71667 7 3.479 6.904 3.287 6.712C3.095 6.52 2.99934 6.28267 3 6C3 5.71667 3.096 5.479 3.288 5.287C3.48 5.095 3.71734 4.99934 4 5H9Z" />
                        </svg>
                        <span class="meta-value"><?= $time ?></span>
                    </div>

                    <div class="persons meta-item">
                        <svg class="meta-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <title><?= __('Количество порций', 'cooklook') ?></title>
                            <path d="M12.2807 17.595C15.5237 17.595 18.1585 15.1077 18.2706 12C18.1585 8.8924 15.5237 6.40503 12.2807 6.40503C9.03812 6.40503 6.40262 8.8924 6.29199 12C6.40224 15.1077 9.03774 17.595 12.2807 17.595Z" />
                            <path d="M12.2816 3.75C7.72537 3.75 4.03125 7.44375 4.03125 12C4.03125 16.5559 7.72575 20.25 12.2816 20.25C16.8375 20.25 20.5316 16.5559 20.5316 12C20.5316 7.44375 16.8375 3.75 12.2816 3.75ZM12.2812 18C8.96737 18 6.28087 15.4046 6.28087 12.2021C6.28087 12.1339 6.29025 12.0675 6.2925 12C6.29025 11.9325 6.28087 11.8658 6.28087 11.7979C6.28087 8.59538 8.96775 6 12.2812 6C15.5951 6 18.2812 8.59538 18.2812 11.7979C18.2812 11.8658 18.2734 11.9325 18.2715 12C18.2737 12.0675 18.2812 12.1339 18.2812 12.2021C18.2809 15.4046 15.5951 18 12.2812 18Z"/>
                            <path d="M1.30554 2.78291C1.39554 2.43003 1.57366 2.77878 1.57366 2.77878C1.57366 2.77878 1.63591 6.43129 2.04091 6.47104C2.44591 6.51079 2.13391 2.71278 2.61316 2.64003C3.05866 2.56916 2.80441 6.53141 3.21016 6.49128C3.61441 6.44853 3.68266 2.79754 3.68266 2.79754C3.68266 2.79754 3.86266 2.44916 3.95154 2.79941C5.04954 7.11566 4.16116 8.88004 4.16116 8.88004C4.16116 8.88004 4.01116 9.25091 3.65491 9.25278C3.62154 9.25091 3.64666 19.5038 3.64441 20.8823C3.64516 21.5524 1.58191 21.5318 1.58304 20.8647C1.58454 19.4862 1.62466 9.23441 1.59241 9.23516C1.23504 9.23516 1.08541 8.86466 1.08541 8.86466C1.08541 8.86466 0.20041 7.10141 1.30554 2.78291ZM20.8577 2.62503C21.3219 2.62391 23.2479 4.01253 23.2502 9.17329C23.2524 10.8237 22.352 10.8233 22.3535 11.8557C22.355 12.8862 22.3607 21.0829 22.3607 21.0829C22.3607 21.0829 21.3729 21.7605 20.6387 21.0514L20.6252 2.77166C20.6259 2.77203 20.6233 2.62466 20.8577 2.62503Z" />
                        </svg>
                        <span class="meta-value"><?= $portions ?></span>
                    </div>
                </div>
            </div>

            <div class="right_side">
                <div class="recipe_img_wrapper">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php $thumbnail_url = get_the_post_thumbnail_url(); ?>
                        <img src="<?= esc_url($thumbnail_url) ?>" alt="<?= sprintf(__('Рецепт %s', 'cooklook'), get_the_title()); ?>">
                    <?php else : ?>
                        Изображение не найдено.
                    <?php endif ?>

                    <a href="" class="bookmark">                    
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <title><?= __('Добавить рецепт в избранное', 'cooklook') ?></title>
                            <path d="M16.8203 2H7.18031C5.05031 2 3.32031 3.74 3.32031 5.86V19.95C3.32031 21.75 4.61031 22.51 6.19031 21.64L11.0703 18.93C11.5903 18.64 12.4303 18.64 12.9403 18.93L17.8203 21.64C19.4003 22.52 20.6903 21.76 20.6903 19.95V5.86C20.6803 3.74 18.9503 2 16.8203 2Z"/>
                        </svg>
                    </a>
                </div>                
            </div>
        </div>
    </section>

    <section>
        <div class="container recipe_energy flex">
            <div class="energy_values">
                <h2 class="energy_values-title"><?= __('Энергетическая ценность на 100 г.','cooklook') ?></h2>
                <div class="energy_values-table">
                    <?php if ($recipe_protein) : ?>
                        <div class="energy_value recipe_protein">
                            <h3 class="energy_value-name"><?= __('Белки','cooklook')  ?></h3>
                            <span class="energy_value-value"><?= $recipe_protein ?></span>
                            <span class="energy_value-mesure"><?= __('Грамм','cooklook') ?></span>
                        </div>
                    <?php endif ?>

                    <?php if ($recipe_fat) : ?>
                        <div class="energy_value recipe_fat">
                            <h3 class="energy_value-name"><?= __('Жиры','cooklook')  ?></h3>
                            <span class="energy_value-value"><?= $recipe_protein ?></span>
                            <span class="energy_value-mesure"><?= __('Грамм','cooklook') ?></span>
                        </div>
                    <?php endif ?>

                    <?php if ($recipe_carbs) : ?>
                        <div class="energy_value recipe_carbs">
                            <h3 class="energy_value-name"><?= __('Углеводы','cooklook')  ?></h3>
                            <span class="energy_value-value"><?= $recipe_protein ?></span>
                            <span class="energy_value-mesure"><?= __('Грамм','cooklook') ?></span>
                        </div>
                    <?php endif ?>

                    <?php if ($recipe_calories) : ?>
                        <div class="energy_value recipe_calories">
                            <h3 class="energy_value-name"><?= __('Калорийность','cooklook')  ?></h3>
                            <span class="energy_value-value"><?= $recipe_protein ?></span>
                            <span class="energy_value-mesure"><?= __('Ккал','cooklook') ?></span>
                        </div>
                    <?php endif ?>
                </div>
            </div>
            <div class="adv_block">
                РЕКЛАМНЫЙ БЛОК
            </div>
        </div>
    </section>

    <?php if($recipe_steps): ?>
        <section>
            <div class="container recipe_instructions">
                
                <h2><?= __('Инструкция по приготовлению', 'cooklook') ?></h2>
                <?php $steps_count = count($recipe_steps); ?>
                <div class="recipe_instructions_wrapper flex">
                    <div class="recipe_instructions_left flex">
                        <?php foreach ($recipe_steps as $index => $step) : ?>
                            
                            <div class="recipe_instructions_left-step step_<?= ($index+1) ?>">
                                <span class="step_number">
                                    <b><?= __('Шаг', 'cooklook').' '.($index+1) ?></b> / <?= $steps_count ?>
                                </span>
                                <div class="step_description">
                                    <?= $step['recipe_step_text'] ?>
                                </div>
                            </div>
                            
                        <?php endforeach; ?>
                    </div>
                    <div class="recipe_instructions_right flex">
                        <svg class="icon_pin" xmlns="http://www.w3.org/2000/svg" width="83" height="83" viewBox="0 0 83 83" fill="none">
                            <g clip-path="url(#clip0_121_4289)">
                                <path d="M43.1879 8.71509C43.4246 8.64662 43.6725 8.62548 43.9174 8.65287C44.1622 8.68026 44.3993 8.75564 44.615 8.87471L67.6192 21.5187C68.055 21.7582 68.3778 22.1611 68.5166 22.6386C68.6553 23.1161 68.5987 23.6292 68.3592 24.065C67.1309 26.2997 65.1147 27.3054 63.5648 27.7586C62.9249 27.9418 62.3386 28.0431 61.8631 28.0985L53.8595 42.6601L54.0291 42.9588C54.4668 43.7428 55.0262 44.8505 55.4947 46.1392C56.4016 48.6232 57.1465 52.2505 55.389 55.4481C55.1494 55.8839 54.7466 56.2067 54.2691 56.3454C53.7916 56.4842 53.2785 56.4276 52.8427 56.1881L39.6974 48.963L31.5691 63.7514C31.0706 64.6584 28.1236 68.2762 27.2166 67.7777C26.3095 67.2791 27.7843 62.8521 28.2828 61.9451L36.4111 47.1567L23.2659 39.9315C22.8301 39.692 22.5073 39.2892 22.3685 38.8117C22.2297 38.3341 22.2863 37.821 22.5259 37.3852C24.2834 34.1877 27.7449 32.8724 30.3248 32.3049C31.5807 32.0339 32.8578 31.8725 34.1416 31.8224L42.1453 17.2608C41.8921 16.7396 41.683 16.1981 41.5202 15.642C41.0721 14.0906 40.8422 11.8461 42.0687 9.61472C42.187 9.39859 42.3467 9.20788 42.5388 9.05352C42.7308 8.89915 42.9514 8.78415 43.1879 8.71509Z" fill="#2DBE64"/>
                            </g>
                            <defs>
                                <clipPath id="clip0_121_4289">
                                <rect width="60" height="60" fill="white" transform="translate(29.8262 0.746338) rotate(28.7949)"/>
                                </clipPath>
                            </defs>
                        </svg>
                        <h3><?= __('Ингридиенты','cooklook') ?></h3>
                        <ul class="ingridients_list">
                            <?php foreach ($recipe_ingridients as $ingridient): ?>
                                <li class="ingridients_list-item">
                                    <span class="ingridients_list-item-name"><?= $ingridient['ingridient_name'] ?></span>
                                    <span class="ingridients_list-item-value"><?= $ingridient['ingridient_value'] ?></span>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section>
        <div class="container">
            <div class="recipe_rating_wrapper">
                <h2><?= __('Оцените этот рецепт', 'cooklook') ?></h2>
                          
                <div class="like_dislike_block">
                    
                        <button id="like_btn-<?= get_the_ID() ?>" class="like_btn">
                            <svg xmlns="http://www.w3.org/2000/svg" class="btn_icon" viewBox="0 0 512 512">
                                <path d="M456 192l-156-12 23-89.4c6-26.6-.78-41.87-22.47-48.6l-34.69-9.85a4 4 0 00-4.4 1.72l-129 202.34a8 8 0 01-6.81 3.81H16V448h117.61a48 48 0 0115.18 2.46l76.3 25.43a80 80 0 0025.3 4.11h177.93c19 0 31.5-13.52 35.23-32.16L496 305.58V232c0-22.06-18-38-40-40z"/>                            
                            </svg>
                        </button>
                    
                    <div class="recipe_rating">
                        <?= $rating ?>
                    </div>
                    
                        <button id="dislike_btn-<?= get_the_ID() ?>" class="dislike_btn">
                            <svg xmlns="http://www.w3.org/2000/svg" class="btn_icon" viewBox="0 0 512 512">
                                <path d="M56 320l156.05 12-23 89.4c-6.08 26.6.7 41.87 22.39 48.62l34.69 9.85a4 4 0 004.4-1.72l129-202.34a8 8 0 016.81-3.81H496V64H378.39a48 48 0 01-15.18-2.46l-76.3-25.43a80 80 0 00-25.3-4.11H83.68c-19 0-31.5 13.52-35.23 32.16L16 206.42V280c0 22.06 18 38 40 40z"/>
                                <path d="M378.45 273.93A15.84 15.84 0 01386 272a15.93 15.93 0 00-7.51 1.91zM337.86 343.22l-.13.22a2.53 2.53 0 01.13-.22c20.5-35.51 30.36-55 33.82-62-3.47 7.06-13.34 26.51-33.82 62z" fill="none"/>                                
                                <path d="M372.66 279.16l-1 2a16.29 16.29 0 016.77-7.26 16.48 16.48 0 00-5.77 5.26z"/>
                            </svg>
                        </button>
                    
                </div>
            
                <span>
                    <?= __('Чтобы оценить рецепт войдите или зарегистрируйтесь', 'cooklook') ?>
                </span>
            
            </div>

            <div class="recipe_tags">
                <h3 class="recipe_tags-title"><?= __('Теги', 'cooklook') ?>:</h3>
                <div class="tags_wrapper">
                    <?php foreach ($recipe_categories as $recipe_cat) : ?>
                        <?php 
                            $category_id = $recipe_cat->term_id;
                            $category_name = $recipe_cat->name;
                            $category_slug = $recipe_cat->slug;
                        ?>
                        <a class="meta_category <?= esc_attr($category_slug) ?>" href="<?= esc_url(get_term_link($category_id)) ?>"><?= esc_html($category_name) ?></a>
                    <?php endforeach ?>
                </div>
                
            </div>
        </div>
        
    </section>

    <section>
        <div class="container recipe_comments">            
            
                <h2><?= __('Комментарии', 'cooklook') ?> (<?= $comments_count ?>):</h2>
                <div class="recipe_comments_wrapper">
                
                    <?php if ( comments_open() || get_comments_number() ) : ?>
                        <div class="comments_wrapper recipe_comments_wrapper_left">                
                            <?php comments_template(); ?>                  
                        </div>
                    <?php endif; ?>

                    <div class="recipe_comments_wrapper_right">
                        <div class="adv_block">
                            Реклама
                        </div>
                    </div>
                </div>
                
        </div>
    </section>


    <?php
        $current_post_categories = wp_get_post_terms(get_the_ID(), 'recipe_category', array('fields' => 'ids'));

        $args = array(
            'post_type' => 'recipe',
            'posts_per_page' => 9, 
            'post_status' => 'publish',
            'orderby' => 'rand',
            'tax_query' => array(
                array(
                    'taxonomy' => 'recipe_category', // таксономия, по которой фильтруем
                    'terms' => $current_post_categories, // текущие категории текущей записи
                    'include_children' => true // не включать дочерние категории
                )
            ),
            'post__not_in' => array( get_the_ID() )
        );
        $the_query = new WP_Query($args);
    ?>

    <?php if ($the_query->have_posts()) : ?>

    <section>
        <div class="container slider_wrapper">
            <h2><?= __('Похожие рецепты','cooklook') ?></h2>
            
                <div class="related_recipes recipes_slider">
                        <?php
                            while ($the_query->have_posts()) {
                                $the_query->the_post();
                                $categories = get_the_category();
                                $post_id = get_the_ID();
                                $recipe_likes = carbon_get_post_meta($post_id, 'recipe_likes');
                                $recipe_dislikes = carbon_get_post_meta($post_id, 'recipe_likes');
                                $rating = calculate_rating($recipe_likes, $recipe_dislikes);
                                $time = carbon_get_post_meta($post_id, 'recipe_time');
                                $portions = carbon_get_post_meta($post_id, 'recipe_portions');
                                $comments = get_comments_number();
                                $recipe_steps = carbon_get_post_meta($post_id, 'recipe_step');
                                $tags = get_the_tags();

                                if(get_the_excerpt()){
                                    $description = get_the_excerpt();
                                }
                                else if(get_the_content()){
                                    $description = get_the_content();
                                }
                                else {
                                    $description = $recipe_steps[0]['recipe_step_text'];
                                }
                               
                                $words = explode(' ', $description);
                                $first_fifteen_words = array_slice($words, 0, 15);
                                $description = implode(' ', $first_fifteen_words);
                                $description .= ' ...';

                                set_query_var('categories', $categories);
                                set_query_var('post_id', $post_id);
                                set_query_var('rating', $rating);
                                set_query_var('time', $time);
                                set_query_var('portions', $portions);
                                set_query_var('comments', $comments);
                                set_query_var('description', $description);
                                set_query_var('tags', $tags);

                                get_template_part('template-parts/recipe-loop-item');
                            }
                        ?>
                </div>
            
        </div>
    </section>
    <?php
        wp_reset_postdata();
        endif;
    ?>

    <section>
        <div class="container">
            <div class="adv_block">
                РЕКЛАМНЫЙ БЛОК
            </div>
        </div>
    </section>

</article>