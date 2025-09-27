<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );
         
if ( !function_exists( 'child_theme_configurator_css' ) ):
    function child_theme_configurator_css() {
        wp_enqueue_style( 'chld_thm_cfg_child', trailingslashit( get_stylesheet_directory_uri() ) . 'style.css', array( 'hello-elementor','hello-elementor-theme-style','hello-elementor-header-footer' ) );
    }
endif;
add_action( 'wp_enqueue_scripts', 'child_theme_configurator_css', 10 );

// END ENQUEUE PARENT ACTION



// Include WooCommerce Account Features
require_once get_stylesheet_directory() . '/inc/woocommerce/account/account-navigation.php';
// Include WooCommerce Orders Features
require_once get_stylesheet_directory() . '/inc/woocommerce/orders/order-history/orders-history.php';
require_once get_stylesheet_directory() . '/inc/woocommerce/orders/order-history/orders-ajax.php';

require_once get_stylesheet_directory() . '/inc/woocommerce/orders/custom-order-status.php';

require_once get_stylesheet_directory() . '/inc/woocommerce/account/list-latest-orders.php';


// Include installment orders tab - Core functions first
require_once get_stylesheet_directory() . '/inc/woocommerce/orders/installment-payment/core/installment-detector.php';
require_once get_stylesheet_directory() . '/inc/woocommerce/orders/installment-payment/core/installment-calculator.php';

// Include admin files
require_once get_stylesheet_directory() . '/inc/woocommerce/orders/installment-payment/admin/order-meta-box.php';
require_once get_stylesheet_directory() . '/inc/woocommerce/orders/installment-payment/admin/payment-type-column.php';

// Include frontend files
require_once get_stylesheet_directory() . '/inc/woocommerce/orders/installment-payment/frontend/installment-status-display.php';
require_once get_stylesheet_directory() . '/inc/woocommerce/orders/installment-payment/frontend/installment-orders-list.php';
require_once get_stylesheet_directory() . '/inc/woocommerce/orders/installment-payment/frontend/installment-orders-helpers.php';

// Include second installment payment handler
// require_once get_stylesheet_directory() . '/inc/woocommerce/orders/installment-payment/second-installment-handler.php';
require_once get_stylesheet_directory() . '/inc/woocommerce/thankyou/thank-you-shortcode.php';

// Include WooCommerce Products Features
// require_once get_stylesheet_directory() . '/inc/woocommerce/products/stock-notice.php';
require_once get_stylesheet_directory() . '/inc/woocommerce/products/suggested-products-dashboard.php';
require_once get_stylesheet_directory() . '/inc/woocommerce/products/hide-variations.php';
require_once get_stylesheet_directory() . '/inc/woocommerce/products/disable-installment-option.php';

// Include WooCommerce Cart Features
require_once get_stylesheet_directory() . '/inc/woocommerce/cart/cart-count-shortcode.php';
require_once get_stylesheet_directory() . '/inc/woocommerce/cart/cart-total-shortcode.php';

// Include back button utility
require_once get_stylesheet_directory() . '/inc/utils/back-button.php';

// Include checkout progress utility
require_once get_stylesheet_directory() . '/inc/utils/checkout-progress.php';

// Include ripple effect utility
require_once get_stylesheet_directory() . '/inc/utils/ripple-effect.php';

// Include asset manager
require_once get_stylesheet_directory() . '/inc/utils/asset-manager.php';

// Include shipping methods display
// require_once get_stylesheet_directory() . '/inc/woocommerce/checkout/shipping-methods-display.php';


// Include Access Control (اول باید لود شود)
require_once get_stylesheet_directory() . '/inc/woocommerce/orders/manual-order/access-control.php';

// Include Manual Order Form
require_once get_stylesheet_directory() . '/inc/woocommerce/orders/manual-order/manual-order-shortcode.php';

// Include Special Orders Admin
require_once get_stylesheet_directory() . '/inc/woocommerce/orders/manual-order/special-orders-admin.php';











/**
 * Redirect homepage to WooCommerce My Account page for vendors only
 * - Skips admin, AJAX, CLI, REST requests
 * - Only redirects if user is logged in and is a Dokan vendor
 * - Avoids loops if already on account page or not front page
 */
