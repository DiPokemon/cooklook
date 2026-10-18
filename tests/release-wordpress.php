<?php
/**
 * Boot the extracted release against the local WordPress without switching its active theme.
 * php tests/release-wordpress.php build/release-.../extracted/cooklook
 * COOKLOOK_WP_ROOT may override the WordPress root. No database writes are allowed.
 */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }

function release_wp_check( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}

$release_theme = isset( $argv[1] ) ? realpath( $argv[1] ) : false;
release_wp_check( $release_theme && is_file( $release_theme . '/functions.php' ), 'Pass an extracted Cooklook theme directory.' );
$release_theme = str_replace( '\\', '/', $release_theme );
release_wp_check( $release_theme !== str_replace( '\\', '/', dirname( __DIR__ ) ), 'Verify the release copy, not the development theme.' );
define( 'DISABLE_WP_CRON', true );
// WordPress converts these preinitialized filters to WP_Hook objects during bootstrap.
foreach ( array( 'template_directory', 'stylesheet_directory' ) as $hook ) {
	$GLOBALS['wp_filter'][ $hook ][ PHP_INT_MAX ][] = array(
		'function' => function () use ( $release_theme ) { return $release_theme; }, 'accepted_args' => 1,
	);
}
$GLOBALS['wp_filter']['muplugins_loaded'][ PHP_INT_MIN ][] = array(
	'function' => function () {
		release_wp_check( ! wp_using_ext_object_cache(), 'Use a local copy without a persistent object-cache drop-in.' );
		// Keep transient creation/expiry in this process's default WP_Object_Cache, not wp_options.
		wp_using_ext_object_cache( true );
	}, 'accepted_args' => 0,
);
$GLOBALS['wp_filter']['option_active_plugins'][ PHP_INT_MAX ][] = array(
	'function' => function ( $plugins ) {
		// This unrelated plugin writes import/export housekeeping data on every bootstrap.
		return array_values( array_filter( $plugins, function ( $plugin ) {
			return 0 !== strpos( $plugin, 'wp-import-export-lite/' );
		} ) );
	}, 'accepted_args' => 1,
);
$GLOBALS['wp_filter']['query'][ PHP_INT_MAX ][] = array(
	'function' => function ( $sql ) {
		release_wp_check( (bool) preg_match( '/^\s*(?:SELECT|SHOW|EXPLAIN|DESCRIBE)\b/i', $sql ), 'Non-read SQL refused during release verification.' );
		return $sql;
	}, 'accepted_args' => 1,
);
require ( getenv( 'COOKLOOK_WP_ROOT' ) ?: dirname( __DIR__, 4 ) ) . '/wp-load.php';
release_wp_check( 'cooklook' === get_template() && $release_theme === get_template_directory(), 'Unexpected active theme.' );
$loaded = array();
foreach ( array( 'cooklook_recipe_query_args', 'cooklook_search_ingredients', 'cooklook_recipe_schema', 'cooklook_render_recipes', 'cooklook_generate_webp', 'cooklook_webp_picture' ) as $function ) {
	$file = str_replace( '\\', '/', ( new ReflectionFunction( $function ) )->getFileName() );
	release_wp_check( 0 === strpos( $file, $release_theme . '/' ), 'Function did not load from release: ' . $function );
	$loaded[ $function ] = substr( $file, strlen( $release_theme ) + 1 );
}
foreach ( array( 'Carbon_Fields\\Carbon_Fields', 'Carbon_Fields_Yoast\\Carbon_Fields_Yoast', 'Cooklook_Cached_Post_Meta_Datastore' ) as $class ) {
	$file = str_replace( '\\', '/', ( new ReflectionClass( $class ) )->getFileName() );
	release_wp_check( 0 === strpos( $file, $release_theme . '/' ), 'Class did not load from release: ' . $class );
	$loaded[ $class ] = substr( $file, strlen( $release_theme ) + 1 );
}
global $wpdb;
$sorts = array();
foreach ( array( 'date', 'recipe_time', 'recipe_views' ) as $sort ) {
	$query = new WP_Query( cooklook_recipe_query_args( cooklook_recipe_filters( array( 'sort_by' => $sort ) ) ) );
	release_wp_check( '' === $wpdb->last_error && $query->post_count > 0, 'Release catalog query failed.' );
	ob_start(); cooklook_render_recipes( $query ); $html = ob_get_clean();
	release_wp_check( substr_count( $html, 'class="recipe_title"' ) === $query->post_count, 'Release cards did not render.' );
	$sorts[ $sort ] = array( 'found' => (int) $query->found_posts, 'cards' => $query->post_count );
}
$search = cooklook_search_ingredients( array( 'term' => 'соль', 'page' => '1' ) );
release_wp_check( ! is_wp_error( $search ) && count( $search['results'] ) > 0 && count( $search['results'] ) <= 20, 'Ingredient lookup failed.' );
$args = array( 'filters' => cooklook_recipe_filters( array( 'include_ingredients' => array( $search['results'][0]['id'] ) ) ) );
ob_start(); get_template_part( 'template-parts/filter-ingridients', null, $args ); $filter_html = ob_get_clean();
release_wp_check( 1 === substr_count( $filter_html, '<option ' ), 'Release filter did not restore exactly one selected option.' );

