<?php
/** Ingredients remain available even when a recipe has no instructions yet. */
defined( 'ABSPATH' ) || exit;
$facts = $args['facts'];
?>
<?php if ( $facts['ingredients'] || $facts['steps'] ) : ?>
    <div class="container recipe_instructions">
        <div class="recipe_instructions_wrapper recipe_directions">
            <?php if ( $facts['ingredients'] ) : ?>
                <section id="ingredients" class="recipe_instructions_right flex" aria-labelledby="ingredients-heading">
                    <h2 id="ingredients-heading"><?php echo esc_html( $facts['portions'] ? sprintf( __( 'Ингредиенты на %d порц.', 'cooklook' ), $facts['portions'] ) : __( 'Ингредиенты', 'cooklook' ) ); ?></h2>
                    <ul class="ingridients_list">
                        <?php foreach ( $facts['ingredients'] as $ingredient ) : ?>
                            <li class="ingridients_list-item"><span class="ingridients_list-item-name"><?php echo esc_html( $ingredient['name'] ); ?></span> <span class="ingridients_list-item-value"><?php echo esc_html( $ingredient['value'] ); ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
            <?php if ( $facts['steps'] ) : ?>
                <section id="instructions" class="recipe_steps" aria-labelledby="instructions-heading">
                    <h2 id="instructions-heading"><?php esc_html_e( 'Приготовление', 'cooklook' ); ?></h2>
                    <ol class="recipe_instructions_left flex" role="list">
                        <?php foreach ( $facts['steps'] as $index => $step ) : ?>
                            <li id="<?php echo esc_attr( $step['anchor'] ); ?>" class="recipe_instructions_left-step">
                                <span class="step_number"><b><?php echo esc_html( sprintf( __( 'Шаг %d', 'cooklook' ), $index + 1 ) ); ?></b> / <?php echo esc_html( count( $facts['steps'] ) ); ?></span>
                                <div class="step_description"><?php echo wp_kses_post( wpautop( $step['content'] ) ); ?></div>
                                <?php if ( $step['image'] ) : ?>
                                    <img class="recipe_step-image" src="<?php echo esc_url( $step['image'] ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Шаг %d: %s', 'cooklook' ), $index + 1, get_the_title() ) ); ?>" loading="lazy" decoding="async">
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </section>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
