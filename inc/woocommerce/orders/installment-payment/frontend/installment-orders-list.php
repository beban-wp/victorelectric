<?php
/**
 * Installment Orders Tab Content
 * 
 * Displays installment orders in a separate tab
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue CSS and JS for installment orders (frontend)
 */
add_action('wp_enqueue_scripts', 'beban_enqueue_installment_frontend_assets');
function beban_enqueue_installment_frontend_assets() {
    if (is_account_page()) {
        // CSS will be added to style.css as requested
        wp_enqueue_script(
            'beban-installment-orders',
            get_stylesheet_directory_uri() . '/assets/js/installment-orders.js',
            array('jquery'),
            '1.0.0',
            true
        );
    } 
}

/**
 * Add installment orders endpoint content
 */
add_action('woocommerce_account_installment-orders_endpoint', 'beban_installment_orders_endpoint_content');
function beban_installment_orders_endpoint_content() {
    // Get current user
    $current_user = wp_get_current_user();
    
    // Get installment orders
    $installment_orders = beban_get_installment_orders($current_user->ID);
    
    // Display header
    echo '<div class="installment-orders-container">';
    echo '<div class="installment-orders-header beban-tab-account-header">';
    echo '<h2 class="installment-orders-title">سفارشات اقساطی</h2>';
    echo '<p class="installment-orders-description">مشاهده و مدیریت وضعیت پرداخت اقساطی سفارشات خود</p>';
    echo '</div>';
    
    if (empty($installment_orders)) {
        // Empty state
        echo '<div class="installment-orders-empty">';
        echo '<p>شما هیچ سفارش اقساطی ندارید.</p>';
        echo '</div>';
    } else {
        // Display orders list
        echo '<div class="installment-orders-list">';
        foreach ($installment_orders as $order) {
            echo '<div class="installment-order-item">';
            echo '<div class="order-header">';
            echo '<div class="order-info">';
            echo '<div class="order-number">';
            echo '<span class="order-label">شماره سفارش:</span>';
            echo '<span class="order-value">' . $order->get_order_number() . '</span>';
            echo '</div>';
            
            // Get installment status info
            $installment_info = beban_get_installment_info_for_order($order);
            if ($installment_info) {
                echo '<div class="installment-status-info">';
                echo '<span class="status-label">وضعیت قسط:</span>';
                echo '<span class="status-value ' . $installment_info['status_class'] . '">' . $installment_info['status_text'] . '</span>';
                echo '</div>';
                
                if ($installment_info['days_remaining'] !== null) {
                    echo '<div class="days-remaining-info">';
                    echo '<span class="days-label">روزهای باقی‌مانده:</span>';
                    echo '<span class="days-value">' . $installment_info['days_remaining'] . ' روز</span>';
                    echo '</div>';
                }
            }
            echo '</div>';
            echo '<div class="order-actions">';
            echo '<button class="view-installment-status-btn" data-order-id="' . $order->get_id() . '">';
            echo '<span class="btn-text"> وضعیت </span>';
            echo '<span class="btn-icon">';
            echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
            echo '<path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
            echo '</svg>';
            echo '</span>';
            echo '</button>';
            echo '</div>';
            echo '</div>';
            echo '<div class="installment-status-content" id="installment-status-' . $order->get_id() . '" style="display: none;">';
            echo '<div class="installment-status-wrapper">';
            // Display installment status here
            beban_display_installment_status_for_order($order);
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    echo '</div>';
}

/**
 * Get installment orders for a user
 * 
 * @param int $user_id
 * @return array
 */
function beban_get_installment_orders($user_id) {
    $installment_orders = array();
    
    // Get all orders for the user
    $orders = wc_get_orders(array(
        'customer' => $user_id,
        'status' => array('wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending'),
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    foreach ($orders as $order) {
        // Check if this order contains installment products - NOW USING CORE FUNCTION
        if (beban_order_has_installment_products($order)) {
            $installment_orders[] = $order;
        }
        
    }
    
    return $installment_orders;
}

