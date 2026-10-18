<?php
/** Process-only harness for local integration tests; no settings or content writes. */
if ( PHP_SAPI !== 'cli' ) { exit; }
$GLOBALS['wp_filter']['option_active_plugins'][ PHP_INT_MAX ][] = array( 'function' => function ( $plugins ) {
	return array_values( array_filter( $plugins, function ( $plugin ) { return 0 !== strpos( $plugin, 'wp-import-export-lite/' ); } ) );
}, 'accepted_args' => 1 );
$GLOBALS['wp_filter']['muplugins_loaded'][ PHP_INT_MIN ][] = array( 'function' => function () {
	if ( wp_using_ext_object_cache() ) { throw new RuntimeException( 'Use the local default object cache.' ); }
	wp_using_ext_object_cache( true );
}, 'accepted_args' => 0 );
$GLOBALS['wp_filter']['query'][ PHP_INT_MAX ][] = array( 'function' => function ( $sql ) {
	if ( ! preg_match( '/^\s*(?:SELECT|SHOW|EXPLAIN|DESCRIBE)\b/i', $sql ) ) { throw new RuntimeException( 'SQL write refused during integration test.' ); }
	return $sql;
}, 'accepted_args' => 1 );
$GLOBALS['wp_filter']['pre_wp_mail'][ PHP_INT_MAX ][] = array( 'function' => function () { return false; }, 'accepted_args' => 1 );
$GLOBALS['wp_filter']['pre_http_request'][ PHP_INT_MAX ][] = array( 'function' => function () { return new WP_Error( 'cooklook_test', 'External requests disabled.' ); }, 'accepted_args' => 1 );
