<?php
    if ( post_password_required() ) { echo get_the_password_form(); return; }
    $post_id = get_the_ID();
    
    $rating_data = cooklook_recipe_rating_data( $post_id );
    $rating = $rating_data['rating'];
    $facts = cooklook_recipe_facts( $post_id );
    $editorial = cooklook_recipe_editorial( $post_id );
    $comments_count = get_comments_number();
    $recipe_protein = cooklook_recipe_value($post_id, 'recipe_protein');
    $recipe_carbs = cooklook_recipe_value($post_id, 'recipe_carbs');
    $recipe_fat = cooklook_recipe_value($post_id, 'recipe_fat');
    $recipe_calories = cooklook_recipe_value($post_id, 'recipe_calories');

    $description = cooklook_recipe_intro( $post_id );

    $user_id = get_current_user_id();
    $is_favorite = is_recipe_favorite($post_id, $user_id);
    $favorite_class = $is_favorite ? 'added' : '';
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <section>
        <div class="container recipe_main-info flex">
            <div class="left_side">
                <div class="top_meta">
                    <?php
                        $recipe_categories = cooklook_recipe_terms(get_the_ID(), 'recipe_category'); // 'recipe_category' - это таксономия для кастомных категорий рецептов

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

                <h1> <?= esc_html(get_the_title()) ?> </h1>
               
                <?php if ($description) : ?>
                    <div class="recipe_description">                        
                        <?= wp_kses_post(wpautop($description)) ?>
                    </div>
                <?php endif; ?>

                <div class="recipe_meta">
                    <div class="rating meta-item">
                        <svg class="meta-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="19" viewBox="0 0 20 19" fill="none">
                            <title><?= __('Рейтинг рецепта', 'cooklook') ?></title>
                            <path d="M8.23497 1.31224C8.98771 -0.100353 11.0123 -0.10035 11.765 1.31224L13.2313 4.06374C13.5202 4.60593 14.0423 4.98524 14.6472 5.09248L17.7171 5.63669C19.2932 5.91607 19.9188 7.84157 18.808 8.99398L16.6442 11.2387C16.2179 11.681 16.0184 12.2948 16.1034 12.9032L16.5345 15.9911C16.7558 17.5763 15.1179 18.7664 13.6786 18.066L10.8751 16.7018C10.3227 16.433 9.67733 16.433 9.1249 16.7018L6.3214 18.066C4.88212 18.7664 3.2442 17.5763 3.46552 15.9911L3.89661 12.9032C3.98155 12.2948 3.78214 11.681 3.35577 11.2387L1.19202 8.99398C0.0811796 7.84156 0.706812 5.91607 2.28288 5.63669L5.3528 5.09248C5.95773 4.98524 6.47981 4.60593 6.76874 4.06374L8.23497 1.31224Z"/>
                        </svg>
                        <span><?php esc_html_e( 'Рейтинг:', 'cooklook' ); ?> <span class="meta-value"><?php echo esc_html( $rating ); ?></span> / 5</span>
                    </div>

                    <div class="reviews meta-item">
                        <svg class="meta-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <title><?= __('Отзывы о рецепте', 'cooklook') ?></title>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M7.268 18.732L5 21V16.843C3.75 15.383 3 13.524 3 11.5C3 6.806 7.03 3 12 3C16.97 3 21 6.806 21 11.5C21 16.194 16.97 20 12 20C10.338 20.0053 8.70464 19.5676 7.268 18.732Z"/>
                        </svg>
                        <span><?php esc_html_e( 'Комментарии:', 'cooklook' ); ?> <span class="meta-value"><?php echo esc_html( $comments_count ); ?></span></span>
                    </div>

                </div>
                <?php get_template_part( 'template-parts/recipe-facts', null, array( 'facts' => $facts, 'editorial' => $editorial, 'post_id' => $post_id ) ); ?>
            </div>

            <div class="right_side">
                <div class="recipe_img_wrapper">
                    <?php echo cooklook_post_image( $post_id, 'large', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 1023px) calc(100vw - 30px), 650px' ) ); ?>
                    <?php if (is_user_logged_in()) : ?>
                        <a href="#" data-recipe-id="<?= get_the_ID(); ?>" class="bookmark <?= $favorite_class ?>">                    
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <title><?= __('Добавить рецепт в избранное', 'cooklook') ?></title>
                                <path d="M16.8203 2H7.18031C5.05031 2 3.32031 3.74 3.32031 5.86V19.95C3.32031 21.75 4.61031 22.51 6.19031 21.64L11.0703 18.93C11.5903 18.64 12.4303 18.64 12.9403 18.93L17.8203 21.64C19.4003 22.52 20.6903 21.76 20.6903 19.95V5.86C20.6803 3.74 18.9503 2 16.8203 2Z"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>                
            </div>
        </div>
    </section>

    <section>
        <?php if ( '' !== trim( get_the_content() ) ) : ?>
            <div class="container recipe_content entry-content">
                <?php the_content(); ?>
                <?php wp_link_pages(); ?>
            </div>
        <?php endif; ?>
        <div class="container recipe_energy flex">
            <div class="energy_values">
                <h2 class="energy_values-title"><?= __('Энергетическая ценность на 100 г.','cooklook') ?></h2>
                <div class="energy_values-table">
                    <?php if ($recipe_protein) : ?>
                        <div class="energy_value recipe_protein">
                            <h3 class="energy_value-name"><?= __('Белки','cooklook')  ?></h3>
                            <span class="energy_value-value"><?= esc_html($recipe_protein) ?></span>
                            <span class="energy_value-mesure"><?= __('Грамм','cooklook') ?></span>
                        </div>
                    <?php endif ?>

                    <?php if ($recipe_fat) : ?>
                        <div class="energy_value recipe_fat">
                            <h3 class="energy_value-name"><?= __('Жиры','cooklook')  ?></h3>
                            <span class="energy_value-value"><?= esc_html($recipe_fat) ?></span>
                            <span class="energy_value-mesure"><?= __('Грамм','cooklook') ?></span>
                        </div>
                    <?php endif ?>

                    <?php if ($recipe_carbs) : ?>
                        <div class="energy_value recipe_carbs">
                            <h3 class="energy_value-name"><?= __('Углеводы','cooklook')  ?></h3>
                            <span class="energy_value-value"><?= esc_html($recipe_carbs) ?></span>
                            <span class="energy_value-mesure"><?= __('Грамм','cooklook') ?></span>
                        </div>
                    <?php endif ?>

                    <?php if ($recipe_calories) : ?>
                        <div class="energy_value recipe_calories">
                            <h3 class="energy_value-name"><?= __('Калорийность','cooklook')  ?></h3>
                            <span class="energy_value-value"><?= esc_html($recipe_calories) ?></span>
                            <span class="energy_value-mesure"><?= __('Ккал','cooklook') ?></span>
                        </div>
                    <?php endif ?>
                </div>
            </div>
            <div class="qwert_block">
                <a class="adv_block" rel="sponsored" href="https://kursy-tut.ru/1637470?erid=2bL9aMPo2e4BA5qnNGCKd2CCV5">
                    <?php echo cooklook_banner( 'recipe_page' ); ?>
                </a>
            </div>
        </div>
    </section>

    <?php get_template_part( 'template-parts/recipe-instructions', null, array( 'facts' => $facts ) ); ?>
    <?php if ( $editorial['notes'] ) : ?>
        <section class="container recipe_notes">
            <h2><?php esc_html_e( 'Советы по приготовлению', 'cooklook' ); ?></h2>
            <p><?php echo esc_html( $editorial['notes'] ); ?></p>
        </section>
    <?php endif; ?>

    <section>
        <div class="container">
            <div class="recipe_rating_wrapper">
                <h2><?= __('Оцените этот рецепт', 'cooklook') ?></h2>
                <p class="recipe_vote_details">
                    <?php esc_html_e( 'Голоса:', 'cooklook' ); ?>
                    <span data-recipe-votes><?php echo esc_html( $rating_data['likes'] + $rating_data['dislikes'] ); ?></span>.
                    <?php esc_html_e( 'Нравится:', 'cooklook' ); ?> <span data-recipe-likes><?php echo esc_html( $rating_data['likes'] ); ?></span>;
                    <?php esc_html_e( 'не нравится:', 'cooklook' ); ?> <span data-recipe-dislikes><?php echo esc_html( $rating_data['dislikes'] ); ?></span>.
                    <?php esc_html_e( 'Рейтинг от 0 до 5: доля «нравится» среди всех голосов × 5. Без голосов рейтинг равен 0.', 'cooklook' ); ?>
                </p>
                <?php if ( is_user_logged_in() ) : ?>          
                    <div class="like_dislike_block">                    
                        <button type="button" aria-label="Нравится" id="like_btn-<?= get_the_ID() ?>" class="like_btn">
                            <svg xmlns="http://www.w3.org/2000/svg" class="btn_icon" viewBox="0 0 512 512">
                                <path d="M456 192l-156-12 23-89.4c6-26.6-.78-41.87-22.47-48.6l-34.69-9.85a4 4 0 00-4.4 1.72l-129 202.34a8 8 0 01-6.81 3.81H16V448h117.61a48 48 0 0115.18 2.46l76.3 25.43a80 80 0 0025.3 4.11h177.93c19 0 31.5-13.52 35.23-32.16L496 305.58V232c0-22.06-18-38-40-40z"/>                            
                            </svg>
                        </button>
                        
                        <div id="recipe_rating-<?= get_the_ID() ?>" class="recipe_rating">
                            <?= $rating ?>
                        </div>
                        
                        <button type="button" aria-label="Не нравится" id="dislike_btn-<?= get_the_ID() ?>" class="dislike_btn">
                            <svg xmlns="http://www.w3.org/2000/svg" class="btn_icon" viewBox="0 0 512 512">
                                <path d="M56 320l156.05 12-23 89.4c-6.08 26.6.7 41.87 22.39 48.62l34.69 9.85a4 4 0 004.4-1.72l129-202.34a8 8 0 016.81-3.81H496V64H378.39a48 48 0 01-15.18-2.46l-76.3-25.43a80 80 0 00-25.3-4.11H83.68c-19 0-31.5 13.52-35.23 32.16L16 206.42V280c0 22.06 18 38 40 40z"/>
                                <path d="M378.45 273.93A15.84 15.84 0 01386 272a15.93 15.93 0 00-7.51 1.91zM337.86 343.22l-.13.22a2.53 2.53 0 01.13-.22c20.5-35.51 30.36-55 33.82-62-3.47 7.06-13.34 26.51-33.82 62z" fill="none"/>                                
                                <path d="M372.66 279.16l-1 2a16.29 16.29 0 016.77-7.26 16.48 16.48 0 00-5.77 5.26z"/>
                            </svg>
                        </button>                   
                    </div>
                <?php else: ?>
                    <span class="login_message">
                        <?php
                            $login_url = wp_login_url(get_permalink()); // URL для страницы входа
                            $register_url = wp_registration_url(); // URL для страницы регистрации

                            $message_template = __('Чтобы оценить рецепт %s или %s', 'cooklook');
                            $login_link = '<a href="' . esc_url($login_url) . '">' . __('войдите', 'cooklook') . '</a>';
                            $register_link = '<a href="' . esc_url($register_url) . '">' . __('зарегистрируйтесь', 'cooklook') . '</a>';

                            $message = sprintf($message_template, $login_link, $register_link);
                            echo $message;
                        ?>
                    </span>  
                <?php endif ?>          
            </div>

            <?php foreach ( array( 'recipe_category' => __( 'Категории', 'cooklook' ), 'recipe_tags' => __( 'Ингредиенты', 'cooklook' ) ) as $taxonomy => $label ) : ?>
                <?php $terms = cooklook_recipe_terms( $post_id, $taxonomy ); if ( ! $terms ) { continue; } ?>
                <div class="recipe_tags" data-taxonomy="<?= esc_attr( $taxonomy ) ?>">
                    <h3 class="recipe_tags-title"><?= esc_html( $label ) ?>:</h3>
                    <div class="tags_wrapper">
                        <?php foreach ( $terms as $term ) : ?>
                            <?php $term_url = get_term_link( $term ); if ( is_wp_error( $term_url ) ) { continue; } ?>
                            <a class="meta_category <?= esc_attr( $term->slug ) ?>" href="<?= esc_url( $term_url ) ?>"><?= esc_html( $term->name ) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
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
                        <a class="adv_block" rel="sponsored" href="https://kursy-tut.ru/2075248?erid=2bL9aMPo2e4BA5qnNJLpeahqHh">
                            <?php echo cooklook_banner( 'recipe_page_square' ); ?>
                        </a>
                    </div>
                </div>                
        </div>
    </section>

    <?php cooklook_recipe_schema_fallback( get_the_ID() ); ?>

    <?php
        $current_post_categories = wp_list_pluck(cooklook_recipe_terms(get_the_ID(), 'recipe_category'), 'term_id');

        $args = array(
            'post_type' => 'recipe',
            'posts_per_page' => 9, 
            'post_status' => 'publish',
            'orderby' => 'date',
            'has_password' => false,
            'no_found_rows' => true,
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
            <div class="qwert_block">                
                <div id="voehw_R-A-8833792-7"></div>
                <script>
                    (window.yaContextCb = window.yaContextCb || []).push(() => {
                    Ya.Context.AdvManager.render({
                        "blockId": "R-A-8833792-7",
                        "renderTo": "voehw_R-A-8833792-7"
                    })
                })
                </script>
            </div>
        </div>
    </section>

</article>
