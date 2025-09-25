<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * نمایش روش‌های حمل و نقل در صفحه تسویه حساب
 * این فایل روش‌های حمل و نقل ووکامرس را قبل از بخش یادداشت مشتری نمایش می‌دهد
 */

// اضافه کردن بخش روش‌های حمل و نقل قبل از یادداشت مشتری
add_action('woocommerce_checkout_before_order_review', 'beban_display_shipping_methods_section', 5);

// اضافه کردن در موقعیت‌های مختلف برای اطمینان از نمایش
// add_action('woocommerce_checkout_after_customer_details', 'beban_display_shipping_methods_section', 5);
add_action('woocommerce_checkout_billing', 'beban_display_shipping_methods_section', 25);

// تابع تست برای اطمینان از نمایش
add_action('woocommerce_checkout_before_order_review', 'beban_test_shipping_display', 1);

function beban_test_shipping_display() {
    if (is_checkout()) {
        echo '<div style="background: #ff0000; color: white; padding: 10px; margin: 10px 0;">تست: بخش روش‌های حمل و نقل</div>';
    }
}

// ========================================
// صفر کردن قیمت‌های حمل و نقل (محاسبات واقعی)
// ========================================

/**
 * صفر کردن قیمت تمام روش‌های حمل و نقل
 * این فیلتر قیمت‌ها را در سطح سیستم تغییر می‌دهد
 */
add_filter('woocommerce_package_rates', 'beban_make_all_shipping_free', 10, 2);

function beban_make_all_shipping_free($rates, $package) {
    // بررسی اینکه آیا باید حمل و نقل رایگان باشد
    if (!beban_should_make_shipping_free()) {
        return $rates;
    }
    
    foreach ($rates as $rate_key => $rate) {
        // صفر کردن قیمت
        $rate->cost = 0;
        
        // صفر کردن مالیات حمل و نقل
        $rate->taxes = array();
        
        // تمیز کردن label از متن‌های اضافی
        $original_label = $rate->get_label();
        
        // حذف متن‌های اضافی از label
        $clean_label = $original_label;
        
        // حذف "(رایگان)" از label
        $clean_label = str_replace(' (رایگان)', '', $clean_label);
        $clean_label = str_replace('(رایگان)', '', $clean_label);
        
        // حذف "(هزینه کرایه در مقصد)" از label
        $clean_label = str_replace(' (هزینه کرایه در مقصد)', '', $clean_label);
        $clean_label = str_replace('(هزینه کرایه در مقصد)', '', $clean_label);
        
        // حذف فاصله‌های اضافی
        $clean_label = trim($clean_label);
        
        $rate->set_label($clean_label);
    }
    
    return $rates;
}

/**
 * بررسی اینکه آیا باید حمل و نقل رایگان باشد
 * می‌توانید این شرایط را تغییر دهید
 */
function beban_should_make_shipping_free() {
    // همیشه رایگان
    return true;
    
    // یا شرایط خاص:
    /*
    // فقط برای کاربران خاص
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        if (in_array('customer', $user->roles)) {
            return true;
        }
    }
    
    // یا بر اساس مبلغ سبد خرید
    $cart_total = WC()->cart->get_cart_contents_total();
    if ($cart_total >= 100000) { // بالای 100 هزار تومان
        return true;
    }
    
    return false;
    */
}

/**
 * اضافه کردن hook برای به‌روزرسانی قیمت‌ها در checkout
 */
add_action('woocommerce_checkout_update_order_review', 'beban_refresh_shipping_rates');

function beban_refresh_shipping_rates($post_data) {
    // به‌روزرسانی نرخ‌های حمل و نقل
    WC()->shipping->reset_shipping();
}

/**
 * تمیز کردن label روش حمل و نقل از متن‌های اضافی
 */
function beban_clean_shipping_label($label) {
    $clean_label = $label;
    
    // حذف "(رایگان)" از label
    $clean_label = str_replace(' (رایگان)', '', $clean_label);
    $clean_label = str_replace('(رایگان)', '', $clean_label);
    
    // حذف "(هزینه کرایه در مقصد)" از label
    $clean_label = str_replace(' (هزینه کرایه در مقصد)', '', $clean_label);
    $clean_label = str_replace('(هزینه کرایه در مقصد)', '', $clean_label);
    
    // حذف فاصله‌های اضافی
    $clean_label = trim($clean_label);
    
    return $clean_label;
}

