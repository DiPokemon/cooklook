<?php
/** Update existing recipes by their external recipe_id; never create duplicates. */
defined( 'ABSPATH' ) || exit;

function cooklook_recipe_importer_menu() {
	add_menu_page( 'Обновление рецептов', 'Обновление рецептов', 'manage_options', 'cooklook-recipe-importer', 'cooklook_recipe_importer_page' );
}
add_action( 'admin_menu', 'cooklook_recipe_importer_menu' );

function cooklook_importer_scripts( $hook ) {
	if ( 'toplevel_page_cooklook-recipe-importer' !== $hook ) {
		return;
	}
	cooklook_enqueue_script( 'cooklook-importer', 'static/js/recipe-importer.js' );
}
add_action( 'admin_enqueue_scripts', 'cooklook_importer_scripts' );

function cooklook_recipe_importer_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Недостаточно прав.', 'cooklook' ) );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Обновление рецептов из CSV', 'cooklook' ); ?></h1>
		<p>CSV в UTF-8 с колонкой recipe_id; до 2 МБ и 500 строк. Обновляются только существующие рецепты. Значения списков разделяются « | ».</p>
		<form id="recipe-import-form" method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'recipe_import', 'recipe_import_nonce' ); ?>
			<input type="file" name="csv_file" accept=".csv,text/csv" required>
			<?php submit_button( __( 'Импортировать', 'cooklook' ) ); ?>
		</form>
		<div id="import-progress" role="status"></div>
	</div>
	<?php
}

function cooklook_set_thumbnail_from_url( $post_id, $url ) {
	$url = esc_url_raw( $url, array( 'http', 'https' ) );
	if ( ! $url || ! wp_http_validate_url( $url ) ) {
		return new WP_Error( 'invalid_image_url', __( 'Недопустимый адрес изображения.', 'cooklook' ) );
	}
	$current = get_post_thumbnail_id( $post_id );
	if ( $current && get_post_meta( $current, '_source_url', true ) === $url ) {
		return $current;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	// Core performs a safe HTTP request, verifies the image and removes failed temp files.
	$attachment = media_sideload_image( $url, $post_id, null, 'id' );
	if ( ! is_wp_error( $attachment ) ) {
		set_post_thumbnail( $post_id, $attachment );
	}
	return $attachment;
}

/** Parse the complete file before making any changes; do not truncate long CSV rows. */
function cooklook_read_recipe_csv( $path ) {
	$handle = fopen( $path, 'rb' );
	if ( ! $handle ) {
		return new WP_Error( 'csv_read', __( 'Не удалось открыть CSV.', 'cooklook' ) );
	}
	try {
		$header = fgetcsv( $handle, 0, ',', '"', '' );
		if ( ! is_array( $header ) ) {
			return new WP_Error( 'csv_header', __( 'CSV пуст.', 'cooklook' ) );
		}
		$header[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $header[0] );
		$header = array_map( 'trim', $header );
		if ( ! in_array( 'recipe_id', $header, true ) || count( array_unique( $header ) ) !== count( $header ) || in_array( '', $header, true ) ) {
			return new WP_Error( 'csv_header', __( 'Нужна колонка recipe_id; имена колонок должны быть непустыми и уникальными.', 'cooklook' ) );
		}
		$rows = array();
		while ( false !== ( $row = fgetcsv( $handle, 0, ',', '"', '' ) ) ) {
			if ( array( null ) === $row ) {
				continue;
			}
			if ( count( $row ) !== count( $header ) ) {
				return new WP_Error( 'csv_columns', sprintf( __( 'Неверное число колонок в строке %d.', 'cooklook' ), count( $rows ) + 2 ) );
			}
			foreach ( $row as $cell ) {
				if ( wp_check_invalid_utf8( $cell, true ) !== $cell ) {
					return new WP_Error( 'csv_encoding', __( 'Сохраните CSV в кодировке UTF-8.', 'cooklook' ) );
				}
			}
			$data = array_combine( $header, $row );
			if ( '' === trim( $data['recipe_id'] ) ) {
				return new WP_Error( 'csv_id', __( 'recipe_id не может быть пустым.', 'cooklook' ) );
			}
			$rows[] = $data;
			if ( count( $rows ) > 500 ) {
				return new WP_Error( 'csv_limit', __( 'Разделите CSV на файлы до 500 строк.', 'cooklook' ) );
			}
		}
		return $rows;
	} finally {
		fclose( $handle );
	}
}

function cooklook_import_recipe_row( $data ) {
	$ids = get_posts( array(
		'post_type' => 'recipe', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 2,
		'meta_key' => '_recipe_id', 'meta_value' => sanitize_text_field( $data['recipe_id'] ),
	) );
	if ( 1 !== count( $ids ) ) {
		return new WP_Error( 'recipe_match', __( 'Рецепт не найден или recipe_id неоднозначен.', 'cooklook' ) );
	}
	$post_id = $ids[0];
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return new WP_Error( 'forbidden', __( 'Нет прав на изменение рецепта.', 'cooklook' ) );
	}
	return cooklook_with_lock( 'recipe-content-' . $post_id, function () use ( $post_id, $data ) {
		wp_cache_delete( $post_id, 'post_meta' );
		$updates = array();
		if ( ! empty( $data['ingredients_names'] ) && isset( $data['ingredients_quantities'] ) ) {
			$names = array_map( 'trim', explode( ' | ', $data['ingredients_names'] ) );
			$quantities = array_map( 'trim', explode( ' | ', $data['ingredients_quantities'] ) );
			if ( count( $names ) !== count( $quantities ) ) {
				return new WP_Error( 'ingredients_mismatch', __( 'Число ингредиентов и количеств не совпадает.', 'cooklook' ) );
			}
			$ingredients = array();
			foreach ( $names as $index => $name ) {
				$ingredients[] = array( 'ingridient_name' => sanitize_text_field( $name ), 'ingridient_value' => sanitize_text_field( $quantities[ $index ] ) );
			}
			// These misspellings are the actual production Carbon Fields schema.
			$updates['ingridients'] = $ingredients;
		}
		if ( ! empty( $data['steps_texts'] ) ) {
			$steps = cooklook_updated_recipe_steps( cooklook_recipe_rows( $post_id, 'recipe_step' ), explode( ' | ', $data['steps_texts'] ) );
			if ( is_wp_error( $steps ) ) {
				return $steps;
			}
			$updates['recipe_step'] = $steps;
		}
		if ( ! empty( $data['original_steps_texts'] ) ) {
			$updates['original_recipe_step'] = array_map( function ( $text ) {
				return array( 'original_recipe_step_text' => sanitize_textarea_field( $text ) );
			}, explode( ' | ', $data['original_steps_texts'] ) );
		}
		// Validate/download before replacing recipe fields so a failed image leaves them intact.
		if ( ! empty( $data['image'] ) ) {
			$image = cooklook_set_thumbnail_from_url( $post_id, $data['image'] );
			if ( is_wp_error( $image ) ) {
				return new WP_Error( 'image_failed', __( 'Не удалось загрузить изображение.', 'cooklook' ) );
			}
		}
		foreach ( $updates as $key => $value ) {
			carbon_set_post_meta( $post_id, $key, $value );
		}
		return $post_id;
	} );
}

