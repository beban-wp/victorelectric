<?php
/**
 * Second Installment Payment Handler
 * 
 * Handles the creation and processing of second installment payments
 * Uses a virtual product approach for simplicity
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create or get the second installment virtual product
 * 
 * @return int|false Product ID or false on failure
 */
function beban_create_second_installment_product() {
    // Check if product already exists
    $existing_product = get_posts(array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_beban_second_installment_product',
                'value' => 'yes',
                'compare' => '='
            )
        ),
        'posts_per_page' => 1
    ));
    
    if (!empty($existing_product)) {
        return $existing_product[0]->ID;
    }
    
    // Create new product
    $product_data = array(
        'post_title' => 'قسط دوم',
        'post_content' => 'محصول مجازی برای پرداخت قسط دوم سفارشات اقساطی',
        'post_status' => 'publish',
        'post_type' => 'product',
        'post_author' => 1
    );
    
    $product_id = wp_insert_post($product_data);
    
    if (is_wp_error($product_id)) {
        return false;
    }
    
    // Set product as virtual
    update_post_meta($product_id, '_virtual', 'yes');
    update_post_meta($product_id, '_downloadable', 'no');
    update_post_meta($product_id, '_beban_second_installment_product', 'yes');
    update_post_meta($product_id, '_visibility', 'hidden');
    update_post_meta($product_id, '_stock_status', 'instock');
    update_post_meta($product_id, '_manage_stock', 'no');
    
    // Set initial price (will be updated dynamically)
    update_post_meta($product_id, '_regular_price', '0');
    update_post_meta($product_id, '_price', '0');
    
    return $product_id;
}

/**
 * Add second installment payment button to order details page
 * 
 * @param WC_Order $order The order object
 */
function beban_add_second_installment_payment_button($order) {
    // Only show for installment orders with First status
    if (!beban_order_has_installment_products($order)) {
        return;
    }
    
    $installment_status = $order->get_meta('_beban_installment_status');
    if (empty($installment_status)) {
        $installment_status = 'First';
    }
    
    // Only show for First status (waiting for second installment)
    if ($installment_status !== 'First') {
        return;
    }
    
    // Calculate remaining amount
    $remaining_amount = beban_calculate_remaining_amount($order);
    
    if ($remaining_amount <= 0) {
        return;
    }
    
    // Get or create second installment product
    $product_id = beban_create_second_installment_product();
    
    if (!$product_id) {
        return;
    }
    
    // Update product price
    update_post_meta($product_id, '_regular_price', $remaining_amount);
    update_post_meta($product_id, '_price', $remaining_amount);
    
    // Create payment URL
    $payment_url = add_query_arg(array(
        'add-to-cart' => $product_id,
        'quantity' => 1,
        'beban_installment_order_id' => $order->get_id(),
        'beban_installment_amount' => $remaining_amount
    ), wc_get_cart_url());
    
    // Display payment button
    echo '<div class="beban-second-installment-payment">';
    echo '<div class="payment-info">';
    echo '<h4>پرداخت قسط دوم</h4>';
    echo '<p>مبلغ باقی‌مانده: <strong>' . wc_price($remaining_amount) . '</strong></p>';
    echo '<a href="' . esc_url($payment_url) . '" class="button button-primary beban-pay-second-installment">';
    echo 'پرداخت قسط دوم';
    echo '</a>';
    echo '</div>';
    echo '</div>';
}

/**
 * Handle second installment product addition to cart
 * 
 * @param string $cart_item_key The cart item key
 * @param int $product_id The product ID
 * @param int $quantity The quantity
 * @param int $variation_id The variation ID
 * @param array $variation The variation data
 * @param array $cart_item_data The cart item data
 */
function beban_handle_second_installment_cart_addition($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    // Check if this is a second installment product
    if (get_post_meta($product_id, '_beban_second_installment_product', true) !== 'yes') {
        return;
    }
    
    // Get installment order ID and amount from URL
    $installment_order_id = isset($_GET['beban_installment_order_id']) ? intval($_GET['beban_installment_order_id']) : 0;
    $installment_amount = isset($_GET['beban_installment_amount']) ? floatval($_GET['beban_installment_amount']) : 0;
    
    if (!$installment_order_id || !$installment_amount) {
        wc_add_notice('خطا در پردازش پرداخت قسط دوم', 'error');
        return;
    }
    
    // Validate order
    $order = wc_get_order($installment_order_id);
    if (!$order || !beban_order_has_installment_products($order)) {
        wc_add_notice('سفارش اقساطی یافت نشد', 'error');
        return;
    }
    
    // Validate amount
    $calculated_amount = beban_calculate_remaining_amount($order);
    if (abs($calculated_amount - $installment_amount) > 0.01) {
        wc_add_notice('مبلغ پرداخت صحیح نیست', 'error');
        return;
    }
    
    // Add meta data to cart item
    WC()->cart->cart_contents[$cart_item_key]['beban_installment_order_id'] = $installment_order_id;
    WC()->cart->cart_contents[$cart_item_key]['beban_installment_amount'] = $installment_amount;
    
    // Update cart
    WC()->cart->set_session();
}

/**
 * Process second installment payment after successful checkout
 * 
 * @param int $order_id The new order ID
 * @param array $posted_data The posted data
 * @param WC_Order $order The order object
 */
function beban_process_second_installment_payment($order_id, $posted_data, $order) {
    // Check if this order contains second installment product
    $has_second_installment = false;
    $installment_order_id = 0;
    
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        
        if (get_post_meta($product_id, '_beban_second_installment_product', true) === 'yes') {
            $has_second_installment = true;
            $installment_order_id = $item->get_meta('beban_installment_order_id');
            break;
        }
    }
    
    if (!$has_second_installment || !$installment_order_id) {
        return;
    }
    
    // Get the original installment order
    $original_order = wc_get_order($installment_order_id);
    
    if (!$original_order) {
        return;
    }
    
    // Update original order status to Second
    $original_order->update_meta_data('_beban_installment_status', 'Second');
    $original_order->save();
    
    // Add order note
    $original_order->add_order_note(
        sprintf(
            'قسط دوم پرداخت شد. سفارش پرداخت: #%s',
            $order->get_order_number()
        )
    );
    
    // Add note to new order
    $order->add_order_note(
        sprintf(
            'پرداخت قسط دوم برای سفارش #%s',
            $original_order->get_order_number()
        )
    );
    
    // Mark new order as second installment payment
    $order->update_meta_data('_is_second_installment_payment', 'yes');
    $order->update_meta_data('_parent_order_id', $installment_order_id);
    $order->save();
}

// Hook into WooCommerce actions
add_action('woocommerce_order_details_after_order_table', 'beban_add_second_installment_payment_button');
add_action('woocommerce_add_to_cart', 'beban_handle_second_installment_cart_addition', 10, 6);
add_action('woocommerce_checkout_order_processed', 'beban_process_second_installment_payment', 10, 3);
