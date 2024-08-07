<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package cooklook
 */

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function cooklook_body_classes( $classes ) {
	// Adds a class of hfeed to non-singular pages.
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	// Adds a class of no-sidebar when there is no sidebar present.
	if ( ! is_active_sidebar( 'sidebar-1' ) ) {
		$classes[] = 'no-sidebar';
	}

	return $classes;
}
add_filter( 'body_class', 'cooklook_body_classes' );

/**
 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
 */
function cooklook_pingback_header() {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">', esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'cooklook_pingback_header' );

//убирает ошибку в schema - query-input 
add_filter( 'aioseo_schema_output', 'aioseo_filter_schema_output' );
function aioseo_filter_schema_output( $graphs ) {
	foreach ( $graphs as $index => $graph ) {
	if ( 'WebSite' === $graph['@type'] ) {
	unset( $graphs[ $index ]['potentialAction']["query-input"] );
	}
}
return $graphs;
}

function add_featured_image_column( $columns ) {
	$new = array();
	foreach($columns as $key => $title) {
		if ($key == 'cb') { // или 'cb' для чекбокса
			$new[$key] = $title;
			$new['featured_image'] = __('Миниатюра');
		} else {
			$new[$key] = $title;
		}
	}
	return $new;
}
add_filter('manage_posts_columns', 'add_featured_image_column');

function featured_image_column($column, $post_id) {
    if ($column == 'featured_image') {
        echo get_the_post_thumbnail($post_id, 'thumbnail');
    }
}
add_action('manage_posts_custom_column', 'featured_image_column', 10, 2);

function my_admin_style() {
    echo '
    <style type="text/css">
		.fixed .column-featured_image{
			width: 100px
		}
        .column-featured_image img {
            max-width: 100px; /* или любой другой размер */
            height: auto;
        }
    </style>';
}
add_action('admin_head', 'my_admin_style');

/* функция рассчета рейтинга рецепта */
function calculate_rating($likes, $dislikes) {
    // Преобразование в целые числа
    $likes = intval($likes);
    $dislikes = intval($dislikes);

    if ($likes + $dislikes == 0) {
        return 0;
    }
    $total_votes = $likes + $dislikes;
    $like_ratio = $likes / $total_votes;
    $rating = $like_ratio * 5;
    return round($rating, 1);
}

// Функция для конвертации времени из минут формат shema.org
function schema_time($cook_time) {
    // Вычисление количества часов и минут
    $hours = floor($cook_time / 60);
    $minutes = $cook_time % 60;

    // Формирование строки в формате PTXHYM
    $formatted_time = "PT";
    if ($hours > 0) {
        $formatted_time .= "{$hours}H";
    }
    if ($minutes > 0) {
        $formatted_time .= "{$minutes}M";
    }

    // В случае если ни часов, ни минут нет
    if ($hours == 0 && $minutes == 0) {
        $formatted_time .= "0M";
    }

    return $formatted_time;
}

//Конвертация минут в человекопонятный формат "2 часа 30 минут"
function convert_time_to_string($cook_time) {
    // Вычисление количества часов и минут
    $hours = floor($cook_time / 60);
    $minutes = $cook_time % 60;

    // Формирование строки с тернарными операторами
    $time_string = ($hours > 0 ? $hours . ' ч.' : '') .
                   ($hours > 0 && $minutes > 0 ? ' ' : '') .
                   ($minutes > 0 ? $minutes . ' мин.' : '');

    // Если ни часов, ни минут нет
    return $time_string ?: '0 минут';
}


/* увеличивает полу recipe_views у рецепта при каждом просмотре (используется для вывода популярных рецептов) */
function increase_post_views() {
    if ( is_single() ) {
        $post_id = get_the_ID();
        $views = carbon_get_post_meta( $post_id, 'recipe_views' );
        $views++;
        carbon_set_post_meta( $post_id, 'recipe_views', $views );
    }
}
add_action( 'wp_head', 'increase_post_views' );

//Вывод категорий у рецепта согласно иерархии
function display_recipe_categories_hierarchy($categories, $parent_id = 0) {                    
    foreach ($categories as $category) : ?>
        <?php if ($category->parent == $parent_id) : ?> 
            <a class ="recipe_category-item flex" href="<?= get_term_link($category) ?>">
                <?= $category->name ?>
                <?php if (has_children_recipe_categories($categories, $category->term_id)) : ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M6.66699 5.33325L9.33366 7.99992L6.66699 10.6666" stroke="#828282" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                <?php else: ?>
                    ,
                <?php endif; ?>
            </a>
            <?php display_recipe_categories_hierarchy($categories, $category->term_id); ?>
        <?php endif; ?>
    <?php endforeach;                         
}

function has_children_recipe_categories($categories, $parent_id) {
    foreach ($categories as $category) {
        if ($category->parent == $parent_id) {
            return true;
        }
    }
    return false;
}

// убирает префикс у заголовков в архивах "Категория:", "Архив:" 
function remove_archive_prefix($title) {
    if (is_archive() && !is_category() && !is_tag() && !is_tax()) {
        $title = single_cat_title('', false);
    }
    return $title;
}
add_filter('get_the_archive_title', 'remove_archive_prefix');

add_filter( 'facetwp_is_main_query', function( $is_main_query, $query ) {
    if ( $query->is_archive() && $query->is_main_query() ) {
      $is_main_query = false;
    }
    return $is_main_query;
  }, 10, 2 );

function add_ingredients_to_js() {
    $ingridients = get_terms(array(
        'taxonomy' => 'recipe_tags',
        'hide_empty' => false,
    ));

    $availableIngredients = array();

    foreach ($ingridients as $ingredient) {
        $availableIngredients[] = $ingredient->name;
    }

    // Преобразуйте массив в формат JSON
    $availableIngredientsJson = json_encode($availableIngredients);

    // Передайте данные в JavaScript с помощью localize_script
    wp_localize_script('filter-ingridients', 'availableIngredients', array(
        'ingredients' => $availableIngredients,
    ));
}
add_action('wp_enqueue_scripts', 'add_ingredients_to_js');





// Function for comment template
function commentsHTML5($comment, $args, $depth) {
    $id = $comment->comment_ID;
    $author = $comment->comment_author;
    $date = get_comment_date();
    $localized_date = date_i18n('j F Y', strtotime($date));
    $content = $comment->comment_content;
    ?>

    <div id="comment-<?= $id ?>" class="comment">
        <div class="comment_header">                
            <?php 
                $avatar_args = [
                    'class' => 'comment_avatar',
                ];
                echo get_avatar($comment, 60, '', $author, $avatar_args);
            ?>
            
            <div class="comment_meta">
                <span class="comment_meta-author">
                    <?= $author ?>
                </span>
                <span class="comment_meta-date">
                    <?= $localized_date ?>
                </span>
            </div>
        </div>
        
        <div class="comment_body">
            <?= $content ?>
        </div>
        <?php if (is_user_logged_in()) : ?>
            <div class="comment_footer">
                <?php    
                    comment_reply_link(array_merge($args, [
                        'add_below' => 'comment',
                        'depth'     => $depth,
                        'max_depth' => $args['max_depth'],
                        'before'    => '<div class="reply">',
                        'after'     => '</div>',
                        'respond_id'=> 'respond'
                    ]));
                ?>
            </div>
        <?php endif ?>
    
    <?php
}


function commentsHTML5_end(){
    echo '</div>';
}

function handle_ajax_comment() {
    $comment = wp_handle_comment_submission( wp_unslash( $_POST ) );
    if ( is_wp_error( $comment ) ) {
        $data = array(
            'status' => 'error',
            'message' => $comment->get_error_message()
        );
        echo json_encode($data);
        wp_die();
    }

    $user = wp_get_current_user();
    do_action( 'set_comment_cookies', $comment, $user );

    // Generate comment HTML
    $comment_html = '';
    ob_start();
    wp_list_comments(array(
        'callback' => 'commentsHTML5',
        'end-callback' => 'commentsHTML5_end',
        'style' => 'div',
        'short_ping' => true,
        'avatar_size' => 60,
        'reverse_top_level' => false,
        'max_depth' => 3,
        'per_page' => 1,
        'page' => 1,
        'echo' => true
    ), array($comment));
    $comment_html = ob_get_clean();

    $data = array(
        'status' => 'success',
        'message' => __( 'Comment submitted successfully!' ),
        'comment_ID' => $comment->comment_ID,
        'comment_html' => $comment_html
    );
    echo json_encode($data);
    wp_die();
}
add_action( 'wp_ajax_nopriv_ajaxcomments', 'handle_ajax_comment' );
add_action( 'wp_ajax_ajaxcomments', 'handle_ajax_comment' );





function add_comment_placeholder($field) {
    // Добавляем плейсхолдер к полю комментария
    $field = str_replace(
        '<textarea',
        '<textarea placeholder="'. __('Ваш комментарий *', 'cooklook') .'"',
        $field
    );
    return $field;
}
add_filter('comment_form_field_comment', 'add_comment_placeholder');

function custom_comment_form_fields($fields) {
    // Добавляем плейсхолдеры к полям
    $fields['author'] = str_replace(
        'id="author"',
        'id="author" placeholder="'. __('Имя *', 'cooklook') .'"',
        $fields['author']
    );

    $fields['email'] = str_replace(
        'id="email"',
        'id="email" placeholder="'. __('E-Mail *', 'cooklook') .'"',
        $fields['email']
    );

    unset($fields['url']);
    return $fields;
}
add_filter('comment_form_default_fields', 'custom_comment_form_fields');

function change_comment_form_button($defaults) {
    // Изменяем текст кнопки
    $defaults['label_submit'] = __('Опубликовать', 'cooklook');
    return $defaults;
}
add_filter('comment_form_defaults', 'change_comment_form_button');

add_filter( 'comment_form_fields', function ( $fields ) {
    $fields['cookies'] = sprintf(
        '<p class="comment-form-cookies-consent">%s %s</p>',
        '<input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes" checked="checked" />',
        '<label for="wp-comment-cookies-consent">'. __('Согласен(а) с политикой конфиденциальности', 'cooklook') .'</label>',
    );

    return $fields;
} );

// Изменение URL логотипа
function custom_login_logo_url() {
    return home_url();
}
add_filter('login_headerurl', 'custom_login_logo_url');

// Изменение заголовка логотипа
function custom_login_logo_url_title() {
    return 'Cook-Look';
}
add_filter('login_headertitle', 'custom_login_logo_url_title');





