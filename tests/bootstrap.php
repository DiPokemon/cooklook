<?php
/** In-memory WordPress boundary. Never loads wp-config.php or connects to a database. */
if ( PHP_SAPI !== 'cli' ) { exit; }
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'MB_IN_BYTES', 1048576 );
class WP_Error {
	public $code; public $message;
	function __construct( $code, $message ) { $this->code = $code; $this->message = $message; }
	function get_error_code() { return $this->code; }
	function get_error_message() { return $this->message; }
}
class Test_Response extends Exception {
	public $success; public $data; public $status;
	function __construct( $success, $data, $status ) { parent::__construct( 'JSON response' ); $this->success = $success; $this->data = $data; $this->status = $status; }
}
function wp_send_json_error( $data, $status = 200 ) { throw new Test_Response( false, $data, $status ); }
function wp_send_json_success( $data, $status = 200 ) { throw new Test_Response( true, $data, $status ); }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function add_action() {}
$GLOBALS['test_filters'] = array();
function add_filter( $tag, $callback, $priority = 10 ) { $GLOBALS['test_filters'][ $tag ][ $priority ] = $callback; }
function remove_filter( $tag, $callback, $priority = 10 ) { unset( $GLOBALS['test_filters'][ $tag ][ $priority ] ); }
function apply_filters( $tag, $value ) { return $value; }
function __( $text ) { return $text; }
function esc_html__( $text ) { return esc_html( $text ); }
function home_url( $path = '' ) { return 'https://example.test/subsite' . $path; }
function _x( $text ) { return $text; }
function absint( $value ) { return abs( (int) $value ); }
function wp_unslash( $value ) { return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( $value ); }
function sanitize_text_field( $value ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( $value ) ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( $value ) ); }
function sanitize_title( $value ) { return strtolower( str_replace( ' ', '-', trim( strip_tags( $value ) ) ) ); }
function strip_shortcodes( $value ) { return preg_replace( '/\[[^\]]+\]/', '', $value ); }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function wp_trim_words( $value, $limit, $more ) { $words = preg_split( '/\s+/', trim( $value ) ); return implode( ' ', array_slice( $words, 0, $limit ) ) . ( count( $words ) > $limit ? $more : '' ); }
function wp_check_invalid_utf8( $text, $strip = false ) { return preg_match( '//u', $text ) ? $text : ''; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return esc_html( $text ); }
function esc_url( $url ) { return esc_html( esc_url_raw( $url ) ); }
function esc_url_raw( $url ) { return preg_match( '#^https?://#', $url ) ? $url : ''; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function get_post( $id ) { return isset( $GLOBALS['test_posts'][ $id ] ) ? $GLOBALS['test_posts'][ $id ] : null; }
function get_post_type( $id = 0 ) { $post = get_post( $id ); return $post ? $post->post_type : false; }
function get_post_status( $id = 0 ) { $post = get_post( $id ); return $post ? $post->post_status : false; }
function post_password_required( $post = 0 ) { $post = is_object( $post ) ? $post : get_post( $post ); return $post && $post->post_password !== ''; }
function get_post_meta( $id, $key, $single = true ) { return isset( $GLOBALS['test_meta'][ $id ][ $key ] ) ? $GLOBALS['test_meta'][ $id ][ $key ] : ''; }
function update_post_meta( $id, $key, $value ) {
	if ( isset( $GLOBALS['test_fail_meta'] ) && $GLOBALS['test_fail_meta'] === $key ) { unset( $GLOBALS['test_fail_meta'] ); return false; }
	$GLOBALS['test_meta'][ $id ][ $key ] = $value; return true;
}
function carbon_get_post_meta( $id, $key ) { return get_post_meta( $id, '_' . $key ); }
function carbon_set_post_meta( $id, $key, $value ) { update_post_meta( $id, '_' . $key, $value ); }
function get_user_meta( $id, $key, $single ) { return isset( $GLOBALS['test_users'][ $id ][ $key ] ) ? $GLOBALS['test_users'][ $id ][ $key ] : ''; }
function update_user_meta( $id, $key, $value ) { $GLOBALS['test_users'][ $id ][ $key ] = $value; return true; }
function get_current_user_id() { return $GLOBALS['test_user_id']; }
function is_user_logged_in() { return get_current_user_id() > 0; }
function current_user_can( $capability, $id = 0 ) { return ! empty( $GLOBALS['test_caps'][ $capability ] ); }
function check_ajax_referer( $action, $key, $die ) { return isset( $_REQUEST[ $key ] ) && $_REQUEST[ $key ] === 'test-valid'; }
function get_post_type_object() { return (object) array( 'cap' => (object) array( 'publish_posts' => 'publish_posts' ) ); }
function wp_update_post( $data, $error = false ) { $GLOBALS['test_posts'][ $data['ID'] ]->post_status = $data['post_status']; return $data['ID']; }
function wp_cache_delete() {}
function wp_doing_ajax() { return ! empty( $GLOBALS['test_ajax'] ); }
function wp_handle_comment_submission() { return $GLOBALS['test_comment']; }
function wp_get_current_user() { return $GLOBALS['test_accounts'][ get_current_user_id() ] ?? (object) array( 'ID' => get_current_user_id() ); }
function get_comment_link( $comment ) { return 'https://example.test/recipe/42/#comment-' . $comment->comment_ID; }
function do_action( $name, ...$args ) { $GLOBALS['test_actions'][ $name ] = $args; }
function get_posts( $args ) {
	$ids = array();
	foreach ( $GLOBALS['test_posts'] as $id => $post ) {
		if ( $post->post_type !== $args['post_type'] ) continue;
		if ( isset( $args['meta_key'] ) && get_post_meta( $id, $args['meta_key'] ) !== $args['meta_value'] ) continue;
		if ( isset( $args['post_name__in'] ) && ! in_array( $post->post_name, $args['post_name__in'], true ) ) continue;
		if ( isset( $args['meta_query'] ) && ! in_array( get_post_meta( $id, $args['meta_query'][0]['key'] ), $args['meta_query'][0]['value'], true ) ) continue;
		$ids[] = $id;
	}
	return isset( $args['posts_per_page'] ) && $args['posts_per_page'] > 0 ? array_slice( $ids, 0, $args['posts_per_page'] ) : $ids;
}
function get_the_terms( $id, $taxonomy ) { return isset( $GLOBALS['test_terms'][ $id ][ $taxonomy ] ) ? $GLOBALS['test_terms'][ $id ][ $taxonomy ] : false; }
function wp_list_pluck( $list, $key ) { return array_map( function ( $row ) use ( $key ) { return is_object( $row ) ? $row->$key : $row[ $key ]; }, $list ); }
function get_the_title( $id ) { return ( is_object( $id ) ? $id : get_post( $id ) )->post_title; }
function get_permalink( $id = null ) { return 'https://example.test/subsite/recipe/' . ( $id ?: 42 ) . '/'; }
function get_the_author_meta() { return 'Author'; }
function get_the_date() { return '2026-10-15T12:00:00+03:00'; }
function get_the_modified_date() { return get_the_date(); }
function get_the_post_thumbnail_url() { return false; }
function get_post_thumbnail_id( $id ) { return get_post_meta( $id, '_thumbnail_id' ); }
function wp_attachment_is_image( $id ) { return isset( $GLOBALS['test_images'][ $id ] ); }
function wp_get_attachment_image_src( $id, $size ) { return $GLOBALS['test_images'][ $id ][ $size ] ?? false; }
function wp_get_attachment_metadata( $id ) { return $GLOBALS['test_image_metadata'][ $id ] ?? array(); }
function get_template_part( $part ) { $GLOBALS['test_rendered'][] = $part; }
function wp_reset_postdata() { $GLOBALS['test_reset'] = true; }
class WP_Query {
	public $query_vars; public $max_num_pages = 3; public $posts = array(); private $index = 0;
	function __construct( $args = array() ) { $this->query_vars = $args; }
	function get( $key ) { return isset( $this->query_vars[ $key ] ) ? $this->query_vars[ $key ] : ''; }
	function have_posts() { return $this->index < count( $this->posts ); }
	function the_post() { $this->index++; }
}
class Test_DB {
	public $prefix = 'wp_'; public $postmeta = 'wp_postmeta'; public $posts = 'wp_posts'; public $locks = array(); public $busy = false; public $favorites = array(); public $reads = 0;
	function prepare( $sql, ...$args ) { foreach ( $args as $arg ) { $sql = preg_replace( '/%[sd]/', is_int( $arg ) ? (string) $arg : "'" . addslashes( $arg ) . "'", $sql, 1 ); } return $sql; }
	function get_var( $sql ) {
		if ( preg_match( "/SELECT GET_LOCK\\('([^']+)'/", $sql, $matches ) ) { if ( $this->busy ) return '0'; $this->locks[ $matches[1] ] = true; return '1'; }
		if ( preg_match( "/SELECT RELEASE_LOCK\\('([^']+)'/", $sql, $matches ) ) { unset( $this->locks[ $matches[1] ] ); return '1'; }
		throw new RuntimeException( 'Unexpected SQL: ' . $sql );
	}
	function get_col( $sql ) {
		$this->reads++;
		preg_match( '/user_id = (\d+)/', $sql, $matches );
		$ids = array();
		foreach ( $this->favorites as $row ) if ( $row['user_id'] == $matches[1] ) $ids[] = $row['recipe_id'];
		return array_unique( $ids );
	}
	function insert( $table, $data, $formats ) { $this->favorites[] = $data; return 1; }
	function delete( $table, $data, $formats ) { $this->favorites = array_values( array_filter( $this->favorites, function ( $row ) use ( $data ) { return $row != $data; } ) ); return 1; }
}
$GLOBALS['wpdb'] = new Test_DB();
function reset_fixture() {
	$GLOBALS['test_user_id'] = 7;
	$GLOBALS['test_caps'] = array( 'manage_options' => true, 'edit_post' => true, 'publish_posts' => true, 'upload_files' => true );
	$GLOBALS['test_posts'] = array( 42 => (object) array( 'ID' => 42, 'post_type' => 'recipe', 'post_status' => 'publish', 'post_password' => '', 'post_title' => 'Суп "с сыром" </script>', 'post_excerpt' => '', 'post_content' => '', 'post_author' => 7 ) );
	$GLOBALS['test_meta'] = array( 42 => array( '_recipe_id' => 'external-42', '_recipe_likes' => 10, '_recipe_dislikes' => 2, '_thumbnail_id' => 100 ) );
	$GLOBALS['test_images'] = array( 100 => array( 'full' => array( 'https://example.test/dish.jpg', 1500, 1000 ) ) );
	$GLOBALS['test_image_metadata'] = array();
	$GLOBALS['test_conditionals'] = array();
	$GLOBALS['test_ajax'] = false;
	$GLOBALS['test_query_vars'] = array();
	$GLOBALS['test_users'] = array(); $GLOBALS['test_terms'] = array();
	$_GET = array(); $_POST = array(); $_REQUEST = array( 'nonce' => 'test-valid', 'recipe_import_nonce' => 'test-valid' );
	$GLOBALS['wpdb']->busy = false;
}
function response_from( $callback ) {
	try { $callback(); } catch ( Test_Response $response ) { return $response; }
	throw new RuntimeException( 'No JSON response' );
}
function expect( $condition, $message ) { if ( ! $condition ) throw new RuntimeException( $message ); }
function test_case( $name, $callback ) {
	reset_fixture();
	try { $callback(); echo "PASS: " . $name . "\n"; $GLOBALS['test_passed']++; }
	catch ( Throwable $error ) { fwrite( STDERR, "FAIL: " . $name . " — " . $error->getMessage() . "\n" ); $GLOBALS['test_failed']++; }
}
set_error_handler( function ( $severity, $message, $file, $line ) { if ( error_reporting() & $severity ) throw new ErrorException( $message, 0, $severity, $file, $line ); return false; } );
$GLOBALS['test_passed'] = 0; $GLOBALS['test_failed'] = 0;
