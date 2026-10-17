<?php
/** Visible recipe facts; all values come from the same model as JSON-LD. */
defined( 'ABSPATH' ) || exit;
$facts = $args['facts'];
$post_id = $args['post_id'];
$editorial = $args['editorial'];
?>
<?php if ( $facts['cook'] || $facts['prep'] || $facts['portions'] || $facts['serving_mass'] || $facts['cuisine'] ) : ?>
<dl class="recipe_facts">
    <?php foreach ( array( 'prep' => __( 'Подготовка', 'cooklook' ), 'cook' => __( 'Приготовление', 'cooklook' ), 'total' => __( 'Общее время', 'cooklook' ) ) as $key => $label ) : ?>
        <?php if ( $facts[ $key ] ) : ?>
            <div><dt><?php echo esc_html( $label ); ?></dt><dd><time datetime="<?php echo esc_attr( schema_time( $facts[ $key ] ) ); ?>"><?php echo esc_html( sprintf( __( '%d мин', 'cooklook' ), $facts[ $key ] ) ); ?></time></dd></div>
        <?php endif; ?>
    <?php endforeach; ?>
    <?php if ( $facts['portions'] ) : ?>
        <div><dt><?php esc_html_e( 'Количество порций', 'cooklook' ); ?></dt><dd><?php echo esc_html( $facts['portions'] ); ?></dd></div>
    <?php endif; ?>
    <?php if ( $facts['serving_mass'] ) : ?>
        <div><dt><?php esc_html_e( 'Масса порции', 'cooklook' ); ?></dt><dd><?php echo esc_html( sprintf( __( '%d г', 'cooklook' ), $facts['serving_mass'] ) ); ?></dd></div>
    <?php endif; ?>
    <?php if ( $facts['cuisine'] ) : ?>
        <div><dt><?php esc_html_e( 'Кухня', 'cooklook' ); ?></dt><dd><?php echo esc_html( $facts['cuisine'] ); ?></dd></div>
    <?php endif; ?>
</dl>
<?php endif; ?>
<div class="recipe_editorial">
    <?php foreach ( array( 'author' => __( 'Автор рецепта', 'cooklook' ), 'reviewer' => __( 'Проверяющий', 'cooklook' ) ) as $key => $label ) : ?>
        <?php if ( $editorial[ $key ] ) : ?><p><?php echo esc_html( $label . ': ' . $editorial[ $key ] ); ?></p><?php endif; ?>
    <?php endforeach; ?>
    <p><?php esc_html_e( 'Опубликовано:', 'cooklook' ); ?> <time datetime="<?php echo esc_attr( get_the_date( 'c', $post_id ) ); ?>"><?php echo esc_html( get_the_date( '', $post_id ) ); ?></time></p>
    <?php if ( get_the_modified_date( 'Y-m-d', $post_id ) !== get_the_date( 'Y-m-d', $post_id ) ) : ?>
        <p><?php esc_html_e( 'Запись обновлена:', 'cooklook' ); ?> <time datetime="<?php echo esc_attr( get_the_modified_date( 'c', $post_id ) ); ?>"><?php echo esc_html( get_the_modified_date( '', $post_id ) ); ?></time></p>
    <?php endif; ?>
    <?php foreach ( array( 'adaptation_notes' => __( 'Что изменено в адаптации', 'cooklook' ), 'change_notes' => __( 'Последнее содержательное изменение', 'cooklook' ) ) as $key => $label ) : ?>
        <?php if ( $editorial[ $key ] ) : ?><p><?php echo esc_html( $label . ': ' . $editorial[ $key ] ); ?></p><?php endif; ?>
    <?php endforeach; ?>
</div>
<?php if ( $facts['ingredients'] || $facts['steps'] ) : ?>
    <nav class="recipe_jump_links" aria-label="<?php esc_attr_e( 'Разделы рецепта', 'cooklook' ); ?>">
        <?php if ( $facts['ingredients'] ) : ?><a href="#ingredients"><?php esc_html_e( 'Ингредиенты', 'cooklook' ); ?></a><?php endif; ?>
        <?php if ( $facts['steps'] ) : ?><a href="#instructions"><?php esc_html_e( 'Приготовление', 'cooklook' ); ?></a><?php endif; ?>
    </nav>
<?php endif; ?>
