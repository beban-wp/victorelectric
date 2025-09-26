<?php
/**
 * Beban Asset Manager
 * 
 * Centralized asset management for CSS and JS files
 * Handles conditional loading based on pages, post types, and other conditions
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Beban_Asset_Manager {
    
    /**
     * Asset configurations
     * 
     * @var array
     */
    private $assets = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->define_assets();
        $this->init_hooks();
    }
    
    /**
     * Define all assets and their conditions
     */
    private function define_assets() {
        $this->assets = array(
            // Installment Admin CSS
            'installment-admin' => array(
                'css' => array(
                    'path' => '/assets/css/admin/installment-admin.css',
                    'dependencies' => array(),
                    'version' => '1.0.0'
                ),
                'conditions' => array(
                    'admin' => true,
                    'pages' => array('edit.php', 'post.php', 'woocommerce_page_wc-orders'),
                    'post_types' => array('shop_order')
                )
            ),
            
            // Orders AJAX moved to orders-history.php for better control
            
            // Installment Frontend JS
            'installment-frontend' => array(
                'js' => array(
                    'path' => '/assets/js/frontend/installment-frontend.js',
                    'dependencies' => array(),
                    'version' => '1.0.0',
                    'in_footer' => true
                ),
                'conditions' => array(
                    'frontend' => true,
                    'pages' => array('my-account')
                )
            ),
            
            // Installment Admin JS
            'installment-admin' => array(
                'js' => array(
                    'path' => '/assets/js/admin/installment-admin.js',
                    'dependencies' => array('jquery'),
                    'version' => '1.0.0',
                    'in_footer' => true
                ),
                'conditions' => array(
                    'admin' => true,
                    'pages' => array('edit.php', 'post.php', 'woocommerce_page_wc-orders'),
                    'post_types' => array('shop_order')
                )
            )
        );
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    /**
     * Enqueue admin assets
     * 
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets($hook) {
        foreach ($this->assets as $handle => $config) {
            if (isset($config['conditions']['admin']) && $config['conditions']['admin']) {
                if ($this->should_enqueue_admin($config['conditions'], $hook)) {
                    $this->enqueue_asset($handle, $config, 'admin');
                }
            }
        }
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        foreach ($this->assets as $handle => $config) {
            if (isset($config['conditions']['frontend']) && $config['conditions']['frontend']) {
                if ($this->should_enqueue_frontend($config['conditions'])) {
                    $this->enqueue_asset($handle, $config, 'frontend');
                }
            }
        }
    }
    
    /**
     * Check if admin asset should be enqueued
     * 
     * @param array $conditions Asset conditions
     * @param string $hook Current admin page hook
     * @return bool
     */
    private function should_enqueue_admin($conditions, $hook) {
        // Check if current hook matches required pages
        if (isset($conditions['pages'])) {
            if (!in_array($hook, $conditions['pages'])) {
                return false;
            }
        }
        
        // Check post type conditions
        if (isset($conditions['post_types'])) {
            $current_post_type = $this->get_current_post_type();
            if (!$current_post_type || !in_array($current_post_type, $conditions['post_types'])) {
                return false;
            }
        }
        
        // Check for WooCommerce orders page (new interface)
        if ($hook === 'woocommerce_page_wc-orders') {
            return true;
        }
        
        return true;
    }
    
    /**
     * Check if frontend asset should be enqueued
     * 
     * @param array $conditions Asset conditions
     * @return bool
     */
    private function should_enqueue_frontend($conditions) {
        // Check page conditions
        if (isset($conditions['pages'])) {
            $current_page = $this->get_current_page();
            if (!$current_page || !in_array($current_page, $conditions['pages'])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get current post type in admin
     * 
     * @return string|false
     */
    private function get_current_post_type() {
        global $post, $typenow, $current_screen;
        
        if ($post && $post->post_type) {
            return $post->post_type;
        }
        
        if ($typenow) {
            return $typenow;
        }
        
        if ($current_screen && $current_screen->post_type) {
            return $current_screen->post_type;
        }
        
        if (isset($_GET['post_type'])) {
            return sanitize_text_field($_GET['post_type']);
        }
        
        return false;
    }
    
    /**
     * Get current page in frontend
     * 
     * @return string|false
     */
    private function get_current_page() {
        if (is_shop()) {
            return 'shop';
        }
        
        if (is_product()) {
            return 'product';
        }
        
        if (is_account_page()) {
            return 'my-account';
        }
        
        return false;
    }
    
    /**
     * Enqueue individual asset
     * 
     * @param string $handle Asset handle
     * @param array $config Asset configuration
     * @param string $context Admin or frontend
     */
    private function enqueue_asset($handle, $config, $context) {
        $base_url = get_stylesheet_directory_uri();
        
        // Enqueue CSS
        if (isset($config['css'])) {
            wp_enqueue_style(
                'beban-' . $handle,
                $base_url . $config['css']['path'],
                $config['css']['dependencies'],
                $config['css']['version']
            );
        }
        
        // Enqueue JS
        if (isset($config['js'])) {
            wp_enqueue_script(
                'beban-' . $handle,
                $base_url . $config['js']['path'],
                $config['js']['dependencies'],
                $config['js']['version'],
                isset($config['js']['in_footer']) ? $config['js']['in_footer'] : false
            );
        }
    }
    
    /**
     * Add new asset configuration
     * 
     * @param string $handle Asset handle
     * @param array $config Asset configuration
     */
    public function add_asset($handle, $config) {
        $this->assets[$handle] = $config;
    }
    
    /**
     * Remove asset configuration
     * 
     * @param string $handle Asset handle
     */
    public function remove_asset($handle) {
        if (isset($this->assets[$handle])) {
            unset($this->assets[$handle]);
        }
    }
    
    /**
     * Get all asset configurations
     * 
     * @return array
     */
    public function get_assets() {
        return $this->assets;
    }
}

// Initialize the asset manager
function beban_init_asset_manager() {
    new Beban_Asset_Manager();
}
add_action('init', 'beban_init_asset_manager');
