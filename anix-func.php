<?php
add_action( 'wp_enqueue_scripts', 'true_child_styles' );
 
function true_child_styles() { 
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array(), null  ); 
}

add_filter( 'woocommerce_product_get_rating_html',
    function( $html, $rating, $count ) {
        /* translators: %s: rating */
        $label = sprintf( __( 'Rated %s out of 5', 'woocommerce' ), $rating );
        $html  = '<div class="star-rating" role="img" aria-label="' . esc_attr( $label ) . '">' . wc_get_star_rating_html( $rating, $count ) . '</div>';

        return $html;
    },
    10,
    3
);

/* SIGN UP FORM */

add_action( 'elementor_pro/forms/new_record',  'anix_elementor_form_create_new_user' , 10, 2 );
function anix_elementor_form_create_new_user($record,$ajax_handler)
{
    $form_name = $record->get_form_settings('form_name');
    //Check that the form is the "create new user form" if not - stop and return;
    if ('Sign up' !== $form_name) {
        return;
    }   
	
	global $sitepress;
    $current_language = $sitepress->get_current_language();    
	$form_data = $record->get_formatted_data();
	
	if ($current_language == 'en'){		
        $username=$form_data['Username']; //Get the value of the input with the label "User Name"
        $password = $form_data['Password']; //Get the value of the input with the label "Password"
        $email=$form_data['Email'];  //Get the value of the input with the label "Email"
	
	}
	elseif($current_language == 'et'){
		$username=$form_data['Kasutajanimi']; //Get the value of the input with the label "User Name"
        $password = $form_data['Parool']; //Get the value of the input with the label "Password"
        $email=$form_data['E-post'];  //Get the value of the input with the label "Email"
	}
	else {
		$username=$form_data['Имя пользователя']; //Get the value of the input with the label "User Name"
        $password = $form_data['Пароль']; //Get the value of the input with the label "Password"
        $email=$form_data['E-mail'];  //Get the value of the input with the label "Email"
	}
    
	
	
    $user = wp_create_user($username,$password,$email); // Create a new user, on success return the user_id no failure return an error object
    if (is_wp_error($user)){ // if there was an error creating a new user
        $ajax_handler->add_error_message("Failed to create new user: ".$user->get_error_message()); //add the message
        $ajax_handler->is_success = false;
        return;
    }
}

/* PRODUCTS COUNTER */

function products_counter(){
	if ( ! wc_get_loop_prop( 'is_paginated' ) || ! woocommerce_products_will_display() ) {
		return;
	}
	$args = array(
		'total'    => wc_get_loop_prop( 'total' ),
		'per_page' => wc_get_loop_prop( 'per_page' ),
		'current'  => wc_get_loop_prop( 'current_page' ),
	);

	wc_get_template( 'result-count-custom.php', $args );
}

add_shortcode('products_counter', 'products_counter');

/* ORDERING PRODUCTS CHANGE TEXTS */
add_filter('woocommerce_catalog_orderby', 'wc_customize_product_sorting');

function wc_customize_product_sorting($sorting_options){
    $sorting_options = array(
        'menu_order' => __( 'Sorting', 'woocommerce' ),
        'popularity' => __( 'Popularity', 'woocommerce' ),
        'rating'     => __( 'Average rating', 'woocommerce' ),
        'date'       => __( 'Newness', 'woocommerce' ),
        'price'      => __( 'Price &#8593;', 'woocommerce' ),
        'price-desc' => __( 'Price &#8595;', 'woocommerce' ),
    );

    return $sorting_options;
}

// add_action( 'woocommerce_shop_loop_item_title', 'woocommerce_product_loop_tags', 9 );
// function woocommerce_product_loop_tags() {
// 	global $post, $product;
// 	$terms = get_terms( 'product_tag' );
// 	$term_array = array();
// 	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
// 		foreach ( $terms as $term ) {
// 			$term_array[] = $term->name;
// 		}
// 	}	
//  	echo '<div class="product_tags">';
// 	$i = 0;
//  	foreach ($term_array as $tag){
//  		echo '<span>'.$tag.'</span>';
// 		if (++$i == 2) break;
//  	};
//  	echo '</div>';
// }


add_action('woocommerce_after_shop_loop_item', 'show_tags', 3);
function show_tags()
{
    global $product;
    // get the product_tags of the current product
    $current_tags = get_the_terms(get_the_ID() , 'product_tag');
    // only start if we have some tags
    if ($current_tags && !is_wp_error($current_tags))
    {
        echo '<p class="product_tags">';
        
        // for each tag we create an item
        foreach ($current_tags as $tag)
        {
            $tag_title = $tag->name; // tag name
            $tag_link = get_term_link($tag); // tag archive link
            $separator = ' ';
            $tagstrings[] = '<span>' . $tag_title . '</span>';
        }
        echo implode($separator, $tagstrings) . '</p>';
    }
}


	
//remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );

function product_tags_badges(){
	
	$product = new WC_Product(get_the_ID());
	$tags_array = [];
	$sale = __('Sale', 'hello-elementor');
	$instock = __('In stock', 'hello-elementor');
	$outstock = __('Out of stock', 'hello-elementor');
	if ( $product->is_on_sale() )  {    
		$tags_array[] = $sale;
	}
	else {
		return;
	};
	if ( $product->is_in_stock())  {    
		$tags_array[] = $instock;
	}
	else {
		$tags_array[] = $outstock;
	};	
	$terms = get_the_terms( $post->ID, 'product_tag' );
	$tags = array();
	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
		foreach ( $terms as $term ) {
			$tags_array[] = $term->slug;
		}
	}	
	echo '<div class="tags_badges">';
	foreach ($tags_array as $tag){		
		echo '<span>'.$tag.'</span>';		
	}
	echo '</div>';
}

add_shortcode('tags_badges', 'product_tags_badges');


function load_slick_assets(){  
	wp_enqueue_script( 'slick_main', 'https:////cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', array( 'jquery' ));
	wp_enqueue_style( 'slick_main', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css' );
}
add_action('wp_enqueue_scripts', 'load_slick_assets', 10);

/* PRODUCTS BLOCK */
function products_block(){ 
	$args = array(
		'post_type'      => 'product',
		'posts_per_page' => 8,
		'orderby' => 'rand',
	);
	$loop = new WP_Query( $args ); 
?>	
	<h2 class="products_block_title"><?php echo __('You may also like', 'hello-elementor') ?> </h2>
    <div class="products_slider_controls">
        <div class="prev">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
				<path d="M15.0013 20.67C14.8113 20.67 14.6213 20.6 14.4713 20.45L7.95125 13.93C6.89125 12.87 6.89125 11.13 7.95125 10.07L14.4713 3.55002C14.7613 3.26002 15.2413 3.26002 15.5312 3.55002C15.8212 3.84002 15.8212 4.32002 15.5312 4.61002L9.01125 11.13C8.53125 11.61 8.53125 12.39 9.01125 12.87L15.5312 19.39C15.8212 19.68 15.8212 20.16 15.5312 20.45C15.3813 20.59 15.1912 20.67 15.0013 20.67Z"/>
			</svg>
		</div>
        <div class="next">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
				<path d="M8.91156 20.67C8.72156 20.67 8.53156 20.6 8.38156 20.45C8.09156 20.16 8.09156 19.68 8.38156 19.39L14.9016 12.87C15.3816 12.39 15.3816 11.61 14.9016 11.13L8.38156 4.61002C8.09156 4.32002 8.09156 3.84002 8.38156 3.55002C8.67156 3.26002 9.15156 3.26002 9.44156 3.55002L15.9616 10.07C16.4716 10.58 16.7616 11.27 16.7616 12C16.7616 12.73 16.4816 13.42 15.9616 13.93L9.44156 20.45C9.29156 20.59 9.10156 20.67 8.91156 20.67Z"/>
			</svg>
		</div>
    </div>
	<div class="products_block">
	
<?php
    while ( $loop->have_posts() ) : 
		$loop->the_post(); 
        global $post, $product;

        $terms = get_terms( 'product_tag' );
        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
            foreach ( $terms as $term ) {
                $term_array[] = $term->name;
            }
        };
        $product_id = $product->get_id();								
		$product = wc_get_product( $product_id );								
		/* $product->get_rating_html();*/
		$rating_count = $product->get_rating_count();		
		$review_count = $product->get_review_count();
		$average      = $product->get_average_rating();
		$thumbnail = $product->get_image();
?>		
        <div class="products_block_item">
			
			<a href="<?php echo get_permalink($product_id)?>">
				<?php echo $thumbnail ?>
			</a>
            
            <?php if(wc_get_product_tag_list($product_id)) :?>
				<div class="product_tags">
					<?php echo wc_get_product_tag_list($product_id, '', '', '')	?>
				</div>
			<?php endif;?>
            <div class="product_rating">
				<?php echo wc_get_rating_html( $average, $rating_count ); ?>							
			</div>
            <div class="product_name">
				<a href="<?php echo get_permalink($product_id)?>"><?php echo $product->get_name() ?></a>				
			</div>
            <div class="product_price">
				<?php echo $product->get_price_html(); ?>
			</div>            
            <div class="product-action">
				<?php woocommerce_template_loop_add_to_cart(); ?>
			</div>
        </div>                       
        <?php 
            endwhile; 
            wp_reset_query();
        ?> 
	</div>
	<script>
		jQuery('.products_block').slick({
		    infinite: true,
		    slidesToShow: 4,
		    slidesToScroll: 1,
            prevArrow: jQuery('.prev'),
            nextArrow: jQuery('.next'),
			responsive: [
				{
				  breakpoint: 1024,
				  settings: {
					slidesToShow: 3,
				  }
				},
				{
				  breakpoint: 768,
				  settings: {
					slidesToShow: 1,					
				  }
				}
			]
		});	
	</script>
<?php 
	};
	add_shortcode('products_block', 'products_block');

