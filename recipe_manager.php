<?php
/** Admin workflow for editing and publishing existing draft recipes. */
defined( 'ABSPATH' ) || exit;

function recipe_manager_menu() {
	add_menu_page( 'Recipe Manager', 'Recipe Manager', 'manage_options', 'recipe-manager', 'recipe_manager_page' );
}
add_action( 'admin_menu', 'recipe_manager_menu' );

function cooklook_manager_attempt_key() {
	global $wpdb;
	return $wpdb->prefix . 'cooklook_manager_last_publication';
}

/** Recover the last server-side attempt even when the browser lost its AJAX response. */
function cooklook_manager_last_publication() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return '';
	}
	$attempt = get_user_meta( get_current_user_id(), cooklook_manager_attempt_key(), true );
	if ( ! is_array( $attempt ) || empty( $attempt['post_id'] ) || empty( $attempt['token'] ) || empty( $attempt['started_at'] ) ) {
		return '';
	}
	$post_id = (int) $attempt['post_id'];
	if ( 'recipe' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return '';
	}
	$status = get_post_status( $post_id );
	if ( 'publish' === $status ) {
		$message = $attempt['token'] === get_post_meta( $post_id, '_cooklook_manager_publication_attempt', true )
			? __( 'Рецепт опубликован.', 'cooklook' )
			: __( 'Текущий статус в WordPress — опубликован. Подтверждение этой попытки отсутствует.', 'cooklook' );
	} elseif ( 'draft' === $status ) {
		$message = __( 'Текущий статус в WordPress — черновик. Публикация не подтверждена.', 'cooklook' );
	} else {
		$message = __( 'Публикация этой попытки не подтверждена.', 'cooklook' );
	}
	return sprintf(
		/* translators: %1$s: date and time; %2$d: recipe ID; %3$s: recipe title; %4$s: publication result. */
		__( 'Последняя попытка публикации (%1$s). ID %2$d — %3$s. %4$s', 'cooklook' ),
		wp_date( 'd.m.Y H:i:s', (int) $attempt['started_at'] ),
		$post_id,
		get_the_title( $post_id ),
		$message
	);
}

function cooklook_manager_scripts( $hook ) {
	if ( 'toplevel_page_recipe-manager' !== $hook ) {
		return;
	}
	cooklook_enqueue_script( 'cooklook-manager', 'static/js/recipe-manager.js' );
	wp_localize_script( 'cooklook-manager', 'cooklookManager', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'cooklook_manager' ),
		'user_id' => get_current_user_id(),
		'last_publication' => cooklook_manager_last_publication(),
	) );
}
add_action( 'admin_enqueue_scripts', 'cooklook_manager_scripts' );

function recipe_manager_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Недостаточно прав.', 'cooklook' ) );
	}
	$counts = wp_count_posts( 'recipe' );
	?>
	<div class="wrap cooklook-recipe-manager">
		<h1><?php esc_html_e( 'Управление рецептами', 'cooklook' ); ?></h1>
		<p><?php esc_html_e( 'Черновиков:', 'cooklook' ); ?> <span id="manager-draft-count"><?php echo esc_html( $counts->draft ); ?></span> | <?php esc_html_e( 'Опубликовано:', 'cooklook' ); ?> <span id="manager-published-count"><?php echo esc_html( $counts->publish ); ?></span></p>
		<p id="manager-publication" role="status" hidden></p>
		<div id="manager-message" role="status"></div>
		<div id="recipe-info">
			<img id="recipe-image" alt="" width="100" hidden>
			<h2 id="recipe-title"></h2><p id="recipe-ingredients"></p>
		</div>
		<form id="editable-steps-form">
			<input type="hidden" name="recipe_id" id="current-recipe-id">
			<div id="recipe_time"></div>
			<table class="widefat cooklook-manager-steps"><thead><tr><th scope="col">Оригинальные шаги</th><th scope="col">Редактируемые шаги</th></tr></thead><tbody id="steps-table-body"></tbody></table>
			<p><button type="submit" id="save-steps" class="button button-primary" disabled>Сохранить и опубликовать</button>
			<button type="button" id="skip-recipe" class="button" disabled>Пропустить</button>
			<span id="manager-retry-action" hidden><button type="button" id="retry-recipe" class="button">Повторить загрузку</button></span>
			<span id="manager-restart-action" hidden><button type="button" id="restart-recipe-pass" class="button">Начать просмотр сначала</button></span></p>
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

function cooklook_manager_draft_after_id( $where, $query ) {
	$after_id = (int) $query->get( 'cooklook_manager_after_id' );
	if ( $after_id <= 0 ) {
		return $where;
	}
	global $wpdb;
	return $where . $wpdb->prepare( " AND {$wpdb->posts}.ID > %d", $after_id );
}
add_filter( 'posts_where', 'cooklook_manager_draft_after_id', 10, 2 );

function cooklook_manager_find_draft( $after_id ) {
	$query = new WP_Query( array(
		'post_type' => 'recipe', 'post_status' => 'draft', 'posts_per_page' => 1,
		'orderby' => null === $after_id ? 'rand' : 'ID', 'order' => 'ASC', 'no_found_rows' => true,
		'cooklook_manager_after_id' => $after_id,
		'meta_query' => array( array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ) ),
	) );
	return $query->posts[0] ?? null;
}

function get_random_draft_recipe() {
	cooklook_manager_authorize();
	// Older clients omit the cursor and keep the original random selection.
	$after_id = isset( $_POST['after_id'] ) ? cooklook_request_id( $_POST, 'after_id' ) : null;
	$post = cooklook_manager_find_draft( $after_id );
	if ( ! $post ) {
		if ( $after_id && cooklook_manager_find_draft( 0 ) ) {
			wp_send_json_success( array(
				'pass_complete' => true,
				'message' => __( 'Конец текущего прохода. В списке остались более ранние черновики.', 'cooklook' ),
			) );
		}
		wp_send_json_error( __( 'Нет подходящих черновиков.', 'cooklook' ) );
	}
	if ( ! current_user_can( 'edit_post', $post->ID ) ) {
		wp_send_json_error( __( 'Недостаточно прав.', 'cooklook' ), 403 );
	}
	wp_send_json_success( array(
		'ID' => $post->ID, 'title' => get_the_title( $post ),
		'original_steps' => wp_list_pluck( cooklook_recipe_rows( $post->ID, 'original_recipe_step' ), 'original_recipe_step_text' ),
		'steps' => wp_list_pluck( cooklook_recipe_rows( $post->ID, 'recipe_step' ), 'recipe_step_text' ),
		'ingredients' => wp_list_pluck( cooklook_recipe_ingredients( $post->ID ), 'text' ),
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

        $attempt = array(
            'post_id' => $post_id,
            'token' => wp_generate_uuid4(),
            'started_at' => time(),
        );
        if ( ! update_user_meta( get_current_user_id(), cooklook_manager_attempt_key(), $attempt ) ) {
            return new WP_Error( 'attempt_failed', __( 'Не удалось записать попытку публикации. Рецепт остаётся черновиком.', 'cooklook' ) );
        }

        carbon_set_post_meta( $post_id, 'recipe_step', $steps );
        foreach ( $times as $key => $value ) {
            carbon_set_post_meta( $post_id, $key, $value );
        }
        $published = wp_update_post( array(
            'ID' => $post_id,
            'post_status' => 'publish',
            'meta_input' => array( '_cooklook_manager_publication_attempt' => $attempt['token'] ),
        ), true );
        if ( is_wp_error( $published ) || 'publish' !== get_post_status( $post_id ) ) {
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
