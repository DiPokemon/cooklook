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
	<div class="bottom_menu mobile_display">
		<a href="<?= home_url() ?>" class="bottom_menu-item">
			<svg xmlns="http://www.w3.org/2000/svg" width="25" height="24" viewBox="0 0 25 24" fill="none">
				<path d="M20.4406 6.81994L14.6806 2.78994C13.1106 1.68994 10.7006 1.74994 9.19063 2.91994L4.18062 6.82994C3.18062 7.60994 2.39062 9.20994 2.39062 10.4699V17.3699C2.39062 19.9199 4.46062 21.9999 7.01062 21.9999H17.7906C20.3406 21.9999 22.4106 19.9299 22.4106 17.3799V10.5999C22.4106 9.24994 21.5406 7.58994 20.4406 6.81994ZM13.1506 17.9999C13.1506 18.4099 12.8106 18.7499 12.4006 18.7499C11.9906 18.7499 11.6506 18.4099 11.6506 17.9999V14.9999C11.6506 14.5899 11.9906 14.2499 12.4006 14.2499C12.8106 14.2499 13.1506 14.5899 13.1506 14.9999V17.9999Z"/>
			</svg>
			<span><?= __('Главная', 'cooklook') ?></span>
		</a>

		<a href="<?= home_url('/recipes/') ?>" class="bottom_menu-item">
			<svg xmlns="http://www.w3.org/2000/svg" width="25" height="24" viewBox="0 0 25 24" fill="none">
				<path d="M22.2012 4.84993V16.7399C22.2012 17.7099 21.4112 18.5999 20.4412 18.7199L20.1312 18.7599C18.4912 18.9799 16.1812 19.6599 14.3212 20.4399C13.6712 20.7099 12.9512 20.2199 12.9512 19.5099V5.59993C12.9512 5.22993 13.1612 4.88993 13.4912 4.70993C15.3212 3.71993 18.0912 2.83993 19.9712 2.67993H20.0312C21.2312 2.67993 22.2012 3.64993 22.2012 4.84993Z" />
				<path d="M10.9114 4.70993C9.08141 3.71993 6.31141 2.83993 4.43141 2.67993H4.36141C3.16141 2.67993 2.19141 3.64993 2.19141 4.84993V16.7399C2.19141 17.7099 2.98141 18.5999 3.95141 18.7199L4.26141 18.7599C5.90141 18.9799 8.21141 19.6599 10.0714 20.4399C10.7214 20.7099 11.4414 20.2199 11.4414 19.5099V5.59993C11.4414 5.21993 11.2414 4.88993 10.9114 4.70993ZM5.20141 7.73993H7.45141C7.86141 7.73993 8.20141 8.07993 8.20141 8.48993C8.20141 8.90993 7.86141 9.23993 7.45141 9.23993H5.20141C4.79141 9.23993 4.45141 8.90993 4.45141 8.48993C4.45141 8.07993 4.79141 7.73993 5.20141 7.73993ZM8.20141 12.2399H5.20141C4.79141 12.2399 4.45141 11.9099 4.45141 11.4899C4.45141 11.0799 4.79141 10.7399 5.20141 10.7399H8.20141C8.61141 10.7399 8.95141 11.0799 8.95141 11.4899C8.95141 11.9099 8.61141 12.2399 8.20141 12.2399Z"/>
			</svg>
			<span><?= __('Рецепты', 'cooklook') ?></span>
		</a>

		<a href="<?= home_url('/#') ?>" class="bottom_menu-item">
			<svg xmlns="http://www.w3.org/2000/svg" width="25" height="24" viewBox="0 0 25 24" fill="none">
				<path d="M17.3008 2H7.66078C5.53078 2 3.80078 3.74 3.80078 5.86V19.95C3.80078 21.75 5.09078 22.51 6.67078 21.64L11.5508 18.93C12.0708 18.64 12.9108 18.64 13.4208 18.93L18.3008 21.64C19.8808 22.52 21.1708 21.76 21.1708 19.95V5.86C21.1608 3.74 19.4308 2 17.3008 2Z"/>
			</svg>
			<span><?= __('Избранное', 'cooklook') ?></span>
		</a>

		<a href="<?= home_url('/account/') ?>" class="bottom_menu-item">
			<svg xmlns="http://www.w3.org/2000/svg" width="25" height="24" viewBox="0 0 25 24" fill="none">
				<path d="M12.5996 12C15.361 12 17.5996 9.76142 17.5996 7C17.5996 4.23858 15.361 2 12.5996 2C9.83819 2 7.59961 4.23858 7.59961 7C7.59961 9.76142 9.83819 12 12.5996 12Z" />
				<path d="M12.5998 14.5C7.58977 14.5 3.50977 17.86 3.50977 22C3.50977 22.28 3.72977 22.5 4.00977 22.5H21.1898C21.4698 22.5 21.6898 22.28 21.6898 22C21.6898 17.86 17.6098 14.5 12.5998 14.5Z" />
			</svg>
			<span><?= __('Аккаунт', 'cooklook') ?></span>
		</a>

	</div>

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
