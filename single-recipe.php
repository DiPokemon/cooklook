<?php
/* 
* Templae name: Страница рецепта
*/

include 'template-parts/variables.php';
get_header();

?>

<main id="primary" class="site-main">
    <section class="page_header">
        <div class="container">
            <header>
                <?php if (function_exists('breadcrumbs')) breadcrumbs(); ?>
            </header>
        </div>
    </section>
    
    
	<?php
		while ( have_posts() ) :
			the_post();           

			get_template_part( 'template-parts/content', get_post_type() );

			the_post_navigation(
				array(
					'prev_text' => '<span class="nav-subtitle">' . esc_html__( 'Previous:', 'cooklook' ) . '</span> <span class="nav-title">%title</span>',
					'next_text' => '<span class="nav-subtitle">' . esc_html__( 'Next:', 'cooklook' ) . '</span> <span class="nav-title">%title</span>',
				)
			);

			// If comments are open or we have at least one comment, load up the comment template.
			

		endwhile; // End of the loop.
	?>

</main><!-- #main -->

<?php
get_footer()
?>