/* PRODUCTS SLIDER ON MAIN PAGE */
function double_products_latest() {
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => 10,
        'orderby' => 'date',
		'post__not_in' => array(169054, 169059, 169060)
    );  
    $loop = new WP_Query($args);     
    ?>
	<div class="double_products_latest_controls products_slider_controls">
        <div class="prev">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
				<path d="M15.0013 20.67C14.8113 20.67 14.6213 20.6 14.4713 20.45L7.95125 13.93C6.89125 12.87 6.89125 11.13 7.95125 10.07L14.4713 3.55002C14.7613 3.26002 15.2413 3.26002 15.5312 3.55002C15.8212 3.84002 15.8212 4.32002 15.5312 4.61002L9.01125 11.13C8.53125 11.61 8.53125 12.39 9.01125 12.87L15.5312 19.39C15.8212 19.68 15.8212 20.16 15.5312 20.45C15.3813 20.59 15.1912 20.67 15.0013 20.67Z"/>
			</svg>
		</div>
        <div class="next">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
				<path d="M8.91156 20.67C8.72156 20.67 8.53156 20.6 8.38156 20.45C8.09156 20.16 8.09156 19.68 8.38156 19.39L14.9016 12.87C15.3816 12.39 15.3816 11.61 14.9016 11.13L8.38156 4.61002C8.09156 4.32002 8.09156 3.84002 8.38156 3.55002C8.67156 3.26002 9.15156 3.26002 9.44156 3.55002L15.9616 10.07C16.4716 10.58 16.7616 11.27 16.7616 12C16.7616 12.73 16.4816 13.42 15.9616 13.93L9.44156 20.45C9.29156 20.59 9.10156 20.67 8.91156 20.67Z"/>
			</svg>
		</div>
    </div>
    <div class="woocommerce double_products_block_latest double_products_block">
        <?php
        while ($loop->have_posts()) : 
            // Начало блока для двух товаров
            //echo '<div class="double_produts_wrapper">';

            // Обработка двух товаров за раз
            for ($i = 0; $i < 2; $i++) {
                if (!$loop->have_posts()) {
                    break;
                }

                $loop->the_post();
                global $product;

                $product_id = $product->get_id();								
				$product = wc_get_product( $product_id );								
				/* $product->get_rating_html();*/
				$rating_count = $product->get_rating_count();		
				$review_count = $product->get_review_count();
				$average      = $product->get_average_rating();
				$thumbnail = $product->get_image();

                // Вывод информации о товаре
                ?>
						<div class="products_block_item">
							<div class="add_to_wishlist">
								<?php echo do_shortcode('[yith_wcwl_add_to_wishlist]') ?>
							</div>
							<a href="<?php echo get_permalink($product_id)?>">
								<?php echo $thumbnail ?>
							</a>
							
							<?php //if(wc_get_product_tag_list($product_id)) :?>
								<div class="product_tags">
									<?php echo wc_get_product_tag_list($product_id, '', '', '')	?>
								</div>
							<?php //endif;?>
							<div class="product_rating">
								<?php echo wc_get_rating_html( $average, $rating_count ); ?>							
							</div>
							<div class="product_name">
								<a href="<?php echo get_permalink($product_id)?>"><?php echo $product->get_name() ?></a>				
							</div>
							<div class="product_price">
								<?php echo $product->get_price_html(); ?>
							</div>            
							<div class="product-action">
								<?php woocommerce_template_loop_add_to_cart(); ?>
							</div>
						</div> 
                <?php
            }

            // Конец блока для двух товаров
            //echo '</div>';
        endwhile;
        ?>
    </div>
	
	
    <?php
    wp_reset_query();
}
add_shortcode('double_products_block_latest', 'double_products_latest');


function double_products_popular() {
    $args = array(
        'post_type' => 'product',
		'posts_per_page' => 10,
		'meta_key' => 'total_sales',
		'orderby' => 'meta_value_num',
		'order' => 'DESC',
		'post__not_in' => array(169054)
    );  
    $loop = new WP_Query($args);     
    ?>
	<div class="double_products_popular_controls products_slider_controls">
        <div class="prev">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
				<path d="M15.0013 20.67C14.8113 20.67 14.6213 20.6 14.4713 20.45L7.95125 13.93C6.89125 12.87 6.89125 11.13 7.95125 10.07L14.4713 3.55002C14.7613 3.26002 15.2413 3.26002 15.5312 3.55002C15.8212 3.84002 15.8212 4.32002 15.5312 4.61002L9.01125 11.13C8.53125 11.61 8.53125 12.39 9.01125 12.87L15.5312 19.39C15.8212 19.68 15.8212 20.16 15.5312 20.45C15.3813 20.59 15.1912 20.67 15.0013 20.67Z"/>
			</svg>
		</div>
        <div class="next">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
				<path d="M8.91156 20.67C8.72156 20.67 8.53156 20.6 8.38156 20.45C8.09156 20.16 8.09156 19.68 8.38156 19.39L14.9016 12.87C15.3816 12.39 15.3816 11.61 14.9016 11.13L8.38156 4.61002C8.09156 4.32002 8.09156 3.84002 8.38156 3.55002C8.67156 3.26002 9.15156 3.26002 9.44156 3.55002L15.9616 10.07C16.4716 10.58 16.7616 11.27 16.7616 12C16.7616 12.73 16.4816 13.42 15.9616 13.93L9.44156 20.45C9.29156 20.59 9.10156 20.67 8.91156 20.67Z"/>
			</svg>
		</div>
    </div>
    <div class="woocommerce double_products_block_popular double_products_block">
        <?php
        while ($loop->have_posts()) : 
            // Начало блока для двух товаров
            //echo '<div class="double_produts_wrapper">';

            // Обработка двух товаров за раз
            for ($i = 0; $i < 2; $i++) {
                if (!$loop->have_posts()) {
                    break;
                }

                $loop->the_post();
                global $product;

                $product_id = $product->get_id();								
				$product = wc_get_product( $product_id );								
				/* $product->get_rating_html();*/
				$rating_count = $product->get_rating_count();		
				$review_count = $product->get_review_count();
				$average      = $product->get_average_rating();
				$thumbnail = $product->get_image();

                // Вывод информации о товаре
                ?>
						<div class="products_block_item">
							<div class="add_to_wishlist">
								<?php echo do_shortcode('[yith_wcwl_add_to_wishlist]') ?>
							</div>
							<a href="<?php echo get_permalink($product_id)?>">
								<?php echo $thumbnail ?>
							</a>
							
							<?php //if(wc_get_product_tag_list($product_id)) :?>
								<div class="product_tags">
									<?php echo wc_get_product_tag_list($product_id, '', '', '')	?>
								</div>
							<?php //endif;?>
							<div class="product_rating">
								<?php echo wc_get_rating_html( $average, $rating_count ); ?>							
							</div>
							<div class="product_name">
								<a href="<?php echo get_permalink($product_id)?>"><?php echo $product->get_name() ?></a>				
							</div>
							<div class="product_price">
								<?php echo $product->get_price_html(); ?>
							</div>            
							<div class="product-action">
								<?php woocommerce_template_loop_add_to_cart(); ?>
							</div>
						</div> 
                <?php
            }

            // Конец блока для двух товаров
            //echo '</div>';
        endwhile;
        ?>
    </div>
	
	
    <?php
    wp_reset_query();
}
add_shortcode('double_products_block_popular', 'double_products_popular');


