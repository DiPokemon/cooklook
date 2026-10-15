<?php
/**
 * Template Name: Избранные рецепты
 * @package cooklook
 */
get_header();
?>
<main id="primary" class="site-main">
	<section class="page_header"><div class="container"><h1 class="page_title"><?php esc_html_e( 'Избранные рецепты', 'cooklook' ); ?></h1></div></section>
	<section><div class="container">
	<?php if ( is_user_logged_in() ) :
		$ids = cooklook_favorite_ids( get_current_user_id() );
		$query = new WP_Query( array(
			'post_type' => 'recipe', 'post_status' => 'publish', 'has_password' => false,
			'post__in' => $ids ?: array( 0 ), 'orderby' => 'post__in',
			'posts_per_page' => 12, 'paged' => max( 1, absint( get_query_var( 'paged' ) ), absint( get_query_var( 'page' ) ) ),
		) );
		?>
		<div id="response" class="recipes_grid"><?php cooklook_render_recipes( $query ); ?></div>
		<div class="pagination"><?php echo cooklook_recipe_pagination( $query ); ?></div>
	<?php else : ?>
		<p><a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Войдите, чтобы посмотреть избранные рецепты.', 'cooklook' ); ?></a></p>
	<?php endif; ?>
	</div></section>
</main>
<?php get_footer(); ?>
