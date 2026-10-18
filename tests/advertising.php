<?php
/** Advertising regressions without a database, uploads or requests to Yandex. */
require __DIR__ . '/bootstrap.php';
function get_option( $key, $default = false ) { return 'cooklook_ads' === $key ? $GLOBALS['ads_saved'] : $default; }
function get_template_directory() { return dirname( __DIR__ ); }
function get_template_directory_uri() { return 'https://example.test/subsite/wp-content/themes/cooklook'; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ); }
function wp_getimagesize( $path ) { return getimagesize( $path ); }
function wp_get_attachment_image( $id, $size, $icon, $attributes ) {
	$src = wp_get_attachment_image_src( $id, $size );
	$html = '<img src="' . esc_url( $src[0] ) . '" width="' . (int) $src[1] . '" height="' . (int) $src[2] . '"';
	foreach ( $attributes as $key => $value ) { $html .= ' ' . $key . '="' . esc_attr( $value ) . '"'; }
	return $html . '>';
}
function add_settings_error( $setting, $code, $message, $type = 'error' ) { $GLOBALS['ads_errors'][] = array( $message, $type ); }
function register_setting( $group, $name, $args ) { $GLOBALS['ads_registered'] = array( $group, $name, $args ); }
function add_menu_page( $title, $label, $capability, $slug, $callback, $icon, $position ) { $GLOBALS['ads_menu_cap'] = $capability; }
function is_front_page() { return 'home' === $GLOBALS['ads_page']; }
function is_home() { return false; }
function is_page_template( $template ) { return $template === $GLOBALS['ads_page']; }
function is_post_type_archive( $type ) { return 'catalog' === $GLOBALS['ads_page']; }
function is_tax( $taxonomies = array() ) { return 'taxonomy' === $GLOBALS['ads_page']; }
function is_singular( $type = '' ) { return 'recipe' === $GLOBALS['ads_page']; }
function cooklook_enqueue_script( $handle, $path, $deps ) { $GLOBALS['ads_scripts'][ $handle ] = array( $path, $deps ); }
require dirname( __DIR__ ) . '/inc/media.php';
require dirname( __DIR__ ) . '/inc/advertising.php';
require dirname( __DIR__ ) . '/inc/advertising-admin.php';

function ads_case( $title, $callback ) {
	$GLOBALS['ads_saved'] = array(); $GLOBALS['ads_errors'] = array(); $GLOBALS['ads_scripts'] = array(); $GLOBALS['ads_page'] = '';
	test_case( $title, $callback );
}

ads_case( 'All nine existing placements retain their advertisements and links by default', function () {
	$defaults = cooklook_ads_settings();
	expect( count( $defaults['slots'] ) === 9, 'Missing placement' );
	$expected = array(
		'main_categories' => '1637509?erid=2bL9aMPo2e4BA5qnNGCKd2CXSG',
		'main_square_1' => '2075290?erid=2bL9aMPo2e4BA5qnNJLpeahqfd',
		'main_square_2' => '1637501?erid=2bL9aMPo2e4BA5qnNGCKd2CXS8',
		'main_square_3' => '2075326?erid=2bL9aMPo2e4BA5qnNJLpeaiAcm',
		'recipe_archive_1' => '1637554?erid=2bL9aMPo2e4BA5qnNGCKd2CXpF',
		'recipe_page' => '1637470?erid=2bL9aMPo2e4BA5qnNGCKd2CCV5',
		'recipe_page_square' => '2075248?erid=2bL9aMPo2e4BA5qnNJLpeahqHh',
	);
	foreach ( $expected as $name => $url ) {
		$html = cooklook_ad( $name );
		expect( strpos( $html, 'https://kursy-tut.ru/' . $url ) !== false, 'Original link changed: ' . $name );
		expect( strpos( $html, $name . '.jpg' ) !== false && strpos( $html, 'image/webp' ) !== false, 'Original responsive image lost' );
	}
	expect( strpos( cooklook_ad( 'main_bottom' ), 'R-A-8833792-9' ) !== false, 'Home Yandex ID changed' );
	expect( strpos( cooklook_ad( 'recipe_bottom' ), 'R-A-8833792-7' ) !== false, 'Recipe Yandex ID changed' );
	expect( array() === $GLOBALS['ads_saved'], 'Defaults should not migrate options' );
} );