function double_products_onsale() {
    $product_ids_on_sale = wc_get_product_ids_on_sale();

    if ($product_ids_on_sale){

        $args_onsale = array(
            'post_type' => 'product',
            'posts_per_page' => 10,
            'post__in' => $product_ids_on_sale,
        );

        $loop_onsale = new WP_Query($args_onsale);     
    ?>
	<div class="double_products_on-sale_controls products_slider_controls">
        <div class="prev">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
				<path d="M15.0013 20.67C14.8113 20.67 14.6213 20.6 14.4713 20.45L7.95125 13.93C6.89125 12.87 6.89125 11.13 7.95125 10.07L14.4713 3.55002C14.7613 3.26002 15.2413 3.26002 15.5312 3.55002C15.8212 3.84002 15.8212 4.32002 15.5312 4.61002L9.01125 11.13C8.53125 11.61 8.53125 12.39 9.01125 12.87L15.5312 19.39C15.8212 19.68 15.8212 20.16 15.5312 20.45C15.3813 20.59 15.1912 20.67 15.0013 20.67Z"/>
			</svg>
		</div>
        <div class="next">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
				<path d="M8.91156 20.67C8.72156 20.67 8.53156 20.6 8.38156 20.45C8.09156 20.16 8.09156 19.68 8.38156 19.39L14.9016 12.87C15.3816 12.39 15.3816 11.61 14.9016 11.13L8.38156 4.61002C8.09156 4.32002 8.09156 3.84002 8.38156 3.55002C8.67156 3.26002 9.15156 3.26002 9.44156 3.55002L15.9616 10.07C16.4716 10.58 16.7616 11.27 16.7616 12C16.7616 12.73 16.4816 13.42 15.9616 13.93L9.44156 20.45C9.29156 20.59 9.10156 20.67 8.91156 20.67Z"/>
			</svg>
		</div>
    </div>
    <div class="woocommerce double_products_block_on-sale double_products_block">
        <?php
            if ($loop_onsale->have_posts()) {
                while ($loop_onsale->have_posts()) : 
                    // Начало блока для двух товаров
                    //echo '<div class="double_produts_wrapper">';

                    // Обработка двух товаров за раз
                    for ($i = 0; $i < 2; $i++) {
                        if (!$loop_onsale->have_posts()) {
                            break;
                        }

                        $loop_onsale->the_post();
                        global $product;

                        $product_id = $product->get_id();				
                        
                        $product = wc_get_product( $product_id );								
                        /* $product->get_rating_html();*/
                        $rating_count = $product->get_rating_count();		
                        $review_count = $product->get_review_count();
                        $average      = $product->get_average_rating();
                        $thumbnail = $product->get_image();

                        // Вывод информации о товаре
                        ?>
                                <div class="products_block_item">
                                    <div class="add_to_wishlist">
                                        <?php echo do_shortcode('[yith_wcwl_add_to_wishlist]') ?>
                                    </div>
                                    <a href="<?php echo get_permalink($product_id)?>">
                                        <?php echo $thumbnail ?>
                                    </a>
                                    
                                    <?php //if(wc_get_product_tag_list($product_id)) :?>
                                        <div class="product_tags">
                                            <?php echo wc_get_product_tag_list($product_id, '', '', '')	?>
                                        </div>
                                    <?php //endif;?>
                                    <div class="product_rating">
                                        <?php echo wc_get_rating_html( $average, $rating_count ); ?>							
                                    </div>
                                    <div class="product_name">
                                        <a href="<?php echo get_permalink($product_id)?>"><?php echo $product->get_name() ?></a>				
                                    </div>
                                    <div class="product_price">
                                        <?php echo $product->get_price_html(); ?>
                                    </div>            
                                    <div class="product-action">
                                        <?php woocommerce_template_loop_add_to_cart(); ?>
                                    </div>
                                </div> 
                        <?php
                    }

                    // Конец блока для двух товаров
                    //echo '</div>';
                endwhile;
            }
        ?>
    </div>	
    <?php 
    }
    else{
        echo 'no products';
    }
    wp_reset_query();
}
add_shortcode('double_products_block_onsale', 'double_products_onsale');



function enqueue_custom_scripts() {
     if ( is_cart() ) {
         wp_enqueue_script( 'sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11.0.16/dist/sweetalert2.all.min.js', array( 'jquery' ), null, true );
         wp_enqueue_style( 'sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11.0.16/dist/sweetalert2.min.css' );
         wp_enqueue_script( 'cart-confirmation', get_stylesheet_directory_uri() . '/assets/js/cart-confirmation.js', array( 'jquery', 'sweetalert' ), null, true );
 		//wp_enqueue_script( 'cart-quantity-update', get_stylesheet_directory_uri() . '/assets/js/cart-quantity-update.js', array( 'jquery' ), null, true );

         // Localize script to provide AJAX URL
         wp_localize_script( 'cart-quantity-update', 'wc_cart_params', array(
             'ajax_url' => admin_url( 'admin-ajax.php' )
         ));
     }
 }
add_action( 'wp_enqueue_scripts', 'enqueue_custom_scripts' );
 
 
/**
 * @snippet       Plus Minus Buttons @ WooCommerce Add Cart Quantity
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @compatible    WooCommerce 7
 * @donate $9     https://businessbloomer.com/bloomer-armada/
 */
  
// -------------
// 1. Show plus minus buttons
  
add_action( 'woocommerce_after_quantity_input_field', 'display_quantity_plus' );
  
function display_quantity_plus() {
   echo '<button type="button" class="plus"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25" fill="none">
<path d="M18 13.25H6C5.59 13.25 5.25 12.91 5.25 12.5C5.25 12.09 5.59 11.75 6 11.75H18C18.41 11.75 18.75 12.09 18.75 12.5C18.75 12.91 18.41 13.25 18 13.25Z"/>
<path d="M12 19.25C11.59 19.25 11.25 18.91 11.25 18.5V6.5C11.25 6.09 11.59 5.75 12 5.75C12.41 5.75 12.75 6.09 12.75 6.5V18.5C12.75 18.91 12.41 19.25 12 19.25Z"/>
</svg></button>';
}
  
add_action( 'woocommerce_before_quantity_input_field', 'display_quantity_minus' );
  
function display_quantity_minus() {
   echo '<button type="button" class="minus"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25" fill="none">
<path d="M18 13.25H6C5.59 13.25 5.25 12.91 5.25 12.5C5.25 12.09 5.59 11.75 6 11.75H18C18.41 11.75 18.75 12.09 18.75 12.5C18.75 12.91 18.41 13.25 18 13.25Z"/>
</svg></button>';
}
  
// -------------
// 2. Trigger update quantity script
  
add_action( 'wp_footer', 'add_cart_quantity_plus_minus' );
  
