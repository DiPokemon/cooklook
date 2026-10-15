<?php
/** Front-end profile and access policy, using WordPress users and user metadata. */
defined( 'ABSPATH' ) || exit;

function cooklook_account_url() {
	return home_url( '/account/' );
}

function cooklook_can_access_admin( $user = null ) {
	return user_can( $user ?: wp_get_current_user(), 'manage_options' );
}

/** Restrict screens, while preserving the endpoints used by front-end forms/plugins. */
function cooklook_restrict_admin() {
	global $pagenow;
	if ( ! is_admin() || ! is_user_logged_in() || cooklook_can_access_admin() || wp_doing_ajax() || 'admin-post.php' === $pagenow ) {
		return;
	}
	$url = cooklook_account_url();
	if ( 'profile.php' === $pagenow ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Carry the link only; confirmation requires a separate nonce-protected POST.
		$token = cooklook_request_text( $_GET, 'newuseremail' );
		if ( $token ) {
			$url = add_query_arg( 'newuseremail', $token, $url );
		}
	}
	wp_safe_redirect( $url );
	exit;
}
// Run before WordPress builds the admin menu and may render its own access-denied page.
add_action( 'init', 'cooklook_restrict_admin', 20 );

function cooklook_account_admin_bar( $show ) {
	return $show && cooklook_can_access_admin();
}
add_filter( 'show_admin_bar', 'cooklook_account_admin_bar' );

function cooklook_account_profile_url( $url, $user_id ) {
	return is_user_logged_in() && get_current_user_id() === (int) $user_id && ! cooklook_can_access_admin()
		? cooklook_account_url() . '#account-profile' : $url;
}
add_filter( 'edit_profile_url', 'cooklook_account_profile_url', 10, 2 );

function cooklook_account_login_redirect( $redirect, $requested, $user ) {
	if ( ! $user instanceof WP_User || ! $user->ID || cooklook_can_access_admin( $user ) ) {
		return $redirect;
	}
	$path = wp_parse_url( $redirect, PHP_URL_PATH ) ?: '';
	$admin_path = untrailingslashit( wp_parse_url( admin_url(), PHP_URL_PATH ) );
	return ! $requested || $path === $admin_path || 0 === strpos( $path, $admin_path . '/' )
		? cooklook_account_url() : $redirect;
}
add_filter( 'login_redirect', 'cooklook_account_login_redirect', 10, 3 );

/** Passwords are unslashed only: never trim or sanitize secret characters. */
function cooklook_account_secret( $input, $key ) {
	return isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? wp_unslash( $input[ $key ] ) : '';
}

/** The only editable public profile fields. Missing/array values cannot erase data. */
function cooklook_account_profile_data( $input ) {
	$data = array();
	foreach ( array( 'first_name' => 100, 'last_name' => 100, 'display_name' => 250, 'description' => 2000 ) as $key => $limit ) {
		if ( ! isset( $input[ $key ] ) || ! is_string( $input[ $key ] ) ) {
			return new WP_Error( 'invalid_profile', __( 'Проверьте заполнение полей профиля.', 'cooklook' ) );
		}
		$value = wp_unslash( $input[ $key ] );
		$value = 'description' === $key ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
		if ( mb_strlen( $value, 'UTF-8' ) > $limit || ( 'display_name' === $key && '' === $value ) ) {
			return new WP_Error( 'invalid_profile', __( 'Укажите отображаемое имя и соблюдайте ограничения длины полей.', 'cooklook' ) );
		}
		$data[ $key ] = $value;
	}
	return $data;
}

