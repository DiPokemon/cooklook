<?php
/** Native Settings API: options.php owns the capability and nonce checks. */
defined( 'ABSPATH' ) || exit;

function cooklook_ads_admin_menu() {
	add_menu_page( __( 'Рекламные места', 'cooklook' ), __( 'Реклама', 'cooklook' ), 'manage_options', 'cooklook-advertising', 'cooklook_ads_admin_page', 'dashicons-megaphone', 61 );
}
add_action( 'admin_menu', 'cooklook_ads_admin_menu' );

function cooklook_ads_register_settings() {
	register_setting( 'cooklook_advertising', 'cooklook_ads', array( 'type' => 'array', 'sanitize_callback' => 'cooklook_ads_sanitize', 'show_in_rest' => false ) );
}
add_action( 'admin_init', 'cooklook_ads_register_settings' );

function cooklook_ads_setting_error( $name, $message, $type = 'error' ) {
	$slots = cooklook_ad_slots();
	add_settings_error( 'cooklook_ads', 'cooklook_ads_' . $name . '_' . md5( $message ), $slots[ $name ]['title'] . ': ' . $message, $type );
}

/** Reject executable code, foreign fields and invalid images without erasing a working ad. */
function cooklook_ads_sanitize( $input ) {
	$settings = cooklook_ads_settings();
	if ( ! current_user_can( 'manage_options' ) || ! is_array( $input ) ) { return $settings; }
	foreach ( array( 'enabled', 'banner_enabled', 'yandex_enabled' ) as $key ) {
		if ( isset( $input[ $key ] ) ) { $settings[ $key ] = in_array( $input[ $key ], array( true, 1, '1' ), true ); }
	}
	foreach ( cooklook_ad_slots() as $name => $slot ) {
		if ( ! isset( $input['slots'][ $name ] ) || ! is_array( $input['slots'][ $name ] ) ) { continue; }
		$raw = $input['slots'][ $name ];
		$ad = $settings['slots'][ $name ];
		if ( isset( $raw['type'] ) && in_array( $raw['type'], array( 'off', 'banner', 'yandex' ), true ) ) { $ad['type'] = $raw['type']; }
		if ( isset( $raw['image_source'] ) && in_array( $raw['image_source'], array( 'theme', 'media', 'none' ), true ) ) {
			$source = $raw['image_source'];
			$id = isset( $raw['image_id'] ) && is_scalar( $raw['image_id'] ) ? absint( $raw['image_id'] ) : 0;
			if ( ( 'media' === $source && ( ! $id || ! wp_attachment_is_image( $id ) || 'trash' === get_post_status( $id ) ) ) || ( 'theme' === $source && ! isset( $slot['banner'] ) ) ) {
				cooklook_ads_setting_error( $name, __( 'Выберите изображение из медиатеки. Прежняя картинка сохранена.', 'cooklook' ) );
			} else {
				$ad['image_source'] = $source;
				$ad['image_id'] = 'media' === $source ? $id : 0;
			}
		}
		if ( isset( $raw['url'] ) && is_string( $raw['url'] ) ) {
			$url = cooklook_ad_url( $raw['url'] );
			if ( '' !== trim( $raw['url'] ) && ! $url ) {
				cooklook_ads_setting_error( $name, __( 'Ссылка должна начинаться с http:// или https://. Прежняя ссылка сохранена.', 'cooklook' ) );
			} else { $ad['url'] = $url; }
		}
		if ( isset( $raw['alt'] ) && is_string( $raw['alt'] ) ) { $ad['alt'] = sanitize_text_field( $raw['alt'] ); }
		if ( isset( $raw['new_tab'] ) ) { $ad['new_tab'] = in_array( $raw['new_tab'], array( true, 1, '1' ), true ); }
		if ( isset( $raw['yandex_id'] ) && is_string( $raw['yandex_id'] ) ) {
			$id = trim( $raw['yandex_id'] );
			if ( '' === $id || cooklook_ad_valid_yandex_id( $id ) ) { $ad['yandex_id'] = $id; }
			else { cooklook_ads_setting_error( $name, __( 'Укажите ID блока вида R-A-123456-1 или R-I-123456-1, без HTML и JavaScript. Прежний ID сохранён.', 'cooklook' ) ); }
		}
		if ( 'yandex' === $ad['type'] && ! cooklook_ad_valid_yandex_id( $ad['yandex_id'] ) ) {
			cooklook_ads_setting_error( $name, __( 'Для включения РСЯ нужен ID блока. Прежний режим сохранён.', 'cooklook' ) );
			$ad['type'] = $settings['slots'][ $name ]['type'];
			$ad['yandex_id'] = $settings['slots'][ $name ]['yandex_id'];
		}
		if ( 'banner' === $ad['type'] && 'none' === $ad['image_source'] ) {
			cooklook_ads_setting_error( $name, __( 'Картинка не выбрана: место не будет показываться, пока вы не добавите баннер.', 'cooklook' ), 'warning' );
		}
		$settings['slots'][ $name ] = $ad;
	}
	return $settings;
}

