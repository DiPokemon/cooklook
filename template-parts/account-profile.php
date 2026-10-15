<?php
/** Profile forms. Changes are processed before headers by cooklook_account_request(). */
defined( 'ABSPATH' ) || exit;
if ( ! is_user_logged_in() ) {
	return;
}
$profile_user = wp_get_current_user();
$profile_error = isset( $GLOBALS['cooklook_account_error'] ) ? $GLOBALS['cooklook_account_error'] : null;
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Only reopens the relevant form after validation failed.
$failed_action = $profile_error ? cooklook_request_text( $_POST, 'account_action' ) : '';
$profile_action_url = cooklook_account_url() . '#account-profile';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only selects a fixed, escaped feedback message.
$profile_notice = cooklook_account_notice( cooklook_request_text( $_GET, 'account_notice' ) );
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only; a nonce-protected POST confirms the token.
$email_token = cooklook_request_text( $_GET, 'newuseremail' );
$pending_email = get_user_meta( $profile_user->ID, '_new_email', true );
$profile_values = array();
foreach ( array( 'first_name', 'last_name', 'display_name', 'description' ) as $field ) {
	$profile_values[ $field ] = $profile_user->$field;
}
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Redisplay only sanitized public fields after a failed submission, never passwords.
if ( $profile_error && 'profile' === cooklook_request_text( $_POST, 'account_action' ) ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Field parser sanitizes and allowlists these display-only values.
	$submitted_values = cooklook_account_profile_data( $_POST );
	if ( ! is_wp_error( $submitted_values ) ) {
		$profile_values = $submitted_values;
	}
}
?>
<section id="account-profile" class="account-profile" aria-labelledby="account-profile-heading">
	<div class="account-section-heading">
		<h2 id="account-profile-heading"><?php esc_html_e( 'Мой профиль', 'cooklook' ); ?></h2>
		<p><?php esc_html_e( 'Так вас будут видеть другие любители готовить.', 'cooklook' ); ?></p>
	</div>
	<?php if ( $profile_error ) : ?>
		<p class="account-notice account-notice-error" role="alert"><?php echo esc_html( $profile_error->get_error_message() ); ?></p>
	<?php elseif ( $profile_notice ) : ?>
		<p class="account-notice" role="status"><?php echo esc_html( $profile_notice ); ?></p>
	<?php endif; ?>
	<?php if ( $email_token ) : ?>
		<form method="post" action="<?php echo esc_url( $profile_action_url ); ?>" class="account-notice account-confirm-email">
			<?php wp_nonce_field( 'cooklook_account', 'account_nonce', false ); ?>
			<input type="hidden" name="account_action" value="confirm_email">
			<input type="hidden" name="email_token" value="<?php echo esc_attr( $email_token ); ?>">
			<p><?php esc_html_e( 'Подтвердите смену электронной почты для текущего аккаунта.', 'cooklook' ); ?></p>
			<button class="btn_bg" type="submit"><?php esc_html_e( 'Подтвердить новый адрес', 'cooklook' ); ?></button>
		</form>
	<?php endif; ?>
	<form method="post" action="<?php echo esc_url( $profile_action_url ); ?>" class="account-form">
		<?php wp_nonce_field( 'cooklook_account', 'account_nonce', false ); ?>
		<input type="hidden" name="account_action" value="profile">
		<div class="account-form-grid">
			<div class="account-field">
				<label for="account-first-name"><?php esc_html_e( 'Имя', 'cooklook' ); ?></label>
				<input id="account-first-name" name="first_name" autocomplete="given-name" maxlength="100" value="<?php echo esc_attr( $profile_values['first_name'] ); ?>">
			</div>
			<div class="account-field">
				<label for="account-last-name"><?php esc_html_e( 'Фамилия', 'cooklook' ); ?></label>
				<input id="account-last-name" name="last_name" autocomplete="family-name" maxlength="100" value="<?php echo esc_attr( $profile_values['last_name'] ); ?>">
			</div>
			<div class="account-field account-field-wide">
				<label for="account-display-name"><?php esc_html_e( 'Отображаемое имя', 'cooklook' ); ?></label>
				<input id="account-display-name" name="display_name" autocomplete="nickname" maxlength="250" required aria-describedby="account-display-name-help" value="<?php echo esc_attr( $profile_values['display_name'] ); ?>">
				<small id="account-display-name-help"><?php esc_html_e( 'Это имя будет показано рядом с вашими комментариями.', 'cooklook' ); ?></small>
			</div>
			<div class="account-field account-field-wide">
				<label for="account-description"><?php esc_html_e( 'О себе', 'cooklook' ); ?></label>
				<textarea id="account-description" name="description" rows="4" maxlength="2000"><?php echo esc_textarea( $profile_values['description'] ); ?></textarea>
				<small><?php esc_html_e( 'До 2000 символов.', 'cooklook' ); ?></small>
			</div>
		</div>
		<button class="btn_bg" type="submit"><?php esc_html_e( 'Сохранить профиль', 'cooklook' ); ?></button>
	</form>