ads_case( 'Global, type and placement switches remove the entire advertisement', function () {
	$GLOBALS['ads_saved'] = cooklook_ads_defaults();
	$GLOBALS['ads_saved']['enabled'] = false;
	foreach ( array_keys( cooklook_ad_slots() ) as $name ) { expect( '' === cooklook_ad( $name, 'grid-cell' ), 'Disabled ad left markup' ); }
	$GLOBALS['ads_saved']['enabled'] = true;
	$GLOBALS['ads_saved']['banner_enabled'] = false;
	expect( '' === cooklook_ad( 'recipe_archive_1' ) && '' !== cooklook_ad( 'main_bottom' ), 'Banner switch affected Yandex' );
	$GLOBALS['ads_saved']['banner_enabled'] = true;
	$GLOBALS['ads_saved']['yandex_enabled'] = false;
	expect( '' !== cooklook_ad( 'recipe_archive_1' ) && '' === cooklook_ad( 'main_bottom' ), 'Yandex switch affected banners' );
	$GLOBALS['ads_saved']['slots']['recipe_archive_1']['type'] = 'off';
	expect( '' === cooklook_ad( 'recipe_archive_1' ) && '' === cooklook_ad( '../other' ), 'Off/unknown slot rendered' );
} );

ads_case( 'Uploaded banner is escaped and retains its campaign URL when switching modes', function () {
	$input = cooklook_ads_defaults();
	$input['slots']['main_categories'] = array_merge( $input['slots']['main_categories'], array(
		'image_source' => 'media', 'image_id' => '100', 'alt' => '<b>Курс</b> " & вкус',
		'url' => 'https://example.test/course?utm_source=cooklook&erid=abc%2F123', 'new_tab' => '1', 'yandex_id' => 'R-A-123-4',
	) );
	$GLOBALS['ads_saved'] = cooklook_ads_sanitize( $input );
	$html = cooklook_ad( 'main_categories' );
	expect( strpos( $html, 'dish.jpg' ) !== false && strpos( $html, 'main_categories.jpg' ) === false, 'Media image not selected' );
	expect( strpos( $html, 'alt="Курс &quot; &amp; вкус"' ) !== false, 'Alt is not escaped' );
	expect( strpos( $html, 'rel="sponsored noopener noreferrer"' ) !== false && strpos( $html, 'target="_blank"' ) !== false, 'Unsafe new tab' );
	expect( strpos( $html, 'erid=abc%2F123' ) !== false, 'Tracking value changed' );
	$input = $GLOBALS['ads_saved']; $input['slots']['main_categories']['type'] = 'yandex';
	$GLOBALS['ads_saved'] = cooklook_ads_sanitize( $input );
	expect( strpos( cooklook_ad( 'main_categories' ), 'data-cooklook-yandex="R-A-123-4"' ) !== false, 'Did not switch to Yandex' );
	expect( strpos( cooklook_ad( 'main_categories' ), '<a' ) === false, 'Both modes rendered' );
	$input = $GLOBALS['ads_saved']; $input['slots']['main_categories']['type'] = 'banner';
	$GLOBALS['ads_saved'] = cooklook_ads_sanitize( $input );
	expect( $html === cooklook_ad( 'main_categories' ), 'Switching lost the saved banner' );
} );

ads_case( 'Invalid data cannot replace a working image, URL or Yandex ID', function () {
	$input = cooklook_ads_defaults();
	$input['slots']['main_categories']['image_source'] = 'media';
	$input['slots']['main_categories']['image_id'] = array( '100' );
	$input['slots']['main_categories']['url'] = 'javascript:alert(1)';
	$input['slots']['main_categories']['unknown'] = '<script>alert(1)</script>';
	$input['slots']['main_categories']['type'] = 'yandex';
	$input['slots']['main_categories']['yandex_id'] = '<script>evil()</script>';
	$input['slots']['main_bottom']['yandex_id'] = 'R-A-1-2" onmouseover="evil()';
	$input['slots']['foreign'] = array( 'type' => 'banner' );
	$result = cooklook_ads_sanitize( $input );
	expect( $result['slots'] === cooklook_ads_defaults()['slots'], 'Invalid input replaced defaults' );
	expect( count( $GLOBALS['ads_errors'] ) >= 4, 'Validation errors not visible to administrator' );
	foreach ( array( 'javascript:alert(1)', 'data:text/html,test', '//evil.test', '/relative', array( 'https://example.test' ), 'https://' ) as $url ) {
		expect( '' === cooklook_ad_url( $url ), 'Unsafe or malformed URL allowed' );
	}
	foreach ( array( 'R-A-1-2', 'R-I-100-200' ) as $id ) { expect( cooklook_ad_valid_yandex_id( $id ), 'Valid ID rejected' ); }
	foreach ( array( "R-A-1-2\n", 'R-A-1-2<script>', array(), 'R-A--2' ) as $id ) { expect( ! cooklook_ad_valid_yandex_id( $id ), 'Unsafe ID accepted' ); }
} );