function beban_redirect_home_to_account() {
    if ( is_admin() || wp_doing_ajax() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
        return;
    }

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        return;
    }

    if ( ! function_exists( 'wc_get_page_permalink' ) ) {
        return;
    }

    if ( ! is_front_page() ) {
        return;
    }

    // Check if user is logged in
    if ( ! is_user_logged_in() ) {
        return;
    }

    // Check if user is a Dokan vendor
    $current_user = wp_get_current_user();
    if ( ! function_exists( 'dokan_is_user_seller' ) || ! dokan_is_user_seller( $current_user->ID ) ) {
        return;
    }

    $account_url = wc_get_page_permalink( 'myaccount' );
    if ( empty( $account_url ) ) {
        return;
    }

    // Prevent redirect loop
    if ( trailingslashit( home_url( '/' ) ) === trailingslashit( $account_url ) ) {
        return;
    }

    wp_safe_redirect( $account_url );
    exit;
}
add_action( 'template_redirect', 'beban_redirect_home_to_account' );



/**
 * Change "Add to cart" button text to "Buy Product"
 */
function beban_change_add_to_cart_text($text, $product) {
    if ($product && $product->is_type('simple')) {
        return 'خرید محصول';
    }
    return $text;
}
add_filter('woocommerce_product_add_to_cart_text', 'beban_change_add_to_cart_text', 10, 2);
add_filter('woocommerce_product_single_add_to_cart_text', 'beban_change_add_to_cart_text', 10, 2);

/**
 * Include vendor shortcode
 */
require_once get_stylesheet_directory() . '/inc/woocommerce/account/vendor-shortcode.php';

/**
 * Allow vendors to purchase their own products
 * This function overrides the Dokan restriction that prevents vendors from buying their own products
 */
function beban_allow_vendor_own_product_purchase( $is_purchasable, $product ) {
    // Check if the current user is a vendor and owns this product
    if ( dokan_is_user_seller( dokan_get_current_user_id() ) && dokan_is_product_author( $product->get_id() ) ) {
        // Allow the vendor to purchase their own product
        return true;
    }
    
    return $is_purchasable;
}
add_filter( 'dokan_vendor_own_product_purchase_restriction', 'beban_allow_vendor_own_product_purchase', 10, 2 );

/**
 * Remove the "vendor cannot buy own product" notice
 */
function beban_remove_vendor_own_product_notice() {
    // Remove the notice that says vendors cannot buy their own products
    remove_action( 'woocommerce_before_single_product', 'dokan_vendor_own_product_purchase_restriction_notice' );
}
add_action( 'init', 'beban_remove_vendor_own_product_notice' );

/**
 * Custom notice for vendors buying their own products
 */
function beban_vendor_own_product_notice() {
    if ( ! is_user_logged_in() || ! dokan_is_user_seller( dokan_get_current_user_id() ) ) {
        return;
    }
    
    global $product;
    if ( ! $product || ! dokan_is_product_author( $product->get_id() ) ) {
        return;
    }
    
    echo '<div class="woocommerce-info beban-vendor-own-product-notice">';
    echo '<p>شما در حال خرید محصول خودتان هستید. این خرید برای تست و بررسی محصول شما انجام می‌شود.</p>';
    echo '</div>';
}
add_action( 'woocommerce_before_single_product', 'beban_vendor_own_product_notice' );

/**
 * Redirect /author/ URLs to /store/ URLs
 */
function beban_redirect_author_to_store() {
    // Check if we're on an author page
    if (is_author()) {
        $author_id = get_queried_object_id();
        $author = get_userdata($author_id);
        
        if ($author) {
            // Check if user is a vendor
            if (dokan_is_user_seller($author_id)) {
                // Get current URL
                $current_url = $_SERVER['REQUEST_URI'];
                
                // Replace /author/ with /store/
                $new_url = str_replace('/author/', '/store/', $current_url);
                
                // If URL changed, redirect
                if ($new_url !== $current_url) {
                    wp_redirect(home_url($new_url), 301);
                    exit;
                }
            }
        }
    }
}
add_action('template_redirect', 'beban_redirect_author_to_store');
















add_filter( 'woocommerce_checkout_fields', 'change_address_1_labels', 100 );
function change_address_1_labels( $fields ) {
    // تغییر لیبل فیلد آدرس billing
    $fields['billing']['billing_address_1']['label'] = 'آدرس کامل';
    
    // تغییر لیبل فیلد آدرس shipping
    $fields['shipping']['shipping_address_1']['label'] = 'آدرس کامل';
    
    return $fields;
}

add_filter( 'woocommerce_checkout_fields', 'add_phone_to_shipping_fields' );
function add_phone_to_shipping_fields( $fields ) {
    // افزودن فیلد شماره موبایل به بخش shipping
    $fields['shipping']['shipping_phone'] = array(
        'label'       => __( 'شماره موبایل', 'woocommerce' ),
        'placeholder' => _x( 'شماره موبایل خود را وارد کنید', 'placeholder', 'woocommerce' ),
        'required'    => true, // اجباری بودن فیلد
        'class'       => array( 'form-row-wide' ), // کلاس CSS برای چیدمان
        'priority'    => 100, // ترتیب نمایش
        'type'        => 'tel', // نوع فیلد
        'validate'    => array( 'phone' ), // اعتبارسنجی شماره موبایل
        'autocomplete' => 'tel',
    );

    return $fields;
}

