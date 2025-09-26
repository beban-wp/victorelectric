<?php
/**
 * Custom Order Status for Installment Payments
 * 
 * Adds a custom order status "پرداخت قسط اول" for orders with installment products
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}


// تغییر متن وضعیت های ووکامرس
add_filter( 'wc_order_statuses', 'beban_rename_order_status', 20, 1 );
function beban_rename_order_status( $order_statuses ) {
    $order_statuses['wc-completed']  = _x( 'تحویل به مراکز پستی', 'Order status', 'woocommerce' );
	$order_statuses['wc-refunded']  = _x( 'مرجوع شده', 'Order status', 'woocommerce' );
    $order_statuses['wc-processing'] = _x( 'در حال بررسی', 'Order status', 'woocommerce' );
    $order_statuses['wc-on-hold']    = _x( 'در حال بسته بندی', 'Order status', 'woocommerce' );
    return $order_statuses;
}


/**
 * Handle installment status meta on checkout
 */
add_action('woocommerce_checkout_order_processed', 'beban_handle_installment_order_status', 20, 1);
function beban_handle_installment_order_status($order_id) {
    $order = wc_get_order($order_id);
    
    if (!$order) {
        return;
    }
    
    $is_installment = false;
    
    // Check all items in the order
    foreach ($order->get_items() as $item) {
        $product = wc_get_product($item->get_product_id());
        
        if (!$product) {
            continue;
        }
        
        // Get order type from order item meta (this is the correct method for variable products)
        $order_type = $item->get_meta('pa_type-order');
        
        // If not found in item meta, try from product attributes
        if (empty($order_type)) {
            $order_type = $product->get_attribute('pa_type-order');
        }
        
        // If still not found, try the old method
        if (empty($order_type)) {
            $order_type = $product->get_attribute('type-order');
        }
        
        // Check if it's installment
        if ($order_type && (
            strtolower(trim($order_type)) === 'pay-deposit' || 
            trim($order_type) === 'خرید اقساطی'
        )) {
            $is_installment = true;
            break;
        }
    }
    
    // If this is an installment order, add installment status meta
    if ($is_installment) {
        // Add installment status meta if not already set
        if (!get_post_meta($order_id, '_beban_installment_status', true)) {
            update_post_meta($order_id, '_beban_installment_status', 'First');
        }
    }
}


// Register Custom Order Status for Manual Orders
function register_manual_order_status() {
    register_post_status('wc-special-payment', array(
        'label'                     => 'پرداخت خاص',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('پرداخت خاص (%s)', 'پرداخت خاص (%s)')
    ));
}
add_action('init', 'register_manual_order_status');

// Add Custom Order Status to WooCommerce
function add_manual_order_status_to_woocommerce($order_statuses) {
    $order_statuses['wc-special-payment'] = 'پرداخت خاص';
    return $order_statuses;
}
add_filter('wc_order_statuses', 'add_manual_order_status_to_woocommerce');

