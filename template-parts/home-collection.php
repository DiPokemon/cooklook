<?php
/** The active collection is unlimited; only its homepage preview is capped at ten. */
defined( 'ABSPATH' ) || exit;

$settings_id = isset( $args['settings_id'] ) ? absint( $args['settings_id'] ) : get_queried_object_id();
$collection = cooklook_home_collection( $settings_id );
if ( ! $collection ) { return; }
$collection_url = get_term_link( $collection );
if ( is_wp_error( $collection_url ) ) { return; }
$collection_query = cooklook_home_collection_query( $collection );
if ( ! $collection_query->have_posts() ) { return; }
$collection_title = trim( (string) carbon_get_post_meta( $settings_id, 'home_collection_title' ) ) ?: $collection->name;
$collection_button = trim( (string) carbon_get_post_meta( $settings_id, 'home_collection_button' ) ) ?: __( 'Смотреть всю подборку', 'cooklook' );
?>
<section class="seasonal_collection" aria-labelledby="home-collection-title">
    <div class="container">
        <div class="section_header flex">
            <h2 id="home-collection-title" class="section_title"><?= esc_html( $collection_title ) ?></h2>
            <a href="<?= esc_url( $collection_url ) ?>" class="btn_bg hide_mobile"><?= esc_html( $collection_button ) ?></a>
        </div>
        <div class="recipes_grid seasonal_recipes">
            <?php
            while ( $collection_query->have_posts() ) {
                $collection_query->the_post();
                get_template_part( 'template-parts/recipe-loop-item' );
            }
            wp_reset_postdata();
            ?>
        </div>
        <div class="collection_slider-controls" hidden>
            <button class="collection_slider-prev btn_bg" type="button" aria-label="<?= esc_attr__( 'Предыдущие рецепты подборки', 'cooklook' ) ?>">
                <img src="<?= esc_url( get_template_directory_uri() . '/static/img/chevron-left.svg' ) ?>" alt="" width="24" height="24">
            </button>
            <span class="collection_slider-count" aria-live="polite" aria-atomic="true"></span>
            <button class="collection_slider-next btn_bg" type="button" aria-label="<?= esc_attr__( 'Следующие рецепты подборки', 'cooklook' ) ?>">
                <img src="<?= esc_url( get_template_directory_uri() . '/static/img/chevron-right.svg' ) ?>" alt="" width="24" height="24">
            </button>
        </div>
        <a href="<?= esc_url( $collection_url ) ?>" class="view_all btn_bg mobile_display"><?= esc_html( $collection_button ) ?></a>
    </div>
</section>
