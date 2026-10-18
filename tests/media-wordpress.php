<?php
/** Real WordPress image editors, isolated files and in-memory metadata; no DB writes. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
define( 'DISABLE_WP_CRON', true );
$root = getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 );
require __DIR__ . '/support/readonly-wordpress.php';
require $root . '/wp-load.php';
function media_check( $ok, $message ) {
	if ( ! $ok ) { throw new RuntimeException( $message ); }
	echo 'PASS: ' . $message . "\n";
}
$directory = dirname( __DIR__ ) . '/build/media-test-' . gmdate( 'Ymd-His' ) . '-' . bin2hex( random_bytes( 3 ) );
mkdir( $directory, 0755, true );
copy( dirname( __DIR__ ) . '/static/img/adv/recipe_page.jpg', $directory . '/photo.jpg' );
$small = new Imagick( $directory . '/photo.jpg' );
$small->resizeImage( 200, 0, Imagick::FILTER_LANCZOS, 1 );
$small->writeImage( $directory . '/small.jpg' );
$small->clear();
$paths = array( 900000001 => $directory . '/photo.jpg', 900000002 => $directory . '/small.jpg', 900000003 => dirname( __DIR__ ) . '/static/img/adv/recipe_page.jpg' );
$meta = array();
$crop = false;
foreach ( $paths as $id => $path ) {
	wp_cache_set( $id, (object) array( 'ID' => $id, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg', 'post_status' => 'inherit', 'post_parent' => 0, 'filter' => 'raw' ), 'posts' );
}
add_filter( 'upload_dir', function ( $uploads ) use ( $directory ) {
	$uploads['basedir'] = $directory;
	$uploads['baseurl'] = 'http://cooklook/wp-content/themes/cooklook/' . str_replace( '\\', '/', substr( $directory, strlen( dirname( __DIR__ ) ) + 1 ) );
	return $uploads;
} );
add_filter( 'get_attached_file', function ( $file, $id ) use ( &$paths ) { return $paths[ $id ] ?? $file; }, 10, 2 );
add_filter( 'get_post_metadata', function ( $value, $id, $key ) use ( &$meta, $paths ) {
	return isset( $paths[ $id ] ) && '_cooklook_webp_v1' === $key ? array( $meta[ $id ] ?? '' ) : $value;
}, 10, 3 );
add_filter( 'update_post_metadata', function ( $value, $id, $key, $data ) use ( &$meta, $paths ) {
	if ( isset( $paths[ $id ] ) && '_cooklook_webp_v1' === $key ) { $meta[ $id ] = $data; return true; }
	return $value;
}, 10, 4 );
add_filter( 'image_downsize', function ( $value, $id ) use ( $paths, &$crop ) {
	if ( ! isset( $paths[ $id ] ) ) { return $value; }
	$dimensions = getimagesize( $paths[ $id ] );
	return array( 'http://cooklook/photo.jpg', $crop ? 100 : $dimensions[0], $crop ? 100 : $dimensions[1], $crop );
}, 10, 2 );

$fallback = '<img src="photo.jpg" width="1084" height="472" loading="eager" fetchpriority="high" alt="Фото">';
$attributes = array( 'sizes' => '360px' );
$before = hash_file( 'sha256', $paths[900000001] );
media_check( $fallback === cooklook_webp_picture( 900000001, 'large', $attributes, $fallback ), 'Missing derivatives preserve the entire fallback and queue work without conversion' );
media_check( ! is_dir( $directory . '/cooklook-webp' ), 'Rendering performs no filesystem writes' );
media_check( cooklook_generate_webp( 900000001 ), 'Actual WordPress editor creates responsive WebP copies' );
media_check( $before === hash_file( 'sha256', $paths[900000001] ), 'JPEG original is byte-for-byte unchanged' );
$generated = $meta[900000001];
media_check( count( $generated['sources'] ) === 3 && max( array_keys( $generated['sources'] ) ) === 1084, 'Three bounded widths are generated without upscaling' );
$picture = cooklook_webp_picture( 900000001, 'large', $attributes, $fallback );
media_check( strpos( $picture, '<source type="image/webp"' ) !== false && strpos( $picture, $fallback ) !== false, 'Picture retains original alt, geometry, loading and fallback' );
$files = array();
foreach ( $generated['sources'] as $width => $name ) {
	$path = $directory . '/' . $name;
	$dimensions = getimagesize( $path );
	media_check( $dimensions[0] === $width && 'image/webp' === $dimensions['mime'], 'Decodable WebP at width ' . $width );
	$files[ $width ] = array( 'bytes' => filesize( $path ), 'sha256' => hash_file( 'sha256', $path ) );
}
media_check( cooklook_generate_webp( 900000001 ) && $generated === $meta[900000001], 'Already generated photos are reused' );
$crop = true;
media_check( $fallback === cooklook_webp_picture( 900000001, 'thumbnail', $attributes, $fallback ), 'A custom crop keeps its original composition' );
$crop = false;
media_check( cooklook_generate_webp( 900000002 ) && array_keys( $meta[900000002]['sources'] ) === array( 200 ), 'Small originals convert without a failing no-op resize or upscaling' );
media_check( false === cooklook_webp_source( 900000003 ), 'Files outside uploads are rejected' );
$missing = reset( $generated['sources'] );
unlink( $directory . '/' . $missing );
media_check( $fallback === cooklook_webp_picture( 900000001, 'large', $attributes, $fallback ) && cooklook_generate_webp( 900000001 ), 'A deleted derivative falls back safely and can regenerate' );
clearstatcache();
touch( $paths[900000001], filemtime( $paths[900000001] ) + 2 );
clearstatcache();
media_check( $fallback === cooklook_webp_picture( 900000001, 'large', $attributes, $fallback ), 'Replacing the source invalidates cached WebP metadata' );
$no_editors = function () { return array(); };
add_filter( 'wp_image_editors', $no_editors );
media_check( false === cooklook_generate_webp( 900000001 ) && empty( $meta[900000001]['sources'] ) && $meta[900000001]['retry_after'] > time(), 'Missing WebP support keeps the fallback and defers retries' );
remove_filter( 'wp_image_editors', $no_editors );
$report = array( 'checked_at' => gmdate( 'c' ), 'original_bytes' => filesize( $paths[900000001] ), 'webp' => $files, 'database_writes' => false, 'fixture_directory' => $directory );
file_put_contents( dirname( __DIR__ ) . '/docs/performance-media-check.json', wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );
echo "Media integration checks complete.\n";
