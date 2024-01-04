<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package cooklook
 */
include 'template-parts/variables.php';
?>

	<footer id="colophon" class="site-footer">
		<div class="container ">
			<div class="footer_top flex">
				<div class="footer_logo">
					<?php the_custom_logo(); ?>
					<span><?php bloginfo('description'); ?></span>
				</div>

				<div class="footer_menu">
					<?php
						$args = array(
							'container'       => 'nav',          
							'container_class' => 'footer_menu menu',           
							'menu_class'      => 'footer_menu_list',          
							'fallback_cb'     => 'wp_page_menu',            
							'link_class'     => 'menu_link',           
							'theme_location' => 'main_menu',
							'menu_id'        => 'primary-menu-footer',
							'add_li_class'    => 'menu_item',
							'echo'          => false,               
						);
					$temp_menu = wp_nav_menu($args);
					echo $temp_menu;
					?>
				</div>

				<div class="footer_links">
					<?php
						$args = array(
							'container'       => 'nav',          
							'container_class' => 'footer_menu menu',           
							'menu_class'      => 'footer_menu_list',          
							'fallback_cb'     => 'wp_page_menu',            
							'link_class'     => 'menu_link',           
							'theme_location' => 'main_menu',
							'menu_id'        => 'links-menu-footer',
							'add_li_class'    => 'menu_item',
							'echo'          => false,               
						);
						$temp_menu = wp_nav_menu($args);
						echo $temp_menu;
					?>						
				</div>

				<div class="footer_contacts">
					<div class="contacts_item">
						<span class="footer_contacts-title"><?= __('Email', 'cooklook') ?></span>
						<a href="mailto:<?= $email ?>" class="footer_contacts-link"><?= $email ?></a>
					</div>

					<div class="contacts_item">
						<span class="footer_contacts-title"><?= __('Реклама на сайте', 'cooklook') ?></span>
						<a href="mailto:<?= $ads_email ?>" class="footer_contacts-link"><?= $ads_email ?></a>
					</div>				
				</div>

			</div>
			

			

		</div>

		<div class="site-info">
			<a href="<?php echo esc_url( __( 'https://wordpress.org/', 'cooklook' ) ); ?>">
				<?php
				/* translators: %s: CMS name, i.e. WordPress. */
				printf( esc_html__( 'Proudly powered by %s', 'cooklook' ), 'WordPress' );
				?>
			</a>
			<span class="sep"> | </span>
				<?php
				/* translators: 1: Theme name, 2: Theme author. */
				printf( esc_html__( 'Theme: %1$s by %2$s.', 'cooklook' ), 'cooklook', '<a href="https://github.com/DiPokemon/cooklook">Dimitriy Nikolenko</a>' );
				?>
		</div><!-- .site-info -->

	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
