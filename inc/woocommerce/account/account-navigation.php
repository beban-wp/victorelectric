<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Replace default WooCommerce account navigation with custom version
 */
add_action('init', 'replace_woocommerce_account_navigation');

function replace_woocommerce_account_navigation() {
    remove_action('woocommerce_account_navigation', 'woocommerce_account_navigation');
    add_action('woocommerce_account_navigation', 'custom_account_navigation');
}

// Register custom endpoints
add_action('init', 'register_custom_account_endpoints');
function register_custom_account_endpoints() {
    add_rewrite_endpoint('wishlist_products', EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('installment-orders', EP_ROOT | EP_PAGES);
    
    // Flush rewrite rules after adding new endpoints
    if (get_option('beban_endpoints_flushed') !== 'yes') {
        flush_rewrite_rules();
        update_option('beban_endpoints_flushed', 'yes');
    }
}

// Flush rewrite rules on plugin activation (only once)
function beban_flush_rewrite_rules_once() {
    if (get_option('beban_rewrite_rules_flushed') !== 'yes') {
        flush_rewrite_rules();
        update_option('beban_rewrite_rules_flushed', 'yes');
    }
}
add_action('init', 'beban_flush_rewrite_rules_once');

// Ensure endpoints are properly registered with WooCommerce
add_action('woocommerce_init', 'ensure_woocommerce_endpoints');
function ensure_woocommerce_endpoints() {
    // Re-register endpoints to ensure WooCommerce recognizes them
    add_rewrite_endpoint('wishlist_products', EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('installment-orders', EP_ROOT | EP_PAGES);
}

// Remove downloads and vendor dashboard from account menu and add installment orders
add_filter('woocommerce_account_menu_items', 'modify_account_menu_items');
function modify_account_menu_items($items) {
    unset($items['downloads']);
    
    // Remove Dokan vendor dashboard button
    if (isset($items['seller-dashboard'])) {
        unset($items['seller-dashboard']);
    }
    
    // Add installment orders after orders
    $new_items = array();
    foreach ($items as $key => $value) {
        $new_items[$key] = $value;
        if ($key === 'orders') {
            $new_items['installment-orders'] = 'سفارشات اقساطی';
        }
    }
    
    return $new_items;
}


function custom_account_navigation() {
    $current_user = wp_get_current_user();
    $first_name = $current_user->first_name;
    $last_name = $current_user->last_name;
    $phone = $current_user->billing_phone;
    
    // SVG edit icon
    $edit_icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M13.26 3.59997L5.04997 12.29C4.73997 12.62 4.43997 13.27 4.37997 13.72L4.00997 16.96C3.87997 18.13 4.71997 18.93 5.87997 18.73L9.09997 18.18C9.54997 18.1 10.18 17.77 10.49 17.43L18.7 8.73997C20.12 7.23997 20.76 5.52997 18.55 3.43997C16.35 1.36997 14.68 2.09997 13.26 3.59997Z" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M11.89 5.05005C12.32 7.81005 14.56 9.92005 17.34 10.2" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M3 22H21" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
</svg>';
    
    // Add account header
    echo '<div class="account-header">';
    
    // Right side - Mobile menu toggle button and Site logo
    echo '<div class="header-right-section">';
    
    // Mobile menu toggle button
    echo '<button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="باز کردن منو">';
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">';
    echo '<path d="M3 7H21" stroke="#292D32" stroke-width="1.5" stroke-linecap="round"/>';
    echo '<path d="M3 12H21" stroke="#292D32" stroke-width="1.5" stroke-linecap="round"/>';
    echo '<path d="M3 17H21" stroke="#292D32" stroke-width="1.5" stroke-linecap="round"/>';
    echo '</svg>';
    echo '</button>';
    
    // Site logo
    echo '<div class="header-logo">';
    if (has_custom_logo()) {
        $custom_logo_id = get_theme_mod('custom_logo');
        $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
        echo '<a href="' . esc_url(home_url('/')) . '">';
        echo '<img src="' . esc_url($logo[0]) . '" alt="' . get_bloginfo('name') . '">';
        echo '</a>';
    } else {
        echo '<a href="' . esc_url(home_url('/')) . '">';
        echo esc_html(get_bloginfo('name'));
        echo '</a>';
    }
    echo '</div>';
    
    echo '</div>'; // End header-right-section
    
    // Left side - Navigation buttons
    echo '<div class="header-buttons">';
    
    // Check if user is a vendor
    $is_vendor = false;
    $vendor_shop_url = '';
    
    // Check if Dokan plugin is active and user is vendor
    if (function_exists('dokan_is_user_seller') && dokan_is_user_seller($current_user->ID)) {
        $is_vendor = true;
        $vendor_shop_url = dokan_get_store_url($current_user->ID);
    }
    
    // Display appropriate button based on user role
    if ($is_vendor && $vendor_shop_url) {
        // Quick Order button for featured vendors only
        $is_featured_vendor = get_user_meta($current_user->ID, 'dokan_feature_seller', true);
        if ($is_featured_vendor === 'yes' || $is_featured_vendor === '1' || $is_featured_vendor === true) {
            echo '<a href="/manual_order/" class="header-btn quick-order-btn">';
            echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
            echo '<path d="M9.62 16L11.12 17.5L14.37 14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
            echo '<path d="M8.81 2L5.19 5.63" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>';
            echo '<path d="M15.19 2L18.81 5.63" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>';
            echo '<path d="M2 7.84998C2 5.99998 2.99 5.84998 4.22 5.84998H19.78C21.01 5.84998 22 5.99998 22 7.84998C22 9.99998 21.01 9.84998 19.78 9.84998H4.22C2.99 9.84998 2 9.99998 2 7.84998Z" stroke="currentColor" stroke-width="1.5"/>';
            echo '<path d="M3.5 10L4.91 18.64C5.23 20.58 6 22 8.86 22H14.89C18 22 18.46 20.64 18.82 18.76L20.5 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>';
            echo '</svg>';
            echo 'سفارش سریع';
            echo '</a>';
        }


        // Vendor shop button
        echo '<a href="' . esc_url($vendor_shop_url) . '" class="header-btn vendor-shop-btn">';
        echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
        echo '<path d="M3.00999 11.22V15.71C3.00999 20.2 4.80999 22 9.29999 22H14.69C19.18 22 20.98 20.2 20.98 15.71V11.22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        echo '<path d="M12 12C13.83 12 15.18 10.51 15 8.68L14.34 2H9.67L9 8.68C8.82 10.51 10.17 12 12 12Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        echo '<path d="M18.31 12C20.33 12 21.81 10.36 21.61 8.35L21.33 5.6C20.97 3 19.97 2 17.35 2H14.3L15 9.01C15.17 10.66 16.66 12 18.31 12Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        echo '<path d="M5.64 12C7.29 12 8.78 10.66 8.94 9.01L9.16 6.8L9.64001 2H6.59C3.97001 2 2.97 3 2.61 5.6L2.34 8.35C2.14 10.36 3.62 12 5.64 12Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        echo '<path d="M12 17C10.33 17 9.5 17.83 9.5 19.5V22H14.5V19.5C14.5 17.83 13.67 17 12 17Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        echo '</svg>';
        echo 'فروشگاه من';
        echo '</a>';   
    } else {
        // Home button for regular users
        echo '<a href="' . esc_url(home_url('/')) . '" class="header-btn home-btn">';
        echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
        echo '<path d="M9.02 2.84004L3.63 7.04004C2.73 7.74004 2 9.23004 2 10.36V17.77C2 20.09 3.89 21.99 6.21 21.99H17.79C20.11 21.99 22 20.09 22 17.78V10.5C22 9.29004 21.19 7.74004 20.2 7.05004L14.02 2.72004C12.62 1.74004 10.37 1.79004 9.02 2.84004Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        echo '<path d="M12 17.99V14.99" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        echo '</svg>';
        echo 'صفحه اصلی';
        echo '</a>';
    }
    
    
    echo '</div>'; // End header-buttons
    echo '</div>'; // End account-header
    
    // Start main nav container
    echo '<nav class="woocommerce-MyAccount-navigation mobile-hidden" id="account-navigation" aria-label="برگه های حساب کاربری">';
    
    // Add custom user info section
    echo '<div class="custom-user-info-section">';
    
    // Top row with avatar only
    echo '<div class="avatar-container">';
    
    // User avatar section
    echo '<div class="user-avatar-section">';
    $avatar_url = get_avatar_url($current_user->ID, array('size' => 60));
    if ($avatar_url) {
        echo '<img src="' . esc_url($avatar_url) . '" alt="' . esc_attr($current_user->display_name) . '">';
    } else {
        // Fallback avatar with initials
        $initials = '';
        if ($first_name && $last_name) {
            $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
        } elseif ($first_name) {
            $initials = strtoupper(substr($first_name, 0, 1));
        } else {
            $initials = strtoupper(substr($current_user->display_name, 0, 1));
        }
        echo '<div class="fallback-avatar">' . esc_html($initials) . '</div>';
    }
    // Green online indicator
    echo '<div class="online-indicator"></div>';
    echo '</div>';
    echo '</div>'; // End avatar-container
    
    // Bottom row with user details and edit button
    echo '<div class="user-details">';
    echo '<div class="user-info-text">';
    if ($first_name || $last_name) {
        echo '<h4>' . esc_html($first_name . ' ' . $last_name) . '</h4>';
    } else {
        echo '<h4>' . esc_html($current_user->display_name) . '</h4>';
    }
    if ($phone) {
        echo '<p>' . esc_html($phone) . '</p>';
    }
    echo '</div>';
    
    // Edit link
    $edit_url = wc_get_account_endpoint_url('edit-account');
    echo '<a href="' . esc_url($edit_url) . '" class="edit-account-link">';
    echo $edit_icon;
    echo '</a>';
    echo '</div>';
    
    // User level section
    echo '<div class="user-level-section">';
    echo '<div class="user-level-info">';
    echo '<span class="user-level-label">سطح شما:</span>';
    
    // Check if user is a vendor
    $is_vendor = false;
    if (function_exists('dokan_is_user_seller') && dokan_is_user_seller($current_user->ID)) {
        $is_vendor = true;
    }
    
    if ($is_vendor) {
        // بررسی اینکه آیا فروشنده ویژه است یا نه
        $is_featured_vendor = get_user_meta($current_user->ID, 'dokan_feature_seller', true);
        
        if ($is_featured_vendor === 'yes' || $is_featured_vendor === '1' || $is_featured_vendor === true) {
            echo '<span class="user-level-value vendor-level featured-vendor">همکار ویژه</span>';
        } else {
            echo '<span class="user-level-value vendor-level">همکار</span>';
        }
    } else {
        echo '<span class="user-level-value customer-level">مشتری</span>';
    }
    
    echo '</div>';
    echo '</div>';
    
    echo '</div>'; // End custom-user-info-section
    
    // Get navigation items
    $endpoints = wc_get_account_menu_items();
    
    // SVG icons for menu items
    $icons = [
        'dashboard' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M19 22V11" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M19 7V2" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M12 22V17" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M12 13V2" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M5 22V11" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M5 7V2" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M3 11H7" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M17 11H21" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M10 13H14" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
',
        'orders' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M9.62 16L11.12 17.5L14.37 14.5" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M8.81 2L5.19 5.63" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M15.19 2L18.81 5.63" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M2 7.84998C2 5.99998 2.99 5.84998 4.22 5.84998H19.78C21.01 5.84998 22 5.99998 22 7.84998C22 9.99998 21.01 9.84998 19.78 9.84998H4.22C2.99 9.84998 2 9.99998 2 7.84998Z" stroke="#292D32" stroke-width="1.5"/>
<path d="M3.5 10L4.91 18.64C5.23 20.58 6 22 8.86 22H14.89C18 22 18.46 20.64 18.82 18.76L20.5 10" stroke="#292D32" stroke-width="1.5" stroke-linecap="round"/>
</svg>
',
        'installment-orders' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M8.30011 18.0399V16.8799C6.00011 15.4899 4.11011 12.7799 4.11011 9.89993C4.11011 4.94993 8.66011 1.06993 13.8001 2.18993C16.0601 2.68993 18.0401 4.18993 19.0701 6.25993C21.1601 10.4599 18.9601 14.9199 15.7301 16.8699V18.0299C15.7301 18.3199 15.8401 18.9899 14.7701 18.9899H9.26011C8.16011 18.9999 8.30011 18.5699 8.30011 18.0399Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M8.5 22C10.79 21.35 13.21 21.35 15.5 22" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
',
        'wishlist_products' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M12.62 20.81C12.28 20.93 11.72 20.93 11.38 20.81C8.48 19.82 2 15.69 2 8.68998C2 5.59998 4.49 3.09998 7.56 3.09998C9.38 3.09998 10.99 3.97998 12 5.33998C13.01 3.97998 14.63 3.09998 16.44 3.09998C19.51 3.09998 22 5.59998 22 8.68998C22 15.69 15.52 19.82 12.62 20.81Z" fill="white" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
',
        'edit-address' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M2.28998 7.77998V17.51C2.28998 19.41 3.63998 20.19 5.27998 19.25L7.62998 17.91C8.13998 17.62 8.98998 17.59 9.51998 17.86L14.77 20.49C15.3 20.75 16.15 20.73 16.66 20.44L20.99 17.96C21.54 17.64 22 16.86 22 16.22V6.48998C22 4.58998 20.65 3.80998 19.01 4.74998L16.66 6.08998C16.15 6.37998 15.3 6.40998 14.77 6.13998L9.51998 3.51998C8.98998 3.25998 8.13998 3.27998 7.62998 3.56998L3.29998 6.04998C2.73998 6.36998 2.28998 7.14998 2.28998 7.77998Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M8.56 4V17" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M15.73 6.62012V20.0001" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
',
        'edit-account' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M2 12.8799V11.1199C2 10.0799 2.85 9.21994 3.9 9.21994C5.71 9.21994 6.45 7.93994 5.54 6.36994C5.02 5.46994 5.33 4.29994 6.24 3.77994L7.97 2.78994C8.76 2.31994 9.78 2.59994 10.25 3.38994L10.36 3.57994C11.26 5.14994 12.74 5.14994 13.65 3.57994L13.76 3.38994C14.23 2.59994 15.25 2.31994 16.04 2.78994L17.77 3.77994C18.68 4.29994 18.99 5.46994 18.47 6.36994C17.56 7.93994 18.3 9.21994 20.11 9.21994C21.15 9.21994 22.01 10.0699 22.01 11.1199V12.8799C22.01 13.9199 21.16 14.7799 20.11 14.7799C18.3 14.7799 17.56 16.0599 18.47 17.6299C18.99 18.5399 18.68 19.6999 17.77 20.2199L16.04 21.2099C15.25 21.6799 14.23 21.3999 13.76 20.6099L13.65 20.4199C12.75 18.8499 11.27 18.8499 10.36 20.4199L10.25 20.6099C9.78 21.3999 8.76 21.6799 7.97 21.2099L6.24 20.2199C5.33 19.6999 5.02 18.5299 5.54 17.6299C6.45 16.0599 5.71 14.7799 3.9 14.7799C2.85 14.7799 2 13.9199 2 12.8799Z" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
',
        'customer-logout' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M15.1 16.44C14.79 20.04 12.94 21.51 8.88998 21.51H8.75998C4.28998 21.51 2.49998 19.72 2.49998 15.25V8.73998C2.49998 4.26998 4.28998 2.47998 8.75998 2.47998H8.88998C12.91 2.47998 14.76 3.92998 15.09 7.46998" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M9 12H20.38" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M18.15 15.3499L21.5 11.9999L18.15 8.6499" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
'
    ];
    
    // Output modified navigation content with icons
    echo '<ul class="woocommerce-MyAccount-navigation-list">';
    $current_page = get_permalink();
    $account_url = wc_get_page_permalink('myaccount');
    $current_endpoint = WC()->query->get_current_endpoint();
    
    // Get current URL to check which endpoint is active
    $current_url = $_SERVER['REQUEST_URI'];
    
    // Debug information (remove this after fixing)
    if (current_user_can('administrator')) {
        echo '<!-- DEBUG: Current Endpoint: ' . $current_endpoint . ' -->';
        echo '<!-- DEBUG: Current URL: ' . $current_url . ' -->';
        echo '<!-- DEBUG: Account URL: ' . $account_url . ' -->';
    }
    
    foreach ($endpoints as $endpoint => $label) {
        $icon = isset($icons[$endpoint]) ? $icons[$endpoint] : '';
        $current = '';
        
        // Get the endpoint URL
        $endpoint_url = wc_get_account_endpoint_url($endpoint);
        
        // Debug information for each endpoint
        if (current_user_can('administrator')) {
            echo '<!-- DEBUG: Endpoint: ' . $endpoint . ' | URL: ' . $endpoint_url . ' -->';
            echo '<!-- DEBUG: Current Page URL: ' . $_SERVER['REQUEST_URI'] . ' -->';
        }
        
        // Check if this is the current endpoint
        if ($current_endpoint === $endpoint) {
            $current = 'is-active';
        } elseif ($endpoint === 'orders' && ($current_endpoint === 'view-order' || $current_endpoint === 'orders')) {
            $current = 'is-active';
        } elseif ($endpoint === 'wishlist_products' && ($current_endpoint === 'wishlist_products' || $current_endpoint === 'view-wishlist')) {
            $current = 'is-active';
        } elseif ($endpoint === 'edit-address' && ($current_endpoint === 'edit-address' || $current_endpoint === 'view-address')) {
            $current = 'is-active';
        } elseif ($endpoint === 'edit-account' && ($current_endpoint === 'edit-account' || $current_endpoint === 'view-account')) {
            $current = 'is-active';
        }
        
        // Additional check for custom endpoints using URL comparison
        if (empty($current)) {
            // Get the current page URL
            $current_page_url = $_SERVER['REQUEST_URI'];
            
            // Remove trailing slash for comparison
            $current_page_url = rtrim($current_page_url, '/');
            $endpoint_url_clean = rtrim($endpoint_url, '/');
            
            // Special handling for dashboard - only active if we're exactly on dashboard page
            if ($endpoint === 'dashboard') {
                if ($current_page_url === $endpoint_url_clean || $current_page_url === '/dashboard') {
                    $current = 'is-active';
                }
            } else {
                // For other endpoints, check if current URL matches the endpoint URL exactly
                if ($current_page_url === $endpoint_url_clean) {
                    $current = 'is-active';
                }
                
                // Additional check for custom endpoints that might be in the URL
                if (empty($current) && strpos($current_page_url, '/' . $endpoint) !== false) {
                    $current = 'is-active';
                }
            }
        }
        
        // Debug information for active state
        if (current_user_can('administrator')) {
            $current_page_url = rtrim($_SERVER['REQUEST_URI'], '/');
            $endpoint_url_clean = rtrim($endpoint_url, '/');
            echo '<!-- DEBUG: Endpoint ' . $endpoint . ' Active: ' . ($current ? 'YES' : 'NO') . ' | Current: ' . $current_page_url . ' | Endpoint: ' . $endpoint_url_clean . ' -->';
        }
        
        echo '<li class="woocommerce-MyAccount-navigation-link ' . esc_attr($current) . '">';
        echo '<a href="' . esc_url($endpoint_url) . '">';
        echo '<span class="menu-item-content">';
        echo esc_html($label);
        echo $icon;
        echo '</span>';
        echo '<svg class="arrow-left" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">';
        echo '<path d="M10.0002 13.2788L5.65355 8.93208C5.14022 8.41875 5.14022 7.57875 5.65355 7.06542L10.0002 2.71875" stroke="#333333" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path>';
        echo '</svg>';
        echo '</a>';
        echo '</li>';
    }
    echo '</ul>';
    
    // Close main nav container
    echo '</nav>';
}

// ============================================================================
// CUSTOM ENDPOINT CONTENT MODIFICATIONS
// ============================================================================

// Add custom title to edit-address endpoint content
add_action('woocommerce_account_edit-address_endpoint', 'beban_edit_address_custom_title', 5);
function beban_edit_address_custom_title() {
    echo '<div class="edit-address-header beban-tab-account-header">';
    echo '<h2 class="edit-address-title">مدیریت آدرس‌های ارسال</h2>';
    echo '<p class="edit-address-description">آدرس‌های خود را مدیریت کنید و آدرس جدید اضافه کنید</p>';
    echo '</div>';
}

// Add custom title to edit-account endpoint content
add_action('woocommerce_account_edit-account_endpoint', 'beban_edit_account_custom_title', 5);
function beban_edit_account_custom_title() {
    echo '<div class="edit-account-header beban-tab-account-header">';
    echo '<h2 class="edit-account-title">ویرایش اطلاعات حساب کاربری</h2>';
    echo '<p class="edit-account-description">اطلاعات شخصی و رمز عبور خود را تغییر دهید</p>';
    echo '</div>';
}

// Add mobile menu styles and functionality
add_action('wp_head', 'beban_mobile_menu_styles');
function beban_mobile_menu_styles() {
    if (is_account_page()) {
        echo '<style>
        /* Header Layout */
        .account-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        
        .header-right-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-buttons {
            display: flex;
            align-items: center;
        }
        
        /* Mobile Menu Toggle Button */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            color: #333;
            transition: all 0.3s ease;
            order: 1;
            border: 1px solid #EDEDED !important;
            box-shadow: 0 2px 4px #05060f0a, 0 0 2px #05060f05;
            height: 46px !important;;
            width: 46px !important;
            background: #fff !important;
            border-radius: 14px !important;
        }
        
        .mobile-menu-toggle:hover, .mobile-menu-toggle:focus ,.mobile-menu-toggle:active{
            background: #fff !important;
        }
        
        .mobile-menu-toggle svg {
            width: 24px;
            height: 24px;
        }
        
        /* Header Logo */
        .header-logo {
            order: 2;
        }
        
        /* Hide logo on mobile */
        @media (max-width: 1024px) {
            .header-logo {
                display: none;
            }
        }
        
        /* Mobile Navigation Styles */
        .woocommerce-MyAccount-navigation.mobile-hidden {
            display: block;
        }
            .mobile-menu-header{
            display: none;
        }
        
        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .mobile-menu-header{
                display: block;
            }
            .mobile-menu-toggle {
                display: flex !important;   
                justify-content: center;
                align-items: center;
            }
            
            .woocommerce-MyAccount-navigation.mobile-hidden {
                display: none;
                position: fixed;
                top: 0;
                right: 0;
                width: 80% !important;
                height: 100vh !important;
                background: #fff;
                z-index: 9999;
                box-shadow: -2px 0 10px rgba(0,0,0,0.05);
                overflow-y: auto;
                transform: translateX(100%);
                opacity: 0;
                transition: all 0.3s ease;
            }
            
            .woocommerce-MyAccount-navigation.mobile-hidden.mobile-open {
                display: block;
                transform: translateX(0);
                opacity: 1;
            }
            
            /* Mobile overlay */
            .mobile-menu-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.07);
                z-index: 9998;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            
            .mobile-menu-overlay.active {
                display: block;
                opacity: 1;
            }
            
            /* Mobile menu header */
            .mobile-menu-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 15px 20px;
                border-bottom: 1px solid #eee;
                background: #f8f9fa;
                font-family: "IranYekanX";
            }
            
            .mobile-menu-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #666;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .mobile-menu-close:hover {
                color: #333;
            }
            
            /* Adjust navigation content for mobile */
            .woocommerce-MyAccount-navigation.mobile-hidden .custom-user-info-section {
                padding: 20px;
            }
            
            .woocommerce-MyAccount-navigation.mobile-hidden .woocommerce-MyAccount-navigation-list {
                padding: 0px;
            }
        }
        
        /* Featured Vendor Style */
        .user-level-value.featured-vendor {
            background: linear-gradient(135deg, #28a745, #20c997) !important;
            color: white;
        }
        
        /* Quick Order Button Style */
        .header-btn.quick-order-btn {
            background: linear-gradient(257.48deg, #ff9606 4.12%, #f9bb00 95.45%) !important;
            font-weight: 600 !important;
        }
        
        /* Desktop styles remain unchanged */
        @media (min-width: 1025px) {
            .mobile-menu-toggle {
                display: none !important;
            }
            

        }
        </style>';
    }
}

