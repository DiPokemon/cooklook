<?php
use Carbon_Fields\Container;
use Carbon_Fields\Field;

Container::make( 'post_meta', 'Custom Data' )
    ->where( 'post_type', '=', 'post' )
    ->add_fields( array(
        Field::make( 'text', 'cook_time', __( 'Cook time', 'cooklook' ) )                    
        ->set_width(25),
    ));