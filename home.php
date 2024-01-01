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
			
		</section>

		

	</main><!-- #main -->

<?php
get_sidebar();
get_footer();
