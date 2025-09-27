<?php
/**
 * Customer Installment Status Display
 * 
 * Shows installment status to customers on order details page
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
 
/**
 * Add installment status box to customer order details page
 */
add_action('woocommerce_order_details_after_order_table', 'beban_display_customer_installment_status');
function beban_display_customer_installment_status($order) {
    // Check if this is an installment order - NOW USING CORE FUNCTION
    if (!beban_order_has_installment_products($order)) {
        return;
    }
    
    // Get installment status
    $installment_status = get_post_meta($order->get_id(), '_beban_installment_status', true);
    if (empty($installment_status)) {
        $installment_status = 'First'; // Default value
    }
    
    // Get days remaining - NOW USING CORE FUNCTION
    $days_remaining = beban_calculate_installment_days_remaining($order);
    
    // Display installment status box
    echo '<div class="beban-customer-installment-status">';
    echo '<h3 class="installment-status-title">وضعیت پرداخت اقساط</h3>';
    
    // Status display with progress bar
    echo '<div class="installment-progress-container">';
    echo '<div class="progress-steps">';
    
    // Step 1: First Installment
    $step1_completed = in_array($installment_status, ['First', 'Second', 'Both']);
    $step1_class = $step1_completed ? 'completed' : 'pending';
    echo '<div class="progress-step ' . $step1_class . '">';
    echo '<div class="step-icon">';
    if ($step1_completed) {
        echo '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
        echo '<path d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        echo '<path d="M7.75 12L10.58 14.83L16.25 9.17004" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        echo '</svg>';
    } else {
        echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">';
        echo '<path d="M15 15L1 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>';
        echo '<path d="M1 15L15 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>';
        echo '</svg>';
    }
    echo '</div>';
    echo '<div class="step-content">';
    echo '<div class="step-title">قسط اول</div>';
    echo '<div class="step-status">پرداخت شده</div>';
    echo '</div>';
    echo '<div class="step-progress">';
    echo '<div class="progress-bar ' . ($step1_completed ? 'filled' : '') . '"></div>';
    echo '</div>';
    echo '</div>';
    
    // Step 2: Waiting for Second Installment
    $step2_completed = in_array($installment_status, ['Second', 'Both']);
    $step2_class = $step2_completed ? 'completed' : ($installment_status === 'First' ? 'current' : 'pending');
    echo '<div class="progress-step ' . $step2_class . '">';
    echo '<div class="step-icon">';
    if ($step2_completed) {
        echo '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
        echo '<path d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        echo '<path d="M7.75 12L10.58 14.83L16.25 9.17004" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        echo '</svg>';
    } elseif ($installment_status === 'First') {
        echo '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
        echo '<path d="M15.24 2H8.76004C5.00004 2 4.71004 5.38 6.74004 7.22L17.26 16.78C19.29 18.62 19 22 15.24 22H8.76004C5.00004 22 4.71004 18.62 6.74004 16.78L17.26 7.22C19.29 5.38 19 2 15.24 2Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        echo '</svg>';
    } else {
        echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">';
        echo '<path d="M15 15L1 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>';
        echo '<path d="M1 15L15 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>';
        echo '</svg>';
    }
    echo '</div>';
    echo '<div class="step-content">';
    echo '<div class="step-title">قسط دوم</div>';
    echo '<div class="step-status">' . ($step2_completed ? 'پرداخت شده' : ($installment_status === 'First' ? 'در انتظار پرداخت' : 'در انتظار')) . '</div>';
    echo '</div>';
    echo '<div class="step-progress">';
    echo '<div class="progress-bar ' . ($step2_completed ? 'filled' : ($installment_status === 'First' ? 'partial' : '')) . '"></div>';
    echo '</div>';
    echo '</div>';
    
    // Step 3: Complete (automatically completed when both installments are paid)
    $step3_completed = in_array($installment_status, ['Second', 'Both']);
    $step3_class = $step3_completed ? 'completed' : 'pending';
    echo '<div class="progress-step ' . $step3_class . '">';
    echo '<div class="step-icon">';
    if ($step3_completed) {
        echo '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
        echo '<path d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        echo '<path d="M7.75 12L10.58 14.83L16.25 9.17004" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        echo '</svg>';
    } else {
        echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">';
        echo '<path d="M15 15L1 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>';
        echo '<path d="M1 15L15 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>';
        echo '</svg>';
    }
    echo '</div>';
    echo '<div class="step-content">';
    echo '<div class="step-title">تکمیل</div>';
    echo '<div class="step-status">' . ($step3_completed ? 'تمام اقساط پرداخت شده' : 'در انتظار') . '</div>';
    echo '</div>';
    echo '<div class="step-progress">';
    echo '<div class="progress-bar ' . ($step3_completed ? 'filled' : '') . '"></div>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>';
    echo '</div>';
    
    // Days remaining information (only for First status)
    if ($installment_status === 'First' && $days_remaining !== false) {
        echo '<div class="days-remaining-info">';
        echo '<div class="days-remaining-content">';
        echo '<div class="days-remaining-icon">';
        echo '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
        echo '<path d="M20.75 13.25C20.75 18.08 16.83 22 12 22C7.17 22 3.25 18.08 3.25 13.25C3.25 8.42 7.17 4.5 12 4.5C16.83 4.5 20.75 8.42 20.75 13.25Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        echo '<path d="M12 8V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        echo '<path d="M9 2H15" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>';
        echo '</svg>';
        echo '</div>';
        echo '<div class="days-remaining-text">';
        echo '<strong>موعد پرداخت قسط دوم:</strong> ';
        
        if ($days_remaining > 0) {
            echo '<span class="days-remaining">' . $days_remaining . ' روز باقی مانده</span>';
        } elseif ($days_remaining == 0) {
            echo '<span class="days-due">امروز موعد پرداخت است</span>';
        } else {
            echo '<span class="days-overdue">' . abs($days_remaining) . ' روز گذشته</span>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    
    // Payment information
    echo '<div class="payment-info">';
    echo '<h4 class="payment-info-title">اطلاعات پرداخت:</h4>';
    
    $items = $order->get_items(); // Get items for payment info section
    foreach ($items as $item) {
        $product = wc_get_product($item->get_product_id());
        if (!$product) continue;
        
        // Check if this is an installment product - NOW USING CORE FUNCTION
        if (beban_is_installment_product($product, $item)) {
        
            $quantity = $item->get_quantity();
            $paid_amount = $item->get_total();
            $full_price_single = beban_get_full_price_from_variations($product);
            
            if ($full_price_single !== false) {
                $full_price_total = $full_price_single * $quantity;
                $remaining_amount = $full_price_total - $paid_amount;
                
                echo '<div class="product-payment-details">';
                echo '<strong>' . $product->get_name() . '</strong> (تعداد: ' . $quantity . ')';
                echo '<div class="payment-amounts">';
                echo '<div><span>قیمت کامل</span><strong>' . wc_price($full_price_total) . '</strong></div>';
                echo '<div><span>پرداخت شده</span><strong class="paid-amount">' . wc_price($paid_amount) . '</strong></div>';
                echo '<div><span>باقی‌مانده</span><strong class="remaining-amount">' . wc_price($remaining_amount) . '</strong></div>';
                echo '</div>';
                echo '</div>';
            }
        }
    }
    echo '</div>';
    
    // Help text
    echo '<div class="installment-help">';
    echo '<div class="help-content">';
    echo '<div class="help-icon">';
    echo '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
    echo '<path d="M8.30011 18.0399V16.8799C6.00011 15.4899 4.11011 12.7799 4.11011 9.89993C4.11011 4.94993 8.66011 1.06993 13.8001 2.18993C16.0601 2.68993 18.0401 4.18993 19.0701 6.25993C21.1601 10.4599 18.9601 14.9199 15.7301 16.8699V18.0299C15.7301 18.3199 15.8401 18.9899 14.7701 18.9899H9.26011C8.16011 18.9999 8.30011 18.5699 8.30011 18.0399Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '<path d="M8.5 22C10.79 21.35 13.21 21.35 15.5 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '</svg>';
    echo '</div>';
    echo '<div class="help-text">';
    echo '<strong>راهنمایی:</strong> برای پرداخت قسط دوم، لطفاً با پشتیبانی تماس بگیرید.';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>';
}