function add_cart_quantity_plus_minus() {
 
   if ( ! is_product() && ! is_cart() ) return;
    
   wc_enqueue_js( "   
           
      jQuery(document).on( 'click', 'button.plus, button.minus', function() {
  
         var qty = jQuery( this ).parent( '.quantity' ).find( '.qty' );
         var val = parseFloat(qty.val());
         var max = parseFloat(qty.attr( 'max' ));
         var min = parseFloat(qty.attr( 'min' ));
         var step = parseFloat(qty.attr( 'step' ));
 
         if ( jQuery( this ).is( '.plus' ) ) {
            if ( max && ( max <= val ) ) {
               qty.val( max ).change();
            } else {
               qty.val( val + step ).change();
            }
         } else {
            if ( min && ( min >= val ) ) {
               qty.val( min ).change();
            } else if ( val > 1 ) {
               qty.val( val - step ).change();
            }
         }
 
      });
        
   " );
}



/* PRODUCT REVIEW SHORTCODE */

function product_review_shortcode() {
    global $product;
    $product_id = $product->get_id();
	$review_count = $product->get_review_count();
	$average      = $product->get_average_rating();

    $comments = get_comments( array (
        'number'    => '5',
        'post__in'  => array($product_id), // <= HERE your array of product Ids
        'post_type' => 'product',
        'meta_key'  => 'rating',
        'orderby'   => 'meta_value_num',
        'order'     => 'DESC'
    ) );
    ?>
		<div class="review_header">
			<div class="product_rating">
				<?php echo wc_get_rating_html( $average, $rating_count ); ?>
			</div>
			<a href="javascript:void(0)" id="write_review_btn" class="write_review"><?php echo __('Write a review', 'hello-elementor')?></a> 
			<a href="javascript:void(0)" id="write_review_btn_close" class="write_review_close"><?php echo __('Cancel', 'hello-elementor')?></a>
			
		</div>
		
		<div id="review_form_block" class="review_form">
			
			<p class="review_form_title"><?php echo __('Write a review', 'hello-elementor'); ?></p>
			<?php if ( get_option( 'woocommerce_review_rating_verification_required' ) === 'no' || wc_customer_bought_product( '', get_current_user_id(), $product->get_id() ) ) : ?>
				<div id="review_form_wrapper">
					 
					<div id="review_form">
						<?php
						$commenter    = wp_get_current_commenter();
						$comment_form = array(
							/* translators: %s is product title */
							'title_reply'         => have_comments() ? esc_html__( 'Add a review', 'woocommerce' ) : sprintf( esc_html__( 'Be the first to review &ldquo;%s&rdquo;', 'woocommerce' ), get_the_title() ),
							/* translators: %s is product title */
							'title_reply_to'      => esc_html__( 'Leave a Reply to %s', 'woocommerce' ),
							'title_reply_before'  => '<span id="reply-title" class="comment-reply-title">',
							'title_reply_after'   => '</span>',
							'comment_notes_after' => '',
							'label_submit'        => esc_html__( 'Post a review', 'woocommerce' ),
							'logged_in_as'        => '',
							'comment_field'       => '',
						);

						$name_email_required = (bool) get_option( 'require_name_email', 1 );
						$fields              = array(
							'author' => array(
								'label'    => __( 'Name', 'woocommerce' ),
								'type'     => 'text',
								'value'    => $commenter['comment_author'],
								'required' => $name_email_required,
							),
							'email'  => array(
								'label'    => __( 'Email', 'woocommerce' ),
								'type'     => 'email',
								'value'    => $commenter['comment_author_email'],
								'required' => $name_email_required,
							),
						);

						$comment_form['fields'] = array();

						foreach ( $fields as $key => $field ) {
							$field_html  = '<p class="comment-form-' . esc_attr( $key ) . '">';
							$field_html .= '<label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] );

							if ( $field['required'] ) {
								$field_html .= '&nbsp;<span class="required">*</span>';
							}

							$field_html .= '</label><input id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" type="' . esc_attr( $field['type'] ) . '" value="' . esc_attr( $field['value'] ) . '" size="30" ' . ( $field['required'] ? 'required' : '' ) . ' /></p>';

							$comment_form['fields'][ $key ] = $field_html;
						}

						$account_page_url = wc_get_page_permalink( 'myaccount' );
						if ( $account_page_url ) {
							/* translators: %s opening and closing link tags respectively */
							$comment_form['must_log_in'] = '<p class="must-log-in">' . sprintf( esc_html__( 'You must be %1$slogged in%2$s to post a review.', 'woocommerce' ), '<a href="' . esc_url( $account_page_url ) . '">', '</a>' ) . '</p>';
						}

						if ( wc_review_ratings_enabled() ) {
							$comment_form['comment_field'] = '<div class="comment-form-rating"><label for="rating">' . esc_html__( 'Your rating', 'woocommerce' ) . ( wc_review_ratings_required() ? '&nbsp;<span class="required">*</span>' : '' ) . '</label><select name="rating" id="rating" required>
								<option value="">' . esc_html__( 'Rate&hellip;', 'woocommerce' ) . '</option>
								<option value="5">' . esc_html__( 'Perfect', 'woocommerce' ) . '</option>
								<option value="4">' . esc_html__( 'Good', 'woocommerce' ) . '</option>
								<option value="3">' . esc_html__( 'Average', 'woocommerce' ) . '</option>
								<option value="2">' . esc_html__( 'Not that bad', 'woocommerce' ) . '</option>
								<option value="1">' . esc_html__( 'Very poor', 'woocommerce' ) . '</option>
							</select></div>';
						}

						$comment_form['comment_field'] .= '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Your review', 'woocommerce' ) . '&nbsp;<span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="3" required></textarea></p>';

						comment_form( apply_filters( 'woocommerce_product_review_comment_form_args', $comment_form ) );
						?>
					</div>
				</div>
			<?php else : ?>
				<p class="woocommerce-verification-required"><?php esc_html_e( 'Only logged in customers who have purchased this product may leave a review.', 'woocommerce' ); ?></p>
			<?php endif; ?>
		</div>
		


        <div class="product_reviews">
            <?php foreach($comments as $comment):?>
                <?php 
                    $timestamp = strtotime( $comment->comment_date ); //Changing comment time to timestamp
                    $date = date('F d, Y', $timestamp);
                ?>
                <div class="review_item" itemprop="reviews" itemscope itemtype="http://schema.org/Review">
                    <div class="review_item_header">
                        <div class="review_header_left">							
							<div class="author_img">
								<?php echo get_avatar( $comment->comment_author_email, $size = '50' ); ?>
							</div>
							
							<div class="author_wrap">							
								<div class="author_name" itemprop="author">
									<?php echo $comment->comment_author; ?>
								</div>					
                           
								<div itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating" class="star-rating" title="<?php echo esc_attr( get_comment_meta( $comment->comment_ID, 'rating', true ) ); ?>">
									<span style="width:<?php echo get_comment_meta( $comment->comment_ID, 'rating', true )*22; ?>px"><span itemprop="ratingValue"><?php echo get_comment_meta( $comment->comment_ID, 'rating', true ); ?></span> <?php _e('out of 5', 'woocommerce'); ?></span>
								</div>
								
							</div>
                        </div>
                        <div class="review_header_right">
                            <time itemprop="datePublished" datetime="<?php echo $comment->comment_date; ?>"><?php echo $date; ?></time>
                        </div>
                        
                    </div>
                    <div itemprop="description" class="review_content">
                        <?php echo $comment->comment_content; ?>
                    </div>
                </div>
            <?php endforeach ?>           
        </div>
    <?
}

add_shortcode('single_product_review', 'product_review_shortcode');

/**
 * @snippet       Automatically Update Cart on Quantity Change - WooCommerce
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @compatible    WooCommerce 7
 * @donate $9     https://businessbloomer.com/bloomer-armada/
 */

add_action( 'wp_footer', 'bbloomer_cart_refresh_update_qty' ); 

