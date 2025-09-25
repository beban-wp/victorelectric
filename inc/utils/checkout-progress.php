<?php
/**
 * Checkout Progress Bar Component
 * 
 * A vertical progress bar that shows the checkout process steps
 * 
 * @package Beban
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get current checkout step based on current page
 */
function beban_get_current_checkout_step() {
    if (is_cart()) {
        return 'cart';
    } elseif (is_checkout() && !is_wc_endpoint_url('order-received')) {
        return 'checkout';
    } elseif (is_wc_endpoint_url('order-received')) {
        // On thank you page, show order-details as current step
        // This will make thank-you step completed (green) and order-details current
        return 'order-details';
    } elseif (is_account_page() && is_wc_endpoint_url('orders')) {
        return 'order-details';
    }
    return 'cart';
}

/**
 * Checkout Progress Shortcode
 */
function beban_checkout_progress_shortcode($atts) {
    $atts = shortcode_atts(array(
        'current_step' => '', // cart, checkout, payment, thank-you, order-details (empty for auto-detect)
        'show_icons' => 'true',
        'show_status' => 'true'
    ), $atts);
    
    // Auto-detect current step if not specified
    $current_step = !empty($atts['current_step']) ? $atts['current_step'] : beban_get_current_checkout_step();
    $show_icons = $atts['show_icons'] === 'true';
    $show_status = $atts['show_status'] === 'true';
    
    // Define steps
    $steps = array(
        'cart' => array(
            'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 18.5C9.66 18.5 7.75 16.59 7.75 14.25C7.75 13.84 8.09 13.5 8.5 13.5C8.91 13.5 9.25 13.84 9.25 14.25C9.25 15.77 10.48 17 12 17C13.52 17 14.75 15.77 14.75 14.25C14.75 13.84 15.09 13.5 15.5 13.5C15.91 13.5 16.25 13.84 16.25 14.25C16.25 16.59 14.34 18.5 12 18.5Z" fill="currentColor"/><path d="M5.19086 6.37994C5.00086 6.37994 4.80086 6.29994 4.66086 6.15994C4.37086 5.86994 4.37086 5.38994 4.66086 5.09994L8.29086 1.46994C8.58086 1.17994 9.06086 1.17994 9.35086 1.46994C9.64086 1.75994 9.64086 2.23994 9.35086 2.52994L5.72086 6.15994C5.57086 6.29994 5.38086 6.37994 5.19086 6.37994Z" fill="currentColor"/><path d="M18.8091 6.37994C18.6191 6.37994 18.4291 6.30994 18.2791 6.15994L14.6491 2.52994C14.3591 2.23994 14.3591 1.75994 14.6491 1.46994C14.9391 1.17994 15.4191 1.17994 15.7091 1.46994L19.3391 5.09994C19.6291 5.38994 19.6291 5.86994 19.3391 6.15994C19.1991 6.29994 18.9991 6.37994 18.8091 6.37994Z" fill="currentColor"/><path d="M20.21 10.6001C20.14 10.6001 20.07 10.6001 20 10.6001H19.77H4C3.3 10.6101 2.5 10.6101 1.92 10.0301C1.46 9.5801 1.25 8.8801 1.25 7.8501C1.25 5.1001 3.26 5.1001 4.22 5.1001H19.78C20.74 5.1001 22.75 5.1001 22.75 7.8501C22.75 8.8901 22.54 9.5801 22.08 10.0301C21.56 10.5501 20.86 10.6001 20.21 10.6001ZM4.22 9.1001H20.01C20.46 9.1101 20.88 9.1101 21.02 8.9701C21.09 8.9001 21.24 8.6601 21.24 7.8501C21.24 6.7201 20.96 6.6001 19.77 6.6001H4.22C3.03 6.6001 2.75 6.7201 2.75 7.8501C2.75 8.6601 2.91 8.9001 2.97 8.9701C3.11 9.1001 3.54 9.1001 3.98 9.1001H4.22Z" fill="currentColor"/><path d="M14.8907 22.75H8.86073C5.28073 22.75 4.48073 20.62 4.17073 18.77L2.76073 10.12C2.69073 9.71 2.97073 9.33 3.38073 9.26C3.78073 9.19 4.17073 9.47 4.24073 9.88L5.65073 18.52C5.94073 20.29 6.54073 21.25 8.86073 21.25H14.8907C17.4607 21.25 17.7507 20.35 18.0807 18.61L19.7607 9.86C19.8407 9.45 20.2307 9.18 20.6407 9.27C21.0507 9.35 21.3107 9.74 21.2307 10.15L19.5507 18.9C19.1607 20.93 18.5107 22.75 14.8907 22.75Z" fill="currentColor"/></svg>',
            'title' => 'بررسی سبد خرید',
            'description' => 'محصولات انتخابی خود را بررسی کنید'
        ),
        'checkout' => array(
            'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.0009 14.1699C9.87086 14.1699 8.13086 12.4399 8.13086 10.2999C8.13086 8.15994 9.87086 6.43994 12.0009 6.43994C14.1309 6.43994 15.8709 8.16994 15.8709 10.3099C15.8709 12.4499 14.1309 14.1699 12.0009 14.1699ZM12.0009 7.93994C10.7009 7.93994 9.63086 8.99994 9.63086 10.3099C9.63086 11.6199 10.6909 12.6799 12.0009 12.6799C13.3109 12.6799 14.3709 11.6199 14.3709 10.3099C14.3709 8.99994 13.3009 7.93994 12.0009 7.93994Z" fill="currentColor"/><path d="M11.9997 22.76C10.5197 22.76 9.02969 22.2 7.86969 21.09C4.91969 18.25 1.65969 13.72 2.88969 8.33C3.99969 3.44 8.26969 1.25 11.9997 1.25C11.9997 1.25 11.9997 1.25 12.0097 1.25C15.7397 1.25 20.0097 3.44 21.1197 8.34C22.3397 13.73 19.0797 18.25 16.1297 21.09C14.9697 22.2 13.4797 22.76 11.9997 22.76ZM11.9997 2.75C9.08969 2.75 5.34969 4.3 4.35969 8.66C3.27969 13.37 6.23969 17.43 8.91969 20C10.6497 21.67 13.3597 21.67 15.0897 20C17.7597 17.43 20.7197 13.37 19.6597 8.66C18.6597 4.3 14.9097 2.75 11.9997 2.75Z" fill="currentColor"/></svg>',
            'title' => 'انتخاب آدرس پستی گیرنده',
            'description' => 'آدرس تحویل سفارش را مشخص کنید'
        ),
        'payment' => array(
            'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22 9.25H2C1.59 9.25 1.25 8.91 1.25 8.5C1.25 8.09 1.59 7.75 2 7.75H22C22.41 7.75 22.75 8.09 22.75 8.5C22.75 8.91 22.41 9.25 22 9.25Z" fill="currentColor"/><path d="M8 17.25H6C5.59 17.25 5.25 16.91 5.25 16.5C5.25 16.09 5.59 15.75 6 15.75H8C8.41 15.75 8.75 16.09 8.75 16.5C8.75 16.91 8.41 17.25 8 17.25Z" fill="currentColor"/><path d="M14.5 17.25H10.5C10.09 17.25 9.75 16.91 9.75 16.5C9.75 16.09 10.09 15.75 10.5 15.75H14.5C14.91 15.75 15.25 16.09 15.25 16.5C15.25 16.91 14.91 17.25 14.5 17.25Z" fill="currentColor"/><path d="M17.56 21.25H6.44C2.46 21.25 1.25 20.05 1.25 16.11V7.89C1.25 3.95 2.46 2.75 6.44 2.75H17.55C21.53 2.75 22.74 3.95 22.74 7.89V16.1C22.75 20.05 21.54 21.25 17.56 21.25ZM6.44 4.25C3.3 4.25 2.75 4.79 2.75 7.89V16.1C2.75 19.2 3.3 19.74 6.44 19.74H17.55C20.69 19.74 21.24 19.2 21.24 16.1V7.89C21.24 4.79 20.69 4.25 17.55 4.25H6.44Z" fill="currentColor"/></svg>',
            'title' => 'پرداخت هزینه',
            'description' => 'وارد درگاه پرداخت شوید'
        ),
        'thank-you' => array(
            'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 22.75H9C3.57 22.75 1.25 20.43 1.25 15V9C1.25 3.57 3.57 1.25 9 1.25H15C20.43 1.25 22.75 3.57 22.75 9V15C22.75 20.43 20.43 22.75 15 22.75ZM9 2.75C4.39 2.75 2.75 4.39 2.75 9V15C2.75 19.61 4.39 21.25 9 21.25H15C19.61 21.25 21.25 19.61 21.25 15V9C21.25 4.39 19.61 2.75 15 2.75H9Z" fill="currentColor"/><path d="M10.5795 15.5801C10.3795 15.5801 10.1895 15.5001 10.0495 15.3601L7.21945 12.5301C6.92945 12.2401 6.92945 11.7601 7.21945 11.4701C7.50945 11.1801 7.98945 11.1801 8.27945 11.4701L10.5795 13.7701L15.7195 8.6301C16.0095 8.3401 16.4895 8.3401 16.7795 8.6301C17.0695 8.9201 17.0695 9.4001 16.7795 9.6901L11.1095 15.3601C10.9695 15.5001 10.7795 15.5801 10.5795 15.5801Z" fill="currentColor"/></svg>',
            'title' => 'تکمیل سفارش و دریافت شماره سفارش',
            'description' => 'سفارش شما با موفقیت ثبت شد'
        ),
        'order-details' => array(
            'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.0006 13.0799C11.8706 13.0799 11.7406 13.0499 11.6206 12.9799L6.32061 9.91994C5.96061 9.70994 5.84059 9.24995 6.05059 8.89995C6.26059 8.53995 6.71059 8.41994 7.08059 8.62994L12.0006 11.4799L16.8906 8.64995C17.2506 8.43995 17.7106 8.56994 17.9206 8.91994C18.1306 9.27994 18.0006 9.73995 17.6506 9.93995L12.3906 12.9799C12.2606 13.0399 12.1306 13.0799 12.0006 13.0799Z" fill="currentColor"/><path d="M12 18.5201C11.59 18.5201 11.25 18.1801 11.25 17.7701V12.3301C11.25 11.9201 11.59 11.5801 12 11.5801C12.41 11.5801 12.75 11.9201 12.75 12.3301V17.7701C12.75 18.1801 12.41 18.5201 12 18.5201Z" fill="currentColor"/><path d="M12.0002 18.75C11.4202 18.75 10.8503 18.62 10.3903 18.37L7.19025 16.59C6.23025 16.06 5.49023 14.79 5.49023 13.69V10.3C5.49023 9.21005 6.24025 7.93005 7.19025 7.40005L10.3903 5.62005C11.3103 5.11005 12.6902 5.11005 13.6102 5.62005L16.8102 7.40005C17.7702 7.93005 18.5103 9.20005 18.5103 10.3V13.69C18.5103 14.78 17.7602 16.06 16.8102 16.59L13.6102 18.37C13.1502 18.62 12.5802 18.75 12.0002 18.75ZM12.0002 6.75005C11.6702 6.75005 11.3502 6.81005 11.1202 6.94005L7.92026 8.72005C7.43026 8.99005 6.99023 9.75005 6.99023 10.3V13.69C6.99023 14.25 7.43026 15 7.92026 15.27L11.1202 17.05C11.5802 17.31 12.4202 17.31 12.8802 17.05L16.0802 15.27C16.5702 15 17.0103 14.24 17.0103 13.69V10.3C17.0103 9.74005 16.5702 8.99005 16.0802 8.72005L12.8802 6.94005C12.6502 6.81005 12.3302 6.75005 12.0002 6.75005Z" fill="currentColor"/><path d="M15.0002 22.75C14.7302 22.75 14.4802 22.6 14.3502 22.37C14.2202 22.13 14.2202 21.85 14.3602 21.61L15.4102 19.86C15.6202 19.51 16.0802 19.39 16.4402 19.6C16.8002 19.81 16.9102 20.27 16.7002 20.63L16.4302 21.08C19.1902 20.43 21.2602 17.95 21.2602 14.99C21.2602 14.58 21.6002 14.24 22.0102 14.24C22.4202 14.24 22.7602 14.58 22.7602 14.99C22.7502 19.27 19.2702 22.75 15.0002 22.75Z" fill="currentColor"/><path d="M2 9.75C1.59 9.75 1.25 9.41 1.25 9C1.25 4.73 4.73 1.25 9 1.25C9.27 1.25 9.51999 1.4 9.64999 1.63C9.77999 1.87 9.78001 2.15 9.64001 2.39L8.59 4.14C8.38 4.49 7.92 4.61 7.56 4.4C7.2 4.19 7.08999 3.73 7.29999 3.37L7.57001 2.92C4.81001 3.57 2.73999 6.05 2.73999 9.01C2.74999 9.41 2.41 9.75 2 9.75Z" fill="currentColor"/></svg>',
            'title' => 'پیگیری سفارش',
            'description' => 'جزئیات سفارش در حساب کاربری'
        )
    );
    
    // Determine step order
    $step_order = array('cart', 'checkout', 'payment', 'thank-you', 'order-details');
    $current_index = array_search($current_step, $step_order);
    
    $output = '<div class="beban-checkout-progress">';
    
    foreach ($step_order as $index => $step_key) {
        $step = $steps[$step_key];
        $is_completed = $index < $current_index;
        $is_current = $index === $current_index;
        $is_pending = $index > $current_index;
        
        $step_class = 'beban-progress-step';
        if ($is_completed) $step_class .= ' completed';
        if ($is_current) $step_class .= ' current';
        if ($is_pending) $step_class .= ' pending';
        
        $output .= '<div class="' . $step_class . '">';
        
        // Step icon
        if ($show_icons) {
            $icon_class = 'beban-step-icon';
            if ($is_current && $step_key === 'order-details') {
                $icon_class .= ' order-details';
            }
            $output .= '<div class="' . $icon_class . '">';
            $output .= $step['icon'];
            $output .= '</div>';
        }
        
        // Step content
        $output .= '<div class="beban-step-content">';
        $title_class = 'beban-step-title';
        if ($is_current && $step_key === 'order-details') {
            $title_class .= ' order-details';
        }
        $output .= '<div class="' . $title_class . '">' . $step['title'] . '</div>';
        $output .= '<div class="beban-step-description">' . $step['description'] . '</div>';
        $output .= '</div>';
        
        // Status icon
        if ($show_status) {
            $output .= '<div class="beban-step-status">';
            if ($is_completed) {
                $output .= '<div class="beban-status-icon completed">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 16.17L4.83 12L3.41 13.41L9 19L21 7L19.59 5.59L9 16.17Z" fill="currentColor"/>
                    </svg>
                </div>';
            } elseif ($is_current) {
                $status_class = 'beban-status-icon current';
                if ($step_key === 'order-details') {
                    $status_class .= ' order-details';
                }
                $output .= '<div class="' . $status_class . '">
                    <div class="beban-loading-spinner"></div>
                </div>';
            } else {
                $output .= '<div class="beban-status-icon pending">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2Z" fill="currentColor"/>
                    </svg>
                </div>';
            }
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        // Progress line (except for last step)
        if ($index < count($step_order) - 1) {
            $line_class = 'beban-progress-line';
            if ($is_completed) $line_class .= ' completed';
            $output .= '<div class="' . $line_class . '"></div>';
        }
    }
    
    $output .= '</div>';
    
    return $output;
}
add_shortcode('beban_checkout_progress', 'beban_checkout_progress_shortcode');

/**
 * Add checkout progress styles
 */
function beban_add_checkout_progress_styles() {
    ?>
    <style>
    .beban-checkout-progress-container {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        margin: 20px 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border: 1px solid #e0e0e0;
    }
    
    .beban-checkout-progress {
        display: flex;
        flex-direction: column;
        gap: 0;
        font-family: 'IRANYekanX', Arial, sans-serif;
        max-width: 400px;
        margin: 0 auto;
    }
    
    .beban-progress-step {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 0;
        position: relative;
    }
    
    .beban-step-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f5f5f5;
        color: #999;
        flex-shrink: 0;
        transition: all 0.3s ease;
    }
    
    .beban-progress-step.completed .beban-step-icon {
        background: #3EB580;
        color: #fff;
        box-shadow: 0 0 0 4px #3eb58020;
    }
    
    .beban-progress-step.current .beban-step-icon {
        background: #513deb;
        color: #fff;
        box-shadow: 0 0 0 4px #513deb20;
    }
    
    .beban-progress-step.current .beban-step-icon.order-details {
        background: #ff9606;
        box-shadow: 0 0 0 4px #ff960620;
    }
    
    .beban-progress-step.pending .beban-step-icon {
        background: #f5f5f5;
        color: #ccc;
    }
    
    .beban-step-content {
        flex: 1;
        text-align: right;
    }
    
    .beban-step-title {
        font-size: 15px;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
        line-height: 1.8;
    }
    
    .beban-progress-step.completed .beban-step-title {
        color: #3EB580;
    }
    
    .beban-progress-step.current .beban-step-title {
        color: #513deb;
    }
    
    .beban-progress-step.current .beban-step-title.order-details {
        color: #ff9606;
    }
    
    .beban-progress-step.pending .beban-step-title {
        color: #999;
    }
    
    .beban-step-description {
        font-size: 12px;
        color: #666;
        line-height: 1.4;
    }
    
    .beban-progress-step.pending .beban-step-description {
        color: #ccc;
    }
    
    .beban-step-status {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .beban-status-icon {
        width: 20px;
        height: 20px;
        border-radius: 50% !important;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    .beban-status-icon.completed {
        background: #3EB580;
        color: #fff;
        box-shadow: 0 0 0 4px #3eb58020;
    }
    
    .beban-status-icon.current {
        background: #513deb;
        color: #fff;
        box-shadow: 0 0 0 4px #513deb20;
    }
    
    .beban-status-icon.current.order-details {
        background: #ff9606;
        box-shadow: 0 0 0 4px #ff960620;
    }
    
    .beban-status-icon.pending {
        background: #f5f5f5;
        color: #ccc;
        border: 2px solid #e0e0e0;
    }
    
    .beban-progress-line {
        width: 1px;
        height: 30px;
        background: #E1E1E1;
        margin: 0 20px 0px 0px;
        transition: all 0.3s ease;
    }
    
    .beban-progress-line.completed {
        background: #3EB580;
    }
    
    .beban-loading-spinner {
        width: 12px;
        height: 12px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top: 2px solid #fff;
        border-radius: 50%;
        animation: beban-spin 1s linear infinite;
    }
    
    @keyframes beban-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Mobile responsive */
    @media (max-width: 768px) {
        .beban-checkout-progress-container {
            margin: 15px 0;
            padding: 15px;
        }
        
        .beban-checkout-progress {
            max-width: 100%;
            margin: 0;
        }
        
        .beban-progress-step {
            padding: 12px 0;
            gap: 10px;
        }
        
        .beban-step-icon {
            width: 36px;
            height: 36px;
        }
        
        .beban-step-title {
            font-size: 13px;
        }
        
        .beban-step-description {
            font-size: 11px;
        }
        
        .beban-progress-line {
            margin: 0 0 0 18px;
        }
    }
    
    /* Order Tracking Button Styles */
    .beban-thankyou-tracking-section {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        margin: 20px 0;
        border: 1px solid #e0e0e0;
    }
    
    .beban-order-tracking-button-container {
        margin: 0;
        max-width: 100%;
    }
    
    .beban-order-tracking-button {
        display: flex;
        align-items: center;
        gap: 8px;
        border-radius: 16px;
        padding: 16px !important;
        cursor: pointer;
        transition: all 0.3s ease;
        color: #05060F;
        font-family: 'IRANYekanX', Arial, sans-serif;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        outline: none;
        background-color: #F1F1F1;
        width: 100%;
        box-shadow: none;
        border: none;
    justify-content: space-between;
    }
    
    .beban-order-tracking-button:hover {
        background-color: #F1F1F1;
        text-decoration: none;
        color: #05060F;
    }
    
    .beban-left-arrow-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #05060F;
        margin-right: 8px;
        transform: rotate(120deg);
    }
    
    .beban-tracking-content {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .beban-tracking-icon {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .beban-tracking-text {
        font-weight: 500;
    }
    
    /* Mobile responsive for tracking button */
    @media (max-width: 768px) {
        .beban-thankyou-tracking-section {
            margin: 15px 0;
            padding: 15px;
        }
        
        .beban-order-tracking-button {
            padding: 14px !important;
            font-size: 13px;
        }
    }
    </style>
    <?php
}
add_action('wp_head', 'beban_add_checkout_progress_styles');

/**
 * Automatically add checkout progress to WooCommerce pages
 */
function beban_add_checkout_progress_to_pages() {
    // Only show on WooCommerce pages
    if (!function_exists('is_woocommerce') || !is_woocommerce()) {
        return;
    }
    
    // Show on cart, checkout, and order received pages
    if (is_cart() || is_checkout() || is_wc_endpoint_url('order-received')) {
        echo '<div class="beban-checkout-progress-container">';
        echo do_shortcode('[beban_checkout_progress]');
        echo '</div>';
    }
}

// Add checkout progress to cart page
add_action('woocommerce_before_cart', 'beban_add_checkout_progress_to_pages');

// Add checkout progress to checkout page
add_action('woocommerce_before_checkout_form', 'beban_add_checkout_progress_to_pages');

// Add checkout progress to order received page
add_action('woocommerce_thankyou', 'beban_add_checkout_progress_to_pages', 5);

/**
 * Automatically add back to vendor store button to thank you page
 */
function beban_add_order_tracking_button_to_thankyou() {
    if (is_wc_endpoint_url('order-received')) {
        echo '<div class="beban-thankyou-tracking-section">';
        echo do_shortcode('[beban_back_to_vendor_store]');
        echo '</div>';
    }
}

// Add back to vendor store button to thank you page
add_action('woocommerce_thankyou', 'beban_add_order_tracking_button_to_thankyou', 20);

/**
 * Back to Vendor Store Button Shortcode
 * Usage: [beban_back_to_vendor_store]
 */
function beban_back_to_vendor_store_shortcode($atts) {
    // Only show on order received page
    if (!is_wc_endpoint_url('order-received')) {
        return '';
    }
    
    // Get order ID from URL
    global $wp;
    $order_id = isset($wp->query_vars['order-received']) ? $wp->query_vars['order-received'] : 0;
    
    if (!$order_id) {
        return '';
    }
    
    // Get order object
    $order = wc_get_order($order_id);
    if (!$order) {
        return '';
    }
    
    // Get vendor URL from order items
    $vendor_url = home_url('/'); // Default to homepage
    
    // Get order items to find vendor
    $items = $order->get_items();
    foreach ($items as $item) {
        $product = wc_get_product($item->get_product_id());
        if ($product) {
            $vendor = dokan_get_vendor_by_product($product->get_id());
            if ($vendor) {
                // Get vendor ID from the vendor object
                $vendor_id = $vendor->get_id();
                
                // Try to get vendor store URL using different methods
                $vendor_url = $vendor->get_shop_url();
                
                // If that doesn't work, try alternative methods
                if (empty($vendor_url) || $vendor_url === home_url('/')) {
                    // Method 1: Use dokan_get_store_url
                    $vendor_url = dokan_get_store_url($vendor_id);
                    
                    // Method 2: Use user data
                    if (empty($vendor_url) || $vendor_url === home_url('/')) {
                        $user = get_userdata($vendor_id);
                        if ($user && !empty($user->user_login)) {
                            $vendor_url = home_url('/store/' . $user->user_login . '/');
                        }
                    }
                }
                
                // If we found a valid vendor URL, use it
                if (!empty($vendor_url) && $vendor_url !== home_url('/')) {
                    break; // Since customers can only buy from one vendor
                }
            }
        }
    }
    
    // Order tracking icon (same as in checkout progress)
    $tracking_icon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M3.00999 11.22V15.71C3.00999 20.2 4.80999 22 9.29999 22H14.69C19.18 22 20.98 20.2 20.98 15.71V11.22" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M12 12C13.83 12 15.18 10.51 15 8.68L14.34 2H9.67L9 8.68C8.82 10.51 10.17 12 12 12Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M18.31 12C20.33 12 21.81 10.36 21.61 8.35L21.33 5.6C20.97 3 19.97 2 17.35 2H14.3L15 9.01C15.17 10.66 16.66 12 18.31 12Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M5.64 12C7.29 12 8.78 10.66 8.94 9.01L9.16 6.8L9.64001 2H6.59C3.97001 2 2.97 3 2.61 5.6L2.34 8.35C2.14 10.36 3.62 12 5.64 12Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M12 17C10.33 17 9.5 17.83 9.5 19.5V22H14.5V19.5C14.5 17.83 13.67 17 12 17Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
';
    
    // Left arrow icon
    $left_arrow_icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M14.4291 18.8201C14.2391 18.8201 14.0491 18.7501 13.8991 18.6001C13.6091 18.3101 13.6091 17.8301 13.8991 17.5401L19.4391 12.0001L13.8991 6.46012C13.6091 6.17012 13.6091 5.69012 13.8991 5.40012C14.1891 5.11012 14.6691 5.11012 14.9591 5.40012L21.0291 11.4701C21.3191 11.7601 21.3191 12.2401 21.0291 12.5301L14.9591 18.6001C14.8091 18.7501 14.6191 18.8201 14.4291 18.8201Z" fill="currentColor"/>
        <path d="M20.33 12.75H3.5C3.09 12.75 2.75 12.41 2.75 12C2.75 11.59 3.09 11.25 3.5 11.25H20.33C20.74 11.25 21.08 11.59 21.08 12C21.08 12.41 20.74 12.75 20.33 12.75Z" fill="currentColor"/>
    </svg>';
    
    $output = '<div class="beban-order-tracking-button-container">';
    $output .= '<a href="' . esc_url($vendor_url) . '" class="beban-order-tracking-button beban-ripple-effect beban-ripple-dark">';
    $output .= '<div class="beban-tracking-content">';
    $output .= '<span class="beban-tracking-icon">' . $tracking_icon . '</span>';
    $output .= '<span class="beban-tracking-text">بازگشت به فروشگاه</span>';
    $output .= '</div>';
    $output .= '<span class="beban-left-arrow-icon">' . $left_arrow_icon . '</span>';
    $output .= '</a>';
    $output .= '</div>';
    
    return $output;
}
add_shortcode('beban_back_to_vendor_store', 'beban_back_to_vendor_store_shortcode');