/** Authorize every operation against the session, never a posted user ID or role. */
function cooklook_update_account( $input ) {
	$user = wp_get_current_user();
	if ( ! $user->ID ) {
		return new WP_Error( 'not_logged_in', __( 'Войдите в аккаунт, чтобы изменить профиль.', 'cooklook' ) );
	}
	if ( ! wp_verify_nonce( cooklook_request_text( $input, 'account_nonce' ), 'cooklook_account' ) ) {
		return new WP_Error( 'invalid_nonce', __( 'Сессия формы истекла. Обновите страницу и повторите попытку.', 'cooklook' ) );
	}
	$action = cooklook_request_text( $input, 'account_action' );
	if ( 'profile' === $action ) {
		$data = cooklook_account_profile_data( $input );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		$data['ID'] = $user->ID;
		$result = wp_update_user( wp_slash( $data ) );
		return is_wp_error( $result ) ? $result : 'profile_saved';
	}
	if ( 'cancel_email' === $action ) {
		delete_user_meta( $user->ID, '_new_email' );
		return 'email_cancelled';
	}
	if ( 'confirm_email' === $action ) {
		$pending = get_user_meta( $user->ID, '_new_email', true );
		$token = cooklook_request_text( $input, 'email_token' );
		if ( ! is_array( $pending ) || ! isset( $pending['hash'], $pending['newemail'] ) || ! is_string( $pending['hash'] ) || ! is_string( $pending['newemail'] ) || '' === $pending['hash'] || ! hash_equals( $pending['hash'], $token ) || ( isset( $pending['expires'] ) && time() > $pending['expires'] ) ) {
			return new WP_Error( 'invalid_email_token', __( 'Ссылка подтверждения недействительна или устарела. Запросите смену почты заново.', 'cooklook' ) );
		}
		if ( ! is_email( $pending['newemail'] ) || email_exists( $pending['newemail'] ) ) {
			return new WP_Error( 'invalid_email', __( 'Этот адрес электронной почты недоступен.', 'cooklook' ) );
		}
		$result = wp_update_user( array( 'ID' => $user->ID, 'user_email' => wp_slash( $pending['newemail'] ) ) );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		delete_user_meta( $user->ID, '_new_email' );
		return 'email_saved';
	}
	if ( ! in_array( $action, array( 'email', 'password' ), true ) ) {
		return new WP_Error( 'invalid_action', __( 'Неизвестное действие.', 'cooklook' ) );
	}
	$current_password = cooklook_account_secret( $input, 'current_password' );
	if ( '' === $current_password || strlen( $current_password ) > 4096 || ! wp_check_password( $current_password, $user->user_pass, $user->ID ) ) {
		return new WP_Error( 'incorrect_password', __( 'Текущий пароль указан неверно.', 'cooklook' ) );
	}
	if ( 'password' === $action ) {
		$password = cooklook_account_secret( $input, 'new_password' );
		$confirmation = cooklook_account_secret( $input, 'confirm_password' );
		if ( mb_strlen( $password, 'UTF-8' ) < 12 || strlen( $password ) > 4096 || trim( $password ) !== $password ) {
			return new WP_Error( 'invalid_password', __( 'Используйте пароль от 12 символов без пробелов в начале и конце.', 'cooklook' ) );
		}
		if ( ! hash_equals( $password, $confirmation ) ) {
			return new WP_Error( 'password_mismatch', __( 'Новый пароль и его повтор не совпадают.', 'cooklook' ) );
		}
		// WordPress hashes the raw password, refreshes this session and sends its notification.
		$result = wp_update_user( array( 'ID' => $user->ID, 'user_pass' => $password ) );
		return is_wp_error( $result ) ? $result : 'password_saved';
	}
	$email = trim( cooklook_account_secret( $input, 'email' ) );
	if ( ! is_email( $email ) || strlen( $email ) > 100 ) {
		return new WP_Error( 'invalid_email', __( 'Укажите корректный адрес электронной почты.', 'cooklook' ) );
	}
	if ( 0 === strcasecmp( $email, $user->user_email ) ) {
		return new WP_Error( 'unchanged_email', __( 'Этот адрес уже используется в вашем аккаунте.', 'cooklook' ) );
	}
	if ( email_exists( $email ) ) {
		return new WP_Error( 'unavailable_email', __( 'Этот адрес электронной почты недоступен.', 'cooklook' ) );
	}
	$previous = get_user_meta( $user->ID, '_new_email', true );
	if ( is_array( $previous ) && isset( $previous['requested_at'] ) && time() - $previous['requested_at'] < MINUTE_IN_SECONDS ) {
		return new WP_Error( 'email_rate_limit', __( 'Подождите минуту перед повторной отправкой письма.', 'cooklook' ) );
	}
	$pending = array( 'hash' => wp_generate_password( 32, false ), 'newemail' => $email, 'requested_at' => time(), 'expires' => time() + DAY_IN_SECONDS );
	if ( ! update_user_meta( $user->ID, '_new_email', $pending ) ) {
		return new WP_Error( 'email_storage_failed', __( 'Не удалось сохранить запрос. Повторите попытку.', 'cooklook' ) );
	}
	$url = add_query_arg( 'newuseremail', $pending['hash'], cooklook_account_url() );
	$message = sprintf(
		/* translators: %s: Account email confirmation URL. */
		__( "Вы запросили смену электронной почты в Cook Look.\n\nВойдите в свой аккаунт и подтвердите новый адрес по ссылке:\n%s\n\nСсылка действует 24 часа. Если вы не отправляли запрос, проигнорируйте это письмо.", 'cooklook' ),
		$url
	);
	if ( ! wp_mail( $email, __( 'Cook Look — подтверждение электронной почты', 'cooklook' ), $message ) ) {
		if ( $previous ) {
			update_user_meta( $user->ID, '_new_email', $previous );
		} else {
			delete_user_meta( $user->ID, '_new_email' );
		}
		return new WP_Error( 'email_send_failed', __( 'Не удалось отправить письмо. Адрес не изменён; попробуйте позже.', 'cooklook' ) );
	}
	return 'email_pending';
}

/** Process before headers; retain safe field values on failure, redirect after success. */
function cooklook_account_request() {
	if ( ! is_page( 'account' ) && ! is_page_template( 'page-account.php' ) ) {
		return;
	}
	nocache_headers();
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Routing only; the handler verifies the nonce.
	if ( ! isset( $_SERVER['REQUEST_METHOD'], $_POST['account_action'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified inside the handler before any mutation.
	$result = cooklook_update_account( $_POST );
	if ( is_wp_error( $result ) ) {
		$GLOBALS['cooklook_account_error'] = $result;
		return;
	}
	wp_safe_redirect( add_query_arg( 'account_notice', $result, cooklook_account_url() ) . '#account-profile', 303 );
	exit;
}
add_action( 'template_redirect', 'cooklook_account_request' );

function cooklook_account_notice( $code ) {
	$messages = array(
		'profile_saved' => __( 'Изменения профиля сохранены.', 'cooklook' ),
		'email_pending' => __( 'Письмо отправлено на новый адрес. Подтвердите его в течение 24 часов; до этого действует прежняя почта.', 'cooklook' ),
		'email_saved' => __( 'Новый адрес электронной почты подтверждён и сохранён.', 'cooklook' ),
		'email_cancelled' => __( 'Запрос на смену электронной почты отменён.', 'cooklook' ),
		'password_saved' => __( 'Пароль изменён.', 'cooklook' ),
	);
	return isset( $messages[ $code ] ) ? $messages[ $code ] : '';
}
