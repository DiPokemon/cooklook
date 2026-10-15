<?php
/** @package cooklook */
get_header();
global $wp_query;
?>
<main id="primary" class="site-main">
	<section class="page_header"><div class="container">
		<?php if ( function_exists( 'breadcrumbs' ) ) { breadcrumbs(); } ?>
		<h1 class="page_title"><?php echo esc_html( sprintf( __( 'Результаты поиска по запросу: «%s»', 'cooklook' ), get_search_query( false ) ) ); ?></h1>
	</div></section>
	<section><div class="container">
		<div id="response" class="recipes_grid"><?php cooklook_render_recipes( $wp_query, true ); ?></div>
		<div class="pagination"><?php echo cooklook_recipe_pagination( $wp_query ); ?></div>
	</div></section>
</main>
<?php get_footer(); ?>