// اطمینان از اجباری بودن فیلد شماره موبایل billing
add_filter( 'woocommerce_checkout_fields', 'make_billing_phone_required' );
function make_billing_phone_required( $fields ) {
    if ( isset( $fields['billing']['billing_phone'] ) ) {
        $fields['billing']['billing_phone']['required'] = true;
    }
    return $fields;
}

// اطمینان از اجباری بودن فیلد شماره موبایل billing با فیلتر دیگه
add_filter( 'woocommerce_billing_fields', 'force_billing_phone_required' );
function force_billing_phone_required( $fields ) {
    if ( isset( $fields['billing_phone'] ) ) {
        $fields['billing_phone']['required'] = true;
    }
    return $fields;
}


// // اختیاری کردن کد پستی و حذف ایمیل
add_filter('woocommerce_billing_fields', 'beban_customize_checkout_fields', 1000);
function beban_customize_checkout_fields($fields) {
    if (isset($fields['billing_email'])) {
        unset($fields['billing_email']);
    }

    return $fields;
}

// جلوگیری از خطای ایمیل
add_filter('woocommerce_checkout_required_field_notice', 'beban_remove_email_required_notice', 10, 2);
function beban_remove_email_required_notice($notice, $field_key) {
    if ($field_key === 'billing_email') {
        return '';
    }
    return $notice;
}















add_filter( 'woocommerce_get_price_html', 'custom_show_pay_full_price', 100, 2 );

function custom_show_pay_full_price( $price_html, $product ) {
    if ( ! $product->is_type( 'variable' ) ) {
        return $price_html;
    }

    $attribute_name = 'pa_type-order';
    $target_value = 'pay-full';

    $variations = $product->get_available_variations();
    $pay_full_price = null;
    $variation_product = null;

    foreach ( $variations as $variation ) {
        $variation_product = wc_get_product( $variation['variation_id'] );
        $attributes = $variation_product->get_attributes();

        if ( isset( $attributes[$attribute_name] ) && $attributes[$attribute_name] === $target_value ) {
            $pay_full_price = $variation_product->get_price();
            break;
        }
    }

    if ( $pay_full_price !== null && $variation_product ) {
        if ( $variation_product->is_on_sale() ) {
            $regular_price = $variation_product->get_regular_price();
            $price_html = wc_format_sale_price( wc_price( $regular_price ), wc_price( $pay_full_price ) ) . $product->get_price_suffix();
        } else {
            $price_html = wc_price( $pay_full_price ) . $product->get_price_suffix();
        }
    }

    return $price_html;
}










add_action( 'add_meta_boxes', 'add_product_english_name_metabox' );

function add_product_english_name_metabox() {
    add_meta_box(
        'product_english_name',
        __( 'English Name', 'woocommerce' ),
        'render_product_english_name_metabox',
        'product',
        'side',
        'default'
    );
}

function render_product_english_name_metabox( $post ) {
    $english_name = get_post_meta( $post->ID, '_product_english_name', true );
    wp_nonce_field( 'product_english_name_nonce', 'product_english_name_nonce_field' );
    ?>
    <p>
        <label for="product_english_name"><?php _e( 'English Name', 'woocommerce' ); ?></label>
        <input type="text" name="product_english_name" id="product_english_name" value="<?php echo esc_attr( $english_name ); ?>" class="widefat" />
    </p>
    <?php
}

add_action( 'save_post_product', 'save_product_english_name' );

function save_product_english_name( $post_id ) {
    if ( ! isset( $_POST['product_english_name_nonce_field'] ) || ! wp_verify_nonce( $_POST['product_english_name_nonce_field'], 'product_english_name_nonce' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['product_english_name'] ) ) {
        $english_name = sanitize_text_field( $_POST['product_english_name'] );
        update_post_meta( $post_id, '_product_english_name', $english_name );
    } else {
        delete_post_meta( $post_id, '_product_english_name' );
    }
}













// ---------------------------------------------------------------------------------------------------------------------------------------
// سفارشی کردن پیام سبد خرید خالی در مینی کارت المنتور
// ---------------------------------------------------------------------------------------------------------------------------------------

/**
 * Customize empty cart message for Elementor WooCommerce Mini Cart Widget
 */
