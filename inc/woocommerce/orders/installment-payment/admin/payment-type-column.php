<?php
/**
 * Payment Type Column for WooCommerce Orders
 * 
 * Adds a "نوع پرداخت" column to the classic WooCommerce orders list
 * Shows "اقساطی" or "کامل" based on product type-order attribute
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
 
/**
 * Add payment type column to orders list (Legacy storage)
 */
function beban_add_payment_type_column($columns) {
    $new_columns = array();
    
    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;
        
        // Add our columns after the order status column
        if ($key === 'order_status') {
            $new_columns['beban_payment_type'] = 'نوع پرداخت';
            $new_columns['beban_installment_status'] = 'وضعیت اقساط';
            $new_columns['beban_customer_type'] = 'نوع مشتری';
        }
    }
    
    return $new_columns;
}
add_filter('manage_edit-shop_order_columns', 'beban_add_payment_type_column');


/**
 * Display payment type in the custom column (Legacy storage)
 */
function beban_render_payment_type_column($column, $post_id) {
    if ($column === 'beban_payment_type') {
        $order = wc_get_order($post_id);
        
        if (!$order) {
            echo '-';
            return;
        }
        
        // Check if this is an installment order - NOW USING CORE FUNCTION
        $is_installment = beban_order_has_installment_products($order);
        
        // Display the result
        if ($is_installment) {
            echo '<span style="color: #d63638; font-weight: bold;">اقساطی</span>';
        } else {
            echo '<span style="color: #00a32a; font-weight: bold;">کامل</span>';
        }
    }
}
add_action('manage_shop_order_posts_custom_column', 'beban_render_payment_type_column', 10, 2);

/**
 * Display installment status in the custom column
 */
function beban_render_installment_status_column($column, $post_id) {
    if ($column === 'beban_installment_status') {
        $order = wc_get_order($post_id);
        
        if (!$order) {
            echo '-';
            return;
        }
        
        // Check if this is an installment order - NOW USING CORE FUNCTION
        $is_installment = beban_order_has_installment_products($order);
        
        // Display the result
        if ($is_installment) {
            $installment_status = get_post_meta($post_id, '_beban_installment_status', true);
            
            // If no meta exists, it means it's a new installment order
            if (empty($installment_status)) {
                echo '<span style="color: #d63638; font-weight: bold;">قسط اول</span>';
            } else {
                switch ($installment_status) {
                    case 'First':
                        echo '<span style="color: #d63638; font-weight: bold;">قسط اول</span>';
                        break;
                    case 'Second':
                        echo '<span style="color: #ff8c00; font-weight: bold;">قسط دوم</span>';
                        break;
                    case 'Both':
                        echo '<span style="color: #00a32a; font-weight: bold;">تکمیل شده</span>';
                        break;
                    default:
                        echo '<span style="color: #666; font-weight: bold;">نامشخص</span>';
                        break;
                }
            }
        } else {
            echo '-';
        }
    }
}
add_action('manage_shop_order_posts_custom_column', 'beban_render_installment_status_column', 10, 2);

/**
 * Display customer type in the custom column
 */
function beban_render_customer_type_column($column, $post_id) {
    if ($column === 'beban_customer_type') {
        $order = wc_get_order($post_id);
        
        if (!$order) {
            echo '-';
            return;
        }
        
        // Get customer ID
        $customer_id = $order->get_customer_id();
        
        if (!$customer_id) {
            echo '<span style="color: #666; font-weight: bold;">مهمان</span>';
            return;
        }
        
        // Get user object
        $user = get_user_by('ID', $customer_id);
        
        if (!$user) {
            echo '<span style="color: #666; font-weight: bold;">نامشخص</span>';
            return;
        }
        
        // Check user roles
        $user_roles = $user->roles;
        
        if (in_array('seller', $user_roles)) {
            echo '<span style="color: #0073aa; font-weight: bold;">همکار</span>';
        } elseif (in_array('wcwp_wholesale', $user_roles)) {
            echo '<span style="color: #00a32a; font-weight: bold;">مشتری معمولی</span>';
        } else {
            echo '<span style="color: #666; font-weight: bold;">مشتری عادی</span>';
        }
    }
}
add_action('manage_shop_order_posts_custom_column', 'beban_render_customer_type_column', 10, 2);


/**
 * Enqueue CSS and JS for installment admin
 */
// add_action('admin_enqueue_scripts', 'beban_enqueue_installment_admin_assets');
// function beban_enqueue_installment_admin_assets($hook) {
//     // Only load on orders related pages
//     if (($hook === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'shop_order') ||
//         ($hook === 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) === 'shop_order') ||
//         ($hook === 'woocommerce_page_wc-orders')) {
        
//         wp_enqueue_style(
//             'beban-installment-admin',
//             get_stylesheet_directory_uri() . '/assets/css/installment-admin.css',
//             array(),
//             '1.0.0'
//         );
        
//         wp_enqueue_script(
//             'beban-installment-admin',
//             get_stylesheet_directory_uri() . '/assets/js/installment-admin.js',
//             array('jquery'),
//             '1.0.0',
//             true
//         );
//     }
// }




