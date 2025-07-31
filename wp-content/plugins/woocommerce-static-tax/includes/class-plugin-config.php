<?php
/**
 * Plugin Configuration Class
 * 
 * Handles plugin settings and configuration
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Static_Tax_Config {
    
    private $option_name = 'wc_static_tax_settings';
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Plugin activation/deactivation hooks
        register_activation_hook(dirname(dirname(__FILE__)) . '/woocommerce-static-tax.php', array($this, 'plugin_activation'));
        register_deactivation_hook(dirname(dirname(__FILE__)) . '/woocommerce-static-tax.php', array($this, 'plugin_deactivation'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Static Tax Settings',
            'Static Tax',
            'manage_woocommerce',
            'wc-static-tax-settings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('wc_static_tax_settings', $this->option_name);
        
        add_settings_section(
            'wc_static_tax_main',
            'Static Tax Configuration',
            array($this, 'settings_section_callback'),
            'wc_static_tax_settings'
        );
        
        add_settings_field(
            'tax_amount',
            'Tax Amount ($)',
            array($this, 'tax_amount_field'),
            'wc_static_tax_settings',
            'wc_static_tax_main'
        );
        
        add_settings_field(
            'tax_label',
            'Tax Label',
            array($this, 'tax_label_field'),
            'wc_static_tax_settings',
            'wc_static_tax_main'
        );
        
        add_settings_field(
            'enabled',
            'Enable Static Tax',
            array($this, 'enabled_field'),
            'wc_static_tax_settings',
            'wc_static_tax_main'
        );
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WooCommerce Static Tax Settings</h1>
            
            <div class="notice notice-info">
                <p><strong>How it works:</strong> This plugin adds a fixed tax amount to all WooCommerce orders. The tax will appear in the cart, checkout, order confirmation, admin orders, and email notifications.</p>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('wc_static_tax_settings');
                do_settings_sections('wc_static_tax_settings');
                submit_button();
                ?>
            </form>
            
            <div class="card">
                <h2>Plugin Information</h2>
                <p><strong>Current Settings:</strong></p>
                <ul>
                    <li>Tax Amount: $<?php echo esc_html($this->get_tax_amount()); ?></li>
                    <li>Tax Label: <?php echo esc_html($this->get_tax_label()); ?></li>
                    <li>Status: <?php echo $this->is_enabled() ? 'Enabled' : 'Disabled'; ?></li>
                </ul>
                
                <p><strong>Where the tax appears:</strong></p>
                <ul>
                    <li>✓ Shopping Cart</li>
                    <li>✓ Checkout Page</li>
                    <li>✓ Order Thank You Page</li>
                    <li>✓ Admin Order Details</li>
                    <li>✓ Customer Email Notifications</li>
                    <li>✓ Admin Email Notifications</li>
                    <li>✓ Order Reports</li>
                </ul>
            </div>
            
            <style>
                .card {
                    background: #fff;
                    border: 1px solid #c3c4c7;
                    border-radius: 4px;
                    padding: 20px;
                    margin-top: 20px;
                }
                .card h2 {
                    margin-top: 0;
                }
                .card ul {
                    margin-left: 20px;
                }
            </style>
        </div>
        <?php
    }
    
    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>Configure the static tax amount and label that will be applied to all orders.</p>';
    }
    
    /**
     * Tax amount field
     */
    public function tax_amount_field() {
        $settings = get_option($this->option_name, array());
        $value = isset($settings['tax_amount']) ? $settings['tax_amount'] : 25;
        echo '<input type="number" step="0.01" min="0" name="' . $this->option_name . '[tax_amount]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Enter the fixed tax amount in dollars (e.g., 25 for $25.00)</p>';
    }
    
    /**
     * Tax label field
     */
    public function tax_label_field() {
        $settings = get_option($this->option_name, array());
        $value = isset($settings['tax_label']) ? $settings['tax_label'] : 'Processing Fee';
        echo '<input type="text" name="' . $this->option_name . '[tax_label]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">The label that will appear for this tax (e.g., "Processing Fee", "Service Charge", etc.)</p>';
    }
    
    /**
     * Enabled field
     */
    public function enabled_field() {
        $settings = get_option($this->option_name, array());
        $value = isset($settings['enabled']) ? $settings['enabled'] : 1;
        echo '<label><input type="checkbox" name="' . $this->option_name . '[enabled]" value="1" ' . checked($value, 1, false) . ' /> Enable static tax</label>';
        echo '<p class="description">Uncheck to disable the static tax without deactivating the plugin</p>';
    }
    
    /**
     * Get tax amount
     */
    public function get_tax_amount() {
        $settings = get_option($this->option_name, array());
        return isset($settings['tax_amount']) ? floatval($settings['tax_amount']) : 25.00;
    }
    
    /**
     * Get tax label
     */
    public function get_tax_label() {
        $settings = get_option($this->option_name, array());
        return isset($settings['tax_label']) ? $settings['tax_label'] : 'Processing Fee';
    }
    
    /**
     * Check if enabled
     */
    public function is_enabled() {
        $settings = get_option($this->option_name, array());
        return isset($settings['enabled']) ? (bool) $settings['enabled'] : true;
    }
    
    /**
     * Plugin activation
     */
    public function plugin_activation() {
        // Set default options
        $default_settings = array(
            'tax_amount' => 25,
            'tax_label' => 'Processing Fee',
            'enabled' => 1
        );
        
        add_option($this->option_name, $default_settings);
        
        // Clear any caches
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function plugin_deactivation() {
        // Clean up any temporary data, but keep settings
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
    
    /**
     * Get instance (singleton pattern)
     */
    public static function get_instance() {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }
        return $instance;
    }
}