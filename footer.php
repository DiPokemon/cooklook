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

			<div class="footer_bottom">
				© cook-look.ru, 2024. <?= __('Все права защищены', 'cooklook') ?>
			</div>
		</div>
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
