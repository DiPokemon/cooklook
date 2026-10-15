<?php
/** Admin workflow for editing and publishing existing draft recipes. */
defined( 'ABSPATH' ) || exit;

function recipe_manager_menu() {
	add_menu_page( 'Recipe Manager', 'Recipe Manager', 'manage_options', 'recipe-manager', 'recipe_manager_page' );
}
add_action( 'admin_menu', 'recipe_manager_menu' );

function cooklook_manager_scripts( $hook ) {
	if ( 'toplevel_page_recipe-manager' !== $hook ) {
		return;
	}
	cooklook_enqueue_script( 'cooklook-manager', 'static/js/recipe-manager.js' );
	wp_localize_script( 'cooklook-manager', 'cooklookManager', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'cooklook_manager' ) ) );
}
add_action( 'admin_enqueue_scripts', 'cooklook_manager_scripts' );

function recipe_manager_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Недостаточно прав.', 'cooklook' ) );
	}
	$counts = wp_count_posts( 'recipe' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Управление рецептами', 'cooklook' ); ?></h1>
		<p><?php esc_html_e( 'Черновиков:', 'cooklook' ); ?> <span id="manager-draft-count"><?php echo esc_html( $counts->draft ); ?></span> | <?php esc_html_e( 'Опубликовано:', 'cooklook' ); ?> <span id="manager-published-count"><?php echo esc_html( $counts->publish ); ?></span></p>
		<div id="manager-message" role="status"></div>
		<div id="recipe-info">
			<img id="recipe-image" alt="" width="100" hidden>
			<h2 id="recipe-title"></h2><p id="recipe-ingredients"></p>
		</div>
		<form id="editable-steps-form">
			<input type="hidden" name="recipe_id" id="current-recipe-id">
			<div id="recipe_time"></div>
			<table class="widefat"><thead><tr><th>Оригинальные шаги</th><th>Редактируемые шаги</th></tr></thead><tbody id="steps-table-body"></tbody></table>
			<p><button type="submit" id="save-steps" class="button button-primary" disabled>Сохранить и опубликовать</button>
			<button type="button" id="skip-recipe" class="button">Пропустить</button></p>
		</form>
	</div>
	<?php
}

function cooklook_manager_authorize() {
	cooklook_verify_ajax_nonce( 'cooklook_manager' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Недостаточно прав.', 'cooklook' ), 403 );
	}
}

function get_random_draft_recipe() {
	cooklook_manager_authorize();
	$query = new WP_Query( array(
		'post_type' => 'recipe', 'post_status' => 'draft', 'posts_per_page' => 1,
		'orderby' => 'rand', 'no_found_rows' => true,
		'meta_query' => array( array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ) ),
	) );
	if ( ! $query->posts ) {
		wp_send_json_error( __( 'Нет подходящих черновиков.', 'cooklook' ) );
	}
	$post = $query->posts[0];
	if ( ! current_user_can( 'edit_post', $post->ID ) ) {
		wp_send_json_error( __( 'Недостаточно прав.', 'cooklook' ), 403 );
	}
	wp_send_json_success( array(
		'ID' => $post->ID, 'title' => get_the_title( $post ),
		'original_steps' => wp_list_pluck( cooklook_recipe_rows( $post->ID, 'original_recipe_step' ), 'original_recipe_step_text' ),
		'steps' => wp_list_pluck( cooklook_recipe_rows( $post->ID, 'recipe_step' ), 'recipe_step_text' ),
		'ingredients' => wp_list_pluck( cooklook_recipe_terms( $post->ID, 'recipe_tags' ), 'name' ),
		'edit_link' => get_edit_post_link( $post->ID, 'raw' ),
		'image' => get_the_post_thumbnail_url( $post, 'medium' ),
		'time' => max( 0, (int) cooklook_recipe_value( $post->ID, 'recipe_time' ) ),
		'prep' => max( 0, (int) cooklook_recipe_value( $post->ID, 'recipe_prep' ) ),
		'url' => esc_url_raw( cooklook_recipe_value( $post->ID, 'recipe_url' ) ),
	) );
}
add_action( 'wp_ajax_get_random_draft_recipe', 'get_random_draft_recipe' );

