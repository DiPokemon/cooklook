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
                            <form id="recipe-filter">
                                <select id="recipe_category" name="recipe_category">
                                    <option value=""><?= __('Любая категория', 'cooklook') ?></option>
                                    <?php
                                    // Получаем список категорий
                                        $categories = get_terms(array(
                                            'taxonomy' => 'recipe_category',
                                            'hide_empty' => false,
                                            'parent' => 0,
                                        ));

                                        foreach ($categories as $category) {
                                            echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                                        }
                                    ?>
                                </select>

                                <select id="recipe_subcategory" name="recipe_subcategory" disabled>
                                    <option value=""><?= __('Любое блюдо', 'cooklook') ?></option>
                                </select>

                                <select id="recipe_region" name="recipe_region">
                                    <option value=""><?= __('Любое меню', 'cooklook') ?></option>
                                    <?php                                
                                        $regions = get_posts(array(
                                            'post_type' => 'recipe', // Замените 'recipes' на свой тип записи
                                            'posts_per_page' => -1,
                                            'meta_key' => '_recipe_region', // Замените 'recipe_region' на ваше кастомное поле
                                            'fields' => 'ids',
                                        ));

                                        $regions = array_unique($regions); // Убираем дубликаты

                                        foreach ($regions as $region_id) {
                                            $region_name = carbon_get_post_meta($region_id, 'recipe_region'); // Замените на ваше кастомное поле
                                            if ($region_name) :
                                            ?>
                                                <option value="<?= esc_attr($region_name) ?>"><?= esc_html($region_name) ?></option>
                                            <?php endif;
                                        }
                                ?>
                                    
                                </select>

                                <?php get_template_part('template-parts/filter-ingridients'); ?>

                                <button class="btn_nonbg ingridients_btn"><?= __('Ингридиенты', 'cooklook') ?></button>
                                
                                <button class="btn_bg" type="submit"><?= __('Применить фильтр', 'cooklook') ?></button>
                            </form>


						</div>
					</header><!-- .page-header -->
				</div>
			</section>

		

			<section>
				<div class="container">
                <div id="response" class="recipes_grid">
                    <?php
                        $args = array(
                            'post_type' => 'recipe', // Тип записи, для стандартных записей это 'post'.                            
                            'posts_per_page' => 10, // Количество записей для показа.
                            'orderby' => 'date', // Сортировка по дате.
                            'order' => 'DESC', // Сортировка по убыванию.                            
                        );

                        // Добавляем фильтрацию по категории и подкатегории, если они выбраны в форме
                        $category_id = isset($_GET['recipe_category']) ? intval($_GET['recipe_category']) : 0;
                        $subcategory_id = isset($_GET['recipe_subcategory']) ? intval($_GET['recipe_subcategory']) : 0;

                        if ($category_id) {
                            $args['tax_query'] = array(
                                array(
                                    'taxonomy' => 'recipe_category',
                                    'field' => 'id',
                                    'terms' => $category_id,
                                ),
                            );
                        }

                        if ($subcategory_id) {
                            $args['tax_query'][] = array(
                                'taxonomy' => 'recipe_category',
                                'field' => 'id',
                                'terms' => $subcategory_id,
                            );
                        }
                        $post_counter = 0;
                        $the_query = new WP_Query($args);
                        if ($the_query->have_posts()) {
                            while ($the_query->have_posts()) {
                                $the_query->the_post();
                                $post_counter++;
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


                                if ($post_counter % 4 == 0) {
                                    get_template_part('template-parts/recipe-loop-item-adv');
                                }
                                else {
                                    get_template_part('template-parts/recipe-loop-item');
                                }
                            }
                        } else {
                            get_template_part('template-parts/recipe-loop-nothing');
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
