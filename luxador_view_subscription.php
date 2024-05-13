<?php
/**
 * Subscription details
 *
 * @package YITH WooCommerce Subscription
 * @since   2.0.0
 * @author  YITH <plugins@yithemes.com>
 *
 * @var YWSBS_Subscription $subscription Current Subscription.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wc_print_notices();

if ( $subscription->get_user_id() !== get_current_user_id() ) {
	esc_html_e( 'You do not have the necessary permissions to access to this page', 'yith-woocommerce-subscription' );
	return;
}
$subscription_id          = $subscription->get_id();
$next_payment_due_date    = ( ! in_array( $subscription->status, array( 'paused', 'cancelled' ), true ) && $subscription->payment_due_date ) ? date_i18n( wc_date_format(), $subscription->payment_due_date ) : '';
$subscription_status_list = ywsbs_get_status();
$status                   = $subscription->get_status(); //phpcs:ignore
$subscription_status      = $subscription_status_list[ $status ];
$subscription_name        = sprintf( '%s - %s', $subscription->get_number(), $subscription->get_product_name() );
$subscription_title       = apply_filters( 'ywsbs_subscription_view_title', sprintf( '%s %s - %s', __( 'Subscription', 'yith-woocommerce-subscription' ), $subscription->get_number(), $subscription->get_product_name() ), $subscription );
$last_billing_date        = $subscription->get_last_billing_date();

$product_id = $subscription->get_product_id();
$product = wc_get_product($product_id);
$subscription_img = wp_get_attachment_url($product->get_image_id());


$billing_address    = $subscription->get_address_fields( 'billing', true );
$shipping_address   = $subscription->get_address_fields( 'shipping', true );
$billing_formatted  = WC()->countries->get_formatted_address( $billing_address );
$shipping_formatted = WC()->countries->get_formatted_address( $shipping_address );




$shipping_address_1 = $shipping_address['address_1'];
$shipping_address_2 = $shipping_address['address_2'];
$shipping_city = $shipping_address['city'];
$shipping_postcode = $shipping_address['postcode'];
$shipping_country = $shipping_address['country'];
$shipping_state = $shipping_address['state'];

?>
<div class="ywsbs-subscription-view-wrap">
	<?php do_action('ywsbs_before_subscription_view', $subscription_id ); ?>
    <div class="subscription_header">
        <div class="left_column">
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( YITH_WC_Subscription::$endpoint ) ); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M15.5306 18.9698C15.6003 19.0395 15.6556 19.1222 15.6933 19.2132C15.731 19.3043 15.7504 19.4019 15.7504 19.5004C15.7504 19.599 15.731 19.6965 15.6933 19.7876C15.6556 19.8786 15.6003 19.9614 15.5306 20.031C15.461 20.1007 15.3782 20.156 15.2872 20.1937C15.1961 20.2314 15.0986 20.2508 15 20.2508C14.9015 20.2508 14.8039 20.2314 14.7128 20.1937C14.6218 20.156 14.5391 20.1007 14.4694 20.031L6.96938 12.531C6.89965 12.4614 6.84433 12.3787 6.80659 12.2876C6.76885 12.1966 6.74942 12.099 6.74942 12.0004C6.74942 11.9019 6.76885 11.8043 6.80659 11.7132C6.84433 11.6222 6.89965 11.5394 6.96938 11.4698L14.4694 3.96979C14.6101 3.82906 14.801 3.75 15 3.75C15.199 3.75 15.3899 3.82906 15.5306 3.96979C15.6714 4.11052 15.7504 4.30139 15.7504 4.50042C15.7504 4.69944 15.6714 4.89031 15.5306 5.03104L8.56032 12.0004L15.5306 18.9698Z" fill="#A58651"/>
                </svg>
                <?php esc_html_e( 'Back to subscriptions list', 'woocommerce' ); ?>
            </a>

            <div class="subscription_id">
                <span><?= __('Subscription', 'woocommerce') ?></span>
                <span class="id_num"><?= $subscription_id ?></span>
            </div>

            <span class="subscription_status subscription_status-<?= $subscription_status ?>"><?= $subscription_status ?></span>
        </div>

        <div class="right_column">
            <?php do_action( 'ywsbs_after_subscription_status', $subscription ); ?>
        </div>
    </div>

    <div class="subscription_info">
        <div class="subscription_info-row">
            <span class="subscription_info-row-label sub_name">
                <?= __('Product', 'woocommerce') ?>
            </span>

            <div class="my_subscriptions-name">
                <img src="<?= $subscription_img ?>" alt="">
                <?php if ( $subscription->get_user_id() === get_current_user_id() ) : ?>
                    <a href="<?php echo esc_url( ywsbs_get_view_subscription_url( $subscription->get_id() ) ); ?>">
                        <?= $subscription->get( 'product_name' ) ?>
                    </a>
                <?php else : ?>
                    <span><?= $subscription->get( 'product_name' ) ?></span>
                <?php endif; ?>                
            </div>
        </div>

        <div class="subscription_info-row">
            <span class="subscription_info-row-label">
                <?= __('Subsсription plan', 'woocommerce') ?>
            </span>

            <span class="subscription_info-row-value">
                <?php 
                    if ( $subscription->variation_id ) {
						yith_ywsbs_get_product_meta( $subscription, $subscription->get_variation() );
					}
                ?>
                <?php do_action( 'ywsbs_after_subscription_plan_info', $subscription ); ?>
            </span>
        </div>

        <div class="subscription_info-row">
            <span class="subscription_info-row-label">
                <?= __('Subscription created on', 'woocommerce') ?>
            </span>

            <span class="subscription_info-row-value">
                <?php echo esc_html( date_i18n( wc_date_format(), $subscription->get_start_date() ) ); ?>
            </span>            
        </div>

        <div class="subscription_info-row">
            <span class="subscription_info-row-label">
                <?= __('Regular payment/Monthly', 'woocommerce') ?>
            </span>

            <span class="subscription_info-row-value subscription_info-row-value-price">
                <?php echo wp_kses_post( YWSBS_Subscription_Helper()->get_formatted_recurring( $subscription ) ); ?>
            </span>    
        </div>

        <div class="subscription_info-row">
            <span class="subscription_info-row-label">
                <?= __('Subscription ends', 'woocommerce') ?>
            </span>

            <span class="subscription_info-row-value">
                <?php echo esc_html( date_i18n( wc_date_format(), $subscription->get_expired_date() ) ); ?>
            </span>            
        </div>

        <div class="subscription_info-row">
            <span class="subscription_info-row-label">
                <?= __('Next payment date', 'woocommerce') ?>
            </span>

            <span class="subscription_info-row-value">
                <?php echo esc_html( wc_format_datetime( $last_billing_date ) ); ?>
            </span>   
        </div>

        <div class="subscription_info-row">
            <span class="subscription_info-row-label">
                <?= __('Shipping address', 'woocommerce') ?>
            </span>

            <span class="subscription_info-row-value">
                <?= $shipping_address_1.' '.$shipping_address_2.' '.$shipping_city.', '.$shipping_state.' '.$shipping_country.', '.$shipping_postcode ?>
            </span>  
        </div>

        <div class="subscription_info-row">
            <span class="subscription_info-row-label">
                <?= __('Payment details', 'woocommerce') ?>
            </span>

            <span class="subscription_info-row-value">
                <?php echo esc_html( $subscription->get_payment_method_title() ); ?>
            </span>  
        </div>
    </div>	

	<?php do_action( 'ywsbs_my_account_after_subscription_info', $subscription ); ?>