/** Preserve each step's image and Carbon Fields row type when replacing text. */
function cooklook_updated_recipe_steps( $existing, $texts ) {
	if ( ! is_array( $texts ) || ! $texts || count( $texts ) > 100 ) {
		return new WP_Error( 'invalid_steps', __( 'Укажите от 1 до 100 шагов.', 'cooklook' ) );
	}
	$steps = array();
	foreach ( array_values( $texts ) as $index => $text ) {
		if ( ! is_string( $text ) || '' === trim( $text ) || strlen( $text ) > 30000 ) {
			return new WP_Error( 'invalid_steps', __( 'Проверьте текст шагов.', 'cooklook' ) );
		}
		$step = isset( $existing[ $index ] ) && is_array( $existing[ $index ] ) ? $existing[ $index ] : array();
		$step['recipe_step_text'] = sanitize_textarea_field( $text );
		if ( '' === trim( $step['recipe_step_text'] ) ) {
			return new WP_Error( 'invalid_steps', __( 'Шаг не может быть пустым.', 'cooklook' ) );
		}
		$steps[] = $step;
	}
	return $steps;
}

function save_recipe_steps() {
    cooklook_manager_authorize();
    $post_id = cooklook_request_id( $_POST, 'recipe_id' );
    $type = get_post_type_object( 'recipe' );
    if ( ! $post_id || 'recipe' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( $type->cap->publish_posts ) ) {
        wp_send_json_error( __( 'Недостаточно прав для публикации рецепта.', 'cooklook' ), 403 );
    }
    $texts = isset( $_POST['steps_texts'] ) ? wp_unslash( $_POST['steps_texts'] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- cooklook_updated_recipe_steps validates every row before writing.
    $times = array();
    foreach ( array( 'recipe_time', 'recipe_prep' ) as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            $value = cooklook_request_text( $_POST, $key );
            if ( ! ctype_digit( $value ) || (int) $value > 525600 ) {
                wp_send_json_error( __( 'Время должно быть целым неотрицательным числом минут.', 'cooklook' ), 400 );
            }
            $times[ $key ] = (int) $value;
        }
    }
    $result = cooklook_with_lock( 'recipe-content-' . $post_id, function () use ( $post_id, $texts, $times ) {
        wp_cache_delete( $post_id, 'posts' );
        wp_cache_delete( $post_id, 'post_meta' );
        if ( 'draft' !== get_post_status( $post_id ) ) {
            return new WP_Error( 'conflict', __( 'Рецепт уже изменён. Загрузите другой черновик.', 'cooklook' ) );
        }
        $steps = cooklook_updated_recipe_steps( cooklook_recipe_rows( $post_id, 'recipe_step' ), $texts );
        if ( is_wp_error( $steps ) ) { return $steps; }
        carbon_set_post_meta( $post_id, 'recipe_step', $steps );
        foreach ( $times as $key => $value ) {
            carbon_set_post_meta( $post_id, $key, $value );
        }
        $published = wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ), true );
        if ( is_wp_error( $published ) ) {
            return new WP_Error( 'publish_failed', __( 'Шаги сохранены, но публикация не удалась.', 'cooklook' ) );
        }
        return __( 'Рецепт опубликован.', 'cooklook' );
    } );
    if ( is_wp_error( $result ) ) {
        $status = in_array( $result->get_error_code(), array( 'busy', 'conflict' ), true ) ? 409 : ( 'invalid_steps' === $result->get_error_code() ? 400 : 500 );
        wp_send_json_error( $result->get_error_message(), $status );
    }
    wp_send_json_success( $result );
}
add_action( 'wp_ajax_save_recipe_steps', 'save_recipe_steps' );
