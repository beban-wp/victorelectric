<?php
/**
 * Installment Orders Helper Functions
 * 
 * Helper functions for installment orders list functionality
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get installment info for order display
 * 
 * @param WC_Order $order
 * @return array|null
 */
function beban_get_installment_info_for_order($order) {
    $items = $order->get_items();
    
    foreach ($items as $item) {
        $product = wc_get_product($item->get_product_id());
        if (!$product) continue;
         
        // Check if this is an installment product - NOW USING CORE FUNCTION
        if (beban_is_installment_product($product, $item)) {
            // Get installment status from order meta
            $installment_status = $order->get_meta('_beban_installment_status');
            $second_installment_due_date = $order->get_meta('second_installment_due_date');
            
            $status_text = '';
            $status_class = '';
            $days_remaining = null;
            
            // Determine status based on _beban_installment_status
            if ($installment_status === 'Second' || $installment_status === 'Complete') {
                $status_text = 'تکمیل اقساط';
                $status_class = 'status-complete';
            } elseif ($installment_status === 'First') {
                $status_text = 'در انتظار پرداخت قسط دوم';
                $status_class = 'status-pending';
            } else {
                // If no status is set, it means first installment is paid (order is created)
                $status_text = 'در انتظار پرداخت قسط دوم';
                $status_class = 'status-pending';
            }
            
            return [
                'status_text' => $status_text,
                'status_class' => $status_class,
                'days_remaining' => $days_remaining
            ];
        }
    }
    
    return null;
}

/**
 * Display installment status for a specific order
 * 
 * @param WC_Order $order
 */
function beban_display_installment_status_for_order($order) {
    // Get order items
    $items = $order->get_items();
    
    foreach ($items as $item) {
        $product = wc_get_product($item->get_product_id());
        if (!$product) continue;
        
        // Check if this is an installment product - NOW USING CORE FUNCTION
        if (beban_is_installment_product($product, $item)) {
            // This is an installment product, display its status
            beban_display_customer_installment_status($order, $item);
            break; // Only show status for the first installment product
        }
    }
}