function cooklook_ads_admin_assets( $hook ) {
	if ( 'toplevel_page_cooklook-advertising' !== $hook || ! current_user_can( 'manage_options' ) ) { return; }
	wp_enqueue_media();
	cooklook_enqueue_script( 'cooklook-advertising-admin', 'static/js/advertising-admin.js', array( 'jquery', 'media-views' ) );
	wp_localize_script( 'cooklook-advertising-admin', 'cooklookAdsAdmin', array(
		'title' => __( 'Выберите или загрузите баннер', 'cooklook' ), 'button' => __( 'Использовать баннер', 'cooklook' ),
	) );
	wp_enqueue_style( 'cooklook-advertising-admin', get_template_directory_uri() . '/static/css/advertising-admin.css', array(), cooklook_asset_version( 'static/css/advertising-admin.css' ) );
}
add_action( 'admin_enqueue_scripts', 'cooklook_ads_admin_assets' );

function cooklook_ads_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Недостаточно прав.', 'cooklook' ) ); }
	$settings = cooklook_ads_settings();
	$groups = array( 'home' => __( 'Главная', 'cooklook' ), 'catalog' => __( 'Каталог', 'cooklook' ), 'recipe' => __( 'Страница рецепта', 'cooklook' ) );
	?>
	<div class="wrap cooklook-ads-admin">
		<h1><?php esc_html_e( 'Рекламные места', 'cooklook' ); ?></h1>
		<p><?php esc_html_e( 'Выберите, что показывать в каждом месте. Настройки баннера и РСЯ сохраняются при переключении режима.', 'cooklook' ); ?></p>
		<?php settings_errors(); ?>
		<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post" novalidate>
			<?php settings_fields( 'cooklook_advertising' ); ?>
			<div class="cooklook-ads-global">
				<h2><?php esc_html_e( 'Общие настройки', 'cooklook' ); ?></h2>
				<?php foreach ( array( 'enabled' => __( 'Показывать рекламу на сайте', 'cooklook' ), 'banner_enabled' => __( 'Разрешить баннеры', 'cooklook' ), 'yandex_enabled' => __( 'Разрешить Яндекс РСЯ', 'cooklook' ) ) as $key => $label ) : ?>
					<input type="hidden" name="cooklook_ads[<?php echo esc_attr( $key ); ?>]" value="0">
					<label><input type="checkbox" name="cooklook_ads[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $settings[ $key ] ); ?>> <?php echo esc_html( $label ); ?></label>
				<?php endforeach; ?>
				<p class="description"><?php esc_html_e( 'Общий выключатель скрывает все места. Запрет типа скрывает места с этим типом; их настройки остаются сохранёнными.', 'cooklook' ); ?></p>
			</div>
			<?php submit_button( __( 'Сохранить рекламу', 'cooklook' ) ); ?>
			<nav class="cooklook-ads-nav" aria-label="<?php esc_attr_e( 'Разделы рекламы', 'cooklook' ); ?>">
				<?php foreach ( $groups as $key => $label ) : ?><a class="button" href="#ads-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></a> <?php endforeach; ?>
			</nav>
			<?php foreach ( $groups as $group => $label ) : ?>
				<h2 id="ads-<?php echo esc_attr( $group ); ?>"><?php echo esc_html( $label ); ?></h2>
				<div class="cooklook-ads-grid">
					<?php foreach ( cooklook_ad_slots() as $name => $slot ) {
						if ( $slot['group'] === $group ) { cooklook_ads_admin_slot( $name, $slot, $settings['slots'][ $name ] ); }
					} ?>
				</div>
			<?php endforeach; ?>
			<?php submit_button( __( 'Сохранить рекламу', 'cooklook' ) ); ?>
			<p class="description"><?php esc_html_e( 'После сохранения очистите кеш страниц, если на сайте включено кеширование.', 'cooklook' ); ?></p>
		</form>
	</div>
	<?php
}

