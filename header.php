<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package cooklook
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php include 'template-parts/variables.php' ?>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'К содержимому', 'cooklook' ); ?></a>

	<header id="masthead" class="site-header">
		<div class="container flex">
			<div class="header_left flex">
				<?php the_custom_logo(); ?>
				<nav id="site-navigation" class="main-navigation">					
					<?php
						wp_nav_menu(
							array(
								'theme_location' => 'main_menu',
								'menu_id'        => 'primary-menu',
							)
						);
					?>
		   		</nav><!-- #site-navigation -->
			</div>
			<div class="header_right flex">
				<a href="#" class="header_search flex">					
					<svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
						<title><?= __('Поиск рецептов на сайте', 'cooklook') ?></title>
						<path d="M11.5 21.75C5.85 21.75 1.25 17.15 1.25 11.5C1.25 5.85 5.85 1.25 11.5 1.25C17.15 1.25 21.75 5.85 21.75 11.5C21.75 17.15 17.15 21.75 11.5 21.75ZM11.5 2.75C6.67 2.75 2.75 6.68 2.75 11.5C2.75 16.32 6.67 20.25 11.5 20.25C16.33 20.25 20.25 16.32 20.25 11.5C20.25 6.68 16.33 2.75 11.5 2.75Z" />
						<path d="M22.0004 22.7499C21.8104 22.7499 21.6204 22.6799 21.4704 22.5299L19.4704 20.5299C19.1804 20.2399 19.1804 19.7599 19.4704 19.4699C19.7604 19.1799 20.2404 19.1799 20.5304 19.4699L22.5304 21.4699C22.8204 21.7599 22.8204 22.2399 22.5304 22.5299C22.3804 22.6799 22.1904 22.7499 22.0004 22.7499Z" />
					</svg>				
				</a>
				<a href="#" class="header_bookmark flex">
					<svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
						<title><?= __('Добавить рецепт в избранное', 'cooklook') ?></title>
						<path d="M16.5 2H6.86C4.73 2 3 3.74 3 5.86V19.95C3 21.75 4.29 22.51 5.87 21.64L10.75 18.93C11.27 18.64 12.11 18.64 12.62 18.93L17.5 21.64C19.08 22.52 20.37 21.76 20.37 19.95V5.86C20.36 3.74 18.63 2 16.5 2Z"/>
					</svg>
				</a>
				<a href="#" class="header_account flex">					
					<svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
						<title><?= __('Войти в аккаунт', 'cooklook') ?></title>
						<path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z"/>
						<path d="M12.0002 14.5C6.99016 14.5 2.91016 17.86 2.91016 22C2.91016 22.28 3.13016 22.5 3.41016 22.5H20.5902C20.8702 22.5 21.0902 22.28 21.0902 22C21.0902 17.86 17.0102 14.5 12.0002 14.5Z"/>
					</svg>
					<?= __('Войти', 'cooklook')?>
				</a>
				<a href="#" class="header_add_recipe btn_bg"><?= __('Добавить рецепт', 'cooklook') ?></a>
			</div>
			
		</div>		
	</header><!-- #masthead -->
	