/**
 * اضافه کردن فیلتر برای صفر کردن قیمت در محاسبات نهایی
 */
add_filter('woocommerce_cart_shipping_total', 'beban_force_shipping_total_zero', 10, 1);

function beban_force_shipping_total_zero($total) {
    if (beban_should_make_shipping_free()) {
        return wc_price(0);
    }
    return $total;
}

/**
 * صفر کردن قیمت حمل و نقل در خلاصه سفارش
 */
add_filter('woocommerce_cart_totals_shipping_html', 'beban_force_shipping_html_zero', 10, 1);

function beban_force_shipping_html_zero($html) {
    if (beban_should_make_shipping_free()) {
        return '<span class="amount">رایگان</span>';
    }
    return $html;
}

/**
 * صفر کردن قیمت حمل و نقل در checkout
 */
add_filter('woocommerce_checkout_shipping_total', 'beban_force_checkout_shipping_zero', 10, 1);

function beban_force_checkout_shipping_zero($total) {
    if (beban_should_make_shipping_free()) {
        return 0;
    }
    return $total;
}

/**
 * تمیز کردن label در تمام جاهای ووکامرس
 */
add_filter('woocommerce_cart_shipping_method_full_label', 'beban_clean_shipping_label_woocommerce', 10, 2);

function beban_clean_shipping_label_woocommerce($label, $method) {
    return beban_clean_shipping_label($label);
}

/**
 * تمیز کردن label در checkout
 */
add_filter('woocommerce_checkout_shipping_method_label', 'beban_clean_checkout_shipping_label', 10, 2);

function beban_clean_checkout_shipping_label($label, $method) {
    return beban_clean_shipping_label($label);
}

