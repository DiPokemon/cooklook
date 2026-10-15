<?php
/**
 * Template Name: Личный кабинет
 *
 * Uses the existing WordPress user and favorites; no separate profile storage.
 * @package cooklook
 */
defined( 'ABSPATH' ) || exit;
nocache_headers();
get_header();
?>
<main id="primary" class="site-main cooklook-account">
	<div class="container">
		<?php while ( have_posts() ) : the_post(); ?>
			<div class="account-heading">
				<h1><?php the_title(); ?></h1>
				<p><?php esc_html_e( 'Ваши сохранённые рецепты и настройки профиля — в одном месте.', 'cooklook' ); ?></p>
			</div>

			<?php if ( shortcode_exists( 'wp-recall' ) && has_shortcode( get_the_content(), 'wp-recall' ) ) : ?>
				<div class="account-plugin"><?php the_content(); ?></div>
			<?php elseif ( is_user_logged_in() ) :
				$account_user = wp_get_current_user();
				?>
				<div class="account-summary">
					<p><?php esc_html_e( 'Вы вошли как', 'cooklook' ); ?> <strong><?php echo esc_html( $account_user->display_name ); ?></strong></p>
					<a class="account-text-link" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Выйти из аккаунта', 'cooklook' ); ?></a>
				</div>
				<div class="account-grid">
					<section class="account-card" aria-labelledby="account-favorites-title">
						<h2 id="account-favorites-title"><?php esc_html_e( 'Избранные рецепты', 'cooklook' ); ?></h2>
						<p><?php esc_html_e( 'Сохраняйте понравившиеся блюда закладкой на карточке рецепта, чтобы быстро вернуться к ним.', 'cooklook' ); ?></p>
						<a class="btn_bg" href="<?php echo esc_url( home_url( '/my-favorites/' ) ); ?>"><?php esc_html_e( 'Открыть избранное', 'cooklook' ); ?></a>
					</section>
					<section class="account-card" aria-labelledby="account-profile-title">
						<h2 id="account-profile-title"><?php esc_html_e( 'Настройки профиля', 'cooklook' ); ?></h2>
						<p><?php esc_html_e( 'Расскажите о себе, выберите имя для комментариев и управляйте доступом к аккаунту.', 'cooklook' ); ?></p>
						<a class="btn_nonbg" href="#account-profile"><?php esc_html_e( 'Редактировать профиль', 'cooklook' ); ?></a>
					</section>
				</div>
				<?php get_template_part( 'template-parts/account-profile' ); ?>
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<section class="account-admin" aria-labelledby="account-admin-title">
						<h2 id="account-admin-title"><?php esc_html_e( 'Управление сайтом', 'cooklook' ); ?></h2>
						<p><?php esc_html_e( 'Инструменты администратора для работы с рецептами и поиском.', 'cooklook' ); ?></p>
						<nav aria-label="<?php esc_attr_e( 'Инструменты администратора', 'cooklook' ); ?>">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=recipe-manager' ) ); ?>"><?php esc_html_e( 'Менеджер рецептов', 'cooklook' ); ?></a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=cooklook-recipe-importer' ) ); ?>"><?php esc_html_e( 'Обновление из CSV', 'cooklook' ); ?></a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=search-moderation' ) ); ?>"><?php esc_html_e( 'Модерация поиска', 'cooklook' ); ?></a>
						</nav>
					</section>
				<?php endif; ?>
			<?php else : ?>
				<section class="account-card account-sign-in" aria-labelledby="account-sign-in-title">
					<h2 id="account-sign-in-title"><?php esc_html_e( 'Войдите в свой аккаунт', 'cooklook' ); ?></h2>
					<p><?php esc_html_e( 'После входа вы сможете сохранять рецепты в избранное, оценивать блюда и управлять своим профилем.', 'cooklook' ); ?></p>
					<?php
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve a confirmation link through login; does not perform a change.
					$email_token = cooklook_request_text( $_GET, 'newuseremail' );
					$login_return = $email_token ? add_query_arg( 'newuseremail', $email_token, get_permalink() ) : get_permalink();
					?>
					<a class="btn_bg" href="<?php echo esc_url( wp_login_url( $login_return ) ); ?>"><?php esc_html_e( 'Войти', 'cooklook' ); ?></a>
				</section>
			<?php endif; ?>
		<?php endwhile; ?>
	</div>
</main>
<?php get_footer(); ?>