function custom_empty_cart_message($message) {
    // Check if we're in the mini cart context
    if (wp_doing_ajax() || (isset($_POST['action']) && $_POST['action'] === 'elementor_ajax')) {
        return 'سبد خرید خالی است (:';
    }
    
    // For regular cart page, keep original message
    return $message;
}
add_filter('wc_empty_cart_message', 'custom_empty_cart_message');

/**
 * Customize Elementor WooCommerce Mini Cart empty message
 */
function custom_elementor_mini_cart_empty_message($message) {
    return 'سبد خرید خالی است (:';
}
add_filter('elementor/widget/mini-cart/empty_cart_message', 'custom_elementor_mini_cart_empty_message');

/**
 * Override WooCommerce mini cart empty message via JavaScript
 */
function custom_mini_cart_empty_message_script() {
    ?>
    <script>
    // document.addEventListener("DOMContentLoaded", function () {
    //     const observer = new MutationObserver(() => {
    //         const emptyMessage = document.querySelector(".woocommerce-mini-cart__empty-message");
    //         if (emptyMessage && emptyMessage.textContent.includes("No products")) {
    //             emptyMessage.textContent = "سبد خرید خالی است (:";
    //             observer.disconnect();
    //         }
    //     });

    //     observer.observe(document.body, {
    //         childList: true,
    //         subtree: true,
    //     });
    // });
    </script>
    <?php
}
// add_action('wp_footer', 'custom_mini_cart_empty_message_script');

/**
 * Filter WooCommerce cart fragments to customize empty message
 */
function custom_cart_fragments($fragments) {
    if (isset($fragments['div.widget_shopping_cart_content'])) {
        // Replace empty cart message in cart fragments
        $fragments['div.widget_shopping_cart_content'] = str_replace(
            'No products in the cart.',
            'سبد خرید خالی است (:',
            $fragments['div.widget_shopping_cart_content']
        );
    }
    
    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'custom_cart_fragments');







add_shortcode('beban_carousel', 'beban_carousel_shortcode');
function beban_carousel_shortcode() {
    // تولید اعداد تصادفی
    $cart_count = rand(3, 50);
    $view_count = rand(100, 5000);
    
    // اسلایدها با لینک‌های آیکون
    $slides = [
        [
            'text' => sprintf(__('در سبد خرید <span class="cart-count">+%s</span> نفر', 'beban-carousel'), $cart_count),
            'icon' => 'https://peymanbaba.com/wp-content/uploads/2025/06/svgexport-49.svg' // سبد خرید
        ],
        [
            'text' => __('بهترین قیمت در ۳۰ روز گذشته', 'beban-carousel'),
            'icon' => 'https://peymanbaba.com/wp-content/uploads/2025/06/svgexport-47.svg' // شکلک ستاره‌دار
        ],
        [
            'text' => sprintf(__('<span class="view-count">%s</span> بازدید در ۲۴ ساعت اخیر', 'beban-carousel'), $view_count),
            'icon' => 'https://peymanbaba.com/wp-content/uploads/2025/06/svgexport-52.svg' // آتش
        ],
    ];

    // تولید HTML کاروسل
    ob_start();
    ?>
    <div class="beban-carousel">
        <?php foreach ($slides as $index => $slide) : ?>
            <div class="beban-carousel-slide" style="transform: translateY(<?php echo $index === 0 ? '0' : '100%'; ?>);">
                <span class="beban-icon-wrapper">
                    <img src="<?php echo esc_url($slide['icon']); ?>" alt="Icon" class="beban-icon">
                </span>
                <span class="beban-text"><?php echo wp_kses_post($slide['text']); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}




















/**
 * Move out of stock products to the end of category pages
 * 
 * @param WP_Query $q The WooCommerce product query object
 */
function beban_move_out_of_stock_products_to_end($q) {
    // Only apply on product category archive pages, not in admin
    if (!is_admin() && is_product_category()) {
        // Set meta query to include both in-stock and out-of-stock products
        $q->set('meta_query', array(
            'relation' => 'OR',
            array(
                'key' => '_stock_status',
                'value' => 'instock',
                'compare' => '='
            ),
            array(
                'key' => '_stock_status',
                'value' => 'outofstock',
                'compare' => '='
            )
        ));
        
        // Sort by stock status (instock comes before outofstock alphabetically)
        $q->set('orderby', 'meta_value');
        $q->set('meta_key', '_stock_status');
        $q->set('order', 'ASC');
    }
}

// Hook into WooCommerce product query
add_action('woocommerce_product_query', 'beban_move_out_of_stock_products_to_end');