ads_case( 'Missing fields are preserved, removed images stay removed, trashed media stays hidden', function () {
	$input = array( 'enabled' => '1', 'banner_enabled' => '1', 'yandex_enabled' => '1', 'slots' => array( 'main_categories' => array( 'image_source' => 'none', 'image_id' => 0 ) ) );
	$GLOBALS['ads_saved'] = cooklook_ads_sanitize( $input );
	expect( '' === cooklook_ad( 'main_categories' ), 'Removed image silently reverted to theme image' );
	expect( '' !== cooklook_ad( 'recipe_archive_1' ), 'Omitted slot was erased' );
	$GLOBALS['ads_saved']['slots']['main_categories']['image_source'] = 'media';
	$GLOBALS['ads_saved']['slots']['main_categories']['image_id'] = 100;
	$GLOBALS['test_posts'][100] = (object) array( 'post_status' => 'trash' );
	expect( '' === cooklook_ad( 'main_categories' ), 'Trashed media is shown' );
	unset( $GLOBALS['test_images'][100] );
	expect( '' === cooklook_ad( 'main_categories' ), 'Missing media rendered an empty wrapper' );
} );

ads_case( 'Empty link renders an image without a link; saving requires administration rights', function () {
	$input = cooklook_ads_defaults(); $input['slots']['main_categories']['url'] = '';
	$GLOBALS['ads_saved'] = cooklook_ads_sanitize( $input );
	expect( strpos( cooklook_ad( 'main_categories' ), '<img' ) !== false && strpos( cooklook_ad( 'main_categories' ), '<a ' ) === false, 'Empty link produced clickable markup' );
	$before = $GLOBALS['ads_saved'];
	$GLOBALS['test_caps']['manage_options'] = false;
	expect( $before === cooklook_ads_sanitize( array( 'enabled' => '0' ) ), 'Non-admin changed settings' );
	cooklook_ads_admin_menu(); cooklook_ads_register_settings();
	expect( 'manage_options' === $GLOBALS['ads_menu_cap'], 'Admin menu exposed' );
	expect( 'cooklook_ads_sanitize' === $GLOBALS['ads_registered'][2]['sanitize_callback'] && false === $GLOBALS['ads_registered'][2]['show_in_rest'], 'Unprotected setting registration' );
} );

ads_case( 'SDK bootstrap is restricted to pages with active Yandex placements, including initially empty catalogs', function () {
	foreach ( array( 'home', 'recipe', 'home.php' ) as $page ) {
		$GLOBALS['ads_page'] = $page; $GLOBALS['ads_scripts'] = array(); cooklook_ads_enqueue();
		expect( isset( $GLOBALS['ads_scripts']['cooklook-advertising'] ), 'Missing SDK bootstrap on ' . $page );
	}
	foreach ( array( '', 'catalog', 'taxonomy', 'account' ) as $page ) {
		$GLOBALS['ads_page'] = $page; $GLOBALS['ads_scripts'] = array(); cooklook_ads_enqueue();
		expect( ! $GLOBALS['ads_scripts'], 'Unnecessary SDK bootstrap on ' . $page );
	}
	$GLOBALS['ads_saved'] = cooklook_ads_defaults();
	$GLOBALS['ads_saved']['slots']['recipe_archive_1']['type'] = 'yandex';
	$GLOBALS['ads_saved']['slots']['recipe_archive_1']['yandex_id'] = 'R-A-123-4';
	foreach ( array( 'catalog', 'taxonomy', 'archive.php' ) as $page ) {
		$GLOBALS['ads_page'] = $page; $GLOBALS['ads_scripts'] = array(); cooklook_ads_enqueue();
		expect( isset( $GLOBALS['ads_scripts']['cooklook-advertising'] ), 'AJAX catalog would miss SDK bootstrap' );
	}
	$GLOBALS['ads_saved']['yandex_enabled'] = false;
	$GLOBALS['ads_scripts'] = array(); cooklook_ads_enqueue();
	expect( ! $GLOBALS['ads_scripts'], 'Disabled Yandex still enqueues bootstrap' );
} );

echo $GLOBALS['test_passed'] . " passed, " . $GLOBALS['test_failed'] . " failed.\n";
exit( $GLOBALS['test_failed'] ? 1 : 0 );
