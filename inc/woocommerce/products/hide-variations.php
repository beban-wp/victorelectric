<?php
/**
 * Hide Product Variations for Non-Vendors
 * 
 * Simple solution to hide product variations for users who are not vendors
 * or product owners. Only vendors who own the product can see variations.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if user can see variations
 */
function beban_can_see_variations($product_id) {
    // User must be logged in
    if (!is_user_logged_in()) {
        return false;
    }
    
    // User must be a vendor
    if (!dokan_is_user_seller(get_current_user_id())) {
        return false;
    }
    
    // User must own this product
    if (!dokan_is_product_author($product_id)) {
        return false;
    }
    
    return true;
}

/**
 * Hide variations for non-vendors
 */
function beban_hide_variations() {
    if (!is_product()) {
        return;
    }
    
    $product_id = get_the_ID();
    $product = wc_get_product($product_id);
    
    if (!$product || !$product->is_type('variable')) {
        return;
    }
    
    // Don't remove the form, just hide variations with CSS
}
add_action('wp', 'beban_hide_variations');


/**
 * Add CSS to hide variations
 */
function beban_hide_variations_css() {
    if (!is_product()) {
        return;
    }
    
    $product_id = get_the_ID();
    $product = wc_get_product($product_id);
    
    if (!$product || !$product->is_type('variable')) {
        return;
    }
    
    if (!beban_can_see_variations($product_id)) {
        echo '<style>
        .variations,
        .woocommerce-variation {
            display: none !important;
        }

        </style>';
    }else{
        echo '<style>
        .beban-product-price-con{
            filter: blur(3px);
        }
		@media screen and (max-width: 880px){
			.beban-product-price-con{ display: none }
			
		}

		@media screen and (max-width: 880px){
		.beban-product-add-con{
			width: 100%; !important
		}
		
		.beban-product-add-con .variations_form{
			justify-content: space-between !important;
			flex-direction: row-reverse !important;
			width: 100% !important;
		}
		}
        </style>';
    }
}
add_action('wp_head', 'beban_hide_variations_css');
