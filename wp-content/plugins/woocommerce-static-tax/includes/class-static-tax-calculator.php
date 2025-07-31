<?php
/**
 * Static Tax Calculator Class
 * 
 * This class handles the calculation and application of static tax
 * using WooCommerce's native tax system.
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Static_Tax_Calculator {
    
    private $static_tax_amount = 25;
    private $tax_rate_id;
    private $tax_class_slug = 'static-tax';
    private $tax_rate_name = 'Processing Fee';
    
    public function __construct() {
        add_action('init', array($this, 'init'), 20);
    }
    
    public function init() {
        // Create custom tax class and rate
        add_action('woocommerce_init', array($this, 'create_custom_tax_rate'));
        
        // Apply static tax to cart
        add_action('woocommerce_before_calculate_totals', array($this, 'apply_static_tax_to_cart'), 10, 1);
        
        // Modify tax calculations
        add_filter('woocommerce_find_rates', array($this, 'add_static_tax_rate'), 10, 2);
        
        // Override tax calculation for our custom tax
        add_filter('woocommerce_calc_tax', array($this, 'calculate_static_tax'), 10, 4);
        
        // Display custom tax in cart and checkout
        add_filter('woocommerce_cart_tax_totals', array($this, 'add_static_tax_to_totals'), 10, 2);
        
        // Ensure tax appears in all order displays
        add_filter('woocommerce_order_get_tax_totals', array($this, 'add_static_tax_to_order_totals'), 10, 2);
        
        // Add tax details to order meta for consistency
        add_action('woocommerce_checkout_create_order', array($this, 'save_static_tax_details'), 10, 2);
    }
    
    /**
     * Create custom tax rate for our static tax
     */
    public function create_custom_tax_rate() {
        global $wpdb;
        
        // Check if our custom tax rate already exists
        $existing_rate = $wpdb->get_var($wpdb->prepare(
            "SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_name = %s",
            $this->tax_rate_name
        ));
        
        if (!$existing_rate) {
            // Create the tax rate
            $tax_rate_data = array(
                'tax_rate_country' => '',
                'tax_rate_state' => '',
                'tax_rate' => '0', // We'll handle the amount manually
                'tax_rate_name' => $this->tax_rate_name,
                'tax_rate_priority' => 1,
                'tax_rate_compound' => 0,
                'tax_rate_shipping' => 0,
                'tax_rate_order' => 0,
                'tax_rate_class' => $this->tax_class_slug
            );
            
            $this->tax_rate_id = WC_Tax::_insert_tax_rate($tax_rate_data);
            
            // Add location data for the tax rate (global)
            WC_Tax::_update_tax_rate_locations($this->tax_rate_id, array(
                array(
                    'location_code' => '',
                    'location_type' => 'country',
                ),
            ));
        } else {
            $this->tax_rate_id = $existing_rate;
        }
    }
    
    /**
     * Apply static tax to cart items
     */
    public function apply_static_tax_to_cart($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        if ($cart->is_empty()) {
            return;
        }
        
        // Set a flag to indicate we should apply our static tax
        WC()->session->set('apply_static_tax', true);
    }
    
    /**
     * Add our static tax rate to the available rates
     */
    public function add_static_tax_rate($rates, $args) {
        if (WC()->session && WC()->session->get('apply_static_tax')) {
            $rates[$this->tax_rate_id] = array(
                'rate' => '0',
                'label' => $this->tax_rate_name,
                'shipping' => 'no',
                'compound' => 'no'
            );
        }
        
        return $rates;
    }
    
    /**
     * Calculate our static tax amount
     */
    public function calculate_static_tax($taxes, $price, $rates, $price_includes_tax) {
        // Check if our tax rate is in the rates being calculated
        if (isset($rates[$this->tax_rate_id]) && WC()->session && WC()->session->get('apply_static_tax')) {
            $taxes[$this->tax_rate_id] = $this->static_tax_amount;
        }
        
        return $taxes;
    }
    
    /**
     * Add static tax to cart tax totals display
     */
    public function add_static_tax_to_totals($tax_totals, $cart) {
        if (WC()->session && WC()->session->get('apply_static_tax')) {
            $tax_totals['static_tax_' . $this->tax_rate_id] = (object) array(
                'label' => $this->tax_rate_name,
                'amount' => $this->static_tax_amount,
                'formatted_amount' => wc_price($this->static_tax_amount),
                'is_compound' => false
            );
        }
        
        return $tax_totals;
    }
    
    /**
     * Add static tax to order totals display
     */
    public function add_static_tax_to_order_totals($tax_totals, $order) {
        $static_tax_amount = $order->get_meta('_static_tax_applied');
        
        if ($static_tax_amount) {
            $tax_totals['static_tax'] = (object) array(
                'label' => $this->tax_rate_name,
                'amount' => $static_tax_amount,
                'formatted_amount' => wc_price($static_tax_amount),
                'is_compound' => false
            );
        }
        
        return $tax_totals;
    }
    
    /**
     * Save static tax details to order
     */
    public function save_static_tax_details($order, $data) {
        if (WC()->session && WC()->session->get('apply_static_tax')) {
            $order->update_meta_data('_static_tax_applied', $this->static_tax_amount);
            $order->update_meta_data('_static_tax_rate_id', $this->tax_rate_id);
            $order->update_meta_data('_static_tax_label', $this->tax_rate_name);
            
            // Add tax item to order
            $tax_item = new WC_Order_Item_Tax();
            $tax_item->set_rate_id($this->tax_rate_id);
            $tax_item->set_label($this->tax_rate_name);
            $tax_item->set_tax_total($this->static_tax_amount);
            $tax_item->set_shipping_tax_total(0);
            
            $order->add_item($tax_item);
            
            // Update order totals
            $order->set_total($order->get_total() + $this->static_tax_amount);
            
            // Clear the session flag
            WC()->session->set('apply_static_tax', false);
        }
    }
    
    /**
     * Get the static tax amount
     */
    public function get_static_tax_amount() {
        return $this->static_tax_amount;
    }
    
    /**
     * Get the tax rate name
     */
    public function get_tax_rate_name() {
        return $this->tax_rate_name;
    }
}