function bbloomer_cart_refresh_update_qty() {
	if ( is_cart() || ( is_cart() && is_checkout() ) ) {
		wc_enqueue_js( "
			jQuery('body').on('click', 'span.eael-cart-qty-plus', function(){
				let update_btn = $('.eael-cart-update-btn button');
				setTimeout(function(){
					  update_btn.trigger('click');
					}, 500);
				
			});
			jQuery('body').on('click', 'span.eael-cart-qty-minus', function(){
				let update_btn = $('.eael-cart-update-btn button');
				
				setTimeout(function(){
					  update_btn.trigger('click');
					}, 500);
			});
		" );
	}
}

remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
add_action('woocommerce_after_shop_loop_item_title', 'get_star_rating' );
function get_star_rating()
{
    global $woocommerce, $product;
    $average = $product->get_average_rating();

    echo '<div class="star-rating"><span style="width:'.( ( $average / 5 ) * 100 ) . '%"><strong itemprop="ratingValue" class="rating">'.$average.'</strong> '.__( 'out of 5', 'woocommerce' ).'</span></div>';
}

// Change separator on pages.
add_filter( 'aioseo_breadcrumbs_separator', 'aioseo_breadcrumbs_separator' );
function aioseo_breadcrumbs_separator( $separator ) {	
		$separator = '<span class="my-separator"><img src="/wp-content/uploads/2023/09/chevron.svg"/></span>';
	return $separator;
}

/* THANK YOU PAGE */
add_action( 'woocommerce_thankyou', 'custom_thankyou_redirect' );
function custom_thankyou_redirect( $order_id ){
    $order = wc_get_order( $order_id );
    $url = '/thank-you-page/'; // Замените на URL вашей кастомной страницы
    if ( $order->has_status( 'completed' ) ) {
        wp_redirect( $url );
        exit;
    }
}

add_shortcode('current_order_id', 'get_current_order_id');
function get_current_order_id() {    
        global $wp;
        $order_id = apply_filters('woocommerce_thankyou_order_id', absint($wp->query_vars['order-received']));
        return $order_id;    
}

add_shortcode('current_order_date', 'get_current_order_date');
function get_current_order_date() {   
        global $wp;
        $order_id = apply_filters('woocommerce_thankyou_order_id', absint($wp->query_vars['order-received']));
        
        if (!$order_id) {
            $order_id = apply_filters('woocommerce_view_order_order_id', absint($wp->query_vars['view-order']));
        }

        if ($order_id) {
            $order = wc_get_order($order_id);
            return $order->get_date_created()->date('M d, Y');
        }
}

add_shortcode('current_order_total', 'get_current_order_total');
function get_current_order_total() {   
        global $wp;
        $order_id = apply_filters('woocommerce_thankyou_order_id', absint($wp->query_vars['order-received']));
        
        if (!$order_id) {
            $order_id = apply_filters('woocommerce_view_order_order_id', absint($wp->query_vars['view-order']));
        }

        if ($order_id) {
            $order = wc_get_order($order_id);
            return $order->get_formatted_order_total();
        }    
}

add_shortcode('current_order_payment_method', 'get_current_order_payment_method');
function get_current_order_payment_method() {    
        global $wp;
        $order_id = apply_filters('woocommerce_thankyou_order_id', absint($wp->query_vars['order-received']));
        
        if (!$order_id) {
            $order_id = apply_filters('woocommerce_view_order_order_id', absint($wp->query_vars['view-order']));
        }

        if ($order_id) {
            $order = wc_get_order($order_id);
            return $order->get_payment_method_title();
        }   
}

add_shortcode('customer_name', 'get_customer_name');
function get_customer_name() {
    global $wp;
    $order_id = apply_filters('woocommerce_thankyou_order_id', absint($wp->query_vars['order-received']));

    $order = wc_get_order($order_id);
    if (!$order) return '';

    return $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
}

add_shortcode('customer_address', 'get_customer_address');
function get_customer_address() {
    global $wp;
    $order_id = absint($wp->query_vars['order-received'] ?? $wp->query_vars['view-order'] ?? 0);
    if (!$order_id) return '';

    $order = wc_get_order($order_id);
    if (!$order) return '';

    return $order->get_billing_address_1() . '</br>' . $order->get_billing_city() . '</br>' . $order->get_billing_state() . '</br>' . $order->get_billing_country();
}

add_shortcode('customer_phone', 'get_customer_phone');
function get_customer_phone() {
    global $wp;
    $order_id = absint($wp->query_vars['order-received'] ?? $wp->query_vars['view-order'] ?? 0);
    if (!$order_id) return '';

    $order = wc_get_order($order_id);
    if (!$order) return '';

    return $order->get_billing_phone();
}

add_shortcode('customer_email', 'get_customer_email');
function get_customer_email() {
    global $wp;
    $order_id = absint($wp->query_vars['order-received'] ?? $wp->query_vars['view-order'] ?? 0);
    if (!$order_id) return '';

    $order = wc_get_order($order_id);
    if (!$order) return '';

    return $order->get_billing_email();
}

add_shortcode('custom_get_order', 'custom_get_order');
function custom_get_order() {
    global $wp;
    $order_id = absint($wp->query_vars['order-received'] ?? $wp->query_vars['view-order'] ?? 0);
    if (!$order_id) return '';

    $order = wc_get_order($order_id);
    if (!$order) return '';

    return $order;
}


add_shortcode('display_order_details', 'display_order_details_function');
function display_order_details_function() {

    global $wp;
    $order_id = absint($wp->query_vars['order-received'] ?? $wp->query_vars['view-order'] ?? 0);
    if (!$order_id) return '';

    $order = wc_get_order($order_id);
    if (!$order) return '';

    $output = '<div class="custom-order-details">';

    // Товары в заказе
    foreach ($order->get_items() as $item_id => $item) {
        $product = $item->get_product();
        $product_image = $product->get_image();
        $product_name = $item->get_name();
        $product_price = $order->get_formatted_line_subtotal($item);
        
        $output .= '<div class="order-item">';
        $output .= $product_image;
        $output .= '<div class="product-details">';
        $output .= '<p class="product-name">' . $product_name . '</p>';
        $output .= '<p class="product-price">' . $product_price . '</p>';
        $output .= '</div>'; // .product-details
        $output .= '</div>'; // .order-item
    }

    // Подитог
    $output .= '<div class="order_details_bottom_info">';
    $output .= '<p class="subtotal">'. __('Subtotal', 'woocommerce'). ': ' . $order->get_subtotal_to_display() . '</p>';

    // Метод оплаты
    $output .= '<p class="payment-method">'. __('Payment method', 'woocommerce'). ': <span>' . $order->get_payment_method_title() . '</span></p>';

    // Доставка
    $output .= '<p class="shipping-method">'. __('Shipping', 'woocommerce'). ': <span>' . $order->get_shipping_to_display() . '</span></p>';
	$output .= '</div>';
    // Итог
    $vat_amount = $order->get_total_tax(); // Получаем сумму НДС из заказа
	$vat_message = $vat_amount > 0 ? '<span class="vat_info"> ('. __('includes', 'woocommerce'). ' ' . wc_price($vat_amount). ' ' .  __('VAT', 'woocommerce') . ')</span>' : '<span class="vat_info"> ('. __('VAT not applicable', 'woocommerce'). ')</span>'; // Сообщение зависит от наличия НДС
    $output .= '<p class="total">'. __('Total', 'woocommerce'). ': <span>' . $order->get_formatted_order_total() . $vat_message .'</span></p>';

    // Кнопка повторить заказ
    $output .= '<a href="' . esc_url($order->get_checkout_payment_url()) . '" class="button repeat-order">'. __('Repeat order', 'woocommerce') . '</a>';
	
    $output .= '</div>'; // .custom-order-details

    return $output;
}

/* CHECKOUT */
// Функция шорткода для вывода деталей заказа

/*
function checkout_order_details() {
    // Убедитесь, что это страница оформления заказа и что глобальная переменная $woocommerce доступна
    if (is_checkout() && isset(WC()->cart)) {
        ob_start(); // Начало буферизации вывода
		
		// Получаем элементы корзины
		$cart_items = WC()->cart->get_cart();
		// Получаем данные корзины
        $cart_total = WC()->cart->get_total();
        $cart_subtotal = WC()->cart->get_cart_subtotal();
        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $shipping_methods = WC()->shipping->get_packages();
		$tax_total = WC()->cart->get_taxes_total(); // Получаем сумму НДС для корзины
        // Форматирование вывода суммы НДС
        $formatted_tax_total = wc_price($tax_total);
		
		echo '<div class="custom-order-details">';

		// Товары в заказе
		foreach ($cart_items as $cart_item_key => $cart_item) {
			$product = $cart_item['data'];
			$product_image = $product->get_image();
			$product_name = $product->get_name();
			$quantity = $cart_item['quantity'];
			$product_price = WC()->cart->get_product_subtotal($product, $cart_item['quantity']);
		?>

			<div class="order-item">
				<?= $product_image; ?>
				<div class="product-details">
					<p class="product-name"><?= $product_name ?> <br>x<?= $quantity ?></p>
					<p class="product-price"> <?= $product_price ?></p>
				</div>
			</div>	
		<?php } ?>

		<?php 
			if ( ! wc_coupons_enabled() || WC()->cart->has_discount() ) {
				return;	
			} 		
			else {
		?>		

		<form class="checkout-coupon" method="post">
			<p class="coupon-title"><?= __('Have coupon?', 'woocommerce') ?></p>
			<p class="coupon_input">
				
				<input type="text" name="coupon_code" class="input-text" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" id="coupon_code" value="" />
				<button type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e( 'Apply', 'woocommerce' ); ?>"><?php esc_html_e( 'Apply', 'woocommerce' ); ?></button>
			</p>			
			<div class="clear"></div>
		</form>
		<?php } ?>
		


		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<div class="checkout-coupon">			
				<?php wc_cart_totals_coupon_label( $coupon ); ?>
				<?php wc_cart_totals_coupon_html( $coupon ); ?>
			</div>
		<?php endforeach ?>
		
		<p class="checkout_subtotal"><?= __('Subtotal', 'woocommerce')?> <?= $cart_subtotal?> </p>

		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
			<div class="checkout_shipping">
				<table>
					<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
					<?php wc_cart_totals_shipping_html(); ?>
					<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>			
				</table>
			</div>
		<?php endif; ?>	

<p class="checkout_total"><?= __('Total', 'woocommerce') ?> <span class="total_right_side"><?= $cart_total ?><span class="vat_info">(<?= sprintf( __('includes %s VAT','woocommerce'), $formatted_tax_total ) ?>)</span></span></p>   

		<?php
			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

			if ( ! empty( $available_gateways ) ) {
				echo '<div class="wc_payment_methods payment_methods">';
				foreach ( $available_gateways as $gateway ) {
					echo '<div class="wc_payment_method payment_method_' . esc_attr( $gateway->id ) . '">';
					echo '<div class="payment_method_top">';
					echo '<input id="payment_method_' . esc_attr( $gateway->id ) . '" type="radio" class="input-radio" name="payment_method" value="' . esc_attr( $gateway->id ) . '" ' . checked( $gateway->chosen, true, false ) . ' />';
					echo '<label for="payment_method_' . esc_attr( $gateway->id ) . '">' . $gateway->get_title() . ' ' . $gateway->get_icon() . '</label>';
					echo '</div>';
					if ( $gateway->has_fields() || $gateway->get_description() ) {
						echo '<div class="payment-box payment-method-' . esc_attr( $gateway->id ) . '">';
						$gateway->payment_fields();
						echo '</div>';
					}
					
					echo '</div>';
				}
				echo '</div>';
			}
		
 			echo woocommerce_form_field('terms_and_conditions', array(
					'type' => 'checkbox',
					'class' => array('terms-and-conditions checkbox'),
					'label' => __('I have read and agree to the website ', 'woocommerce'),
					'required' => true,
				),
				WC()->checkout->get_value('terms_and_conditions'));	
		
		echo '<div id="place_order_button_wrap">';
		echo '<button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="Place order" data-value="Place order">'.__('Place order', 'woocommerce').'</button>';
		echo '</div>';
		
		echo '</div>';
        return ob_get_clean(); // Возвращаем содержимое буфера и очищаем его
    }
}
*/

// Добавляем шорткод в WordPress
// add_shortcode('checkout_order_details', 'checkout_order_details');

// Billing Fields.
add_filter( 'woocommerce_billing_fields', 'custom_woocommerce_billing_fields' );
function custom_woocommerce_billing_fields( $fields ) {    
    $fields['billing_address_2']['label_class'] = '';    
    return $fields;
}

// Shipping Fields.
add_filter( 'woocommerce_shipping_fields', 'custom_woocommerce_shipping_fields' );
function custom_woocommerce_shipping_fields( $fields ) {
    $fields['shipping_address_2']['label_class'] = '';    
    return $fields;
}


function redirect_login_page() {
  $current_language = icl_get_current_language();
  if ($current_language == 'en'){
	$login_page = home_url('/en/login/');
  }
  elseif ($current_language == 'ru'){
	$login_page = home_url('/ru/login/');
  }
  else{
	$login_page = home_url('/login/');
  }
 $page_viewed = basename($_SERVER['REQUEST_URI']);
 if( $page_viewed == "wp-login.php" && $_SERVER['REQUEST_METHOD'] == 'GET') {
   wp_redirect($login_page);
   exit;
 }
}
add_action('init','redirect_login_page');



function login_failed() {
  $current_language = icl_get_current_language();
  if ($current_language == 'en'){
	$login_page = home_url('/en/login/');
  }
  elseif ($current_language == 'ru'){
	$login_page = home_url('/ru/login/');
  }
  else{
	$login_page = home_url('/login/');
  }
   
  
  wp_redirect( $login_page . '?login=failed' );
  exit;
}
add_action( 'wp_login_failed', 'login_failed' );

// function custom_login_redirect($redirect, $user) {
	// $home_url = apply_filters( 'wpml_home_url', get_option( 'home' ) );  
    // // Если пользователь входит и имеет роль "customer"
    // // if (isset($user->roles) && is_array($user->roles) && in_array('customer', $user->roles)) {
        // // // Укажите свой URL-адрес перенаправления
        // $redirect = $home_url.'/my-account/';
    // // }
	
	// // elseif (isset($user->roles) && is_array($user->roles) && in_array('administrator', $user->roles)) {
        // // // Укажите свой URL-адрес перенаправления
        // // $redirect = home_url('/wp-admin/');
    // // }

    // return $redirect;
// }
// add_filter('woocommerce_login_redirect', 'custom_login_redirect', 10, 2);
 
function verify_username_password( $user, $username, $password ) {
	$current_language = icl_get_current_language();
	  if ($current_language == 'en'){
		$login_page = home_url('/en/login/');
	  }
	  elseif ($current_language == 'ru'){
		$login_page = home_url('/ru/login/');
	  }
	  else{
		$login_page = home_url('/login/');
	  }
	
    if( $username == "" || $password == "" ) {
        wp_redirect( $login_page."/?login=failed" );
        exit;
    }
}
add_filter( 'authenticate', 'verify_username_password', 1, 3);

// function logout_page() {
  // $current_language = icl_get_current_language();
  // if ($current_language == 'en'){
	// $login_page = home_url('/en/login/');
  // }
  // elseif ($current_language == 'ru'){
	// $login_page = home_url('/ru/login/');
  // }
  // else{
	// $login_page = home_url('/login/');
  // }
  // wp_redirect( $login_page . "?action=logout" );
  // exit;
// }
// add_action('wp_logout','logout_page');


/**
 * Logout redirect
*/


// function auto_redirect_after_logout(){
	// $current_language = icl_get_current_language();
	  // if ($current_language == 'en'){
		// $login_page = home_url('/en/login/');
	  // }
	  // elseif ($current_language == 'ru'){
		// $login_page = home_url('/ru/login/');
	  // }
	  // else{
		// $login_page = home_url('/login/');
	  // }
	// wp_redirect( $login_page );
	// exit();
// }
// add_action('wp_logout','auto_redirect_after_logout');


/* MY ACCOUNT */

/**
 * @snippet       Add Custom Page in My Account
 * @author        Misha Rudrastyh
 * @url           https://rudrastyh.com/woocommerce/my-account-menu.html#add-custom-tab
 */
// add menu link
add_filter ( 'woocommerce_account_menu_items', 'trackcodes_link', 40 );
function trackcodes_link( $menu_links ){	
	$menu_links = array_slice( $menu_links, 0, 5, true ) 
	+ array( 'trackcodes' => __( 'Order tracking', 'woocommerce' ) )
	+ array_slice( $menu_links, 5, NULL, true );	
	return $menu_links;
}
// register permalink endpoint
add_action( 'init', 'trackcodes_add_endpoint' );
function trackcodes_add_endpoint() {
	add_rewrite_endpoint( 'trackcodes', EP_PAGES );
}

// content for the new page in My Account, woocommerce_account_{ENDPOINT NAME}_endpoint
add_action( 'woocommerce_account_trackcodes_endpoint', 'trackcodes_my_account_endpoint_content' );
function trackcodes_my_account_endpoint_content() {

	// of course you can print dynamic content here, one of the most useful functions here is get_current_user_id().
	$user_id = get_current_user_id();
    $args = array(
		'customer_id' => $user_id,
		'limit' => -1, // to retrieve _all_ orders by this user
	);
	$orders = wc_get_orders($args);
	$order_number_title = __( 'Order number', 'woocommerce' );
	$track_number_title = __( 'Tracking number', 'woocommerce' );
	$delivery_type_title = __( 'Delivery type', 'woocommerce' );
	$action_title = __( 'Action', 'woocommerce' );
	$track_order_text = __( 'Track order', 'woocommerce' );	
	$track_btn_hover = __( 'copy', 'woocommerce' );	
	$track_btn_click = __( 'copied', 'woocommerce' );
	
	echo '<div class="track_codes"><table><thead><tr><th>'.$order_number_title.'</th><th>'.$delivery_type_title.'</th><th>'.$track_number_title.'</th><th>'.$action_title.'</th></tr></thead><tbody>';
	foreach($orders as $order){
		$delivery_type = $order->get_shipping_method();		
		if ($order->get_meta('_omnivalt_barcode')){
			$track_code = $order->get_meta('_omnivalt_barcode');
			$track_link = 'https://www.omniva.ee/era/jalgimine?barcode='.$track_code;
			$track_link_status='active_link';
			
		}
		elseif ($order->get_meta('_itella_tracking_code')){
			$track_code = $order->get_meta('_itella_tracking_code');
			$track_link = $order->get_meta('_itella_tracking_url');
			$track_link_status='active_link';
		}
		else{
			$track_code = __( 'Tracking number not found', 'woocommerce' );
			$track_link = '';
			$track_link_status='unactive_link';			
		};		
		echo '<tr class="order_track_row"><td><a href="'.esc_url( $order->get_view_order_url() ).'">#'.$order->get_order_number().'</a></td><td>'.$delivery_type.'</td><td><span class="track_code">'.$track_code.'</span><span data-hover="'.$track_btn_hover.'" data-click="'.$track_btn_click.'" class="track_icon_wrapper"><img class="track_copy_btn" src="https://anix-shop.com/copy2/wp-content/uploads/2023/03/copy.svg" /> </span></td><td><a class="track_btn '.$track_link_status.'" href="'.$track_link.'">'.$track_order_text.'</a></td></tr>';	
		
	}
	echo '</tbody></table></div>';
}

// add_action( 'woocommerce_before_checkout_form', 'freeshipping_message_add_checkout_content', 9 );
// function freeshipping_message_add_checkout_content() {
	// $message =  __( 'Free shipping from 50€', 'woocommerce');
	// echo '<div class="free_shipping_message">'.$message.'</div>';
// }

// Itella scripts only in cart/checkout

function deregister_itella_scripts () {		
	if ( !is_checkout() ){
		wp_deregister_script ( 'itella-shippingleaflet.js' );
		wp_deregister_script ( 'itella-shippingitella-mapping.js' );
		wp_deregister_script ( 'itella-shippingitella-shipping-public.js' );	
		wp_deregister_script ( 'itella-shippingitella-init-map.js' );	
		wp_deregister_script ( 'leaflet.js' );	
	}	
}

add_action ( 'wp_print_scripts', 'deregister_itella_scripts');

// MY OWN DESIGN in MY ACCOUNT
// 
// add menu link
add_filter ( 'woocommerce_account_menu_items', 'owndesign_link', 40 );
function owndesign_link( $menu_links ){	
	$menu_links = array_slice( $menu_links, 0, 5, true ) 
	+ array( 'owndesign' => __( 'My own design', 'woocommerce' ) )
	+ array_slice( $menu_links, 5, NULL, true );	
	return $menu_links;
}
// register permalink endpoint
add_action( 'init', 'owndesign_add_endpoint' );
function owndesign_add_endpoint() {
	add_rewrite_endpoint( 'owndesign', EP_PAGES );
}

// content for the new page in My Account, woocommerce_account_{ENDPOINT NAME}_endpoint
add_action( 'woocommerce_account_owndesign_endpoint', 'owndesign_my_account_endpoint_content' );
function owndesign_my_account_endpoint_content() {	
	require_once 'woocommerce/myaccount/orders-owndesign.php';
}

function its_get_wc_category() {
    return single_term_title( '', false );
}
add_shortcode( 'its_cat_title', 'its_get_wc_category' );


add_filter( 'ywpo_get_orders_by_customer_excluded_statuses', '__return_empty_array' );

// Display the product thumbnail in order view pages
add_filter( 'woocommerce_order_item_name', 'codesfever_display_product_image_in_order_item', 20, 3 );
function codesfever_display_product_image_in_order_item( $item_name, $item, $is_visible ) {
    // Targeting view order pages only
    if( is_wc_endpoint_url( 'view-order' ) ) {
        $product   = $item->get_product(); // Get the WC_Product object (from order item)
        $thumbnail = $product->get_image(array( 90, 90)); // Get the product thumbnail (from product object)
		
        if( $product->get_image_id() > 0 ){
            $item_name = '<div class="item-thumbnail">' . $thumbnail . '</div>' . $item_name;
		}
		else{
			$item_name = $item_name;
		}
    }
    return $item_name;
}

//Change order URL in My Account
if (!function_exists('yith_wcmap_change_orders_url')) {
    function yith_wcmap_change_orders_url()
    {
        $jquery = '
            jQuery( document ).ready( function( $ ){
                 $( ".yith-wcmap .myaccount-submenu .yith-orders-e" ).attr( "href", $( ".yith-wcmap .myaccount-submenu .yith-orders-e" ).attr( "href" ).replace( "wt-store-credit", "orders" ) );
            })';
        wp_add_inline_script('ywcmap-frontend', $jquery);
    }
    add_action( 'wp_enqueue_scripts', 'yith_wcmap_change_orders_url', 99 );
}



/* END MY ACCOUNT */


/* ВЫВОД ПОПУЛЯРНЫХ ПОИСКОВЫХ ЗАПРОСОВ */
function save_search_query() {
    if (is_search() && !is_admin()) {
        global $wpdb, $wp_query;
        $search_term = $wp_query->query_vars['s'];
        $table_name = $wpdb->prefix . 'popular_searches';

        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE search_term = %s", $search_term);
        $result = $wpdb->get_row($query);

        if ($result) {
            $wpdb->update($table_name, ['search_count' => $result->search_count + 1], ['search_term' => $search_term]);
        } else {
            $wpdb->insert($table_name, ['search_term' => $search_term, 'search_count' => 1]);
        }
    }
}
add_action('wp_head', 'save_search_query');

add_action( 'wp_enqueue_scripts', 'aws_wp_enqueue_scripts', 999 );
function aws_wp_enqueue_scripts() {
	
	global $wpdb;
    $table_name = $wpdb->prefix . 'popular_searches';
    $popular_searches = $wpdb->get_results("SELECT * FROM $table_name ORDER BY search_count DESC LIMIT 10");   
	$requests_list = '';
	
	$iteration_count = 0;
	foreach ($popular_searches as $search) {
		if ($iteration_count < 3) {
			$requests_list .= '<li><a href="' . esc_url(home_url('/')) . '?s=' . esc_attr($search->search_term) . '&post_type=product&type_aws=true&">' . esc_html($search->search_term) . '</a></li>';
			$iteration_count++;
		} else {
			break; // прервать цикл после трех итераций
		}
	}

    $block_name = __('Popular requests', 'woocommerce');    
	$block = '';
	if (!empty($requests_list)) {
		
		$block .= '<div class="popular_requests">';
		$block .= '<p class="popular_requests_title">' . $block_name . '</p>';
		$block .= '<ul class="popular_requests_list">';
		
		$block .= $requests_list;
			
		$block .= '</ul>';	
		$block .= '<p class="popular_requests_title">' . __('Products', 'woocommerce') . '</p>';
		$block .= "</div>";
		
	}
	
	 // else {
		// $block .= '<div class="popular_requests"> NO SEARCH RESULTS </div>';
	 // }

    // $script = '
        // function aws_results_html( html, options ) {
            // if ( ! html.includes("aws-suggestions") ) {
                // html = "'. $block .'" + html;
			// }
            // return html;
        // }
        // document.addEventListener("awsLoaded", function() {
			// AwsHooks.add_filter( "aws_results_html", aws_results_html );
        // });
    // ';
		
	$script = "
		function aws_results_html( html, options ) {
            
            if ( ! html.includes('aws-suggestions') ) {
                html = '". $block ."' + html;
			}
			
            return html;
        }
        let isFilterAdded = false;

		document.addEventListener('awsLoaded', function() {
			if (!isFilterAdded) {
				AwsHooks.add_filter('aws_results_html', aws_results_html);
				isFilterAdded = true;
			}
		});
		";
    wp_add_inline_script( 'aws-script', $script);
    wp_add_inline_script( 'aws-pro-script', $script);
}

remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
add_action( 'woocommerce_before_shop_loop', 'custom_woocommerce_catalog_ordering', 30 );


add_action( 'woocommerce_before_shop_loop', 'custom_woocommerce_catalog_ordering', 30 );
function custom_woocommerce_catalog_ordering() {
	$catalog_orderby_options = apply_filters( 'woocommerce_catalog_orderby', array(
        'menu_order' => __( 'Default sorting', 'woocommerce' ),
        'popularity' => __( 'Sort by popularity', 'woocommerce' ),
        // Добавьте другие опции сортировки по желанию
    ));

    $current_orderby = $_GET['orderby'] ?? 'menu_order';
    $current_orderby_text = $catalog_orderby_options[$current_orderby] ?? __('Sorting', 'woocommerce');


    echo '<div class="custom-sorting-dropdown">';
    echo '<button id="custom-sorting-button">' . esc_html($current_orderby_text) . '</button>';
    echo '<form id="custom-sorting-form" style="display: none;">';
    foreach ( $catalog_orderby_options as $id => $name ) {
        echo '<label><input type="radio" name="orderby" value="' . esc_attr( $id ) . '" ' . checked( $_GET['orderby'] ?? '', $id, false ) . '> ' . esc_html( $name ) . '</label>';
    }
    echo '</form>';
    echo '</div>
			<script>
				document.getElementById("custom-sorting-button").addEventListener("click", function() {
					var form = document.getElementById("custom-sorting-form");
					form.style.display = form.style.display === "none" ? "block" : "none";
				});

				document.getElementById("custom-sorting-form").addEventListener("change", function(event) {
					var button = document.getElementById("custom-sorting-button");
					var selectedOption = event.target.nextSibling.textContent;
					button.textContent = selectedOption;
					this.submit();
				});
			</script>';
}



function custom_woocommerce_catalog_ordering_mobile() {
	$catalog_orderby_options = apply_filters( 'woocommerce_catalog_orderby', array(
        'menu_order' => __( 'Default sorting', 'woocommerce' ),
        'popularity' => __( 'Sort by popularity', 'woocommerce' ),
        // Добавьте другие опции сортировки по желанию
    ));

    $current_orderby = $_GET['orderby'] ?? 'menu_order';
    $current_orderby_text = $catalog_orderby_options[$current_orderby] ?? __('Sorting', 'woocommerce');

    echo '<div class="custom-sorting-dropdown_mobile">';
    echo '<button id="custom-sorting-button_mobile">' . esc_html($current_orderby_text) . '</button>';
    echo '<form id="custom-sorting-form_mobile" style="display: none;">';
    foreach ( $catalog_orderby_options as $id => $name ) {
        echo '<label><input type="radio" name="orderby" value="' . esc_attr( $id ) . '" ' . checked( $_GET['orderby'] ?? '', $id, false ) . '> ' . esc_html( $name ) . '</label>';
    }
    echo '</form>';
    echo '</div>
			<script>
				document.getElementById("custom-sorting-button_mobile").addEventListener("click", function() {
					var form = document.getElementById("custom-sorting-form_mobile");
					form.style.display = form.style.display === "none" ? "block" : "none";
				});

				document.getElementById("custom-sorting-form_mobile").addEventListener("change", function(event) {
					var button = document.getElementById("custom-sorting-button_mobile");
					var selectedOption = event.target.nextSibling.textContent;
					button.textContent = selectedOption;
					this.submit();
				});
			</script>';
}
add_shortcode( 'ordering_products', 'custom_woocommerce_catalog_ordering_mobile' );

/* МЕНЯЕТ ССЫЛКУ В ИКОНКЕ ЛК, ЕСЛИ ПОЛЬЗОВАТЕЛЬ НЕЗАЛОГИНЕН */
if (!function_exists('yith_wcmap_change_login_url')) {
    function yith_wcmap_change_login_url() {
        $jquery = 'jQuery( document ).ready( function( $ ){ 
            var language = document.documentElement.lang;
            var loginUrl;
            if(language === "en-US") {
                loginUrl = "https://anix-shop.com/en/login/";
            } else if(language === "ru-RU") {
                loginUrl = "https://anix-shop.com/ru/login/";
            } else {
                loginUrl = "https://anix-shop.com/login/";
            }
            $( "body:not(.logged-in) .elementor-element-5966d64 a.elementor-icon" ).attr( "href", loginUrl );
        });';

        wp_add_inline_script('woocommerce', $jquery);
    }
    add_action( 'wp_enqueue_scripts', 'yith_wcmap_change_login_url', 99 );
}

