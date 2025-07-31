<?php
/**
 * Email Customizer Class
 * 
 * Ensures static tax appears in all WooCommerce email notifications
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Static_Tax_Email_Customizer {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Hook into all email types
        add_action('woocommerce_email_order_details', array($this, 'add_tax_to_all_emails'), 25, 4);
        add_action('woocommerce_email_customer_details', array($this, 'add_tax_summary_to_emails'), 15, 4);
        
        // Specifically target different email types
        add_action('woocommerce_email_before_order_table', array($this, 'add_tax_notice_before_table'), 10, 4);
        add_action('woocommerce_email_after_order_table', array($this, 'add_tax_details_after_table'), 10, 4);
        
        // Add to order item table
        add_filter('woocommerce_email_order_items_table', array($this, 'modify_email_order_table'), 10, 1);
        
        // Customize email styles
        add_action('woocommerce_email_header', array($this, 'add_email_styles'));
    }
    
    /**
     * Add tax information to all emails
     */
    public function add_tax_to_all_emails($order, $sent_to_admin, $plain_text, $email) {
        $static_tax = $this->get_order_static_tax($order);
        
        if (!$static_tax) {
            return;
        }
        
        if ($plain_text) {
            echo "\n" . str_repeat('-', 50) . "\n";
            echo "PROCESSING FEE: " . wc_price($static_tax['amount']) . "\n";
            echo "Label: " . $static_tax['label'] . "\n";
            echo str_repeat('-', 50) . "\n";
        } else {
            echo '<div class="static-tax-email-section" style="margin: 20px 0; padding: 15px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">';
            echo '<h3 style="margin: 0 0 10px 0; color: #495057;">' . esc_html($static_tax['label']) . '</h3>';
            echo '<p style="margin: 0; font-size: 16px;"><strong>Amount: ' . wc_price($static_tax['amount']) . '</strong></p>';
            echo '<p style="margin: 5px 0 0 0; font-size: 14px; color: #6c757d;">This processing fee has been applied to your order total.</p>';
            echo '</div>';
        }
    }
    
    /**
     * Add tax summary to customer details section
     */
    public function add_tax_summary_to_emails($order, $sent_to_admin, $plain_text, $email) {
        $static_tax = $this->get_order_static_tax($order);
        
        if (!$static_tax) {
            return;
        }
        
        if ($plain_text) {
            echo "\nProcessing Fee Summary:\n";
            echo "Fee: " . wc_price($static_tax['amount']) . "\n";
        } else {
            echo '<div style="margin: 15px 0; padding: 10px; background-color: #e9ecef; border-radius: 3px;">';
            echo '<strong>Processing Fee Applied:</strong> ' . wc_price($static_tax['amount']);
            echo '</div>';
        }
    }
    
    /**
     * Add notice before order table
     */
    public function add_tax_notice_before_table($order, $sent_to_admin, $plain_text, $email) {
        $static_tax = $this->get_order_static_tax($order);
        
        if (!$static_tax) {
            return;
        }
        
        if (!$plain_text) {
            echo '<div style="background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; padding: 12px; margin: 15px 0; color: #155724;">';
            echo '<strong>Note:</strong> A processing fee of ' . wc_price($static_tax['amount']) . ' has been added to this order.';
            echo '</div>';
        }
    }
    
    /**
     * Add detailed tax information after order table
     */
    public function add_tax_details_after_table($order, $sent_to_admin, $plain_text, $email) {
        $static_tax = $this->get_order_static_tax($order);
        
        if (!$static_tax) {
            return;
        }
        
        if ($plain_text) {
            echo "\n--- Tax Breakdown ---\n";
            echo $static_tax['label'] . ": " . wc_price($static_tax['amount']) . "\n";
            echo "Applied to Order #" . $order->get_order_number() . "\n";
        } else {
            echo '<div style="margin: 20px 0; padding: 15px; border: 2px solid #007cba; border-radius: 5px; background-color: #f0f8ff;">';
            echo '<h4 style="margin: 0 0 10px 0; color: #007cba;">Tax Breakdown</h4>';
            echo '<table style="width: 100%; border-collapse: collapse;">';
            echo '<tr><td style="padding: 5px; border-bottom: 1px solid #ddd;"><strong>Tax Type:</strong></td><td style="padding: 5px; border-bottom: 1px solid #ddd;">' . esc_html($static_tax['label']) . '</td></tr>';
            echo '<tr><td style="padding: 5px; border-bottom: 1px solid #ddd;"><strong>Amount:</strong></td><td style="padding: 5px; border-bottom: 1px solid #ddd;">' . wc_price($static_tax['amount']) . '</td></tr>';
            echo '<tr><td style="padding: 5px;"><strong>Order Number:</strong></td><td style="padding: 5px;">#' . $order->get_order_number() . '</td></tr>';
            echo '</table>';
            echo '</div>';
        }
    }
    
    /**
     * Modify email order table to include tax information
     */
    public function modify_email_order_table($table) {
        // This can be used to inject tax information directly into the order table
        return $table;
    }
    
    /**
     * Add custom styles to emails
     */
    public function add_email_styles() {
        echo '<style type="text/css">
            .static-tax-email-section {
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .static-tax-email-section h3 {
                border-bottom: 2px solid #007cba;
                padding-bottom: 5px;
            }
        </style>';
    }
    
    /**
     * Get static tax information from order
     */
    private function get_order_static_tax($order) {
        $static_tax_amount = $order->get_meta('_static_tax_amount');
        $static_tax_label = $order->get_meta('_static_tax_label');
        
        // Also check for native tax implementation
        if (!$static_tax_amount) {
            $static_tax_amount = $order->get_meta('_static_tax_applied');
        }
        
        // Check in order fees
        if (!$static_tax_amount) {
            foreach ($order->get_fees() as $fee) {
                if (strpos($fee->get_name(), 'Processing Fee') !== false) {
                    $static_tax_amount = $fee->get_amount();
                    $static_tax_label = $fee->get_name();
                    break;
                }
            }
        }
        
        // Check in order taxes
        if (!$static_tax_amount) {
            foreach ($order->get_taxes() as $tax) {
                if (strpos($tax->get_label(), 'Processing Fee') !== false) {
                    $static_tax_amount = $tax->get_tax_total();
                    $static_tax_label = $tax->get_label();
                    break;
                }
            }
        }
        
        if ($static_tax_amount) {
            return array(
                'amount' => $static_tax_amount,
                'label' => $static_tax_label ?: 'Processing Fee'
            );
        }
        
        return false;
    }
}