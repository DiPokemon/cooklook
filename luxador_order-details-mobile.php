<?php

/**
 * Order Item Details Mobile
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-details-item.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woo.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 5.2.0
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!apply_filters('woocommerce_order_item_visible', true, $item)) {
	return;
}
?>

<div class="<?php echo esc_attr(apply_filters('woocommerce_order_item_class', 'woocommerce-table__line-item order_item', $item, $order)); ?>"> 
    <div class="woocommerce-table__product-name product-name">
		<?php
		$is_visible        = $product && $product->is_visible();
		$product_permalink = apply_filters('woocommerce_order_item_permalink', $is_visible ? $product->get_permalink($item) : '', $item, $order);

		echo wp_kses_post(apply_filters('woocommerce_order_item_name', $item->get_name(), $item, $is_visible));

		do_action('woocommerce_order_item_meta_start', $item_id, $item, $order, false);

		wc_display_item_meta($item); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		do_action('woocommerce_order_item_meta_end', $item_id, $item, $order, false);
		?>
	</div>

    <div class="bottom_row">
        <div class="woocommerce-table__product-price product-price">
            <span class="bottom_row-label"><?= __('Price', 'woocommerce') ?></span>
            <span class="bottom_row-value">
                <?php
                    echo ($item['total'] >  0) ? $product->get_price() . get_woocommerce_currency_symbol() : $item['total'] . get_woocommerce_currency_symbol();
                ?>
            </span>
        </div>

        <div class="woocommerce-table__product-amount product-amount">
            <span class="bottom_row-label"><?= __('Amount', 'woocommerce') ?></span>
            <span class="bottom_row-value">
                <?php
                    $qty          = $item->get_quantity();
                    $refunded_qty = $order->get_qty_refunded_for_item($item_id);

                    if ($refunded_qty) {
                        $qty_display = '<del>' . esc_html($qty) . '</del> <ins>' . esc_html($qty - ($refunded_qty * -1)) . '</ins>';
                    } else {
                        $qty_display = esc_html($qty);
                    }
                    echo $qty_display;
                ?>
            </span>
        </div>

        <div class="woocommerce-table__product-total product-total">
            <span class="bottom_row-label"><?= __('Total sum', 'woocommerce') ?></span>
            <span class="bottom_row-value">
                <?= $order->get_formatted_line_subtotal($item); ?>
            </span>
        </div>
    </div>
</div>

<?php if ($show_purchase_note && $purchase_note) : ?>

	<tr class="woocommerce-table__product-purchase-note product-purchase-note">

		<td colspan="2"><?php echo wpautop(do_shortcode(wp_kses_post($purchase_note))); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
						?></td>

	</tr>

<?php endif; ?>