function cooklook_ads_admin_slot( $name, $slot, $ad ) {
	$prefix = 'cooklook_ads[slots][' . $name . ']';
	$base = 'cooklook-ad-' . $name;
	$default_url = isset( $slot['banner'] ) ? get_template_directory_uri() . '/static/img/adv/' . $slot['banner'] . '.jpg' : '';
	$preview = 'theme' === $ad['image_source'] ? $default_url : ( 'media' === $ad['image_source'] ? wp_get_attachment_image_url( $ad['image_id'], 'medium' ) : '' );
	?>
	<section class="cooklook-ads-card" aria-labelledby="<?php echo esc_attr( $base ); ?>-title" data-default-image="<?php echo esc_url( $default_url ); ?>">
		<h3 id="<?php echo esc_attr( $base ); ?>-title"><?php echo esc_html( $slot['title'] ); ?></h3>
		<p class="description"><?php echo esc_html( $slot['description'] ); ?></p>
		<p><label for="<?php echo esc_attr( $base ); ?>-type"><?php esc_html_e( 'Что показывать', 'cooklook' ); ?></label>
			<select id="<?php echo esc_attr( $base ); ?>-type" name="<?php echo esc_attr( $prefix ); ?>[type]" data-ad-type>
				<?php foreach ( array( 'off' => __( 'Выключено', 'cooklook' ), 'banner' => __( 'Баннер: картинка и ссылка', 'cooklook' ), 'yandex' => __( 'Яндекс РСЯ', 'cooklook' ) ) as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $ad['type'], $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<div data-ad-fields="banner" <?php echo 'banner' === $ad['type'] ? '' : 'hidden'; ?>>
			<div class="cooklook-ads-preview"><img data-ad-preview <?php if ( $preview ) : ?>src="<?php echo esc_url( $preview ); ?>"<?php endif; ?> alt="<?php esc_attr_e( 'Предпросмотр баннера', 'cooklook' ); ?>" loading="lazy" decoding="async" <?php echo $preview ? '' : 'hidden'; ?>><span data-ad-empty <?php echo $preview ? 'hidden' : ''; ?>><?php esc_html_e( 'Картинка не выбрана', 'cooklook' ); ?></span></div>
			<input type="hidden" data-ad-image-id name="<?php echo esc_attr( $prefix ); ?>[image_id]" value="<?php echo esc_attr( $ad['image_id'] ); ?>">
			<input type="hidden" data-ad-image-source name="<?php echo esc_attr( $prefix ); ?>[image_source]" value="<?php echo esc_attr( $ad['image_source'] ); ?>">
			<p class="cooklook-ads-image-actions">
				<button type="button" class="button" data-ad-choose><?php esc_html_e( 'Выбрать / загрузить', 'cooklook' ); ?></button>
				<button type="button" class="button" data-ad-remove><?php esc_html_e( 'Убрать картинку', 'cooklook' ); ?></button>
				<?php if ( $default_url ) : ?><button type="button" class="button-link" data-ad-default><?php esc_html_e( 'Картинка из темы', 'cooklook' ); ?></button><?php endif; ?>
			</p>
			<p><label for="<?php echo esc_attr( $base ); ?>-url"><?php esc_html_e( 'Ссылка при нажатии', 'cooklook' ); ?></label><input type="url" id="<?php echo esc_attr( $base ); ?>-url" name="<?php echo esc_attr( $prefix ); ?>[url]" value="<?php echo esc_attr( $ad['url'] ); ?>" placeholder="https://example.com/" aria-describedby="<?php echo esc_attr( $base ); ?>-url-help"></p>
			<p class="description" id="<?php echo esc_attr( $base ); ?>-url-help"><?php esc_html_e( 'Можно указать ссылку с UTM и erid. Пустое поле показывает картинку без ссылки.', 'cooklook' ); ?></p>
			<p><label for="<?php echo esc_attr( $base ); ?>-alt"><?php esc_html_e( 'Описание картинки (alt)', 'cooklook' ); ?></label><input type="text" id="<?php echo esc_attr( $base ); ?>-alt" name="<?php echo esc_attr( $prefix ); ?>[alt]" value="<?php echo esc_attr( $ad['alt'] ); ?>"></p>
			<input type="hidden" name="<?php echo esc_attr( $prefix ); ?>[new_tab]" value="0">
			<p><label class="cooklook-ads-checkbox"><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[new_tab]" value="1" <?php checked( $ad['new_tab'] ); ?>> <?php esc_html_e( 'Открывать ссылку в новой вкладке', 'cooklook' ); ?></label></p>
		</div>
		<div data-ad-fields="yandex" <?php echo 'yandex' === $ad['type'] ? '' : 'hidden'; ?>>
			<p><label for="<?php echo esc_attr( $base ); ?>-yandex"><?php esc_html_e( 'ID блока РСЯ', 'cooklook' ); ?></label><input type="text" id="<?php echo esc_attr( $base ); ?>-yandex" name="<?php echo esc_attr( $prefix ); ?>[yandex_id]" value="<?php echo esc_attr( $ad['yandex_id'] ); ?>" placeholder="R-A-123456-1" aria-describedby="<?php echo esc_attr( $base ); ?>-yandex-help"></p>
			<p class="description" id="<?php echo esc_attr( $base ); ?>-yandex-help"><?php esc_html_e( 'Вставьте только blockId из кабинета РСЯ. Используйте обычный блок для размещения в контейнере, а не полноэкранный или плавающий формат. Загрузчик подключается автоматически.', 'cooklook' ); ?></p>
		</div>
		<p data-ad-fields="off" class="description" <?php echo 'off' === $ad['type'] ? '' : 'hidden'; ?>><?php esc_html_e( 'Это место скрыто. Картинка, ссылка и ID блока сохранены.', 'cooklook' ); ?></p>
	</section>
	<?php
}