function beban_display_shipping_methods_section() {
    // Debug: بررسی اینکه آیا تابع فراخوانی می‌شود
    if (current_user_can('administrator')) {
        echo '<!-- Beban Shipping Methods Function Called -->';
    }
    
    // بررسی اینکه آیا روش‌های حمل و نقل موجود است
    if (!WC()->cart->needs_shipping()) {
        if (current_user_can('administrator')) {
            echo '<!-- Cart does not need shipping -->';
        }
        return;
    }
    
    // دریافت روش‌های حمل و نقل موجود
    $packages = WC()->shipping->get_packages();
    
    if (empty($packages)) {
        if (current_user_can('administrator')) {
            echo '<!-- No shipping packages available -->';
        }
        return;
    }
    
    ?>
    <div class="beban-shipping-methods-section">
        <div class="shipping-methods-header">
            <h3 class="shipping-methods-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3.5 13.37V17.13C3.5 19.38 4.62 20.5 6.87 20.5H17.13C19.38 20.5 20.5 19.38 20.5 17.13V13.37C20.5 11.12 19.38 10 17.13 10H6.87C4.62 10 3.5 11.12 3.5 13.37Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M8 10V8C8 5.79 9.79 4 12 4H16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 8H5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 11H5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 14H5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M19 8H22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M19 11H22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M19 14H22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                روش ارسال
            </h3>
            <p class="shipping-methods-description">روش ارسال مورد نظر خود را انتخاب کنید</p>
        </div>
        
        <div class="shipping-methods-content">
            <?php
            foreach ($packages as $i => $package) {
                $chosen_method = isset(WC()->session->chosen_shipping_methods[$i]) ? WC()->session->chosen_shipping_methods[$i] : '';
                $product_names = array();
                
                if (count($packages) > 1) {
                    foreach ($package['contents'] as $item_id => $values) {
                        $product_names[] = $values['data']->get_name() . ' &times;' . $values['quantity'];
                    }
                    echo '<p class="package-name">' . esc_html(implode(', ', $product_names)) . '</p>';
                }
                
                if ($package['rates']) {
                    echo '<ul class="shipping-methods-list">';
                    foreach ($package['rates'] as $method) {
                        $method_id = $method->get_id();
                        $method_label = $method->get_label();
                        $method_cost = $method->get_cost();
                        $method_tax = $method->get_shipping_tax();
                        $method_total = $method_cost + $method_tax;
                        
                        $is_selected = $chosen_method === $method_id;
                        
                        // تمیز کردن label برای نمایش
                        $clean_method_label = beban_clean_shipping_label($method_label);
                        
                        // همیشه "رایگان" نمایش دهید
                        $cost_display = 'رایگان';
                        
                        // یا اگر می‌خواهید قیمت واقعی را ببینید (برای debug):
                        // $cost_display = $method_total > 0 ? wc_price($method_total) : 'رایگان';
                        
                        ?>
                        <li class="shipping-method-item <?php echo $is_selected ? 'selected' : ''; ?>">
                            <label class="shipping-method-label">
                                <input type="radio" 
                                       name="shipping_method[<?php echo $i; ?>]" 
                                       value="<?php echo esc_attr($method_id); ?>" 
                                       <?php checked($is_selected, true); ?>
                                       class="shipping-method-radio">
                                
                                <div class="shipping-method-info">
                                    <div class="shipping-method-details">
                                        <span class="shipping-method-name"><?php echo esc_html($clean_method_label); ?></span>
                                        <span class="shipping-method-cost"><?php echo $cost_display; ?></span>
                                    </div>
                                    
                                    <?php if ($method->get_meta_data()) : ?>
                                        <div class="shipping-method-meta">
                                            <?php foreach ($method->get_meta_data() as $meta) : ?>
                                                <small class="shipping-meta"><?php echo esc_html($meta->display_key . ': ' . $meta->display_value); ?></small>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="shipping-method-icon">
                                    <?php if ($is_selected) : ?>
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                    <?php else : ?>
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                            </label>
                        </li>
                        <?php
                    }
                    echo '</ul>';
                } else {
                    echo '<p class="no-shipping-methods">هیچ روش ارسالی برای این آدرس در دسترس نیست.</p>';
                }
            }
            ?>
        </div>
    </div>
    
    <style>
    /* استایل‌های روش‌های حمل و نقل */
    .beban-shipping-methods-section {
        background: #ffffff;
        border: 1px solid #e1e5e9;
        border-radius: 12px;
        padding: 24px;
        margin: 20px 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }
    
    .beban-shipping-methods-section:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    }
    
    .shipping-methods-header {
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid #f1f3f4;
    }
    
    .shipping-methods-title {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0 0 8px 0;
        font-size: 18px;
        font-weight: 600;
        color: #2d3748;
        font-family: "IranYekanX", sans-serif;
    }
    
    .shipping-methods-title svg {
        color: #667eea;
        flex-shrink: 0;
    }
    
    .shipping-methods-description {
        margin: 0;
        color: #6c757d;
        font-size: 14px;
        font-family: "IranYekanX", sans-serif;
    }
    
    .package-name {
        background: #f8f9fa;
        padding: 8px 12px;
        border-radius: 8px;
        margin: 0 0 16px 0;
        font-size: 14px;
        color: #495057;
        border-right: 3px solid #667eea;
    }
    
    .shipping-methods-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .shipping-method-item {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.3s ease;
        overflow: hidden;
    }
    
    .shipping-method-item:hover {
        border-color: #cbd5e0;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .shipping-method-item.selected {
        border-color: #667eea;
        background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
        box-shadow: 0 4px 16px rgba(102, 126, 234, 0.15);
    }
    
    .shipping-method-label {
        display: flex;
        align-items: center;
        padding: 16px 20px;
        cursor: pointer;
        width: 100%;
        margin: 0;
        position: relative;
    }
    
    .shipping-method-radio {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    
    .shipping-method-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .shipping-method-details {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }
    
    .shipping-method-name {
        font-weight: 600;
        color: #2d3748;
        font-size: 16px;
        font-family: "IranYekanX", sans-serif;
    }
    
    .shipping-method-cost {
        font-weight: 700;
        color: #667eea;
        font-size: 16px;
        background: rgba(102, 126, 234, 0.1);
        padding: 4px 12px;
        border-radius: 20px;
        font-family: "IranYekanX", sans-serif;
    }
    
    .shipping-method-item.selected .shipping-method-cost {
        background: #667eea;
        color: white;
    }
    
    .shipping-method-meta {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    
    .shipping-meta {
        color: #6c757d;
        font-size: 13px;
        font-family: "IranYekanX", sans-serif;
    }
    
    .shipping-method-icon {
        margin-left: 16px;
        color: #cbd5e0;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }
    
    .shipping-method-item.selected .shipping-method-icon {
        color: #667eea;
    }
    
    .no-shipping-methods {
        text-align: center;
        padding: 20px;
        color: #6c757d;
        background: #f8f9fa;
        border-radius: 8px;
        margin: 0;
        font-family: "IranYekanX", sans-serif;
    }
    
    /* انیمیشن‌ها */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .beban-shipping-methods-section {
        animation: fadeInUp 0.6s ease-out;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .beban-shipping-methods-section {
            padding: 16px;
            margin: 16px 0;
        }
        
        .shipping-methods-title {
            font-size: 16px;
        }
        
        .shipping-method-label {
            padding: 12px 16px;
        }
        
        .shipping-method-details {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        
        .shipping-method-name {
            font-size: 14px;
        }
        
        .shipping-method-cost {
            font-size: 14px;
            padding: 3px 10px;
        }
        
        .shipping-method-icon {
            margin-left: 12px;
        }
    }
    
    /* تم تاریک */
    @media (prefers-color-scheme: dark) {
        .beban-shipping-methods-section {
            background: #2d3748;
            border-color: #4a5568;
        }
        
        .shipping-methods-title {
            color: #ffffff;
        }
        
        .shipping-methods-description {
            color: #a0aec0;
        }
        
        .package-name {
            background: #4a5568;
            color: #e2e8f0;
        }
        
        .shipping-method-item {
            border-color: #4a5568;
            background: #2d3748;
        }
        
        .shipping-method-item:hover {
            border-color: #718096;
        }
        
        .shipping-method-item.selected {
            background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
            border-color: #667eea;
        }
        
        .shipping-method-name {
            color: #ffffff;
        }
        
        .shipping-meta {
            color: #a0aec0;
        }
        
        .no-shipping-methods {
            background: #4a5568;
            color: #a0aec0;
        }
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // مدیریت تغییر روش ارسال
        $('.shipping-method-radio').on('change', function() {
            // حذف کلاس selected از همه آیتم‌ها
            $('.shipping-method-item').removeClass('selected');
            
            // اضافه کردن کلاس selected به آیتم انتخاب شده
            $(this).closest('.shipping-method-item').addClass('selected');
            
            // به‌روزرسانی سبد خرید
            $('body').trigger('update_checkout');
        });
        
        // انیمیشن hover برای آیتم‌ها
        $('.shipping-method-item').hover(
            function() {
                if (!$(this).hasClass('selected')) {
                    $(this).css('transform', 'translateY(-2px)');
                }
            },
            function() {
                if (!$(this).hasClass('selected')) {
                    $(this).css('transform', 'translateY(0)');
                }
            }
        );
    });
    </script>
    <?php
}

// مخفی کردن روش‌های حمل و نقل پیش‌فرض ووکامرس
add_action('wp_head', 'beban_hide_default_shipping_methods');

function beban_hide_default_shipping_methods() {
    if (is_checkout()) {
        ?>
        <style>
        /* مخفی کردن روش‌های حمل و نقل پیش‌فرض ووکامرس */
        .woocommerce-shipping-methods,
        .woocommerce-shipping-fields,
        #shipping_method,
        .woocommerce-shipping-totals,
        .shipping {
            display: none !important;
        }
        
        /* مخفی کردن عنوان "ارسال" */
        h3#ship-to-different-address,
        h3#order_review_heading {
            display: none !important;
        }
        </style>
        <?php
    }
}

// اضافه کردن JavaScript برای به‌روزرسانی قیمت‌ها
add_action('wp_footer', 'beban_shipping_methods_script');

function beban_shipping_methods_script() {
    if (is_checkout()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // به‌روزرسانی قیمت‌ها هنگام تغییر روش ارسال
            $(document.body).on('updated_checkout', function() {
                // به‌روزرسانی نمایش قیمت‌ها
                $('.shipping-method-cost').each(function() {
                    var methodId = $(this).closest('.shipping-method-item').find('.shipping-method-radio').val();
                    var isSelected = $(this).closest('.shipping-method-item').find('.shipping-method-radio').is(':checked');
                    
                    if (isSelected) {
                        $(this).closest('.shipping-method-item').addClass('selected');
                    } else {
                        $(this).closest('.shipping-method-item').removeClass('selected');
                    }
                });
            });
            
            // مدیریت کلیک روی آیتم‌ها
            $('.shipping-method-label').on('click', function(e) {
                e.preventDefault();
                var radio = $(this).find('.shipping-method-radio');
                radio.prop('checked', true).trigger('change');
            });
        });
        </script>
        <?php
    }
}