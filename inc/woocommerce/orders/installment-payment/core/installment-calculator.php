<?php
/**
 * Installment Calculator
 * 
 * Common functions for installment calculations
 * This file contains shared calculation logic extracted from existing files
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get full price from product variations
 * 
 * @param WC_Product $product The product object
 * @return float|false Full price or false if not found
 */
function beban_get_full_price_from_variations($product) {
    // Check if this is a variable product
    if (!$product->is_type('variable')) {
        return false;
    }
    
    // Get all variation IDs
    $variation_ids = $product->get_children();
    
    if (empty($variation_ids)) {
        return false;
    }
    
    foreach ($variation_ids as $variation_id) {
        $variation = wc_get_product($variation_id);
        
        if (!$variation || !$variation->is_purchasable()) {
            continue;
        }
        
        // Check if this variation has pay-full type-order
        $variation_order_type = $variation->get_attribute('pa_type-order');
        
        if (empty($variation_order_type)) {
            $variation_order_type = $variation->get_attribute('type-order');
        }
        
        // Try to get from variation meta
        if (empty($variation_order_type)) {
            $variation_order_type = $variation->get_meta('pa_type-order');
        }
        
        if ($variation_order_type && (
            strtolower(trim($variation_order_type)) === 'pay-full' || 
            trim($variation_order_type) === 'خرید کامل'
        )) {
            return (float) $variation->get_price();
        }
    }
    
    return false;
}

/**
 * Calculate days remaining until second installment payment
 * 
 * @param WC_Order $order The order object
 * @return int|false Days remaining (negative if overdue, false if error)
 */
function beban_calculate_installment_days_remaining($order) {
    if (!$order) {
        return false;
    }
    
    // Get order creation date
    $order_date = $order->get_date_created();
    
    if (!$order_date) {
        return false;
    }
    
    // Convert to DateTime object if it's a string
    if (is_string($order_date)) {
        $order_date = new DateTime($order_date);
    }
    
    // Add 30 days to order date for second installment
    $second_installment_date = clone $order_date;
    $second_installment_date->add(new DateInterval('P30D'));
    
    // Get current date
    $current_date = new DateTime();
    
    // Calculate difference in days
    $diff = $current_date->diff($second_installment_date);
    
    // Determine if it's past due or future
    if ($current_date > $second_installment_date) {
        // Past due - return negative number
        return -$diff->days;
    } else {
        // Future - return positive number
        return $diff->days;
    }
}

/**
 * Calculate remaining amount for second installment
 * 
 * @param WC_Order $order The order object
 * @return float Total remaining amount
 */
function beban_calculate_remaining_amount($order) {
    if (!$order) {
        return 0;
    }
    
    $total_remaining = 0;
    $items = $order->get_items();
    
    foreach ($items as $item) {
        $product = wc_get_product($item->get_product_id());
        if (!$product) continue;
        
        // Check if this is an installment product
        if (beban_is_installment_product($product, $item)) {
            $quantity = $item->get_quantity();
            $paid_amount = $item->get_total();
            $full_price_single = beban_get_full_price_from_variations($product);
            
            if ($full_price_single !== false) {
                $full_price_total = $full_price_single * $quantity;
                $remaining_amount = $full_price_total - $paid_amount;
                $total_remaining += $remaining_amount;
            }
        }
    }
    
    return $total_remaining;
}

/**
 * Check if user can pay second installment
 * 
 * @param WC_Order $order The order object
 * @param int $user_id The user ID (optional, defaults to current user)
 * @return bool True if can pay, false otherwise
 */
function beban_can_pay_second_installment($order, $user_id = null) {
    if (!$order) {
        return false;
    }
    
    // Get user ID
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }
    
    // Check ownership
    if ($order->get_customer_id() !== $user_id) {
        return false;
    }
    
    // Check installment status
    $installment_status = $order->get_meta('_beban_installment_status');
    if ($installment_status !== 'First') {
        return false;
    }
    
    // Check remaining amount
    $remaining_amount = beban_calculate_remaining_amount($order);
    if ($remaining_amount <= 0) {
        return false;
    }
    
    return true;
}
