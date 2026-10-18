<?php
/**
 * Template name: Главная
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package cooklook
 */
$settings_id = get_queried_object_id();
$popular_cats = cooklook_recipe_rows( $settings_id, 'popular_cats' );
$popular_cats_ids = array_filter( array_map( 'absint', wp_list_pluck( $popular_cats, 'id' ) ) );
$popular_terms = $popular_cats_ids ? get_terms( array( 'taxonomy' => 'recipe_category', 'include' => $popular_cats_ids, 'hide_empty' => false, 'orderby' => 'include' ) ) : array();
// Old homepage associations may refer to deleted terms after a data import.
if ( ! is_array( $popular_terms ) || ! $popular_terms ) {
    $popular_terms = get_terms( array( 'taxonomy' => 'recipe_category', 'parent' => 0, 'number' => 5, 'orderby' => 'count', 'order' => 'DESC' ) );
}
$popular_cats_ids = is_array( $popular_terms ) ? wp_list_pluck( $popular_terms, 'term_id' ) : array();
$popular_cats_title = carbon_get_post_meta( $settings_id, 'popular_cats_title' );
$new_recipes_title = carbon_get_post_meta( $settings_id, 'new_recipes_title' );
$popular_recipes_title = carbon_get_post_meta( $settings_id, 'popular_recipes_title' );
$recipes_categories_title = carbon_get_post_meta( $settings_id, 'recipes_categories_title' ) ?: __( 'Категории рецептов', 'cooklook' );
get_header();
?>
	<main id="primary" class="site-main">
		<header class="home_intro container">
			<h1><?= esc_html__( 'Кулинарные рецепты с пошаговыми инструкциями', 'cooklook' ) ?></h1>
			<p><?= esc_html( cooklook_home_intro() ) ?></p>
		</header>
		<section id="popular_categories">
			<div class="container">
                <div class="section_header">
                    <h2 class="section_title"><?= esc_html($popular_cats_title) ?></h2>
                </div>				
                <div class="popular_categories-grid">
                    <?php
                        $counter = 0;
                        foreach($popular_cats_ids as $cat_id) {   
                            $counter++;                    
                            get_template_part('template-parts/category-loop-item', null, array('current_cat_id' => $cat_id, 'priority' => $counter === 1));
                            if($counter == 3) {
                        ?>
                                <?php echo cooklook_ad( 'main_categories', 'category_item category_item-adv' ); ?>
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
                    <h2 class="section_title"><?= esc_html($new_recipes_title) ?></h2>
                    <a href="<?= home_url('/recipes/') ?>" class="btn_bg hide_mobile"><?= __('Смотреть все рецепты', 'cooklook') ?></a>
                </div>
                <div class="recipes_grid">
                    <?php
                        $args = array(
                            'post_type' => 'recipe', // Тип записи, для стандартных записей это 'post'.
                            'posts_per_page' => 5,
                            'post_status' => 'publish',
                            'has_password' => false,
                            'no_found_rows' => true, // Количество записей для показа.
                            'orderby' => 'date', // Сортировка по дате.
                            'order' => 'DESC' // Сортировка по убыванию.
                        );
                        $the_query = new WP_Query($args);
                        if ($the_query->have_posts()) {
                            while ($the_query->have_posts()) {
                                $the_query->the_post();
                                get_template_part('template-parts/recipe-loop-item');
                            }
                        } else {
                            echo 'No posts found.';
                        }
                        
                        wp_reset_postdata();
                    ?>
                </div>
                <a href="<?= home_url('/recipes/') ?>" class="view_all btn_bg mobile_display"><?= __('Смотреть все рецепты', 'cooklook') ?></a>
            </div>			
		</section>

        <?php $home_banner_strip = cooklook_ad( 'main_square_1' ) . cooklook_ad( 'main_square_2' ) . cooklook_ad( 'main_square_3' ); ?>
        <?php if ( $home_banner_strip ) : ?>
        <section>
            <div class="container">
                <div class="main_bigadv_block">
                    <?php echo $home_banner_strip; // Escaped by cooklook_ad(). ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <section>
            <div class="container">
                <div class="section_header flex">
                    <h2 class="section_title"><?= esc_html($popular_recipes_title) ?></h2>
                    <a href="<?= home_url('/recipes/') ?>" class="btn_bg hide_mobile"><?= __('Смотреть все рецепты', 'cooklook') ?></a>
                </div>
                <div class="recipes_grid">
                    <?php
                        $args = array(
                            'post_type' => 'recipe',
                            'posts_per_page' => 5,
                            'post_status' => 'publish',
                            'has_password' => false,
                            'no_found_rows' => true,
                            'cooklook_sort_meta' => '_recipe_views',
                            'order' => 'DESC' 
                        );
                        $the_query = new WP_Query($args);
                        if ($the_query->have_posts()) {
                            while ($the_query->have_posts()) {
                                $the_query->the_post();
                                get_template_part('template-parts/recipe-loop-item');
                            }
                        } else {
                            echo 'No posts found.';
                        }
                        
                        wp_reset_postdata();
                    ?>
                </div>
                <a href="<?= home_url('/recipes/') ?>" class="view_all btn_bg mobile_display"><?= __('Смотреть все рецепты', 'cooklook') ?></a>
                
            </div>			
		</section>

        <section>
            <div class="container">
                <div class="section_header">
                    <h2 class="section_title"><?= esc_html($recipes_categories_title) ?></h2>
                </div>
                <div class="categories_grid">
                    <?php
                        $parent_cats = get_terms(array(
                            'taxonomy' => 'recipe_category', // Замените на вашу кастомную таксономию
                            'parent' => 0,
                            'hide_empty' => true, // Устанавливаем false, чтобы показывать категории, даже если они пустые
                        ));
                        $parent_cats = is_array($parent_cats) ? $parent_cats : array();
                        $parent_cat_count = count($parent_cats);

                        foreach ($parent_cats as $parent_cat) {
                            $child_cats = get_terms(array(
                                'taxonomy' => 'recipe_category',
                                'parent' => $parent_cat->term_id,
                                'hide_empty' => true,
                            ));
                            set_query_var('parent_cat', $parent_cat);
                            set_query_var('child_cats', $child_cats);
                            get_template_part('template-parts/category-list');
                        }
                    ?>
                    
                </div>
                <div class="categories_grid-controls">
                    <button class="categories_grid-controls-prev btn_bg" type="button" aria-label="<?= esc_attr__( 'Предыдущие категории', 'cooklook' ) ?>">
                        <img src="<?= esc_url( get_template_directory_uri() . '/static/img/chevron-left.svg' ) ?>" alt="" width="24" height="24">
                    </button>
                    <div id="categories_grid_slider-counter" class="categories_grid-controls-count">
                        <span class="count_current">1</span> / <span class="count_total"><?= $parent_cat_count ?></span>
                    </div>
                    <button class="categories_grid-controls-next btn_bg" type="button" aria-label="<?= esc_attr__( 'Следующие категории', 'cooklook' ) ?>">
                        <img src="<?= esc_url( get_template_directory_uri() . '/static/img/chevron-right.svg' ) ?>" alt="" width="24" height="24">
                    </button>
                </div>
            </div>
        </section>

        <?php $home_bottom_ad = cooklook_ad( 'main_bottom', 'qwert_block' ); ?>
        <?php if ( $home_bottom_ad ) : ?>
            <section><div class="container"><?php echo $home_bottom_ad; // Escaped by cooklook_ad(). ?></div></section>
        <?php endif; ?>

		

	</main><!-- #main -->

<?php
get_footer();