add_action('woocommerce_review_order_before_submit', 'add_custom_privacy_checkbox', 9);

function add_custom_privacy_checkbox() {
    $privacy_page_id = get_option('wp_page_for_privacy_policy');
    if (!$privacy_page_id) {
        return;
    }
    
    $privacy_text = get_option('woocommerce_checkout_privacy_policy_text');
    if (empty($privacy_text)) {
        // Задайте текст по умолчанию, если он не определен
        $privacy_text = __('I have read and agree to the website terms and conditions *', 'woocommerce');
    }

    woocommerce_form_field('custom_privacy_policy', array(
        'type' => 'checkbox',
        'class' => array('form-row privacy'),
        'label_class' => array('checkout_privacy woocommerce-form__label woocommerce-form__label-for-checkbox checkbox'),
        'input_class' => array('woocommerce-form__input woocommerce-form__input-checkbox input-checkbox'),
        'required' => true,
        'label' => $privacy_text,
    ));
}

/* ВЫКЛ УВЕЛИЧЕНИЕ КАРТИНКИ ТОВАРА ПРИ НАВЕДЕНИИ */
add_action( 'wp', 'disable_zoom_effect' );
function disable_zoom_effect() {
    if ( is_product() ) {
        remove_theme_support( 'wc-product-gallery-zoom' );
		remove_theme_support( 'wc-product-gallery-lightbox' );
    }
}

