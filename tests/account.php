<?php
/** Account security regression tests. In-memory only: no mail, credentials or database. */
require __DIR__ . '/bootstrap.php';
define( 'DAY_IN_SECONDS', 86400 );
class WP_User {
	public $ID; public $caps; public $user_pass; public $user_email; public $display_name;
	public $first_name = ''; public $last_name = ''; public $description = '';
	function __construct( $id, $caps = array() ) { $this->ID = $id; $this->caps = $caps; $this->user_pass = 'fixture-password'; $this->user_email = $id . '@example.test'; $this->display_name = 'User ' . $id; }
}
class Account_Redirect extends Exception {
	public $url;
	function __construct( $url ) { $this->url = $url; }
}
function user_can( $user, $cap ) { return ! empty( $user->caps[ $cap ] ); }
function admin_url() { return home_url( '/wp-admin/' ); }
function untrailingslashit( $value ) { return rtrim( $value, '/\\' ); }
function is_admin() { return $GLOBALS['account_is_admin']; }
function wp_safe_redirect( $url, $status = 302 ) { throw new Account_Redirect( $url ); }
function wp_verify_nonce( $nonce, $action ) { return 'account-valid' === $nonce && 'cooklook_account' === $action; }
function wp_slash( $value ) { return is_array( $value ) ? array_map( 'wp_slash', $value ) : ( is_string( $value ) ? addslashes( $value ) : $value ); }
function wp_check_password( $password, $hash, $id ) { return hash_equals( $hash, $password ); }
function wp_update_user( $data ) {
	if ( ! empty( $GLOBALS['account_write_failure'] ) ) return new WP_Error( 'write_failed', 'Write failed' );
	$GLOBALS['account_writes'][] = $data;
	foreach ( $data as $key => $value ) {
		if ( 'ID' !== $key ) $GLOBALS['test_accounts'][ $data['ID'] ]->$key = 'user_pass' === $key ? $value : wp_unslash( $value );
	}
	return $data['ID'];
}
function delete_user_meta( $id, $key ) { unset( $GLOBALS['test_users'][ $id ][ $key ] ); return true; }
function is_email( $email ) { return filter_var( $email, FILTER_VALIDATE_EMAIL ); }
function email_exists( $email ) { foreach ( $GLOBALS['test_accounts'] as $user ) if ( 0 === strcasecmp( $email, $user->user_email ) ) return $user->ID; return false; }
function wp_generate_password( $length, $special ) { return str_repeat( 'x', $length ); }
function add_query_arg( $key, $value, $url ) { return $url . '?' . rawurlencode( $key ) . '=' . rawurlencode( $value ); }
function wp_mail( $to, $subject, $message ) { $GLOBALS['account_mails'][] = compact( 'to', 'subject', 'message' ); return $GLOBALS['account_mail_success']; }
require dirname( __DIR__ ) . '/inc/recipe-helpers.php';
require dirname( __DIR__ ) . '/inc/account.php';
function account_input( $action, $data = array() ) { return array_merge( array( 'account_action' => $action, 'account_nonce' => 'account-valid' ), wp_slash( $data ) ); }
function account_case( $name, $callback ) {
	test_case( $name, function () use ( $callback ) {
		$GLOBALS['test_accounts'] = array( 7 => new WP_User( 7, array( 'edit_posts' => true ) ), 8 => new WP_User( 8, array( 'manage_options' => true ) ) );
		$GLOBALS['test_ajax'] = false; $GLOBALS['account_is_admin'] = true; $GLOBALS['pagenow'] = 'profile.php';
		$GLOBALS['account_writes'] = array(); $GLOBALS['account_mails'] = array();
		$GLOBALS['account_mail_success'] = true; $GLOBALS['account_write_failure'] = false;
		$callback();
	} );
}
account_case( 'Ordinary users leave admin screens; admins and service endpoints continue', function () {
	try { cooklook_restrict_admin(); throw new RuntimeException( 'Admin screen accessible' ); } catch ( Account_Redirect $r ) { expect( $r->url === home_url( '/account/' ), 'Wrong redirect' ); }
	$GLOBALS['test_ajax'] = true; cooklook_restrict_admin();
	$GLOBALS['test_ajax'] = false; $GLOBALS['account_is_admin'] = false; cooklook_restrict_admin(); $GLOBALS['account_is_admin'] = true;
	$GLOBALS['test_ajax'] = false; $GLOBALS['pagenow'] = 'admin-post.php'; cooklook_restrict_admin();
	$GLOBALS['pagenow'] = 'index.php'; $GLOBALS['test_user_id'] = 8; cooklook_restrict_admin();
	expect( cooklook_account_admin_bar( true ) && ! cooklook_account_admin_bar( false ), 'Admin toolbar preference lost' );
	$GLOBALS['test_user_id'] = 7;
	expect( ! cooklook_account_admin_bar( true ), 'Toolbar exposed to member' );
	expect( cooklook_account_profile_url( 'native', 7 ) === home_url( '/account/' ) . '#account-profile', 'Profile link leaves account' );
	expect( cooklook_account_profile_url( 'native', 8 ) === 'native', 'Another profile URL rewritten' );
	expect( cooklook_account_login_redirect( admin_url(), '', wp_get_current_user() ) === home_url( '/account/' ), 'Default login enters admin' );
	expect( cooklook_account_login_redirect( admin_url() . 'edit.php', 'explicit', wp_get_current_user() ) === home_url( '/account/' ), 'Explicit admin login enters admin' );
	$return = home_url( '/account/?newuseremail=token' );
	expect( cooklook_account_login_redirect( $return, $return, wp_get_current_user() ) === $return, 'Confirmation destination lost' );
	expect( cooklook_account_login_redirect( admin_url(), '', $GLOBALS['test_accounts'][8] ) === admin_url(), 'Admin login changed' );
} );
account_case( 'Profile writes are bound to the session and cannot grant roles or edit another account', function () {
	$other = clone $GLOBALS['test_accounts'][8];
	$data = array( 'ID' => 8, 'user_id' => 8, 'role' => 'administrator', 'user_email' => 'attack@example.test', 'meta_input' => array( 'wp_capabilities' => array( 'administrator' => true ) ), 'first_name' => '<b>Анна</b>', 'last_name' => "O'Reilly", 'display_name' => 'Анна \\ повар', 'description' => "Готовлю\nкаждый день" );
	expect( cooklook_update_account( account_input( 'profile', $data ) ) === 'profile_saved', 'Profile save failed' );
	expect( $GLOBALS['test_accounts'][8] == $other, 'Other account changed' );
	expect( array_keys( $GLOBALS['account_writes'][0] ) === array( 'first_name', 'last_name', 'display_name', 'description', 'ID' ), 'Non-whitelisted field saved' );
	expect( wp_get_current_user()->first_name === 'Анна' && wp_get_current_user()->last_name === "O'Reilly" && wp_get_current_user()->display_name === 'Анна \\ повар', 'Sanitization or slashes corrupted profile' );
} );
account_case( 'Guest, CSRF, malformed and oversized profile submissions never write', function () {
	$valid = account_input( 'profile', array( 'first_name' => '', 'last_name' => '', 'display_name' => 'Cook', 'description' => '' ) );
	$GLOBALS['test_user_id'] = 0; expect( is_wp_error( cooklook_update_account( $valid ) ), 'Guest accepted' ); $GLOBALS['test_user_id'] = 7;
	foreach ( array( array( 'account_nonce' => 'wrong' ), array( 'account_nonce' => array( 'account-valid' ) ), array( 'display_name' => array( 'Cook' ) ), array( 'display_name' => '' ), array( 'description' => str_repeat( 'я', 2001 ) ) ) as $invalid ) {
		expect( is_wp_error( cooklook_update_account( array_merge( $valid, $invalid ) ) ), 'Invalid submission accepted' );
	}
	unset( $valid['first_name'] ); expect( is_wp_error( cooklook_update_account( $valid ) ), 'Missing field cleared profile' );
	expect( ! $GLOBALS['account_writes'], 'Validation caused a partial write' );
} );
account_case( 'Sensitive edits require the current password and retain secret characters', function () {
	foreach ( array( 'email', 'password' ) as $action ) expect( is_wp_error( cooklook_update_account( account_input( $action, array( 'current_password' => 'wrong', 'email' => 'new@example.test', 'new_password' => 'long-enough-password', 'confirm_password' => 'long-enough-password' ) ) ) ), 'Wrong current password accepted' );
	$input = account_input( 'password', array( 'current_password' => 'fixture-password', 'new_password' => 'short', 'confirm_password' => 'short' ) );
	expect( is_wp_error( cooklook_update_account( $input ) ), 'Short password accepted' );
	$input['new_password'] = 'long-enough-password'; expect( is_wp_error( cooklook_update_account( $input ) ), 'Mismatched confirmation accepted' );
	expect( ! $GLOBALS['account_writes'] && ! $GLOBALS['account_mails'], 'Invalid edit wrote data or sent mail' );
	$secret = "a-long \\ ' \"<secret>";
	$result = cooklook_update_account( account_input( 'password', array( 'current_password' => 'fixture-password', 'new_password' => $secret, 'confirm_password' => $secret, 'user_id' => 8 ) ) );
	expect( 'password_saved' === $result && wp_get_current_user()->user_pass === $secret && $GLOBALS['test_accounts'][8]->user_pass === 'fixture-password', 'Password corrupted or other user changed' );
} );
account_case( 'New email remains pending until a session-bound, unexpired confirmation', function () {
	$input = account_input( 'email', array( 'current_password' => 'fixture-password', 'email' => 'new@example.test' ) );
	expect( 'email_pending' === cooklook_update_account( $input ), 'Email request failed' );
	$pending = get_user_meta( 7, '_new_email', true );
	expect( wp_get_current_user()->user_email === '7@example.test' && ! $GLOBALS['account_writes'], 'Unverified email applied' );
	expect( is_wp_error( cooklook_update_account( $input ) ) && count( $GLOBALS['account_mails'] ) === 1, 'Repeated mail not throttled' );
	$confirm = account_input( 'confirm_email', array( 'email_token' => $pending['hash'], 'user_id' => 7 ) );
	$GLOBALS['test_user_id'] = 8; expect( is_wp_error( cooklook_update_account( $confirm ) ), 'Another account used token' ); $GLOBALS['test_user_id'] = 7;
	$GLOBALS['test_users'][7]['_new_email']['expires'] = time() - 1;
	expect( is_wp_error( cooklook_update_account( $confirm ) ), 'Expired token accepted' );
	$GLOBALS['test_users'][7]['_new_email'] = $pending;
	expect( 'email_saved' === cooklook_update_account( $confirm ) && wp_get_current_user()->user_email === 'new@example.test', 'Confirmation failed' );
	expect( is_wp_error( cooklook_update_account( $confirm ) ) && ! get_user_meta( 7, '_new_email', true ), 'Token replay accepted' );
} );
account_case( 'Email errors and cancellation preserve other users and the old address', function () {
	$base = array( 'current_password' => 'fixture-password' );
	foreach ( array( 'invalid', '8@example.test', array( 'bad' ) ) as $email ) expect( is_wp_error( cooklook_update_account( account_input( 'email', $base + array( 'email' => $email ) ) ) ), 'Invalid or occupied email accepted' );
	$GLOBALS['account_mail_success'] = false;
	expect( is_wp_error( cooklook_update_account( account_input( 'email', $base + array( 'email' => 'new@example.test' ) ) ) ), 'Mail failure reported as success' );
	expect( ! get_user_meta( 7, '_new_email', true ) && wp_get_current_user()->user_email === '7@example.test', 'Failed mail left a pending change' );
	$old = array( 'hash' => 'legacy-token', 'newemail' => 'old@example.test' );
	$GLOBALS['test_users'][7]['_new_email'] = $old; $GLOBALS['test_users'][8]['_new_email'] = $old;
	cooklook_update_account( account_input( 'email', $base + array( 'email' => 'new@example.test' ) ) );
	expect( get_user_meta( 7, '_new_email', true ) === $old, 'Mail failure erased previous request' );
	cooklook_update_account( account_input( 'cancel_email', array( 'user_id' => 8 ) ) );
	expect( ! get_user_meta( 7, '_new_email', true ) && get_user_meta( 8, '_new_email', true ) === $old, 'Cancel crossed accounts' );
} );
account_case( 'Confirmation rechecks address ownership and reports persistence errors', function () {
	$GLOBALS['test_users'][7]['_new_email'] = array( 'hash' => 'legacy', 'newemail' => '8@example.test' );
	$confirm = account_input( 'confirm_email', array( 'email_token' => 'legacy' ) );
	expect( is_wp_error( cooklook_update_account( $confirm ) ), 'Recently occupied address accepted' );
	$GLOBALS['test_users'][7]['_new_email']['newemail'] = 'new@example.test'; $GLOBALS['account_write_failure'] = true;
	expect( is_wp_error( cooklook_update_account( $confirm ) ) && get_user_meta( 7, '_new_email', true ), 'Failed write discarded pending confirmation' );
	$GLOBALS['account_write_failure'] = false;
	expect( 'email_saved' === cooklook_update_account( $confirm ), 'Existing native WordPress confirmation no longer supported' );
} );
echo $GLOBALS['test_passed'] . " passed, " . $GLOBALS['test_failed'] . " failed.\n";
exit( $GLOBALS['test_failed'] ? 1 : 0 );
