<?php
/**
 * Template name: Избранные рецепты
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package cooklook
 */

get_header();

$current_user = wp_get_current_user();
global $wpdb;
$table_name = $wpdb->prefix . 'favorite_recipes';
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
} else {
    $user_id = $_COOKIE['user_id'] ?? '0'; // '0' или другое значение по умолчанию, если cookie нет
}

$favorites = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT recipe_id FROM $table_name WHERE user_id = %d",
        $user_id
    )
);
$recipe_ids = array();
foreach ($favorites as $favorite) {
    $recipe_ids[] = $favorite->recipe_id;
}
?>

	<main id="primary" class="site-main">

		<?php if ( !empty($recipe_ids) ) : ?>            
            
			<section class="page_header">
				<div class="container">
					<header>
						<?php if (function_exists('breadcrumbs')) breadcrumbs(); ?>
                        <h1 class="page_title"><?= __('Избранные рецепты', 'cooklook') ?></h1>
						
					</header><!-- .page-header -->
				</div>
			</section>

			<section>
				<div class="container">

                    <div id="response" class="recipes_grid">
                        <?php
                            $args = array(
                                'post_type' => 'recipe', // Тип записи "recipe"
                                'post__in' => $recipe_ids, // Массив ID рецептов
                                'orderby' => 'post__in' // Сортировка по порядку ID
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
                            $the_query = new WP_Query( $args );
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
        <?php else: ?>
            <section class="empty_favorites">
				<div class="container">
                    <div id="response" class="recipes_grid">
                        <div class="recipe_loop-item recipe_loop-item-nothing">
                            <div class="recipe_loop-wrapper">
                                <div class="img_block">
                                    <img src="<?=  get_template_directory_uri() ?>/static/img/onion.png" >            
                                </div>
                                <div class="recipe_loop-content flex">
                                    <span>
                                        <?= __('Ничего не найдено', 'cooklook') ?>
                                    </span>
                                    <p><?= __('Вы не добавили ни одного рецепта в избранные. Вам ничего не понравилось? :(', 'cooklook')?> </p>
                                </div> 
                            </div>
                        </div>
                    </div>
                </div>
            </section>

		<?php endif; ?>
	</main><!-- #main -->
<?php
get_footer();