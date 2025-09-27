<?php
/**
 * Installment Product Detector
 * 
 * Common functions for detecting installment products
 * This file contains shared logic extracted from existing files
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if a product is an installment product
 * 
 * @param WC_Product $product The product object
 * @param WC_Order_Item|null $item The order item (optional)
 * @return bool True if installment product, false otherwise
 */
function beban_is_installment_product($product, $item = null) {
    if (!$product) {
        return false;
    }
    
    $order_type = '';
    
    // First try to get from order item meta (most accurate for variable products)
    if ($item) {
        $order_type = $item->get_meta('pa_type-order');
    }
    
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
        return true;
    }
    
    return false;
}

/**
 * Get the order type for a product
 * 
 * @param WC_Product $product The product object
 * @param WC_Order_Item|null $item The order item (optional)
 * @return string The order type or empty string
 */
function beban_get_installment_order_type($product, $item = null) {
    if (!$product) {
        return '';
    }
    
    $order_type = '';
    
    // First try to get from order item meta
    if ($item) {
        $order_type = $item->get_meta('pa_type-order');
    }
    
    // If not found in item meta, try from product attributes
    if (empty($order_type)) {
        $order_type = $product->get_attribute('pa_type-order');
    }
    
    // If still not found, try the old method
    if (empty($order_type)) {
        $order_type = $product->get_attribute('type-order');
    }
    
    return trim($order_type);
}

/**
 * Check if an order contains installment products
 * 
 * @param WC_Order $order The order object
 * @return bool True if order contains installment products
 */
function beban_order_has_installment_products($order) {
    if (!$order) {
        return false;
    }
    
    $items = $order->get_items();
    
    foreach ($items as $item) {
        $product = wc_get_product($item->get_product_id());
        if (!$product) continue;
        
        if (beban_is_installment_product($product, $item)) {
            return true;
        }
    }
    
    return false;
}
