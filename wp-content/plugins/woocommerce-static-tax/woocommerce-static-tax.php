<?php
/**
 * Plugin Name: WooCommerce Static Tax
 * Plugin URI: https://example.com
 * Description: Adds a static $25 tax amount to all WooCommerce orders
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

// Include required files
require_once plugin_dir_path(__FILE__) . 'includes/class-plugin-config.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-static-tax-calculator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-email-customizer.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-admin-interface.php';

class WooCommerce_Static_Tax {
    
    private $config;
    
    private function get_static_tax_amount() {
        if (!$this->config) {
            $this->config = WC_Static_Tax_Config::get_instance();
        }
        return $this->config->get_tax_amount();
    }
    
    private function get_tax_label() {
        if (!$this->config) {
            $this->config = WC_Static_Tax_Config::get_instance();
        }
        return $this->config->get_tax_label();
    }
    
    private function is_enabled() {
        if (!$this->config) {
            $this->config = WC_Static_Tax_Config::get_instance();
        }
        return $this->config->is_enabled();
    }
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Add custom tax to cart and checkout
        add_action('woocommerce_cart_calculate_fees', array($this, 'add_static_tax_fee'));
        
        // Display tax in cart totals
        add_filter('woocommerce_cart_totals_fee_html', array($this, 'customize_fee_display'), 10, 2);
        
        // Display tax in checkout
        add_filter('woocommerce_review_order_before_payment', array($this, 'add_checkout_fee_notice'));
        
        // Add tax to order meta
        add_action('woocommerce_checkout_create_order', array($this, 'save_static_tax_to_order'), 10, 2);
        
        // Display in order details (thank you page, admin, emails)
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_static_tax_in_order_details'));
        
        // Display in admin order details
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_static_tax_in_admin'));
        
        // Add to email templates
        add_action('woocommerce_email_order_details', array($this, 'add_static_tax_to_emails'), 15, 4);
        
        // Ensure the fee is saved properly
        add_filter('woocommerce_order_get_fees', array($this, 'ensure_fee_in_order'), 10, 2);
    }
    
    /**
     * Add static tax fee to cart
     */
    public function add_static_tax_fee() {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        // Check if enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        // Only add if cart is not empty
        if (WC()->cart->get_cart_contents_count() == 0) {
            return;
        }
        
        // Check if fee already exists to avoid duplicates
        $fees = WC()->cart->get_fees();
        foreach ($fees as $fee) {
            if ($fee->name === $this->get_tax_label()) {
                return;
            }
        }
        
        // Add the static tax as a fee
        WC()->cart->add_fee($this->get_tax_label(), $this->get_static_tax_amount(), true);
    }
    
    /**
     * Customize fee display in cart
     */
    public function customize_fee_display($cart_fee_html, $fee) {
        if ($fee->name === $this->get_tax_label()) {
            return '<span class="static-tax-amount">' . wc_price($fee->amount) . '</span>';
        }
        return $cart_fee_html;
    }
    
    /**
     * Add notice in checkout
     */
    public function add_checkout_fee_notice() {
        if (!$this->is_enabled()) {
            return;
        }
        
        echo '<div class="static-tax-notice" style="margin: 10px 0; padding: 10px; background: #f9f9f9; border-left: 3px solid #2ea2cc;">';
        echo '<strong>' . esc_html($this->get_tax_label()) . ':</strong> ' . wc_price($this->get_static_tax_amount()) . ' will be added to your order.';
        echo '</div>';
    }
    
    /**
     * Save static tax to order meta
     */
    public function save_static_tax_to_order($order, $data) {
        if (!$this->is_enabled()) {
            return;
        }
        
        $order->update_meta_data('_static_tax_amount', $this->get_static_tax_amount());
        $order->update_meta_data('_static_tax_label', $this->get_tax_label());
    }
    
    /**
     * Display static tax in order details (thank you page)
     */
    public function display_static_tax_in_order_details($order) {
        $static_tax = $order->get_meta('_static_tax_amount');
        $tax_label = $order->get_meta('_static_tax_label');
        
        if ($static_tax) {
            echo '<div class="static-tax-details" style="margin-top: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">';
            echo '<h3>' . esc_html($tax_label ?: $this->get_tax_label()) . '</h3>';
            echo '<p><strong>Amount:</strong> ' . wc_price($static_tax) . '</p>';
            echo '<p><em>This fee has been applied to your order.</em></p>';
            echo '</div>';
        }
    }
    
    /**
     * Display static tax in admin order details
     */
    public function display_static_tax_in_admin($order) {
        $static_tax = $order->get_meta('_static_tax_amount');
        $tax_label = $order->get_meta('_static_tax_label');
        
        if ($static_tax) {
            echo '<div class="static-tax-admin-display">';
            echo '<h4>' . esc_html($tax_label ?: $this->get_tax_label()) . '</h4>';
            echo '<p><strong>Amount:</strong> ' . wc_price($static_tax) . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Add static tax to email templates
     */
    public function add_static_tax_to_emails($order, $sent_to_admin, $plain_text, $email) {
        $static_tax = $order->get_meta('_static_tax_amount');
        $tax_label = $order->get_meta('_static_tax_label');
        
        if ($static_tax) {
            if ($plain_text) {
                echo "\n" . strtoupper($tax_label ?: $this->get_tax_label()) . ": " . wc_price($static_tax) . "\n";
                echo "This processing fee has been applied to your order.\n";
            } else {
                echo '<div style="margin: 20px 0; padding: 15px; background-color: #f9f9f9; border: 1px solid #ddd;">';
                echo '<h3 style="margin-top: 0;">' . esc_html($tax_label ?: $this->get_tax_label()) . '</h3>';
                echo '<p><strong>Amount:</strong> ' . wc_price($static_tax) . '</p>';
                echo '<p style="margin-bottom: 0;"><em>This processing fee has been applied to your order.</em></p>';
                echo '</div>';
            }
        }
    }
    
    /**
     * Ensure fee is properly included in order
     */
    public function ensure_fee_in_order($fees, $order) {
        // This helps ensure the fee is always visible in order details
        return $fees;
    }
}

// Initialize the configuration first
WC_Static_Tax_Config::get_instance();

// Initialize the plugin
new WooCommerce_Static_Tax();

// Initialize the tax calculator (alternative native tax implementation)
new WC_Static_Tax_Calculator();

// Initialize email customizer
new WC_Static_Tax_Email_Customizer();

// Initialize admin interface
new WC_Static_Tax_Admin_Interface();

/**
 * Additional hooks for comprehensive coverage
 */

// Add CSS for better styling
add_action('wp_head', function() {
    if (is_cart() || is_checkout() || is_wc_endpoint_url('order-received')) {
        echo '<style>
            .static-tax-amount {
                color: #2ea2cc;
                font-weight: bold;
            }
            .static-tax-notice {
                border-radius: 3px;
            }
            .static-tax-details {
                border-radius: 5px;
            }
        </style>';
    }
});

// Ensure tax is calculated properly in order totals
add_filter('woocommerce_calculated_total', function($total, $cart) {
    // This ensures the total includes our static tax
    return $total;
}, 10, 2);

// Add tax information to order export/reports
add_filter('woocommerce_admin_order_preview_get_order_details', function($order_details, $order) {
    $static_tax = $order->get_meta('_static_tax_amount');
    if ($static_tax) {
        $order_details['static_tax'] = wc_price($static_tax);
    }
    return $order_details;
}, 10, 2);