$sample = new WP_Query( array( 'post_type' => 'recipe', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => 10, 'meta_key' => '_thumbnail_id' ) );
release_wp_check( 10 === $sample->post_count, 'Ten recipes with images are required for this local check.' );
foreach ( $sample->posts as $post ) {
	$GLOBALS['post'] = $post; setup_postdata( $post );
	$schema = cooklook_recipe_schema( $post->ID );
	release_wp_check( $schema && 'CookLook' === $schema['author']['name'], 'Release Recipe author/schema missing.' );
	ob_start(); get_template_part( 'template-parts/content-recipe' ); $html = ob_get_clean();
	release_wp_check( false !== strpos( $html, 'Автор рецепта: CookLook' ) && false !== strpos( $html, 'id="instructions"' ), 'Release recipe content incomplete.' );
}
wp_reset_postdata();

// All literal runtime asset paths from the enqueue modules must exist in the extracted ZIP.
$assets = array();
foreach ( array( 'functions-parts/scripts_load.php', 'functions-parts/styles_load.php', 'inc/recipe-views.php' ) as $file ) {
	preg_match_all( '~[\'\"]/?(static/[^\'\"]+\.(?:css|js))[\'\"]~', file_get_contents( $release_theme . '/' . $file ), $matches );
	foreach ( $matches[1] as $asset ) {
		release_wp_check( is_file( $release_theme . '/' . $asset ), 'Runtime asset missing: ' . $asset );
		$assets[ $asset ] = true;
	}
}
release_wp_check( count( $assets ) >= 10, 'Asset check did not discover the expected enqueue paths.' );
$installed = json_decode( file_get_contents( $release_theme . '/vendor/composer/installed.json' ), true );
release_wp_check( false === $installed['dev'] && 2 === count( $installed['packages'] ), 'Development packages are present.' );
echo wp_json_encode( array(
	'checked_at' => gmdate( 'c' ), 'wordpress' => get_bloginfo( 'version' ), 'php' => PHP_VERSION, 'site' => home_url( '/' ),
	'method' => 'Read-only CLI boot of the extracted ZIP via process-local directory filters. Transients use the default in-memory WP_Object_Cache; wp-import-export-lite is excluded in this process because its bootstrap writes housekeeping data. Active theme/plugin options and site files unchanged. This is not a production, full plugin compatibility or browser measurement.',
	'database_write_queries_allowed' => false, 'loaded_from_release' => $loaded,
	'sorts' => $sorts, 'ingredient_search_results' => count( $search['results'] ), 'selected_options' => 1,
	'recipe_html_and_schema' => $sample->post_count, 'runtime_assets' => array_keys( $assets ), 'development_packages' => 0,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
