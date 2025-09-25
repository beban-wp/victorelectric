<?php
/**
 * کنترل دسترسی برای فرم سفارش دستی
 */

class Manual_Order_Access_Control {
    
    /**
     * سازنده کلاس
     */
    public function __construct() {
        add_action('init', array($this, 'init_access_control'));
    }
    
    /**
     * راه‌اندازی کنترل دسترسی
     */
    public function init_access_control() {
        // بررسی دسترسی در فرم
        add_action('wp_ajax_get_vendor_products', array($this, 'check_manual_order_access'), 1);
        add_action('wp_ajax_submit_manual_order', array($this, 'check_manual_order_access'), 1);
        
        // بررسی دسترسی در admin
        add_action('admin_init', array($this, 'check_admin_access'));
    }
    
    /**
     * بررسی دسترسی کاربر برای استفاده از فرم سفارش دستی
     */
    public function check_manual_order_access() {
        if (!$this->can_use_manual_order_form()) {
            wp_send_json_error('شما دسترسی لازم برای استفاده از این فرم را ندارید.');
        }
    }
    
    /**
     * بررسی دسترسی کاربر برای مشاهده صفحه admin
     */
    public function check_admin_access() {
        // فقط برای صفحه سفارش‌های خاص
        if (isset($_GET['page']) && $_GET['page'] === 'special-orders') {
            if (!$this->can_view_special_orders_admin()) {
                wp_die('شما دسترسی لازم برای مشاهده این صفحه را ندارید.');
            }
        }
    }
    
    /**
     * بررسی آیا کاربر می‌تواند از فرم سفارش دستی استفاده کند
     */
    public function can_use_manual_order_form() {
        // بررسی ورود کاربر
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user = wp_get_current_user();
        
        // مدیر کل
        if (in_array('administrator', $user->roles)) {
            return true;
        }
        
        // مدیر فروشگاه
        if (in_array('shop_manager', $user->roles)) {
            return true;
        }
        
        // فروشنده Dokan (اگر پلاگین فعال باشد)
        if (function_exists('dokan_is_user_seller') && dokan_is_user_seller($user->ID)) {
            // بررسی متا کی Dokan برای فروشنده ویژه
            $is_featured_vendor = get_user_meta($user->ID, 'dokan_feature_seller', true);
            return ($is_featured_vendor === 'yes' || $is_featured_vendor === '1' || $is_featured_vendor === true);
        }
        
        return false;
    }
     
    /**
     * بررسی آیا کاربر می‌تواند صفحه admin سفارش‌های خاص را مشاهده کند
     */
    public function can_view_special_orders_admin() {
        // بررسی ورود کاربر
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user = wp_get_current_user();
        
        // مدیر کل
        if (in_array('administrator', $user->roles)) {
            return true;
        }
        
        // مدیر فروشگاه
        if (in_array('shop_manager', $user->roles)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * دریافت لیست کاربران مجاز
     */
    public function get_authorized_users() {
        $users = array();
        
        // مدیران کل
        $admins = get_users(array(
            'role' => 'administrator',
            'fields' => array('ID', 'display_name', 'user_email')
        ));
        
        foreach ($admins as $admin) {
            $users[] = array(
                'id' => $admin->ID,
                'name' => $admin->display_name,
                'email' => $admin->user_email,
                'role' => 'مدیر کل'
            );
        }
        
        // مدیران فروشگاه
        $shop_managers = get_users(array(
            'role' => 'shop_manager',
            'fields' => array('ID', 'display_name', 'user_email')
        ));
        
        foreach ($shop_managers as $manager) {
            $users[] = array(
                'id' => $manager->ID,
                'name' => $manager->display_name,
                'email' => $manager->user_email,
                'role' => 'مدیر فروشگاه'
            );
        }
        
        // فروشندگان Dokan
        if (function_exists('dokan_is_user_seller')) {
            $all_users = get_users(array(
                'fields' => array('ID', 'display_name', 'user_email')
            ));
            
            foreach ($all_users as $user) {
                if (dokan_is_user_seller($user->ID)) {
                    $users[] = array(
                        'id' => $user->ID,
                        'name' => $user->display_name,
                        'email' => $user->user_email,
                        'role' => 'فروشنده'
                    );
                }
            }
        }
        
        return $users;
    }
    
    /**
     * نمایش پیام عدم دسترسی
     */
    public function show_access_denied_message() {
        ?>
        <div class="manual-order-access-denied" style="
            text-align: center;
            padding: 40px 20px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin: 20px 0;
        ">
            <div style="font-size: 48px; color: #6c757d; margin-bottom: 20px;">🔒</div>
            <h3 style="color: #495057; margin-bottom: 15px;">دسترسی محدود</h3>
            <p style="color: #6c757d; margin-bottom: 20px;">
                شما دسترسی لازم برای استفاده از فرم سفارش دستی را ندارید.
            </p>
            <p style="color: #6c757d; font-size: 14px;">
                برای دسترسی به این بخش، با مدیر سیستم تماس بگیرید.
            </p>
        </div>
        <?php
    }
}

// راه‌اندازی کلاس
new Manual_Order_Access_Control();