// Add mobile menu JavaScript
add_action('wp_footer', 'beban_mobile_menu_script');
function beban_mobile_menu_script() {
    if (is_account_page()) {
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const mobileToggle = document.getElementById("mobile-menu-toggle");
            const navigation = document.getElementById("account-navigation");
            const body = document.body;
            
            if (mobileToggle && navigation) {
                // Create overlay
                const overlay = document.createElement("div");
                overlay.className = "mobile-menu-overlay";
                body.appendChild(overlay);
                
                // Create mobile menu header
                const mobileHeader = document.createElement("div");
                mobileHeader.className = "mobile-menu-header";
                mobileHeader.innerHTML = `
                    <span>منوی حساب کاربری</span>
                    <button class="mobile-menu-close" aria-label="بستن منو">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                `;
                
                // Insert header at the beginning of navigation
                navigation.insertBefore(mobileHeader, navigation.firstChild);
                
                const closeBtn = mobileHeader.querySelector(".mobile-menu-close");
                
                // Toggle menu function
                function toggleMenu() {
                    if (isMobile()) {
                        if (navigation.classList.contains("mobile-open")) {
                            closeMenu();
                        } else {
                            openMenu();
                        }
                    }
                }
                
                // Check if we are on mobile
                function isMobile() {
                    return window.innerWidth <= 1024;
                }
                
                // Open menu function with animation
                function openMenu() {
                    if (isMobile()) {
                        navigation.style.display = "block";
                        overlay.style.display = "block";
                        
                        // Force reflow to ensure display change is applied
                        navigation.offsetHeight;
                        
                        // Add classes for animation
                        navigation.classList.add("mobile-open");
                        overlay.classList.add("active");
                        body.style.overflow = "hidden";
                    }
                }
                
                // Close menu function with animation
                function closeMenu() {
                    if (isMobile()) {
                        navigation.classList.remove("mobile-open");
                        overlay.classList.remove("active");
                        body.style.overflow = "";
                        
                        // Hide elements after animation completes
                        setTimeout(() => {
                            if (!navigation.classList.contains("mobile-open")) {
                                navigation.style.display = "none";
                                overlay.style.display = "none";
                            }
                        }, 300);
                    }
                }
                
                // Event listeners
                mobileToggle.addEventListener("click", toggleMenu);
                closeBtn.addEventListener("click", closeMenu);
                overlay.addEventListener("click", closeMenu);
                
                // Close menu when clicking on navigation links
                const navLinks = navigation.querySelectorAll("a");
                navLinks.forEach(link => {
                    link.addEventListener("click", function() {
                        if (isMobile()) {
                            // Small delay to allow navigation
                            setTimeout(closeMenu, 100);
                        }
                    });
                });
                
                // Close menu on escape key
                document.addEventListener("keydown", function(e) {
                    if (e.key === "Escape" && navigation.classList.contains("mobile-open") && isMobile()) {
                        closeMenu();
                    }
                });
                
                // Handle window resize
                window.addEventListener("resize", function() {
                    if (window.innerWidth > 1024) {
                        // Reset inline styles when switching to desktop
                        navigation.style.display = "";
                        overlay.style.display = "";
                        navigation.classList.remove("mobile-open");
                        overlay.classList.remove("active");
                        body.style.overflow = "";
                    }
                });
            }
        });
        </script>';
    }
}


