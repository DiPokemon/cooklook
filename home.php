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
				<h2 class="section_title"><?= $popular_cats_title ?></h2>
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
                    <a href="#" class="btn_bg">Смотреть все рецепты</a>
                </div>
                <div class="recipes_grid">
                    <?php
                        $args = array(
                            'post_type' => 'post', // Тип записи, для стандартных записей это 'post'.
                            'posts_per_page' => 5, // Количество записей для показа.
                            'orderby' => 'date', // Сортировка по дате.
                            'order' => 'DESC' // Сортировка по убыванию.
                        );
                        $the_query = new WP_Query($args);
                        if ($the_query->have_posts()) {
                            while ($the_query->have_posts()) {
                                $the_query->the_post();
                                $categories = get_the_category();

                                $main_category = $categories[0];
                                $post_id = get_the_ID();
                                
                                $recipe_likes = carbon_get_post_meta($post_id, 'recipe_likes');                                
                                $recipe_dislikes = carbon_get_post_meta($post_id, 'recipe_likes');
                                $rating = calculate_rating($recipe_likes, $recipe_dislikes);
                                $time = carbon_get_post_meta($post_id, 'recipe_time');
                                $portions = carbon_get_post_meta($post_id, 'recipe_portions');
                                $comments = get_comments_number();

                                set_query_var( 'main_category', $main_category );
                                set_query_var('post_id', $post_id);
                                set_query_var('rating', $rating);
                                set_query_var('time', $time);
                                set_query_var('portions', $portions);
                                set_query_var('comments', $comments);

                                get_template_part('template-parts/recipe-loop-item');
                            }
                        } else {
                            echo 'No posts found.';
                        }
                        
                        wp_reset_postdata();
                    ?>
                </div>
            </div>
			
		</section>

		

	</main><!-- #main -->

<?php
get_sidebar();
get_footer();