</section>
<section class="account-security" aria-labelledby="account-security-heading">
	<div class="account-section-heading">
		<h2 id="account-security-heading"><?php esc_html_e( 'Вход и безопасность', 'cooklook' ); ?></h2>
		<p><?php esc_html_e( 'Для смены почты или пароля понадобится текущий пароль.', 'cooklook' ); ?></p>
	</div>
	<?php if ( is_array( $pending_email ) && ! empty( $pending_email['newemail'] ) ) : ?>
		<form method="post" action="<?php echo esc_url( $profile_action_url ); ?>" class="account-notice account-confirm-email">
			<?php wp_nonce_field( 'cooklook_account', 'account_nonce', false ); ?>
			<input type="hidden" name="account_action" value="cancel_email">
			<p><?php esc_html_e( 'Ожидает подтверждения:', 'cooklook' ); ?> <strong><?php echo esc_html( $pending_email['newemail'] ); ?></strong></p>
			<button class="account-text-link" type="submit"><?php esc_html_e( 'Отменить смену почты', 'cooklook' ); ?></button>
		</form>
	<?php endif; ?>
	<div class="account-security-grid">
		<details class="account-security-card"<?php echo 'email' === $failed_action ? ' open' : ''; ?>>
			<summary><?php esc_html_e( 'Изменить электронную почту', 'cooklook' ); ?></summary>
			<form method="post" action="<?php echo esc_url( $profile_action_url ); ?>" class="account-form">
				<?php wp_nonce_field( 'cooklook_account', 'account_nonce', false ); ?>
				<input type="hidden" name="account_action" value="email">
				<p class="account-current-email"><?php esc_html_e( 'Текущий адрес:', 'cooklook' ); ?> <strong><?php echo esc_html( $profile_user->user_email ); ?></strong></p>
				<div class="account-field">
					<label for="account-email"><?php esc_html_e( 'Новый адрес электронной почты', 'cooklook' ); ?></label>
					<input type="email" id="account-email" name="email" autocomplete="email" maxlength="100" required>
				</div>
				<div class="account-field">
					<label for="account-email-password"><?php esc_html_e( 'Текущий пароль', 'cooklook' ); ?></label>
					<input type="password" id="account-email-password" name="current_password" autocomplete="current-password" required>
				</div>
				<button class="btn_nonbg" type="submit"><?php esc_html_e( 'Отправить подтверждение', 'cooklook' ); ?></button>
			</form>
		</details>
		<details class="account-security-card"<?php echo 'password' === $failed_action ? ' open' : ''; ?>>
			<summary><?php esc_html_e( 'Изменить пароль', 'cooklook' ); ?></summary>
			<form method="post" action="<?php echo esc_url( $profile_action_url ); ?>" class="account-form">
				<?php wp_nonce_field( 'cooklook_account', 'account_nonce', false ); ?>
				<input type="hidden" name="account_action" value="password">
				<div class="account-field">
					<label for="account-current-password"><?php esc_html_e( 'Текущий пароль', 'cooklook' ); ?></label>
					<input type="password" id="account-current-password" name="current_password" autocomplete="current-password" required>
				</div>
				<div class="account-field">
					<label for="account-new-password"><?php esc_html_e( 'Новый пароль', 'cooklook' ); ?></label>
					<input type="password" id="account-new-password" name="new_password" autocomplete="new-password" minlength="12" required aria-describedby="account-password-help">
					<small id="account-password-help"><?php esc_html_e( 'От 12 символов; используйте уникальную парольную фразу.', 'cooklook' ); ?></small>
				</div>
				<div class="account-field">
					<label for="account-confirm-password"><?php esc_html_e( 'Повторите новый пароль', 'cooklook' ); ?></label>
					<input type="password" id="account-confirm-password" name="confirm_password" autocomplete="new-password" minlength="12" required>
				</div>
				<button class="btn_nonbg" type="submit"><?php esc_html_e( 'Сохранить пароль', 'cooklook' ); ?></button>
			</form>
		</details>
	</div>
</section>
