<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package cooklook
 */
$title_404 = carbon_get_theme_option('title_404');
$subtitle_404 = carbon_get_theme_option('subtitle_404');
$text_404 = carbon_get_theme_option('text_404');
$img_404 = carbon_get_theme_option('image_404');
$btn_404 = carbon_get_theme_option('btn_404');
get_header();
?>

	<main id="primary" class="site-main">
		<section class="error-404 not-found">
			<div class="container">
				<div class="page-content wrapper_404 flex">
					<?php
						$img_src = !empty($img_404) ? $img_404 : get_template_directory_uri().'/static/img/onion.png';
					?>
					
					<div class="image_404">
						<img src="<?= esc_url($img_src) ?>" alt="Страница не найдена">
					</div>
					<div class="content_404">
						<span class="title_404">
							<?php echo esc_html(!empty($title_404) ? $title_404 : __('ОЙ!', 'cooklook'));	?>
						</span>
						<span class="subtitle_404">
							<?php echo esc_html(!empty($subtitle_404) ? $subtitle_404 : __('Похоже эта страница потерялась', 'cooklook')); ?>
						</span>
						<span class="text_404">
							<?php echo esc_html(!empty($text_404) ? $text_404 : __('Может попробуем вернуться на главную и поискать что-нибудь другое?', 'cooklook')); ?>
						</span>
						<a href="<?= esc_url(home_url()) ?>" class="btn_bg">
							<?php echo esc_html(!empty($btn_404) ? $btn_404 : __('На главную', 'cooklook')); ?>
						</a>
					</div>

				</div>
			</div>
		</section>
	</main>

<?php
get_footer();
