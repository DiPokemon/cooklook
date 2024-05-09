<?php
/**
 * Order details
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-details.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woo.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.5.0
 *
 * @var bool $show_downloads Controls whether the downloads table should be rendered.
 */

defined( 'ABSPATH' ) || exit;

$order = wc_get_order( $order_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

if ( ! $order ) {
	return;
}

$order_items           = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
$show_purchase_note    = $order->has_status( apply_filters( 'woocommerce_purchase_note_order_statuses', array( 'completed', 'processing' ) ) );
$show_customer_details = is_user_logged_in() && $order->get_user_id() === get_current_user_id();
$downloads             = $order->get_downloadable_items();

if ( $show_downloads ) {
	wc_get_template(
		'order/order-downloads.php',
		array(
			'downloads'  => $downloads,
			'show_title' => true,
		)
	);
}
$show_shipping = ! wc_ship_to_billing_address_only() && $order->needs_shipping_address();

?>
<section class="woocommerce-order-details">
	<?php do_action( 'woocommerce_order_details_before_order_table', $order ); ?>
	
	<div class="order_view-header">
        <span class="order_number">
            <?= __('Order','woocommerce').' '.$order->get_order_number() ?>
        </span>        
        <?php $repeat_url = wp_nonce_url( add_query_arg( 'order_again', $order->get_id(), wc_get_endpoint_url( 'view-order', $order->get_id(), wc_get_page_permalink( 'myaccount' ) ) ), 'woocommerce-order_again' ); ?>
        <?php if ($order->get_status() == 'completed') : ?>
            <div class="order_view-buttons desktop">            
                <?php
                    Wf_Woocommerce_Packing_List::generate_print_button_for_user($order,$order_id,'download_invoice',__('Download invoice', 'woocommerce'))
                ?>
                <a href="<?= $repeat_url?> " class="repeat_order"><?= __('Repeat order', 'woocommerce') ?></a>
            </div>
        <?php endif; ?>             
    </div>
    <div class="order_info">
        <span class="order_status order_status-<?=$order->get_status()?>">
            <?= wc_get_order_status_name( $order->get_status() ) ?>
        </span>
        <span class="order_date">
            <?= wc_format_datetime( $order->get_date_created() )  ?>
        </span>
    </div>
	<div class="order_view-header">
        <?php if ($order->get_status() == 'completed') : ?>
            <div class="order_view-buttons mobile">            
                <a href="<?= $repeat_url?> " class="repeat_order"><?= __('Repeat order', 'woocommerce') ?></a>
                <?php
                    Wf_Woocommerce_Packing_List::generate_print_button_for_user($order,$order_id,'download_invoice',__('Download invoice', 'woocommerce'))
                ?>
            </div>
        <?php endif; ?>
    </div>
	

	<table class="woocommerce-table woocommerce-table--order-details shop_table order_details display_desktop">	
		<thead>
			<tr>
				<th width="65%" class="woocommerce-table__product-name product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
				<th width="15%" class="woocommerce-table__product-name product-price"><?php esc_html_e( 'Price', 'woocommerce' ); ?></th>
				<th width="10%" class="woocommerce-table__product-name product-amount"><?php esc_html_e( 'Amount', 'woocommerce' ); ?></th>
				<th width="10%" class="woocommerce-table__product-table product-total"><?php esc_html_e( 'Sum', 'woocommerce' ); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php
			do_action( 'woocommerce_order_details_before_order_table_items', $order );

			foreach ( $order_items as $item_id => $item ) {
				$product = $item->get_product();

				wc_get_template(
					'order/order-details-item.php',
					array(
						'order'              => $order,
						'item_id'            => $item_id,
						'item'               => $item,
						'show_purchase_note' => $show_purchase_note,
						'purchase_note'      => $product ? $product->get_purchase_note() : '',
						'product'            => $product,
					)
				);
			}

			do_action( 'woocommerce_order_details_after_order_table_items', $order );
			?>
		</tbody>
	</table>

    <div id="order_details-mobile" class="woocommerce-table woocommerce-table--order-details shop_table order_details display_mobile">
        <?php foreach ( $order_items as $item_id => $item ) {
				$product = $item->get_product();

				wc_get_template(
					'order/order-details-item-mobile.php',
					array(
						'order'              => $order,
						'item_id'            => $item_id,
						'item'               => $item,
						'show_purchase_note' => $show_purchase_note,
						'purchase_note'      => $product ? $product->get_purchase_note() : '',
						'product'            => $product,
					)
				);
			}
        ?>
        <a id="show_more_items-btn" href="#" class="show_more-items"><?= __('Show all products', 'woocommerce') ?></a>
    </div>

	<?php do_action( 'woocommerce_order_details_after_order_table', $order ); ?>
</section>

<section class="woocommerce-customer-details">	
    <div class="woocommerce-customer-details-leftcolumn">
        <?php 
            if ($order) {
                $shipping_items = $order->get_items('shipping');
                $shipping_method_title = '';
                $shipping_total = 0;

                foreach ($shipping_items as $item_id => $shipping_item) {                    
                    $shipping_method_title = $shipping_item->get_method_title();
                    $shipping_total = $shipping_item->get_total();
                }

                $shipping_address_1 = $order->get_shipping_address_1();
                $shipping_address_2 = $order->get_shipping_address_2();
                $shipping_city = $order->get_shipping_city();
                $shipping_postcode = $order->get_shipping_postcode();
                $shipping_country = $order->get_shipping_country();
                $shipping_state = $order->get_shipping_state();

                $payment_method_title = $order->get_payment_method_title();
                $billing_address_1 = $order->get_billing_address_1();
                $billing_address_2 = $order->get_billing_address_2();
                $billing_city = $order->get_billing_city();
                $billing_postcode = $order->get_billing_postcode();                
                $billing_country = $order->get_billing_country();
                $billing_state = $order->get_billing_state();

                $subtotal = $order->get_subtotal();
                $total_tax = $order->get_total_tax();
                $total = $order->get_total();
            }
        ?>
        <?php if($shipping_method_title): ?>
            <div class="customer_info-item">
                <span class="customer_info-item-label"><?= __('Shipping method', 'woocommerce') ?>:</span>
                <span class="customer_info-item-value"><?= $shipping_method_title ?></span>
            </div>
        <?php endif ?>

        <div class="customer_info-item">
            <span class="customer_info-item-label"><?= __('Shipping address', 'woocommerce') ?>:</span>
            <span class="customer_info-item-value"><?= $shipping_address_1.' '.$shipping_address_2.' '.$shipping_city.', '.$shipping_state.' '.$shipping_country.', '.$shipping_postcode ?></span>
        </div>

        <div class="customer_info-item">
            <span class="customer_info-item-label"><?= __('Payment method', 'woocommerce') ?>:</span>
            <span class="customer_info-item-value"><?= $payment_method_title ?></span>
        </div>

        <div class="customer_info-item">
            <span class="customer_info-item-label"><?= __('Billing address', 'woocommerce') ?>:</span>
            <span class="customer_info-item-value"><?= $billing_address_1.' '.$billing_address_2.' '.$billing_city.', '.$billing_state.' '.$billing_country.', '.$billing_postcode ?></span>
        </div>
    </div>
	<div class="woocommerce-customer-details-rightcolumn">
        <div class="customer_info-item">
			<span class="customer_info-item-label"><?= __('Subtotal', 'woocommerce') ?>:</span>
			<span class="customer_info-item-value"><?= wc_price($subtotal) ?></span>
        </div>

        <div class="customer_info-item">
			<span class="customer_info-item-label"><?= __('VAT', 'woocommerce') ?>:</span>
			<span class="customer_info-item-value"><?= wc_price($total_tax) ?></span>
        </div>

        <div class="customer_info-item shipping_total">
			<span class="customer_info-item-label"><?= __('Shipping', 'woocommerce') ?>:</span>
			<span class="customer_info-item-value"><?php echo $shipping_total == 0 ? __('Free', 'woocommerce') : wc_price($shipping_total); ?>    </span>
        </div>

        <div class="customer_info-item order_total">
			<span class="customer_info-item-label"><?= __('Total', 'woocommerce') ?>:</span>
			<span class="customer_info-item-value"><?= wc_price($total) ?></span>
        </div>
	</div>
</section>

<?php
    $show_more_text = __('Show all products', 'woocommerce');
    $show_less_text = __('Show less products', 'woocommerce');
?>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var link = document.getElementById('show_more_items-btn');
    var details = document.getElementById('order_details-mobile');
    
    link.addEventListener('click', function(e) {
      e.preventDefault(); // Предотвратить действие по умолчанию (переход по ссылке)
      
      // Добавить или удалить класс open
      details.classList.toggle('open');
      
      // Изменить текст и класс ссылки в зависимости от состояния
      if (details.classList.contains('open')) {
        link.textContent = '<?= $show_less_text ?>';
        link.classList.add('active');
      } else {
        link.textContent = '<?= $show_more_text ?>';
        link.classList.remove('active');
      }
    });
  });
</script>


