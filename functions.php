<?php
/**
 * cooklook functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package cooklook
 */

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */

function cooklook_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on cooklook, use a find and replace
		* to change 'cooklook' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'cooklook', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'main_menu' => esc_html__( 'Primary', 'cooklook' ),
			'footer_links' => esc_html__( 'Footer links', 'cooklook' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'cooklook_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'cooklook_setup' );

//Add class for logo
add_filter( 'get_custom_logo', 'change_logo_class' );
function change_logo_class( $html ) {
		$html = str_replace( 'custom-logo', 'logo_img', $html );
       $html = str_replace( 'custom-logo-link', 'header_logo_link', $html );
    return $html;
};

//require_once( get_template_directory().'/functions-parts/theme-settings.php' );
require_once( get_template_directory().'/functions-parts/breadcrumbs.php' );
require_once( get_template_directory().'/functions-parts/navmenu.php' );
require_once( get_template_directory().'/functions-parts/crb_init.php' );
require_once( get_template_directory().'/functions-parts/widget-areas.php' );
require_once( get_template_directory().'/functions-parts/styles_load.php' );
require_once( get_template_directory().'/functions-parts/scripts_load.php' );
require_once( get_template_directory().'/functions-parts/phones_format.php' );
require_once( get_template_directory().'/template-parts/schema.php' );
require_once( get_template_directory().'/search-ajax-handler.php' );
require_once( get_template_directory().'/functions-parts/search_moderation_function.php' );
require_once( get_template_directory().'/functions-parts/custom_post_types.php' );
require_once( get_template_directory().'/filters-handler.php' );
require_once( get_template_directory().'/functions-parts/recipe_rating.php' );
require_once( get_template_directory().'/functions-parts/sorting.php' );
require_once( get_template_directory().'/functions-parts/favorite_recipes.php' );
require_once( get_template_directory().'/functions-parts/process_img.php' );
require_once( get_template_directory().'/recipe_importer.php' );
require_once( get_template_directory().'/recipe_manager.php' );



/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function cooklook_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'cooklook_content_width', 640 );
}
add_action( 'after_setup_theme', 'cooklook_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function cooklook_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar', 'cooklook' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Add widgets here.', 'cooklook' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'cooklook_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function cooklook_scripts() {
	wp_enqueue_style( 'cooklook-style', get_stylesheet_uri(), array(), _S_VERSION );
	wp_style_add_data( 'cooklook-style', 'rtl', 'replace' );

	wp_enqueue_script( 'cooklook-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'cooklook_scripts' );

function default_post_thumbnail($html, $post_id, $post_thumbnail_id, $size, $attr) {
    if (empty($html)) {
        $default_image = get_template_directory_uri() . '/static/img/no_image.png';
        $html = '<img src="' . $default_image . '" alt="Default Image" />';
    }
    return $html;
}
add_filter('post_thumbnail_html', 'default_post_thumbnail', 10, 5);


/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}