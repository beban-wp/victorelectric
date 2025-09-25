<?php
/**
 * Special Orders Admin Page
 * مدیریت سفارش‌های خاص در پنل مدیریت
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

class Special_Orders_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_get_vendor_order_details', array($this, 'get_vendor_order_details'));
        add_action('wp_ajax_get_vendor_stats_details', array($this, 'get_vendor_stats_details'));
    }
    
    /**
     * اضافه کردن زیرمنو به منوی ووکامرس
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'سفارش‌های خاص',
            'سفارش‌های خاص',
            'manage_woocommerce',
            'special-orders',
            array($this, 'admin_page')
        );
    }
    
    /**
     * صفحه مدیریت سفارش‌های خاص
     */
    public function admin_page() {
        // بررسی دسترسی
        $access_control = new Manual_Order_Access_Control();
        if (!$access_control->can_view_special_orders_admin()) {
            wp_die('شما دسترسی لازم برای مشاهده این صفحه را ندارید.');
        }
        
        ?>
        <div class="wrap">
            <h1>سفارش‌های خاص</h1>
            <p>لیست سفارش‌هایی که با فرم دستی ثبت شده‌اند و وضعیت "پرداخت خاص" دارند.</p>
            
            <!-- تب‌ها -->
            <div class="nav-tab-wrapper">
                <a href="#orders-tab" class="nav-tab nav-tab-active" data-tab="orders">📋 لیست سفارشات</a>
                <a href="#stats-tab" class="nav-tab" data-tab="stats">📊 آمار فروش</a>
            </div>
            
            <!-- تب لیست سفارشات -->
            <div id="orders-tab" class="tab-content active">
                <div class="special-orders-container">
                <?php
                // دریافت سفارش‌های خاص
                $special_orders = $this->get_special_orders();
                
                if ($special_orders) {
                    $this->display_orders_table($special_orders);
                } else {
                    $this->display_no_orders_message();
                }
                ?>
                </div>
            </div>
            
            <!-- تب آمار فروش -->
            <div id="stats-tab" class="tab-content">
                <?php $this->display_customer_stats(); ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // مدیریت تب‌ها
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                
                var targetTab = $(this).data('tab');
                
                // حذف کلاس active از همه تب‌ها
                $('.nav-tab').removeClass('nav-tab-active');
                $('.tab-content').removeClass('active');
                
                // اضافه کردن کلاس active به تب انتخاب شده
                $(this).addClass('nav-tab-active');
                $('#orders-tab, #stats-tab').removeClass('active');
                $('#' + targetTab + '-tab').addClass('active');
            });
        });
        </script>
        
        <style>
        /* پس‌زمینه سفید برای صفحه */
        body.woocommerce_page_special-orders {
            background: #ffffff !important;
        }
        
        body.woocommerce_page_special-orders .wrap {
            background: #ffffff;
        }
        
        /* طراحی مدرن تب‌ها */
        .nav-tab-wrapper {
            margin: 30px 0 0 0;
            background: #f8f9fa;
            border-radius: 16px;
            padding: 8px;
            display: inline-flex;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: none;
        }

        .nav-tab {
            background: transparent;
            border: none;
            color: #6c757d;
            display: inline-flex;
            align-items: center;
            padding: 12px 24px;
            text-decoration: none;
            margin: 0 4px;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            font-size: 14px;
            position: relative;
            overflow: hidden;
        }

        .nav-tab::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 12px;
        }

        .nav-tab:hover {
            color: #495057;
            transform: translateY(-1px);
        }

        .nav-tab:hover::before {
            opacity: 0.1;
        }

        .nav-tab-active {
            background: #fff;
            color: #495057;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
            transform: translateY(-1px);
        }

        .nav-tab-active::before {
            opacity: 0;
        }

        .tab-content {
            display: none;
            margin-top: 30px;
            animation: fadeIn 0.3s ease-in-out;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .special-orders-container {
            margin-top: 20px;
        }
        
        /* آمار کلی */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin: 40px 0;
        }
        
        .stats-card {
            background: #ffffff;
            border: 1px solid #eeeef1;
            border-radius: 12px;
            padding: 32px 24px;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stats-card:hover {
            border-color: #d1d5db;
        }
        
        .stats-icon {
            font-size: 40px;
            margin-left: 20px;
            opacity: 0.9;
        }
        
        .stats-content h3 {
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: 700;
            color: #2d3748;
            line-height: 1.2;
        }
        
        .stats-content p {
            margin: 0;
            color: #718096;
            font-size: 15px;
            font-weight: 500;
        }
        
        /* بخش مشتریان */
        .customers-section {
            margin-top: 30px;
        }
        
        .customers-section h2 {
            margin: 0 0 24px 0;
            color: #2d3748;
            font-size: 24px;
            font-weight: 700;
            padding-bottom: 16px;
        }
        
        /* استایل‌های خاص برای جدول فروشندگان */
        .vendor-name {
            font-weight: 600;
            color: #2d3748;
        }
        
        .count-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .total-sales {
            font-weight: 600;
            color: #2d3748;
        }
        
        .last-order {
            color: #6c757d;
        }
        
        .vendor-actions {
            gap: 8px;
            flex-wrap: wrap;
        }
        
        
        
        /* استایل‌های مخصوص */
        .order-count {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
        }
        
        .total-amount {
            color: #2e7d32;
            font-weight: 600;
        }
        
        .avg-amount {
            color: #f57c00;
            font-weight: 500;
        }
        
        .last-order {
            color: #6c757d;
            font-size: 13px;
        }
        
        /* دکمه‌ها */
        .view-details {
            background: #3498db;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .view-details:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }
        
        /* Modal */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .modal-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 18px;
            font-weight: 600;
        }
        
        .close {
            color: #6c757d;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        
        .close:hover {
            color: #495057;
        }
        
        .modal-body {
            padding: 25px;
            max-height: 60vh;
            overflow-y: auto;
        }
        
        /* جزئیات سفارشات مشتری */
        .customer-orders-details h4 {
            margin: 0 0 20px 0;
            color: #2c3e50;
            font-size: 16px;
            font-weight: 600;
        }
        
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .order-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            background: #f8f9fa;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .order-number {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .order-date {
            color: #6c757d;
            font-size: 13px;
        }
        
        .order-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-special-payment {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status-processing {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .status-completed {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .order-products {
            margin: 10px 0;
        }
        
        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .product-name {
            flex: 1;
            font-weight: 500;
        }
        
        .product-quantity {
            color: #6c757d;
            font-size: 13px;
            margin: 0 10px;
        }
        
        .product-total {
            font-weight: 600;
            color: #2e7d32;
        }
        
        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e9ecef;
        }
        
        .order-total {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        
        /* پیام عدم داده */
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .no-data p {
            margin: 0;
            font-size: 16px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .stats-overview {
                grid-template-columns: 1fr;
            }
            
            .stats-card {
                padding: 20px;
            }
            
            .stats-icon {
                font-size: 24px;
                margin-left: 10px;
            }
            
            .stats-content h3 {
                font-size: 24px;
            }
            
            
            .modal-content {
                width: 95%;
                margin: 20px;
            }
            
            .modal-header,
            .modal-body {
                padding: 15px;
            }
        }
        
        .order-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-special-payment {
            background: #e1f5fe;
            color: #0277bd;
            border: 1px solid #81d4fa;
        }
        
        .status-changed {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-processing {
            background: #cce5ff;
            color: #004085;
            border: 1px solid #99d3ff;
        }
        
        .status-pending {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .wp-list-table th,
        .wp-list-table td {
            vertical-align: top;
            padding: 12px 8px;
        }
        
        .wp-list-table th {
            background: #f1f1f1;
            font-weight: bold;
        }
        
        .wp-list-table tbody tr:hover {
            background: #f9f9f9;
        }
        
        
        .order-actions {
            display: flex;
            gap: 5px;
        }
        
        .order-actions .button {
            font-size: 11px;
            padding: 2px 8px;
            height: auto;
            line-height: 1.4;
        }
        </style>
        <?php
    }
    
    /**
     * دریافت سفارش‌های خاص
     */
    private function get_special_orders() {
        // سفارش‌های جدید با meta key
        $args_new = array(
            'post_type' => 'shop_order',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_is_manual_order',
                    'value' => 'yes',
                    'compare' => '='
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $new_orders = get_posts($args_new);
        
        // سفارش‌های قدیمی با وضعیت "پرداخت خاص"
        $args_old = array(
            'post_type' => 'shop_order',
            'post_status' => 'wc-special-payment',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $old_orders = get_posts($args_old);
        
        // سفارش‌هایی که واقعاً با فرم دستی ثبت شده‌اند (بر اساس یادداشت یا meta key خاص)
        $args_manual = array(
            'post_type' => 'shop_order',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_manual_order_vendor_id',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => '_manual_order_date',
                    'compare' => 'EXISTS'
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $manual_orders = get_posts($args_manual);
        
        // ترکیب همه
        $all_orders = array_merge($new_orders, $old_orders, $manual_orders);
        
        // حذف تکراری‌ها
        $unique_orders = array();
        $seen_ids = array();
        
        foreach ($all_orders as $order) {
            if (!in_array($order->ID, $seen_ids)) {
                $unique_orders[] = $order;
                $seen_ids[] = $order->ID;
            }
        }
        
        return $unique_orders;
    }
    
    /**
     * نمایش جدول سفارش‌ها
     */
    private function display_orders_table($orders) {
        // آمار کلی
        $total_orders = count($orders);
        $total_amount = 0;
        $total_items = 0;
        
        foreach ($orders as $order_post) {
            $order = wc_get_order($order_post->ID);
            $total_amount += $order->get_total();
            $total_items += $order->get_item_count();
        }
        ?>
        
        
        <!-- جدول ساده و تمیز -->
        <div class="simple-orders-table">
            <div class="table-header">
                <div class="header-cell">شماره سفارش</div>
                <div class="header-cell">تاریخ</div>
                <div class="header-cell">وضعیت</div>
                <div class="header-cell">مجموع</div>
                <div class="header-cell">مشاهده سفارش</div>
            </div>
            
            <?php foreach ($orders as $order_post): ?>
                <?php $order = wc_get_order($order_post->ID); ?>
                <div class="table-row">
                    <div class="table-cell order-number">
                        <strong>#<?php echo $order->get_order_number(); ?></strong>
                    </div>
                    
                    <div class="table-cell order-date">
                        <?php echo $order->get_date_created()->format('Y/m/d H:i'); ?>
                    </div>
                    
                    <div class="table-cell order-status-cell">
                        <?php
                        $order_status = $order->get_status();
                        $status_label = wc_get_order_status_name($order_status);
                        $status_class = 'status-' . $order_status;
                        
                        // اگر وضعیت اصلی "پرداخت خاص" نیست، نشان دهنده که تغییر کرده
                        if ($order_status !== 'special-payment') {
                            $status_class .= ' status-changed';
                            $status_label .= ' (تغییر یافته)';
                        }
                        ?>
                        <div class="status-badge <?php echo $status_class; ?>">
                            <span class="status-icon">✓</span>
                            <span class="status-text"><?php echo $status_label; ?></span>
                        </div>
                        <?php if ($order_status !== 'special-payment'): ?>
                            <div class="original-status-text">اصل: پرداخت خاص</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="table-cell order-total">
                        <strong><?php echo wc_price($order->get_total()); ?></strong>
                    </div>
                    
                    <div class="table-cell order-actions">
                        <a href="<?php echo admin_url('post.php?post=' . $order->get_id() . '&action=edit'); ?>" 
                           class="view-btn" target="_blank">
                            مشاهده
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * نمایش پیام عدم وجود سفارش
     */
    private function display_no_orders_message() {
        ?>
        <div class="notice notice-info">
            <p>هیچ سفارش خاصی یافت نشد. سفارش‌های دستی در اینجا نمایش داده می‌شوند.</p>
        </div>
        <?php
    }
    
    /**
     * نمایش آمار مشتریان
     */
    private function display_customer_stats() {
        // دریافت آمار کلی
        $overall_stats = $this->get_overall_stats();
        
        // دریافت آمار فروشندگان
        $customer_stats = $this->get_vendor_stats();
        
        ?>
        <div class="customer-stats-admin">
            <!-- آمار کلی -->
            <div class="stats-overview">
                <div class="stats-card">
                    <div class="stats-icon">📦</div>
                    <div class="stats-content">
                        <h3><?php echo $overall_stats['total_orders']; ?></h3>
                        <p>کل سفارشات</p>
                    </div>
                </div>
                
                <div class="stats-card">
                    <div class="stats-icon">💰</div>
                    <div class="stats-content">
                        <h3><?php echo wc_price($overall_stats['total_sales']); ?></h3>
                        <p>مجموع فروش</p>
                    </div>
                </div>
                
                <div class="stats-card">
                    <div class="stats-icon">👥</div>
                    <div class="stats-content">
                        <h3><?php echo $overall_stats['total_vendors']; ?></h3>
                        <p>فروشندگان فعال</p>
                    </div>
                </div>
                
                <div class="stats-card">
                    <div class="stats-icon">📈</div>
                    <div class="stats-content">
                        <h3><?php echo wc_price($overall_stats['average_order_value']); ?></h3>
                        <p>میانگین سفارش</p>
                    </div>
                </div>
            </div>
            
            <!-- لیست فروشندگان -->
            <div class="customers-section">
                <h2>👥 لیست فروشندگان</h2>
                
                <?php if (empty($customer_stats)): ?>
                    <div class="no-data">
                        <p>هنوز هیچ سفارش دستی ثبت نشده است.</p>
                    </div>
                <?php else: ?>
                    <!-- جدول فروشندگان -->
                    <div class="simple-orders-table">
                        <div class="table-header">
                            <div class="header-cell">نام فروشنده</div>
                            <div class="header-cell">تعداد سفارشات</div>
                            <div class="header-cell">مجموع فروش</div>
                            <div class="header-cell">آخرین سفارش</div>
                            <div class="header-cell">عملیات</div>
                        </div>
                        
                        <?php foreach ($customer_stats as $vendor): ?>
                            <div class="table-row">
                                <div class="table-cell vendor-name">
                                    <strong><?php echo esc_html($vendor['name']); ?></strong>
                                </div>
                                
                                <div class="table-cell order-count">
                                    <span class="count-badge"><?php echo $vendor['order_count']; ?></span>
                                </div>
                                
                                <div class="table-cell total-sales">
                                    <strong><?php echo wc_price($vendor['total_sales']); ?></strong>
                                </div>
                                
                                <div class="table-cell last-order">
                                    <?php echo $vendor['last_order_date']; ?>
                                </div>
                                
                                <div class="table-cell vendor-actions">
                                    <button class="view-orders" 
                                            data-vendor-id="<?php echo esc_attr($vendor['id']); ?>"
                                            data-vendor-name="<?php echo esc_attr($vendor['name']); ?>">
                                        لیست سفارشات
                                    </button>
                                    <button class="view-stats" 
                                            data-vendor-id="<?php echo esc_attr($vendor['id']); ?>"
                                            data-vendor-name="<?php echo esc_attr($vendor['name']); ?>">
                                        آمار فروش
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Modal برای نمایش جزئیات -->
        <div id="vendor-details-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modal-title">جزئیات فروشنده</h3>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <!-- تب‌های داخلی Modal -->
                    <div class="modal-tabs" id="modal-tabs" style="display: none;">
                        <button class="modal-tab active" data-tab="orders">لیست سفارشات</button>
                        <button class="modal-tab" data-tab="stats">آمار فروش</button>
                    </div>
                    
                    <!-- محتوای سفارشات -->
                    <div id="orders-content" class="modal-tab-content">
                        <div id="vendor-orders-content">
                            <!-- لیست سفارشات اینجا بارگذاری می‌شود -->
                        </div>
                    </div>
                    
                    <!-- محتوای آمار -->
                    <div id="stats-content" class="modal-tab-content" style="display: none;">
                        <div class="date-range-selector">
                            <h4>انتخاب بازه زمانی</h4>
                            <div class="date-inputs">
                                <label>از تاریخ:</label>
                                <input type="date" id="start-date" class="date-input">
                                <label>تا تاریخ:</label>
                                <input type="date" id="end-date" class="date-input">
                                <button id="load-stats" class="button button-primary">بارگذاری آمار</button>
                            </div>
                        </div>
                        <div id="vendor-stats-content">
                            <!-- آمار فروش اینجا بارگذاری می‌شود -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var currentVendorId = null;
            var currentVendorName = null;
            
            // باز کردن modal برای لیست سفارشات
            $('.view-orders').on('click', function() {
                currentVendorId = $(this).data('vendor-id');
                currentVendorName = $(this).data('vendor-name');
                
                $('#modal-title').text('لیست سفارشات - ' + currentVendorName);
                $('#modal-tabs').hide();
                $('#orders-content').show();
                $('#stats-content').hide();
                
                // پاک کردن محتوای قبلی
                $('#vendor-orders-content').html('');
                $('#vendor-stats-content').html('');
                
                loadVendorOrders(currentVendorId, currentVendorName);
                $('#vendor-details-modal').show();
            });
            
            // باز کردن modal برای آمار فروش
            $('.view-stats').on('click', function() {
                currentVendorId = $(this).data('vendor-id');
                currentVendorName = $(this).data('vendor-name');
                
                $('#modal-title').text('آمار فروش - ' + currentVendorName);
                $('#modal-tabs').hide(); // تب‌ها را مخفی کن
                $('#orders-content').hide();
                $('#stats-content').show();
                
                // پاک کردن محتوای قبلی
                $('#vendor-orders-content').html('');
                $('#vendor-stats-content').html('');
                
                // تنظیم تاریخ پیش‌فرض (30 روز گذشته)
                var today = new Date();
                var thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
                
                $('#start-date').val(thirtyDaysAgo.toISOString().split('T')[0]);
                $('#end-date').val(today.toISOString().split('T')[0]);
                
                $('#vendor-details-modal').show();
            });
            
            // مدیریت تب‌های داخلی Modal (فقط زمانی که تب‌ها نمایش داده شوند)
            $(document).on('click', '.modal-tab', function() {
                var targetTab = $(this).data('tab');
                
                $('.modal-tab').removeClass('active');
                $(this).addClass('active');
                
                $('.modal-tab-content').hide();
                $('#' + targetTab + '-content').show();
                
                if (targetTab === 'orders') {
                    loadVendorOrders(currentVendorId, currentVendorName);
                }
            });
            
            // بارگذاری آمار بر اساس تاریخ
            $('#load-stats').on('click', function() {
                var startDate = $('#start-date').val();
                var endDate = $('#end-date').val();
                
                if (!startDate || !endDate) {
                    alert('لطفاً هر دو تاریخ را انتخاب کنید');
                    return;
                }
                
                loadVendorStats(currentVendorId, currentVendorName, startDate, endDate);
            });
            
            // بستن modal
            $('.close, .modal').on('click', function(e) {
                if (e.target === this) {
                    $('#vendor-details-modal').hide();
                }
            });
            
            function loadVendorOrders(vendorId, vendorName) {
                $('#vendor-orders-content').html('<p>در حال بارگذاری...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_vendor_order_details',
                        vendor_id: vendorId,
                        vendor_name: vendorName,
                        nonce: '<?php echo wp_create_nonce('vendor_details_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#vendor-orders-content').html(response.data);
                        } else {
                            $('#vendor-orders-content').html('<p>خطا در بارگذاری سفارشات</p>');
                        }
                    },
                    error: function() {
                        $('#vendor-orders-content').html('<p>خطا در بارگذاری سفارشات</p>');
                    }
                });
            }
            
            function loadVendorStats(vendorId, vendorName, startDate, endDate) {
                $('#vendor-stats-content').html('<p>در حال بارگذاری آمار...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_vendor_stats_details',
                        vendor_id: vendorId,
                        vendor_name: vendorName,
                        start_date: startDate,
                        end_date: endDate,
                        nonce: '<?php echo wp_create_nonce('vendor_stats_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#vendor-stats-content').html(response.data);
                        } else {
                            $('#vendor-stats-content').html('<p>خطا در بارگذاری آمار</p>');
                        }
                    },
                    error: function() {
                        $('#vendor-stats-content').html('<p>خطا در بارگذاری آمار</p>');
                    }
                });
            }
        });
        </script>
        
        <style>
        /* دکمه‌های عملیات مدرن */
        .view-orders, .view-stats {
            color: #ffffff;
            font-size: 14px;
            font-weight: 500;
            line-height: 24px;
            outline: 2px solid transparent;
            outline-offset: 2px;
            height: 40px;
            padding: 0 16px;
            border-radius: 8px;
            border: none;
            transition: all 0.2s ease;
            cursor: pointer;
            margin-left: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .view-orders {
            background: #3b82f6;
        }
        
        .view-stats {
            background: #10b981;
        }
        
        .view-orders:hover {
            background: #2563eb;
        }
        
        .view-stats:hover {
            background: #059669;
        }
        
        /* تب‌های داخلی Modal مدرن */
        .modal-tabs {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 8px;
            margin-bottom: 24px;
            display: inline-flex;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .modal-tab {
            background: transparent;
            border: none;
            color: #6c757d;
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            text-decoration: none;
            margin: 0 4px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            font-size: 14px;
        }
        
        .modal-tab:hover {
            color: #495057;
            transform: translateY(-1px);
        }
        
        .modal-tab.active {
            background: #fff;
            color: #495057;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }
        
        /* انتخابگر تاریخ مدرن */
        .date-range-selector {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 24px;
            border-radius: 16px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .date-range-selector h4 {
            margin-top: 0;
            color: #2d3748;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        .date-inputs {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .date-inputs label {
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }
        
        .date-input {
            padding: 10px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .date-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        /* دکمه بارگذاری آمار */
        #load-stats {
            color: #ffffff;
            font-size: 14px;
            font-weight: 500;
            line-height: 24px;
            outline: 2px solid transparent;
            outline-offset: 2px;
            height: 40px;
            padding: 0 16px;
            border-radius: 8px;
            background: #8b5cf6;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        #load-stats:hover {
            background: #7c3aed;
        }
        
        /* استایل آمار فروش */
        .vendor-stats-details {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .vendor-stats-details h4 {
            color: #333;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 10px;
        }
        
        .date-range {
            color: #666;
            font-style: italic;
            margin-bottom: 20px;
        }
        
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-item {
            background: #fff;
            padding: 24px;
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .stat-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
        }
        
        .stat-label {
            display: block;
            font-weight: 600;
            color: #718096;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .stat-value {
            display: block;
            font-size: 24px;
            font-weight: 700;
            color: #2d3748;
            line-height: 1.2;
        }
        
        .daily-stats h5 {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 600;
        }
        
        .daily-stats-table {
            overflow-x: auto;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .daily-stats-table table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        
        .daily-stats-table th,
        .daily-stats-table td {
            padding: 16px 20px;
            text-align: center;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .daily-stats-table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: 600;
            color: #495057;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .daily-stats-table tbody tr {
            transition: all 0.2s ease;
        }
        
        .daily-stats-table tbody tr:hover {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            transform: scale(1.01);
        }
        
        /* جدول ساده و تمیز */
        .simple-orders-table {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #eeeef1;
            margin-top: 20px;
        }
        
        .table-header {
            display: grid;
            grid-template-columns: 1fr 0.8fr 1.5fr 1fr 1.5fr;
            background: #fff;
            border-bottom: 1px solid #eeeef1;
        }
        
        .header-cell {
            padding: 16px 20px;
            font-weight: 600;
            color: #495057;
            font-size: 14px;
            text-align: right;
            border-left: 1px solid #e9ecef;
        }
        
        .header-cell:first-child {
            border-left: none;
        }
        
        .table-row {
            display: grid;
            grid-template-columns: 1fr 0.8fr 1.5fr 1fr 1.5fr;
            border-bottom: 1px solid #eeeef1;
            transition: background-color 0.2s ease;
        }
        
        .table-row:hover {
            background: #f8f9fa;
        }
        
        .table-row:last-child {
            border-bottom: none;
        }
        
        .table-cell {
            padding: 16px 20px;
            text-align: right;
            border-left: 1px solid #eeeef1;
            display: flex;
            align-items: center;
            font-size: 14px;
            color: #495057;
        }
        
        .table-cell:first-child {
            border-left: none;
        }
        
        /* شماره سفارش */
        .order-number {
            font-weight: 600;
            color: #2d3748;
        }
        
        /* تاریخ */
        .order-date {
            color: #6c757d;
        }
        
        /* وضعیت */
        .order-status-cell {
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }
        
        .status-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            width: fit-content;
        }
        
        .status-icon {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #3b82f6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }
        
        .status-text {
            color: #2d3748;
        }
        
        .status-badge.status-special-payment {
            background: #dbeafe;
        }
        
        .status-badge.status-processing {
            background: #fef3c7;
        }
        
        .status-badge.status-processing .status-icon {
            background: #f59e0b;
        }
        
        .status-badge.status-completed {
            background: #d1fae5;
        }
        
        .status-badge.status-completed .status-icon {
            background: #10b981;
        }
        
        .status-badge.status-changed {
            background: #fee2e2;
        }
        
        .status-badge.status-changed .status-icon {
            background: #ef4444;
        }
        
        .original-status-text {
            font-size: 12px;
            color: #9ca3af;
            font-style: italic;
        }
        
        /* مجموع */
        .order-total {
            font-weight: 600;
            color: #2d3748;
        }
        
        /* دکمه مشاهده */
        .view-btn {
            color: #ffffff;
            font-size: 14px;
            font-weight: 500;
            line-height: 24px;
            outline: 2px solid transparent;
            outline-offset: 2px;
            height: 40px;
            padding: 0 16px;
            border-radius: 8px;
            background: #6366f1;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .view-btn:hover {
            background: #4f46e5;
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .table-header,
            .table-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .header-cell,
            .table-cell {
                border-left: none;
                border-bottom: 1px solid #f1f3f4;
                padding: 12px 16px;
            }
            
            .header-cell:last-child,
            .table-cell:last-child {
                border-bottom: none;
            }
            
            .table-row {
                border-bottom: 2px solid #e9ecef;
            }
        }
        </style>
        <?php
    }
    
    /**
     * دریافت آمار کلی
     */
    private function get_overall_stats() {
        // دریافت همه سفارش‌های دستی
        $orders = $this->get_special_orders();
        
        $total_orders = count($orders);
        $total_sales = 0;
        $vendors = array();
        
        foreach ($orders as $order_post) {
            $order = wc_get_order($order_post->ID);
            if ($order) {
                $total_sales += $order->get_total();
                
                // شناسایی فروشنده منحصر به فرد
                $vendor_id = $order->get_customer_id();
                $vendors[$vendor_id] = true;
            }
        }
        
        $total_vendors = count($vendors);
        $average_order_value = $total_orders > 0 ? $total_sales / $total_orders : 0;
        
        return array(
            'total_orders' => $total_orders,
            'total_sales' => $total_sales,
            'total_vendors' => $total_vendors,
            'average_order_value' => $average_order_value
        );
    }
    
    /**
     * دریافت آمار فروشندگان
     */
    private function get_vendor_stats() {
        $orders = $this->get_special_orders();
        $vendors = array();
        
        foreach ($orders as $order_post) {
            $order = wc_get_order($order_post->ID);
            if ($order) {
                $vendor_id = $order->get_customer_id(); // customer_user همان فروشنده است
                
                if (!isset($vendors[$vendor_id])) {
                    $vendor_user = get_userdata($vendor_id);
                    if ($vendor_user) {
                        $vendors[$vendor_id] = array(
                            'id' => $vendor_id,
                            'name' => $vendor_user->display_name,
                            'email' => $vendor_user->user_email,
                            'order_count' => 0,
                            'total_sales' => 0,
                            'last_order_date' => '',
                            'orders' => array()
                        );
                    }
                }
                
                if (isset($vendors[$vendor_id])) {
                    $vendors[$vendor_id]['order_count']++;
                    $vendors[$vendor_id]['total_sales'] += $order->get_total();
                    $vendors[$vendor_id]['orders'][] = $order;
                    
                    // آخرین سفارش
                    $order_date = $order->get_date_created();
                    if (empty($vendors[$vendor_id]['last_order_date']) || 
                        $order_date > $vendors[$vendor_id]['last_order_date']) {
                        $vendors[$vendor_id]['last_order_date'] = $order_date->format('Y/m/d');
                    }
                }
            }
        }
        
        // محاسبه میانگین سفارش
        foreach ($vendors as &$vendor) {
            $vendor['average_order'] = $vendor['order_count'] > 0 ? 
                $vendor['total_sales'] / $vendor['order_count'] : 0;
        }
        
        // مرتب‌سازی بر اساس مجموع فروش (نزولی)
        uasort($vendors, function($a, $b) {
            return $b['total_sales'] - $a['total_sales'];
        });
        
        return $vendors;
    }
    
    /**
     * دریافت جزئیات سفارشات فروشنده (AJAX)
     */
    public function get_vendor_order_details() {
        // بررسی nonce
        if (!wp_verify_nonce($_POST['nonce'], 'vendor_details_nonce')) {
            wp_send_json_error('خطای امنیتی');
        }
        
        // بررسی دسترسی
        $access_control = new Manual_Order_Access_Control();
        if (!$access_control->can_view_special_orders_admin()) {
            wp_send_json_error('شما دسترسی لازم ندارید');
        }
        
        $vendor_id = intval($_POST['vendor_id']);
        $vendor_name = sanitize_text_field($_POST['vendor_name']);
        
        // دریافت سفارش‌های فروشنده
        $orders = $this->get_vendor_orders($vendor_id);
        
        if (empty($orders)) {
            wp_send_json_error('هیچ سفارشی یافت نشد');
        }
        
        $html = '<div class="customer-orders-details">';
        $html .= '<h4>سفارشات ' . esc_html($vendor_name) . '</h4>';
        $html .= '<div class="orders-list">';
        
        foreach ($orders as $order) {
            $html .= '<div class="order-item">';
            $html .= '<div class="order-header">';
            $html .= '<span class="order-number">سفارش #' . $order->get_order_number() . '</span>';
            $html .= '<span class="order-date">' . $order->get_date_created()->format('Y/m/d H:i') . '</span>';
            $html .= '<span class="order-status status-' . $order->get_status() . '">' . wc_get_order_status_name($order->get_status()) . '</span>';
            $html .= '</div>';
            
            $html .= '<div class="order-products">';
            foreach ($order->get_items() as $item) {
                $html .= '<div class="product-item">';
                $html .= '<span class="product-name">' . $item->get_name() . '</span>';
                $html .= '<span class="product-quantity">تعداد: ' . $item->get_quantity() . '</span>';
                $html .= '<span class="product-total">' . wc_price($item->get_total()) . '</span>';
                $html .= '</div>';
            }
            $html .= '</div>';
            
            $html .= '<div class="order-footer">';
            $html .= '<span class="order-total">مجموع: ' . wc_price($order->get_total()) . '</span>';
            $html .= '<a href="' . admin_url('post.php?post=' . $order->get_id() . '&action=edit') . '" class="button button-small" target="_blank">مشاهده سفارش</a>';
            $html .= '</div>';
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        wp_send_json_success($html);
    }
    
    /**
     * دریافت سفارش‌های یک فروشنده خاص
     */
    private function get_vendor_orders($vendor_id) {
        $args = array(
            'post_type' => 'shop_order',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_is_manual_order',
                    'value' => 'yes',
                    'compare' => '='
                ),
                array(
                    'key' => '_customer_user',
                    'value' => $vendor_id,
                    'compare' => '='
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $order_posts = get_posts($args);
        $orders = array();
        
        foreach ($order_posts as $order_post) {
            $order = wc_get_order($order_post->ID);
            if ($order) {
                $orders[] = $order;
            }
        }
        
        return $orders;
    }
    
    /**
     * دریافت آمار فروشنده در بازه زمانی مشخص (AJAX)
     */
    public function get_vendor_stats_details() {
        // بررسی nonce
        if (!wp_verify_nonce($_POST['nonce'], 'vendor_stats_nonce')) {
            wp_send_json_error('خطای امنیتی');
        }
        
        // بررسی دسترسی
        $access_control = new Manual_Order_Access_Control();
        if (!$access_control->can_view_special_orders_admin()) {
            wp_send_json_error('شما دسترسی لازم ندارید');
        }
        
        $vendor_id = intval($_POST['vendor_id']);
        $vendor_name = sanitize_text_field($_POST['vendor_name']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        // دریافت سفارش‌های فروشنده در بازه زمانی
        $orders = $this->get_vendor_orders_by_date($vendor_id, $start_date, $end_date);
        
        if (empty($orders)) {
            wp_send_json_error('هیچ سفارشی در این بازه زمانی یافت نشد');
        }
        
        // محاسبه آمار
        $total_orders = count($orders);
        $total_sales = 0;
        $total_products = 0;
        $daily_stats = array();
        
        foreach ($orders as $order) {
            $total_sales += $order->get_total();
            $total_products += $order->get_item_count();
            
            $order_date = $order->get_date_created()->format('Y-m-d');
            if (!isset($daily_stats[$order_date])) {
                $daily_stats[$order_date] = array(
                    'orders' => 0,
                    'sales' => 0,
                    'products' => 0
                );
            }
            
            $daily_stats[$order_date]['orders']++;
            $daily_stats[$order_date]['sales'] += $order->get_total();
            $daily_stats[$order_date]['products'] += $order->get_item_count();
        }
        
        $average_order_value = $total_orders > 0 ? $total_sales / $total_orders : 0;
        $average_products_per_order = $total_orders > 0 ? $total_products / $total_orders : 0;
        
        // مرتب‌سازی آمار روزانه
        ksort($daily_stats);
        
        $html = '<div class="vendor-stats-details">';
        $html .= '<h4>آمار فروش ' . esc_html($vendor_name) . '</h4>';
        $html .= '<p class="date-range">بازه زمانی: ' . $start_date . ' تا ' . $end_date . '</p>';
        
        // آمار کلی
        $html .= '<div class="stats-summary">';
        $html .= '<div class="stat-item">';
        $html .= '<span class="stat-label">تعداد سفارشات:</span>';
        $html .= '<span class="stat-value">' . $total_orders . '</span>';
        $html .= '</div>';
        $html .= '<div class="stat-item">';
        $html .= '<span class="stat-label">مجموع فروش:</span>';
        $html .= '<span class="stat-value">' . wc_price($total_sales) . '</span>';
        $html .= '</div>';
        $html .= '<div class="stat-item">';
        $html .= '<span class="stat-label">میانگین سفارش:</span>';
        $html .= '<span class="stat-value">' . wc_price($average_order_value) . '</span>';
        $html .= '</div>';
        $html .= '<div class="stat-item">';
        $html .= '<span class="stat-label">تعداد محصولات:</span>';
        $html .= '<span class="stat-value">' . $total_products . '</span>';
        $html .= '</div>';
        $html .= '<div class="stat-item">';
        $html .= '<span class="stat-label">میانگین محصولات:</span>';
        $html .= '<span class="stat-value">' . number_format($average_products_per_order, 1) . '</span>';
        $html .= '</div>';
        $html .= '</div>';
        
        // آمار روزانه
        $html .= '<div class="daily-stats">';
        $html .= '<h5>آمار روزانه</h5>';
        $html .= '<div class="daily-stats-table">';
        $html .= '<table class="wp-list-table widefat fixed striped">';
        $html .= '<thead><tr><th>تاریخ</th><th>تعداد سفارشات</th><th>مجموع فروش</th><th>تعداد محصولات</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach ($daily_stats as $date => $stats) {
            $html .= '<tr>';
            $html .= '<td>' . $date . '</td>';
            $html .= '<td>' . $stats['orders'] . '</td>';
            $html .= '<td>' . wc_price($stats['sales']) . '</td>';
            $html .= '<td>' . $stats['products'] . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        wp_send_json_success($html);
    }
    
    /**
     * دریافت سفارش‌های فروشنده در بازه زمانی مشخص
     */
    private function get_vendor_orders_by_date($vendor_id, $start_date, $end_date) {
        $args = array(
            'post_type' => 'shop_order',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_is_manual_order',
                    'value' => 'yes',
                    'compare' => '='
                ),
                array(
                    'key' => '_customer_user',
                    'value' => $vendor_id,
                    'compare' => '='
                )
            ),
            'date_query' => array(
                array(
                    'after' => $start_date,
                    'before' => $end_date . ' 23:59:59',
                    'inclusive' => true
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $order_posts = get_posts($args);
        $orders = array();
        
        foreach ($order_posts as $order_post) {
            $order = wc_get_order($order_post->ID);
            if ($order) {
                $orders[] = $order;
            }
        }
        
        return $orders;
    }
}

// راه‌اندازی کلاس
new Special_Orders_Admin();
