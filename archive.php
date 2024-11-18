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
    <?php if (have_posts()) : ?>
        <section class="page_header">
            <div class="container">
                <header>
                    <?php
                    if (function_exists('breadcrumbs')) breadcrumbs();

                    if (is_archive()) {
                        if (is_post_type_archive('recipe')) {
                            echo '<h1 class="page_title">' . __('Каталог рецептов', 'cooklook') . '</h1>';
                        } elseif (is_tax('recipe_category')) {
                            $term = get_queried_object();
                            $parent_term = get_term($term->parent, 'recipe_category');
                            if ($parent_term instanceof WP_Term) {
                                echo '<h1 class="page_title">' . $parent_term->name . ' / ' . $term->name . '</h1>';
                            } else {
                                echo '<h1 class="page_title">' . $term->name . '</h1>';
                            }
                        } elseif (is_tax('recipe_tags')) {
                            echo '<h1 class="page_title">' . __('Рецепты с "', 'cooklook') . single_tag_title('', false) . '"</h1>';
                        } else {
                            echo '<h1 class="page_title">' . __('Архив', 'cooklook') . '</h1>';
                        }
                    }
                    ?>
                    <div id="filters" class="filters">
                        <div class="filters_header mobile_display">
                            <span><?= __('Фильтры', 'cooklook') ?></span>
                            <button id="close_filter-mobile" class="close_modal">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
                                    <path d="M13.3002 0.70998C13.2077 0.617276 13.0978 0.543728 12.9768 0.493547C12.8559 0.443366 12.7262 0.417535 12.5952 0.417535C12.4643 0.417535 12.3346 0.443366 12.2136 0.493547C12.0926 0.543728 11.9827 0.617276 11.8902 0.70998L7.00022 5.58998L2.11022 0.699979C2.01764 0.607397 1.90773 0.533957 1.78677 0.483852C1.6658 0.433747 1.53615 0.407959 1.40522 0.407959C1.27429 0.407959 1.14464 0.433747 1.02368 0.483852C0.902716 0.533957 0.792805 0.607397 0.700223 0.699979C0.607642 0.792561 0.534202 0.902472 0.484097 1.02344C0.433992 1.1444 0.408203 1.27405 0.408203 1.40498C0.408203 1.53591 0.433992 1.66556 0.484097 1.78652C0.534202 1.90749 0.607642 2.0174 0.700223 2.10998L5.59022 6.99998L0.700223 11.89C0.607642 11.9826 0.534202 12.0925 0.484097 12.2134C0.433992 12.3344 0.408203 12.464 0.408203 12.595C0.408203 12.7259 0.433992 12.8556 0.484097 12.9765C0.534202 13.0975 0.607642 13.2074 0.700223 13.3C0.792805 13.3926 0.902716 13.466 1.02368 13.5161C1.14464 13.5662 1.27429 13.592 1.40522 13.592C1.53615 13.592 1.6658 13.5662 1.78677 13.5161C1.90773 13.466 2.01764 13.3926 2.11022 13.3L7.00022 8.40998L11.8902 13.3C11.9828 13.3926 12.0927 13.466 12.2137 13.5161C12.3346 13.5662 12.4643 13.592 12.5952 13.592C12.7262 13.592 12.8558 13.5662 12.9768 13.5161C13.0977 13.466 13.2076 13.3926 13.3002 13.3C13.3928 13.2074 13.4662 13.0975 13.5163 12.9765C13.5665 12.8556 13.5922 12.7259 13.5922 12.595C13.5922 12.464 13.5665 12.3344 13.5163 12.2134C13.4662 12.0925 13.3928 11.9826 13.3002 11.89L8.41022 6.99998L13.3002 2.10998C13.6802 1.72998 13.6802 1.08998 13.3002 0.70998Z" />
                                </svg>
                            </button>
                        </div>

                        <form id="recipe-filter">
                            <select id="recipe_category" name="recipe_category" class="filter_select" data-placeholder="<?= __('Любая категория', 'cooklook') ?>">
                                <option value="all"><?= __('Любая категория', 'cooklook') ?></option>
                                <?php
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

                            <select id="recipe_subcategory" name="recipe_subcategory" class="filter_select" data-placeholder="<?= __('Любое блюдо', 'cooklook') ?>" disabled>
                                <option value="all"><?= __('Любое блюдо', 'cooklook') ?></option>
                            </select>

                            <select id="recipe_region" name="recipe_region" class="filter_select" data-placeholder="<?= __('Любое меню', 'cooklook') ?>">
                                <option value=""><?= __('Любое меню', 'cooklook') ?></option>
                                <?php
                                global $wpdb;
                                $regions = $wpdb->get_col("SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = '_recipe_region'");

                                foreach ($regions as $region_name) {
                                    if ($region_name) :
                                ?>
                                        <option value="<?= esc_attr($region_name) ?>"><?= esc_html($region_name) ?></option>
                                <?php
                                    endif;
                                }
                                ?>
                            </select>
                            <?php get_template_part('template-parts/filter-ingridients'); ?>
                            <button class="btn_nonbg ingridients_btn"><?= __('Ингридиенты', 'cooklook') ?></button>

                            <button class="btn_bg" type="submit"><?= __('Применить фильтр', 'cooklook') ?></button>
                        </form>
                    </div>

                    <div class="filters_wrapper_mob">
                        <button id="mobile_filters-open">
                            <img src="<?= get_template_directory_uri() ?>/static/img/filter.svg">
                            <?= __('Фильтры', 'cooklook') ?>
                        </button>

                        <div class="sorting mobile_display">
                            <form id="sort_form-mobile">
                                <select name="sort_by" id="sort_by-mobile">
                                    <option value=""><?= __('Сортировка', 'cooklook') ?></option>
                                    <option value="date"><?= __('Дата добавления', 'cooklook') ?></option>
                                    <option value="recipe_views"><?= __('Просмотры', 'cooklook') ?></option>
                                    <option value="recipe_time"><?= __('Время приготовления', 'cooklook') ?></option>
                                </select>
                            </form>
                        </div>
                    </div>
                </header><!-- .page-header -->
            </div>
        </section>

        <section>
            <div class="container">

                <div class="sorting hide_mobile">
                    <span><?= __('Сортировать по', 'cooklook') ?>:</span>
                    <form id="sort_form">
                        <select name="sort_by" id="sort_by">
                            <option value="date">Дата добавления</option>
                            <option value="recipe_views">Просмотры</option>
                            <option value="recipe_time">Время приготовления</option>
                        </select>
                    </form>
                </div>

                <div id="response" class="recipes_grid">
                    <?php
                    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

                    $args = array(
                        'post_status' => 'publish',
                        'post_type' => 'recipe',
                        'posts_per_page' => 13,
                        'orderby' => 'date',
                        'order' => 'DESC',
                        'paged' => $paged,
                    );

                    $category_id = isset($_GET['recipe_category']) ? intval($_GET['recipe_category']) : 0;
                    $subcategory_id = isset($_GET['recipe_subcategory']) ? intval($_GET['recipe_subcategory']) : 0;

                    if ($category_id) {
                        $args['tax_query'] = array(
                            array(
                                'taxonomy' => 'recipe_category',
                                'field' => 'id',
                                'terms' => $category_id,
                                'paged' => $paged,
                            ),
                        );
                    }

                    if ($subcategory_id) {
                        $args['tax_query'][] = array(
                            'taxonomy' => 'recipe_category',
                            'field' => 'id',
                            'terms' => $subcategory_id,
                            'paged' => $paged,
                        );
                    }

                    if (is_tax('recipe_tags')) {
                        $tag = get_queried_object();
                        $args['tax_query'][] = array(
                            'taxonomy' => 'recipe_tags',
                            'field' => 'slug',
                            'terms' => $tag->slug,
                            'paged' => $paged,
                        );
                    }

                    if (is_tax('recipe_category')) {
                        $category = get_queried_object();
                        $args = array(
                            'post_type' => 'recipe',
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'recipe_category',
                                    'field' => 'slug',
                                    'terms' => $category->slug,
                                ),
                            ),
                            'paged' => $paged,
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

                            if (get_the_excerpt()) {
                                $description = get_the_excerpt();
                            } else if (get_the_content()) {
                                $description = get_the_content();
                            } else {
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
                            } else {
                                get_template_part('template-parts/recipe-loop-item');
                            }
                        }
                    } else {
                        get_template_part('template-parts/recipe-loop-nothing');
                    }

                    wp_reset_postdata();
                    ?>
                </div>

                <div class="pagination">
                    <?php
                    the_posts_pagination(array(
                        'mid_size' => 2,
                        'prev_text' => __('Previous', 'cooklook'),
                        'next_text' => __('Next', 'cooklook'),
                        'screen_reader_text' => __('Пагинация', 'cooklook'),
                        
                    ));
                    ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main><!-- #main -->

<?php
get_footer();
?>