if ( defined( 'YITH_WCWL' ) && ! function_exists( 'yith_wcwl_get_items_count' ) ) {
  function yith_wcwl_get_items_count() {
    ob_start();
	
    ?>
	<a href="<?php echo esc_url( YITH_WCWL()->get_wishlist_url() ); ?>" name="" aria-label="<img alt=&quot;Wishlist&quot; src=&quot;https://anix-shop.com/wp-content/uploads/2023/06/heart.svg&quot; /> " class="wishlist_products_counter top_wishlist-custom top_wishlist-">
		<span class="wishlist_products_counter_text">
			<img alt="Wishlist" src="https://anix-shop.com/wp-content/uploads/2023/06/heart.svg"> 
		</span>
		<span class="wishlist_products_counter_number"><?php echo esc_html( yith_wcwl_count_all_products() ); ?></span>
	</a>
    <?php
    return ob_get_clean();
  }

  add_shortcode( 'yith_wcwl_items_count', 'yith_wcwl_get_items_count' );
}

if ( defined( 'YITH_WCWL' ) && ! function_exists( 'yith_wcwl_ajax_update_count' ) ) {
  function yith_wcwl_ajax_update_count() {
    wp_send_json( array(
      'count' => yith_wcwl_count_all_products()
    ) );
  }

  add_action( 'wp_ajax_yith_wcwl_update_wishlist_count', 'yith_wcwl_ajax_update_count' );
  add_action( 'wp_ajax_nopriv_yith_wcwl_update_wishlist_count', 'yith_wcwl_ajax_update_count' );
}

