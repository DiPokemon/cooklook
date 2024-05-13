<?php
/**
 * My Account Subscriptions Section of YITH WooCommerce Subscription
 *
 * @package YITH WooCommerce Subscription
 * @since   1.0.0
 * @version 2.0.0
 * @author  YITH <plugins@yithemes.com>
 *
 * @var array $subscriptions Subscription List.
 * @var $max_pages
 * @var $current_page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

do_action( 'ywsbs_my_subscriptions_view_before' );
$subscription_status_list = ywsbs_get_status();
?>
<?php if ( empty( $subscriptions ) ) : ?>
    <div class="empty_subscribtions">
        <img src="" alt="">
        <span class="empty_subscription-title">
            <?= __('You have no any subscriptions yet', 'woocommerce') ?>
        </span>
        <a href="<?= get_permalink( wc_get_page_id( 'shop' ) ) ?>"><?= __('Retun shopping', 'woocommerce') ?></a>

    </div>	
<?php else : ?>
    <h2 class="subs_title"><?= __('My subscription', 'woocommerce') ?></h2>
    <div class="my_subscriptions-grid">
    <?php
        foreach ( $subscriptions as $subscription_post ) :

        $subscription_id       = is_numeric( $subscription_post ) ? $subscription_post : $subscription_post->ID;
        $subscription          = ywsbs_get_subscription( $subscription_id );
        $subscription_name     = sprintf( '%s - %s', $subscription->get_number(), $subscription->get( 'product_name' ) );
             
        $subscription_status   = $subscription_status_list[ $subscription->get_status() ];
        $next_payment_due_date = ( ! in_array( $subscription_status, array( 'paused', 'cancelled' ), true ) && $subscription->get( 'payment_due_date' ) ) ? date_i18n( wc_date_format(), $subscription->get( 'payment_due_date' ) ) : '<span class="empty-date">-</span>';
        $start_date            = ( $subscription->get( 'start_date' ) ) ? date_i18n( wc_date_format(), $subscription->get( 'start_date' ) ) : '<div class="empty-date">-</div>';
        $end_date              = ( $subscription->get( 'end_date' ) ) ? date_i18n( wc_date_format(), $subscription->get( 'end_date' ) ) : false;
        $end_date              = ! $end_date && ( $subscription->get( 'expired_date' ) ) ? date_i18n( wc_date_format(), $subscription->get( 'expired_date' ) ) : '<div class="empty-date">-</div>';
        $last_billing_date        = $subscription->get_last_billing_date();

        $product_id = $subscription->get_product_id();
        $product = wc_get_product($product_id);
        $subscription_img  = wp_get_attachment_url($product->get_image_id());
    ?>

    

    <div class="my_subscriptions-item">
        <div class="my_subscriptions-item-header">
            <div class="subscriptions_header-left">
                <div class="subscription_id">
                    <span><?= __('Subscription ID', 'woocommerce') ?></span>
                    <span class="id_num"><?= $subscription_id ?></span>
                </div>
                <span class="subscription_status subscription_status-<?= $subscription_status ?>"><?= $subscription_status ?></span>
            </div>

            <?php if ( $subscription->can_be_resubscribed() ) : ?>
                <a class="hide_mobile" href="<?php echo esc_url( ywsbs_get_resubscribe_subscription_url( $subscription ) ); ?>"><?= __('Renew', 'woocommerce') ?></a>
            <?php elseif ( $subscription->get_user_id() === get_current_user_id() ): ?>
                <a class="hide_mobile" href="<?php echo esc_url( ywsbs_get_view_subscription_url( $subscription->get_id() ) ); ?>"><?= __('Edit', 'woocommerce') ?></a>
            <?php endif; ?>
        </div>

        <div class="my_subscriptions-item-body">
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

            <div class="my_subscriptions-details">
                <?php if ( $subscription->get_expired_date() ) : ?>
                    <div class="subscription_details-row">
                        <span class="details_label"><?= __('Subscription ends', 'woocommerce') ?></span>
                        <span class="details_value"><?php echo esc_html( date_i18n( wc_date_format(), $subscription->get_expired_date() ) ); ?></span>
                    </div>
                 <?php endif ?>

                <div class="subscription_details-row">
                    <span class="details_label"><?= __('Next payment', 'woocommerce') ?></span>
                    <span class="details_value"><?= $next_payment_due_date ?></span>
                </div>

                <?php if ( ! empty( $last_billing_date ) ) : ?>
                    <div class="subscription_details-row">
                        <span class="details_label"><?= __('Last payment', 'woocommerce') ?></span>
                        <span class="details_value"><?php echo esc_html( wc_format_datetime( $last_billing_date ) ); ?></span>
                    </div>   
                <?php endif ?>

                <div class="subscription_details-row">
                    <span class="details_label"><?= __('Payment ammount', 'woocommerce') ?></span>
                    <span class="details_value subscription_price"><?php echo wp_kses_post( YWSBS_Subscription_Helper()->get_formatted_recurring( $subscription ) ); ?></span>
                </div>
            </div>            
        </div>
        
        <div class="display_mobile my_subscription-item-footer">
            <?php if ( $subscription->can_be_resubscribed() ) : ?>
                <a href="<?php echo esc_url( ywsbs_get_resubscribe_subscription_url( $subscription ) ); ?>"><?= __('Renew', 'woocommerce') ?></a>
            <?php elseif ( $subscription->get_user_id() === get_current_user_id() ): ?>
                <a href="<?php echo esc_url( ywsbs_get_view_subscription_url( $subscription->get_id() ) ); ?>"><?= __('Edit', 'woocommerce') ?></a>
            <?php endif; ?>
        </div>
    </div>
   
<?php endforeach; ?>
    </div>
	<?php
endif;

$all_subs                                 = YWSBS_Subscription_Helper()->get_subscriptions_by_user( get_current_user_id(), -1 );
$max_pages                                = ceil( count( $all_subs ) / 4 );

if ( 1 < $max_pages ) :
	?>
		<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
			<?php if ( 1 !== $current_page ) : ?>
				<a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button" href="<?php echo esc_url( wc_get_endpoint_url( 'my-subscription', $current_page - 1 ) ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24" viewBox="0 0 25 24" fill="none">
                        <path d="M16.0306 18.9698C16.1003 19.0395 16.1556 19.1222 16.1933 19.2132C16.231 19.3043 16.2504 19.4019 16.2504 19.5004C16.2504 19.599 16.231 19.6965 16.1933 19.7876C16.1556 19.8786 16.1003 19.9614 16.0306 20.031C15.9609 20.1007 15.8782 20.156 15.7872 20.1937C15.6961 20.2314 15.5985 20.2508 15.5 20.2508C15.4014 20.2508 15.3039 20.2314 15.2128 20.1937C15.1218 20.156 15.039 20.1007 14.9694 20.031L7.46935 12.531C7.39962 12.4614 7.3443 12.3787 7.30656 12.2876C7.26882 12.1966 7.24939 12.099 7.24939 12.0004C7.24939 11.9019 7.26882 11.8043 7.30656 11.7132C7.3443 11.6222 7.39962 11.5394 7.46935 11.4698L14.9694 3.96979C15.1101 3.82906 15.301 3.75 15.5 3.75C15.699 3.75 15.8899 3.82906 16.0306 3.96979C16.1713 4.11052 16.2504 4.30139 16.2504 4.50042C16.2504 4.69944 16.1713 4.89031 16.0306 5.03104L9.06029 12.0004L16.0306 18.9698Z" fill="#71717A"/>
                    </svg>
                </a>
			<?php endif; ?>

            <?php for ($i = 1; $i <= $max_pages; $i++) : ?>
                <a href="<?php echo esc_url( wc_get_endpoint_url( 'my-subscription', $i ) ); ?>" class="<?= ($i == $current_page) ? 'current' : ''; ?> pagination_number"><?= $i ?></a>
            <?php endfor; ?>

			<?php if ( intval( $max_pages ) !== $current_page ) : ?>
				<a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button" href="<?php echo esc_url( wc_get_endpoint_url( 'my-subscription', $current_page + 1 ) ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24" viewBox="0 0 25 24" fill="none">
                        <path d="M17.5306 12.531L10.0306 20.031C9.96093 20.1007 9.87821 20.156 9.78716 20.1937C9.69612 20.2314 9.59854 20.2508 9.49999 20.2508C9.40144 20.2508 9.30386 20.2314 9.21282 20.1937C9.12177 20.156 9.03905 20.1007 8.96936 20.031C8.89968 19.9614 8.84441 19.8786 8.80669 19.7876C8.76898 19.6965 8.74957 19.599 8.74957 19.5004C8.74957 19.4019 8.76898 19.3043 8.80669 19.2132C8.84441 19.1222 8.89968 19.0395 8.96936 18.9698L15.9397 12.0004L8.96936 5.03104C8.82863 4.89031 8.74957 4.69944 8.74957 4.50042C8.74957 4.30139 8.82863 4.11052 8.96936 3.96979C9.11009 3.82906 9.30097 3.75 9.49999 3.75C9.69901 3.75 9.88988 3.82906 10.0306 3.96979L17.5306 11.4698C17.6003 11.5394 17.6557 11.6222 17.6934 11.7132C17.7312 11.8043 17.7506 11.9019 17.7506 12.0004C17.7506 12.099 17.7312 12.1966 17.6934 12.2876C17.6557 12.3787 17.6003 12.4614 17.5306 12.531Z" fill="#71717A"/>
                    </svg>
                </a>
			<?php endif; ?>
		</div>
<?php endif;

do_action( 'ywsbs_my_subscriptions_view_after' );
?>
