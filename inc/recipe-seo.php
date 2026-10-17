<?php
/** Recipe data quality shared by markup, the editor and the read-only report. */
defined( 'ABSPATH' ) || exit;

function cooklook_recipe_image_sizes() {
	return array(
		'cooklook-recipe-square' => array( 1200, 1200 ),
		'cooklook-recipe-standard' => array( 1200, 900 ),
		'cooklook-recipe-wide' => array( 1200, 675 ),
	);
}

function cooklook_register_recipe_image_sizes() {
	foreach ( cooklook_recipe_image_sizes() as $name => $size ) {
		add_image_size( $name, $size[0], $size[1], true );
	}
}
add_action( 'after_setup_theme', 'cooklook_register_recipe_image_sizes' );

function cooklook_recipe_image_url( $url ) {
	if ( ! is_string( $url ) ) {
		return '';
	}
	$url = esc_url_raw( $url, array( 'http', 'https' ) );
	$parts = wp_parse_url( $url );
	if ( ! $parts || empty( $parts['host'] ) || empty( $parts['scheme'] ) || ! in_array( $parts['scheme'], array( 'http', 'https' ), true ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
		return '';
	}
	if ( preg_match( '~(?:^|/)no_image(?:-\d+x\d+)?\.png$~i', isset( $parts['path'] ) ? $parts['path'] : '' ) ) {
		return '';
	}
	return $url;
}

/** Zero means WordPress has no reliable dimensions; do not guess from the URL. */
function cooklook_recipe_image_area( $image ) {
	if ( ! is_array( $image ) || ! isset( $image[1], $image[2] ) || ! is_numeric( $image[1] ) || ! is_numeric( $image[2] ) || $image[1] <= 0 || $image[2] <= 0 ) {
		return 0;
	}
	return (float) $image[1] * (float) $image[2];
}

/** Only use generated crops; WordPress falls back to the original for missing sizes. */
function cooklook_recipe_image_variants( $attachment_id ) {
	$metadata = wp_get_attachment_metadata( $attachment_id );
	$images = array();
	foreach ( cooklook_recipe_image_sizes() as $name => $size ) {
		$crop = isset( $metadata['sizes'][ $name ] ) ? $metadata['sizes'][ $name ] : array();
		if ( empty( $crop['width'] ) || empty( $crop['height'] ) || $crop['width'] * $crop['height'] < 50000 || abs( $crop['width'] / $crop['height'] - $size[0] / $size[1] ) > 0.02 ) {
			continue;
		}
		$image = wp_get_attachment_image_src( $attachment_id, $name );
		$url = $image ? cooklook_recipe_image_url( $image[0] ) : '';
		if ( $url ) {
			$images[ $name ] = $url;
		}
	}
	return $images;
}

/** The featured image must be a photograph of the finished dish, selected by an editor. */
function cooklook_recipe_images( $post_id ) {
	$id = get_post_thumbnail_id( $post_id );
	if ( ! $id || ! wp_attachment_is_image( $id ) ) {
		return array();
	}
	$image = wp_get_attachment_image_src( $id, 'full' );
	$url = $image ? cooklook_recipe_image_url( $image[0] ) : '';
	if ( ! $url ) {
		return array();
	}
	return array_values( array_unique( array_merge( array( $url ), cooklook_recipe_image_variants( $id ) ) ) );
}

function cooklook_recipe_step_image( $step ) {
	return ! empty( $step['recipe_step_text'] ) && isset( $step['recipe_step_image'] )
		? cooklook_recipe_image_url( $step['recipe_step_image'] ) : '';
}

function cooklook_recipe_review_issues( $post_id ) {
	$issues = array();
	$image_id = get_post_thumbnail_id( $post_id );
	$image = $image_id && wp_attachment_is_image( $image_id ) ? wp_get_attachment_image_src( $image_id, 'full' ) : false;
	$area = cooklook_recipe_image_area( $image );
	if ( ! cooklook_recipe_images( $post_id ) ) {
		$issues['missing_dish_image'] = __( 'Добавьте фотографию готового блюда как изображение записи. До этого разметка Recipe не выводится.', 'cooklook' );
	} else {
		if ( ! $area ) {
			$issues['unknown_image_dimensions'] = __( 'В метаданных фотографии блюда нет размеров. Проверьте исходник и восстановите метаданные медиатеки: Google рекомендует для Recipe изображения площадью не менее 50 000 пикселей. Существующий URL сохранён в разметке.', 'cooklook' );
		} elseif ( $area < 50000 ) {
			$issues['undersized_dish_image'] = __( 'Фотография готового блюда меньше рекомендуемых Google 50 000 пикселей по площади. По возможности добавьте качественный исходник большего размера без искусственного увеличения. Существующий URL сохранён в разметке Recipe.', 'cooklook' );
		}
		if ( count( cooklook_recipe_image_variants( $image_id ) ) < 3 ) {
			$issues['missing_image_crops'] = __( 'Проверьте варианты фотографии 1:1, 4:3 и 16:9; создавайте их только из подходящего по размеру исходника.', 'cooklook' );
		}
	}
	foreach ( array( 'recipe_calories', 'recipe_protein', 'recipe_fat', 'recipe_carbs' ) as $key ) {
		if ( '' !== (string) cooklook_recipe_value( $post_id, $key ) ) {
			$issues['nutrition_unverified'] = __( 'Проверьте единицы пищевой ценности и массу порции. Исторические значения на 100 г исключены из Recipe; автоматического пересчёта нет.', 'cooklook' );
			break;
		}
	}
	$facts = cooklook_recipe_facts( $post_id );
	if ( ! $facts['ingredients'] ) {
		$issues['missing_ingredients'] = __( 'Заполните ингредиенты.', 'cooklook' );
	}
	if ( ! $facts['steps'] ) {
		$issues['missing_steps'] = __( 'Заполните инструкции приготовления.', 'cooklook' );
	}
	$post = get_post( $post_id );
	if ( $post && '' === cooklook_recipe_plain_text( $post->post_excerpt ) ) {
		$issues['missing_editorial_summary'] = __( 'Добавьте в отрывок краткое описание блюда и результата на основе рецепта. Не копируйте первый шаг.', 'cooklook' );
	}
	foreach ( array( 'recipe_time', 'recipe_prep', 'recipe_portions', 'recipe_serving_mass' ) as $key ) {
		$value = trim( (string) cooklook_recipe_value( $post_id, $key ) );
		if ( '' !== $value && '0' !== $value && ! cooklook_recipe_positive_integer( $value ) ) {
			$issues['invalid_recipe_facts'] = __( 'Проверьте время, число порций и массу порции: нужны положительные целые значения в указанных единицах. Некорректные значения не выводятся.', 'cooklook' );
		}
	}
	return $issues;
}

/** Advisory only: never change publication status or overwrite imported values. */
function cooklook_recipe_seo_editor_notice() {
	$screen = get_current_screen();
	global $post;
	if ( ! $screen || 'post' !== $screen->base || 'recipe' !== $screen->post_type || ! $post || ! current_user_can( 'edit_post', $post->ID ) ) {
		return;
	}
	$issues = cooklook_recipe_review_issues( $post->ID );
	if ( $issues ) {
		echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Проверка рецепта перед публикацией', 'cooklook' ) . '</strong></p><ul>';
		foreach ( $issues as $message ) {
			echo '<li>' . esc_html( $message ) . '</li>';
		}
		echo '</ul></div>';
	}
}
add_action( 'admin_notices', 'cooklook_recipe_seo_editor_notice' );
