<?php
/**
 * Order Meta Box - Display Product Order Type
 * 
 * Adds a meta box to the order details page in WordPress admin
 * to show the order type (full payment or installment) for each product
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
 
/**
 * Add meta box to shop order page
 * Works with both HPOS and Legacy storage
 */
function beban_add_order_type_meta_box() {
    // For legacy post storage
    add_meta_box(
        'beban-order-type-meta-box',
        'اقساط سفارش',
        'beban_order_type_meta_box_content',
        'shop_order',
        'side',
        'default'
    ); 
}
add_action('add_meta_boxes', 'beban_add_order_type_meta_box');

/**
 * Add meta box for HPOS (High-Performance Order Storage)
 */
function beban_add_order_type_meta_box_hpos() {
    add_meta_box(
        'beban-order-type-meta-box-hpos',
        'اقساط سفارش',
        'beban_order_type_meta_box_content_hpos',
        'woocommerce_page_wc-orders',
        'side',
        'default'
    );
}
add_action('add_meta_boxes_woocommerce_page_wc-orders', 'beban_add_order_type_meta_box_hpos');

/**
 * Display meta box content for legacy storage
 * 
 * @param WP_Post $post The post object
 */
function beban_order_type_meta_box_content($post) {
    beban_display_order_type_content($post->ID);
}

/**
 * Common function to display order type content
 * 
 * @param int $order_id The order ID
 */
function beban_display_order_type_content($order_id) {
    // Get the WooCommerce order object
    $order = wc_get_order($order_id);
    
    if (!$order) {
        echo '<p>خطا در بارگذاری سفارش</p>';
        return;
    }
    
    // Get order items
    $items = $order->get_items();
    
    if (empty($items)) {
        echo '<p>هیچ محصولی در این سفارش یافت نشد</p>';
        return;
    }
    
    echo '<div class="beban-order-type-box">';
    echo '<ul>';
    
    foreach ($items as $item) {
        $product_id = $item->get_product_id();
        $product = wc_get_product($product_id);
        
        if (!$product) {
            continue;
        }
        
        // Get product name with link
        $product_name = $product->get_name();
        $product_link = get_edit_post_link($product_id);
        $product_display = $product_link ? 
            '<a href="' . esc_url($product_link) . '" target="_blank">' . esc_html($product_name) . '</a>' : 
            esc_html($product_name);
        
        // Determine display text based on order type - NOW USING CORE FUNCTION
        $order_type_display = '';
        $price_info = '';
        
        if (beban_is_installment_product($product, $item)) {
            $order_type_display = 'خرید اقساطی';
            // Get price information for installment products
            $price_info = beban_get_installment_price_info($product, $item, $order);
        } else {
            // Check if it's a full payment product
            $order_type = $item->get_meta('pa_type-order');
            if (empty($order_type)) {
                $order_type = $product->get_attribute('pa_type-order');
            }
            if (empty($order_type)) {
                $order_type = $product->get_attribute('type-order');
            }
            
            if ($order_type && (
                strtolower(trim($order_type)) === 'pay-full' || 
                trim($order_type) === 'خرید کامل'
            )) {
                $order_type_display = 'خرید کامل';
            } else {
                $order_type_display = 'نامشخص';
            }
        }
        
        // Display product and order type
        echo '<li>';
        echo '<strong>' . $product_display . ':</strong> ' . $order_type_display;
        
        // Display price information for installment products
        if ($price_info) {
            echo '<br><div style="margin-top: 5px; padding: 8px; background: #f8f9fa; border-left: 3px solid #007cba; font-size: 12px;">';
            echo $price_info;
            echo '</div>';
        }
        
        echo '</li>';
    }
    
    echo '</ul>';
    
    // Check if this is an installment order - NOW USING CORE FUNCTION
    $is_installment_order = beban_order_has_installment_products($order);
    
    // Add installment status selector if this is an installment order
    if ($is_installment_order) {
        $installment_status = get_post_meta($order_id, '_beban_installment_status', true);
        if (empty($installment_status)) {
            $installment_status = 'First'; // Default value
        }
        echo '<div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;">';
        echo '<h4 style="margin: 0 0 10px 0; font-size: 14px;">وضعیت پرداخت اقساط:</h4>';
        echo '<select id="beban_installment_status" name="beban_installment_status" style="width: 100%; padding: 5px;">';
        echo '<option value="First"' . selected($installment_status, 'First', false) . '>قسط اول پرداخت شده</option>';
        echo '<option value="Second"' . selected($installment_status, 'Second', false) . '>قسط دوم پرداخت شده</option>';
        echo '<option value="Both"' . selected($installment_status, 'Both', false) . '>هر دو قسط پرداخت شده</option>';
        echo '</select>';
        echo '<button type="button" id="beban_save_installment_status" style="margin-top: 8px; padding: 5px 10px; background: #0073aa; color: white; border: none; border-radius: 3px; cursor: pointer;">ذخیره</button>';
        echo '</div>';
        
        // Add JavaScript for saving installment status
        echo '<script>
        jQuery(document).ready(function($) {
            $("#beban_save_installment_status").click(function() {
                var status = $("#beban_installment_status").val();
                var orderId = ' . $order_id . ';
                
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "beban_save_installment_status",
                        order_id: orderId,
                        installment_status: status,
                        nonce: "' . wp_create_nonce('beban_installment_status_nonce') . '"
                    },
                    success: function(response) {
                        if (response.success) {
                            alert("وضعیت اقساط با موفقیت ذخیره شد.");
                        } else {
                            alert("خطا در ذخیره وضعیت: " + response.data);
                        }
                    },
                    error: function() {
                        alert("خطا در ارتباط با سرور.");
                    }
                });
            });
        });
        </script>';
    }
    
    echo '</div>';
    
    // Add some basic styling
    echo '<style>
        .beban-order-type-box ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .beban-order-type-box li {
            margin-bottom: 8px;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .beban-order-type-box li:last-child {
            border-bottom: none;
        }
        .beban-order-type-box a {
            text-decoration: none;
            color: #0073aa;
        }
        .beban-order-type-box a:hover {
            text-decoration: underline;
        }
    </style>';
}

