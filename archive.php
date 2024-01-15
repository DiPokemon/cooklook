<?php
/**
 * Template name: Архив с рецептами
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package cooklook
 */

get_header();
?>

	<main id="primary" class="site-main">
		<?php if ( have_posts() ) : ?>
			<section class="page_header">
				<div class="container">
					<header>
						<?php
							if (function_exists('breadcrumbs')) breadcrumbs(); 

                            if (is_archive()) {
                                if (is_post_type_archive('recipe')) {
                                    // Если это архив для кастомного типа записи "Рецепты"
                                    echo '<h1 class="page_title">'.__('Каталог рецептов', 'cooklook').'</h1>';
                                } elseif (is_tax('recipe_category')) {
                                    // Если это архив категории (используйте taxonomy slug вместо 'recipe-category', если он отличается)
                                    $term = get_queried_object();
                                    echo '<h1 class="page_title">' . single_term_title('', false) . '</h1>';
                                } elseif (is_tag()) {
                                    // Если это архив тега
                                    echo '<h1 class="page_title">'.__('Рецепты с ','cooklook') . single_tag_title('', false).'</h1>';
                                } else {
                                    // Другие случаи архивов
                                    echo '<h1 class="page_title">'.__('Архив', 'cooklook').'</h1>';
                                }
                            }					
						?>
						<div class="filters">
							<?php echo do_shortcode('[facetwp facet="category"]') ?>
							<?php echo do_shortcode('[facetwp facet="region"]') ?>
						</div>
					</header><!-- .page-header -->
				</div>
			</section>

		

			<section>
				<div class="container">
                <div class="recipes_grid facetwp-template">
                    <?php
                        $args = array(
                            'post_type' => 'recipe', // Тип записи, для стандартных записей это 'post'.                            
                            'posts_per_page' => 10, // Количество записей для показа.
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
				</div>
			</section>
		<?php endif; ?>
	</main><!-- #main -->

<?php
get_footer();
