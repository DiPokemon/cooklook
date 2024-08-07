<?php
  $currenturl = get_permalink();

  $head_code = carbon_get_theme_option( 'head_code' );
  $footer_code = carbon_get_theme_option( 'footer_code' );
  $htmlsitemap_link = carbon_get_theme_option( 'html_sitemap_link' );
  $htmlsitemap_text = carbon_get_theme_option( 'html_sitemap_text' );
  $policy_link = carbon_get_theme_option( 'policy_privacy_link' );
  $policy_text = carbon_get_theme_option( 'policy_privacy_text' );
  $copyright = carbon_get_theme_option( 'copyright' );

  // $contacts_main_phone_front = phone_front(carbon_get_theme_option( 'main_phone' ));
  // $contacts_add_phone_front = phone_front(carbon_get_theme_option( 'second_phone' ));
  // $contacts_main_phone_href = phone_href(carbon_get_theme_option( 'main_phone' ));
  // $contacts_add_phone_href = phone_href(carbon_get_theme_option( 'second_phone' ));

  $email = carbon_get_theme_option( 'email' );
  $ads_email = carbon_get_theme_option( 'ads_email' );
  // $contacts_vk = carbon_get_theme_option( 'vk' );
  // $contacts_wa = phone_wa(carbon_get_theme_option( 'wa' ));
  // $contacts_tg = carbon_get_theme_option( 'tg' );
  // $contacts_inst = carbon_get_theme_option( 'inst' );
  // $contacts_fb = carbon_get_theme_option( 'fb' );

  $address_city = carbon_get_theme_option( 'address_city' );
  $address_street = carbon_get_theme_option( 'address_street' );
  $address_building = carbon_get_theme_option( 'address_build' );
  $address_zipcode = carbon_get_theme_option( 'address_index' );
  $address_latitude = carbon_get_theme_option( 'address_latitude' );
  $address_longitude = carbon_get_theme_option( 'address_longitude' );

  $title_404 = carbon_get_theme_option( 'title_404' );
  $subtitle_404 = carbon_get_theme_option( 'subtitle_404' );
  $text_404 = carbon_get_theme_option( 'text_404' );
  $img_404 = carbon_get_theme_option( 'image_404' );
  $btn_404 = carbon_get_theme_option( 'btn_404' );

  $cf_title = carbon_get_theme_option( 'cf_title' );
  $cf_subtitle = carbon_get_theme_option( 'cf_subtitle' );
  $cf_shortcode = carbon_get_theme_option( 'cf_shortcode' );

  /* MAIN PAGE */
  $popular_cats_title =  carbon_get_the_post_meta( 'popular_cats_title' );
  $popular_cats = carbon_get_the_post_meta( 'popular_cats' );

  $new_recipes_title = carbon_get_the_post_meta( 'new_recipes_title' );
  $popular_recipes_title = carbon_get_the_post_meta( 'popular_recipes_title' );
  $recipes_categories_title = carbon_get_the_post_meta( 'recipes_categories_title' );
?>