/**
 * AJAX handler for saving installment status
 */
add_action('wp_ajax_beban_save_installment_status', 'beban_save_installment_status_handler');
function beban_save_installment_status_handler() {
    // Check nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'beban_installment_status_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user has permission
    if (!current_user_can('edit_shop_orders')) {
        wp_die('Insufficient permissions');
    }
    
    $order_id = intval($_POST['order_id']);
    $installment_status = sanitize_text_field($_POST['installment_status']);
    
    // Validate installment status
    $valid_statuses = ['First', 'Second', 'Both'];
    if (!in_array($installment_status, $valid_statuses)) {
        wp_send_json_error('Invalid installment status');
    }
    
    // Update the meta
    $result = update_post_meta($order_id, '_beban_installment_status', $installment_status);
    
    if ($result !== false) {
        wp_send_json_success('Installment status updated successfully');
    } else {
        wp_send_json_error('Failed to update installment status');
    }
}

/**
 * Get installment price information for a product
 * 
 * @param WC_Product $product The product object
 * @param WC_Order_Item_Product $item The order item
 * @param WC_Order $order The order object
 * @return string HTML formatted price information
 */
function beban_get_installment_price_info($product, $item, $order) {
    // Get the quantity of this item
    $quantity = $item->get_quantity();
    
    // Get the paid amount (current variation price) - this is already total for all quantities
    $paid_amount = $item->get_total();
    
    // Get the full price from the pay-full variation (single item price)
    $full_price_single = beban_get_full_price_from_variations($product);
    
    if ($full_price_single === false) {
        return '<span style="color: #d63638;">خطا: قیمت کامل یافت نشد</span>';
    }
    
    // Calculate full price for all quantities
    $full_price_total = $full_price_single * $quantity;
    
    // Calculate remaining amount
    $remaining_amount = $full_price_total - $paid_amount;
    
    // Format prices
    $paid_formatted = wc_price($paid_amount);
    $full_formatted = wc_price($full_price_total);
    $remaining_formatted = wc_price($remaining_amount);
    
    // Calculate days remaining for second installment
    $days_remaining = beban_calculate_installment_days_remaining($order);
    
    // Create HTML output
    $html = '<div style="line-height: 1.4;">';
    $html .= '<div><strong>تعداد:</strong> ' . $quantity . ' عدد</div>';
    $html .= '<div><strong>قیمت کامل:</strong> ' . $full_formatted . '</div>';
    $html .= '<div><strong>مبلغ پرداخت شده:</strong> ' . $paid_formatted . '</div>';
    $html .= '<div><strong>مبلغ باقی‌مانده:</strong> <span style="color: #d63638; font-weight: bold;">' . $remaining_formatted . '</span></div>';
    
    // Add days remaining information
    if ($days_remaining !== false) {
        $html .= '<div style="margin-top: 8px; padding: 6px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">';
        $html .= '<strong>روزهای باقی‌مانده تا موعد پرداخت قسط دوم:</strong> ';
        
        if ($days_remaining > 0) {
            $html .= '<span style="color: #856404; font-weight: bold;">' . $days_remaining . ' روز</span>';
        } elseif ($days_remaining == 0) {
            $html .= '<span style="color: #721c24; font-weight: bold;">امروز موعد پرداخت است</span>';
        } else {
            $html .= '<span style="color: #721c24; font-weight: bold;">' . abs($days_remaining) . ' روز گذشته</span>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Get full price from product variations
 * 
 * @param WC_Product $product The product object
 * @return float|false The full price or false if not found
 */

/**
 * Calculate days remaining until second installment payment
 * 
 * @param WC_Order $order The order object
 * @return int|false Days remaining (negative if overdue, false if error)
 */

/**
 * Display meta box content for HPOS (High-Performance Order Storage)
 * 
 * @param WP_Post $post The post object
 */
function beban_order_type_meta_box_content_hpos($post) {
    // For HPOS, get order ID from URL parameter
    $order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$order_id) {
        echo '<p>خطا در دریافت شناسه سفارش</p>';
        return;
    }
    
    // Use the same content function but with order ID
    beban_display_order_type_content($order_id);
}