if ( defined( 'YITH_WCWL' ) && ! function_exists( 'yith_wcwl_enqueue_custom_script' ) ) {
  function yith_wcwl_enqueue_custom_script() {
    wp_add_inline_script(
      'jquery-yith-wcwl',
      "
        jQuery( function( $ ) {
          $( document ).on( 'added_to_wishlist removed_from_wishlist', function() {
            $.get( yith_wcwl_l10n.ajax_url, {
              action: 'yith_wcwl_update_wishlist_count'
            }, function( data ) {
              $('.wishlist_products_counter_number').html( data.count );
            } );
          } );
        } );
      "
    );
  }

  add_action( 'wp_enqueue_scripts', 'yith_wcwl_enqueue_custom_script', 20 );
}

if (!function_exists('yith_wcwl_change_wishlist_url_counter')) {
    function yith_wcwl_change_wishlist_url_counter() {
		$current_lang = apply_filters( 'wpml_current_language', NULL ); 
		if ($current_lang == 'et'){
			$jquery = '
            jQuery( document ).ready(function($){
                $( ".wishlist_products_counter" ).attr( "href", "https://anix-shop.com/wishlist/" );
            });';			
		}
		elseif ($current_lang == 'en'){
			$jquery = '
            jQuery( document ).ready(function($){
                $( ".wishlist_products_counter" ).attr( "href", "https://anix-shop.com/en/wishlist/" );
            });';	
		}
		else{
			$jquery = '
            jQuery( document ).ready(function($){
                $( ".wishlist_products_counter" ).attr( "href", "https://anix-shop.com/ru/wishlist/" );
            });';
		}
        
        wp_add_inline_script('woocommerce', $jquery);
       
    }    
   add_action( 'wp_enqueue_scripts', 'yith_wcwl_change_wishlist_url_counter', 999 );
}

function cc_mime_types($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'cc_mime_types');


add_action('admin_init', 'restrict_customer_access_wp_admin');
function restrict_customer_access_wp_admin() {
    if (current_user_can('customer') && is_admin()) {
        wp_redirect(home_url());
        exit;
    }
}