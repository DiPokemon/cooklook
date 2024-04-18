<?php
/**
 * Template name: Главная
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package cooklook
 */
include 'template-parts/variables.php';
get_header();
?>

	<main id="primary" class="site-main">

		<section id="popular_categories">
			<div class="container">
                <div class="section_header">
                    <h2 class="section_title"><?= $popular_cats_title ?></h2>
                </div>				
                <div class="popular_categories-grid">
                    <?php
                        $counter = 0;
                        $popular_cats_ids = wp_list_pluck( $popular_cats, 'id' );
                        foreach($popular_cats_ids as $cat_id) {   
                            $counter++;                    
                            get_template_part('template-parts/category-loop-item', null, array('current_cat_id' => $cat_id));
                            if($counter == 3) {
                        ?>
                                <div class="category_item ads">
                                    Реклама
                                </div>
                        <?php   
                            }
                        }
                    ?>
                </div>                              	
			</div>
		</section>

		<section>
            <div class="container">
                <div class="section_header flex">
                    <h2 class="section_title"><?= $new_recipes_title ?></h2>
                    <a href="<?= home_url('/recipes/') ?>" class="btn_bg hide_mobile"><?= __('Смотреть все рецепты', 'cooklook') ?></a>
                </div>
                <div class="recipes_grid">
                    <?php
                        $args = array(
                            'post_type' => 'recipe', // Тип записи, для стандартных записей это 'post'.
                            'posts_per_page' => 5, // Количество записей для показа.
                            'orderby' => 'date', // Сортировка по дате.
                            'order' => 'DESC' // Сортировка по убыванию.
                        );
                        $the_query = new WP_Query($args);
                        if ($the_query->have_posts()) {
                            while ($the_query->have_posts()) {
                                $the_query->the_post();
                                $categories = get_the_terms(get_the_ID(), 'recipe_category');
                                $post_id = get_the_ID();
                                $recipe_likes = carbon_get_post_meta($post_id, 'recipe_likes');
                                $recipe_dislikes = carbon_get_post_meta($post_id, 'recipe_likes');
                                $rating = calculate_rating($recipe_likes, $recipe_dislikes);
                                $time = carbon_get_post_meta($post_id, 'recipe_time');
                                $portions = carbon_get_post_meta($post_id, 'recipe_portions');
                                $comments = get_comments_number();
                                $recipe_steps = carbon_get_post_meta($post_id, 'recipe_step');
                                $tags = get_the_terms($post_id, 'recipe_tags');

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
                        } else {
                            echo 'No posts found.';
                        }
                        
                        wp_reset_postdata();
                    ?>
                </div>
                <a href="<?= home_url('/recipes/') ?>" class="btn_bg display_mobile"><?= __('Смотреть все рецепты', 'cooklook') ?></a>
            </div>			
		</section>

        <section>
            <div class="container">
                <div class="adv_block">
                    РЕКЛАМНЫЙ БЛОК
                </div>                
            </div>
        </section>

        <section>
            <div class="container">
                <div class="section_header flex">
                    <h2 class="section_title"><?= $popular_recipes_title ?></h2>
                    <a href="<?= home_url('/recipes/') ?>" class="btn_bg hide_mobile"><?= __('Смотреть все рецепты', 'cooklook') ?></a>
                </div>
                <div class="recipes_grid">
                    <?php
                        $args = array(
                            'post_type' => 'recipe',
                            'posts_per_page' => 5,
                            'meta_key' => '_recipe_views',
                            'orderby' => 'meta_value_num', 
                            'order' => 'DESC' 
                        );
                        $the_query = new WP_Query($args);
                        if ($the_query->have_posts()) {
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
                        } else {
                            echo 'No posts found.';
                        }
                        
                        wp_reset_postdata();
                    ?>
                </div>
                <a href="<?= home_url('/recipes/') ?>" class="btn_bg display_mobile"><?= __('Смотреть все рецепты', 'cooklook') ?></a>
                
            </div>			
		</section>

        <section>
            <div class="container">
                <div class="section_header">
                    <h2 class="section_title"><?= $recipes_categories_title ?></h2>
                </div>
                <div class="categories_grid">
                    <?php
                        $parent_cats = get_categories(
                            array( 
                            'parent'  => 0 
                        ));
                        $parent_cat_count = count($parent_cats);

                        foreach ($parent_cats as $parent_cat) {
                            $child_cats = get_categories(array(
                                'parent' => $parent_cat->term_id
                            ));
                            set_query_var('parent_cat', $parent_cat);
                            set_query_var('child_cats', $child_cats);
                            get_template_part('template-parts/category-list');
                        }
                    ?>
                    
                </div>
                <div class="categories_grid-controls">
                    <button class="categories_grid-controls-prev btn_bg" type="button">
                        <img src="<?=  get_template_directory_uri() ?>/static/img/chevron-left.svg">
                    </button>
                    <div id="categories_grid_slider-counter" class="categories_grid-controls-count">
                        <span class="count_current">1</span> / <span class="count_total"><?= $parent_cat_count ?></span>
                    </div>
                    <button class="categories_grid-controls-next btn_bg" type="button">
                        <img src="<?=  get_template_directory_uri() ?>/static/img/chevron-right.svg">
                    </button>
                </div>
            </div>
        </section>

        <section>
            <div class="container">
                <div class="adv_block">
                    РЕКЛАМНЫЙ БЛОК
                </div>
                
            </div>
        </section>

		

	</main><!-- #main -->

<?php
get_footer();
