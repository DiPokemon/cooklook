<?php
/** Check actual WordPress CSS output and fallback behavior without database writes. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
define( 'DISABLE_WP_CRON', true );
require __DIR__ . '/support/readonly-wordpress.php';
require ( getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 ) ) . '/wp-load.php';

function styles_check( $ok, $message ) {
	if ( ! $ok ) { throw new RuntimeException( $message ); }
}

function styles_render( $query, $opt_in ) {
	static $queues = array();
	$GLOBALS['wp_query'] = new WP_Query( $query );
	$key = wp_json_encode( $query );
	if ( ! isset( $queues[ $key ] ) ) {
		$GLOBALS['wp_styles'] = new WP_Styles();
		remove_action( 'wp_enqueue_scripts', 'cooklook_inline_small_styles', 100 );
		do_action( 'wp_enqueue_scripts' );
		add_action( 'wp_enqueue_scripts', 'cooklook_inline_small_styles', 100 );
		$queues[ $key ] = wp_styles();
	}
	// Compare the same queue: some core enqueue callbacks run only once per request.
	$GLOBALS['wp_styles'] = clone $queues[ $key ];
	foreach ( $queues[ $key ]->registered as $handle => $style ) {
		$GLOBALS['wp_styles']->registered[ $handle ] = clone $style;
	}
	if ( $opt_in ) { cooklook_inline_small_styles(); }
	wp_maybe_inline_styles();
	ob_start(); wp_print_styles(); $html = ob_get_clean();
	$document = new DOMDocument();
	$old_errors = libxml_use_internal_errors( true );
	$document->loadHTML( '<html><head><meta charset="utf-8">' . $html . '</head><body></body></html>' );
	libxml_clear_errors(); libxml_use_internal_errors( $old_errors );
	$styles = array();
	foreach ( ( new DOMXPath( $document ) )->query( '//link[@rel="stylesheet"] | //style[@id]' ) as $node ) {
		$id = preg_replace( '/(?:-inline)?-css$/', '', $node->getAttribute( 'id' ) );
		$styles[ $id ] = array( 'tag' => $node->nodeName, 'href' => $node->getAttribute( 'href' ), 'css' => $node->textContent );
	}
	return $styles;
}

$recipe = get_posts( array( 'post_type' => 'recipe', 'post_status' => 'publish', 'numberposts' => 1 ) );
styles_check( ! empty( $recipe ), 'A published recipe is required.' );
$report = array();
foreach ( array( 'catalog' => array( 'post_type' => 'recipe' ), 'recipe' => array( 'p' => $recipe[0]->ID, 'post_type' => 'recipe' ) ) as $name => $query ) {
	$before = styles_render( $query, false );
	$after = styles_render( $query, true );
	styles_check( array_keys( $before ) === array_keys( $after ), 'CSS cascade order changed: ' . $name );
	$inlined = array();
	foreach ( $before as $handle => $style ) {
		if ( $style['tag'] === $after[ $handle ]['tag'] ) {
			styles_check( $style === $after[ $handle ], 'Unrelated CSS changed: ' . $handle );
			continue;
		}
		styles_check( 'link' === $style['tag'] && 'style' === $after[ $handle ]['tag'], 'Unexpected style replacement.' );
		$registered = wp_styles()->registered[ $handle ];
		$css = file_get_contents( $registered->extra['path'] );
		$normalized = _wp_normalize_relative_css_links( $css, $registered->extra['inlined_src'] );
		styles_check( false !== strpos( $after[ $handle ]['css'], trim( $normalized ) ), 'CSS rules missing: ' . $handle );
		styles_check( ! preg_match( '~url\s*\(\s*[\'"]?\./~', $after[ $handle ]['css'] ), 'Page-relative asset URL remains.' );
		if ( 'slick' === $handle ) {
			styles_check( false !== strpos( $after[ $handle ]['css'], '/wp-content/themes/cooklook/static/libs/slick/fonts/slick.woff' ), 'Slick font path was not preserved.' );
		}
		$inlined[ $handle ] = strlen( $css );
	}
	styles_check( isset( $inlined[ 'catalog' === $name ? 'cooklook-catalog' : 'slick' ] ), 'Expected theme stylesheet is not inline.' );
	styles_check( 'link' === $after['style_min']['tag'], 'Shared large CSS must remain cacheable.' );
	if ( isset( $before['relevanssi-live-search'] ) ) { styles_check( isset( $inlined['relevanssi-live-search'] ), 'Installed live-search styles not inline.' ); }
	$report[ $name ] = array( 'removed_blocking_requests' => count( $inlined ), 'inline_css_bytes' => $inlined, 'cascade_order_preserved' => true );
}

// Respect WordPress's cap and keep the external fallback when inlining is disabled.
add_filter( 'styles_inline_size_limit', '__return_zero' );
$capped = styles_render( array( 'post_type' => 'recipe' ), true );
styles_check( 'link' === $capped['cooklook-catalog']['tag'], 'Inline-size cap ignored.' );
remove_filter( 'styles_inline_size_limit', '__return_zero' );

// A child theme/plugin can replace a registered handle without receiving stale parent CSS.
wp_deregister_style( 'cooklook-catalog' );
wp_enqueue_style( 'cooklook-catalog', 'https://example.invalid/custom.css' );
cooklook_inline_small_styles();
styles_check( false === wp_styles()->get_data( 'cooklook-catalog', 'path' ), 'A replacement stylesheet must not opt in.' );
wp_deregister_style( 'relevanssi-live-search' );
cooklook_inline_small_styles();
styles_check( ! wp_style_is( 'relevanssi-live-search', 'registered' ), 'A disabled plugin must remain absent.' );

echo wp_json_encode( array( 'checked_at' => gmdate( 'c' ), 'wordpress' => get_bloginfo( 'version' ), 'database_writes' => false, 'pages' => $report, 'fallback_cap_and_replacement_checks' => true ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