function ajax_recipe_import_from_csv() {
	cooklook_verify_ajax_nonce( 'recipe_import', 'recipe_import_nonce' );
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'upload_files' ) ) {
		wp_send_json_error( __( 'Недостаточно прав.', 'cooklook' ), 403 );
	}
	if ( ! isset( $_FILES['csv_file'] ) || ! is_array( $_FILES['csv_file'] ) ) {
		wp_send_json_error( __( 'Выберите CSV.', 'cooklook' ), 400 );
	}
	$file = $_FILES['csv_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- PHP upload metadata is validated below; never unslash a filesystem path.
	if ( ! isset( $file['error'], $file['tmp_name'], $file['size'], $file['name'] ) ||
		! is_string( $file['tmp_name'] ) || ! is_string( $file['name'] ) ||
		UPLOAD_ERR_OK !== $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ||
		$file['size'] > min( 2 * MB_IN_BYTES, wp_max_upload_size() ) ||
		'csv' !== strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) ) ) {
		wp_send_json_error( __( 'Нужен корректно загруженный CSV размером до 2 МБ.', 'cooklook' ), 400 );
	}
	$rows = cooklook_read_recipe_csv( $file['tmp_name'] );
	if ( is_wp_error( $rows ) ) {
		wp_send_json_error( $rows->get_error_message(), 400 );
	}
	$offset = cooklook_request_id( $_POST, 'offset' );
	$summary = array( 'updated' => 0, 'skipped' => 0, 'errors' => array(), 'total' => count( $rows ) );
	foreach ( array_slice( $rows, $offset, 5, true ) as $index => $data ) {
		$result = cooklook_import_recipe_row( $data );
		if ( is_wp_error( $result ) ) {
			$summary['skipped']++;
			$summary['errors'][] = sprintf( __( 'Строка %1$d: %2$s', 'cooklook' ), $index + 2, $result->get_error_message() );
		} else {
			$summary['updated']++;
		}
	}
	$summary['next_offset'] = min( count( $rows ), $offset + 5 );
	$summary['done'] = $summary['next_offset'] >= count( $rows );
	wp_send_json_success( $summary );
}
add_action( 'wp_ajax_import_recipes', 'ajax_recipe_import_from_csv' );
