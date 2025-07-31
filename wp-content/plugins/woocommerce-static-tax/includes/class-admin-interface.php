<?php
/**
 * Admin Interface Class
 * 
 * Handles the display of static tax in WordPress admin area
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Static_Tax_Admin_Interface {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Admin order details
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_static_tax_in_admin_order'));
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'display_tax_summary_in_admin'));
        
        // Order list columns
        add_filter('manage_edit-shop_order_columns', array($this, 'add_tax_column_to_orders'), 20);
        add_action('manage_shop_order_posts_custom_column', array($this, 'display_tax_in_order_column'), 10, 2);
        
        // Order preview (quick view)
        add_filter('woocommerce_admin_order_preview_get_order_details', array($this, 'add_tax_to_order_preview'), 10, 2);
        
        // Add tax information to order notes
        add_action('woocommerce_checkout_order_processed', array($this, 'add_tax_order_note'), 10, 1);
        
        // Admin styles
        add_action('admin_head', array($this, 'add_admin_styles'));
        
        // Order totals meta box
        add_action('woocommerce_admin_order_totals_after_total', array($this, 'display_tax_in_totals_metabox'));
        
        // Reports integration
        add_filter('woocommerce_reports_get_order_report_data', array($this, 'include_tax_in_reports'), 10, 1);
    }
    
    /**
     * Display static tax in admin order details
     */
    public function display_static_tax_in_admin_order($order) {
        $static_tax = $this->get_order_static_tax($order);
        
        if (!$static_tax) {
            return;
        }
        
        echo '<div class="static-tax-admin-section">';
        echo '<h3>Processing Fee Details</h3>';
        echo '<div class="static-tax-details">';
        echo '<p><strong>Fee Type:</strong> ' . esc_html($static_tax['label']) . '</p>';
        echo '<p><strong>Amount:</strong> <span class="static-tax-amount">' . wc_price($static_tax['amount']) . '</span></p>';
        echo '<p><strong>Applied:</strong> Yes</p>';
        echo '<p><em>This fee was automatically applied to the order during checkout.</em></p>';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Display tax summary in admin order details
     */
    public function display_tax_summary_in_admin($order) {
        $static_tax = $this->get_order_static_tax($order);
        
        if (!$static_tax) {
            return;
        }
        
        echo '<div class="static-tax-summary">';
        echo '<h4>Tax Breakdown</h4>';
        echo '<table class="static-tax-breakdown-table">';
        echo '<tr><td>Processing Fee:</td><td>' . wc_price($static_tax['amount']) . '</td></tr>';
        echo '<tr><td>Tax Rate:</td><td>Fixed Amount</td></tr>';
        echo '<tr><td>Taxable Base:</td><td>Order Total</td></tr>';
        echo '</table>';
        echo '</div>';
    }
    
    /**
     * Add tax column to orders list
     */
    public function add_tax_column_to_orders($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            
            // Add after order total column
            if ('order_total' === $key) {
                $new_columns['processing_fee'] = 'Processing Fee';
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Display tax in order list column
     */
    public function display_tax_in_order_column($column, $post_id) {
        if ('processing_fee' !== $column) {
            return;
        }
        
        $order = wc_get_order($post_id);
        if (!$order) {
            return;
        }
        
        $static_tax = $this->get_order_static_tax($order);
        
        if ($static_tax) {
            echo '<span class="processing-fee-applied">' . wc_price($static_tax['amount']) . '</span>';
        } else {
            echo '<span class="processing-fee-not-applied">—</span>';
        }
    }
    
    /**
     * Add tax to order preview popup
     */
    public function add_tax_to_order_preview($order_details, $order) {
        $static_tax = $this->get_order_static_tax($order);
        
        if ($static_tax) {
            $order_details['processing_fee'] = array(
                'label' => 'Processing Fee',
                'value' => wc_price($static_tax['amount'])
            );
        }
        
        return $order_details;
    }
    
    /**
     * Add order note about static tax
     */
    public function add_tax_order_note($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        $static_tax = $this->get_order_static_tax($order);
        
        if ($static_tax) {
            $note = sprintf(
                'Processing fee of %s (%s) was automatically applied to this order.',
                wc_price($static_tax['amount']),
                $static_tax['label']
            );
            
            $order->add_order_note($note, false, true);
        }
    }
    
    /**
     * Add admin styles
     */
    public function add_admin_styles() {
        $screen = get_current_screen();
        
        if ($screen && (strpos($screen->id, 'shop_order') !== false || strpos($screen->id, 'woocommerce') !== false)) {
            echo '<style>
                .static-tax-admin-section {
                    background: #f9f9f9;
                    border: 1px solid #ddd;
                    padding: 15px;
                    margin: 15px 0;
                    border-radius: 5px;
                }
                
                .static-tax-admin-section h3 {
                    margin-top: 0;
                    color: #2271b1;
                    border-bottom: 2px solid #2271b1;
                    padding-bottom: 5px;
                }
                
                .static-tax-amount {
                    color: #00a32a;
                    font-weight: bold;
                    font-size: 14px;
                }
                
                .static-tax-breakdown-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }
                
                .static-tax-breakdown-table td {
                    padding: 5px 10px;
                    border-bottom: 1px solid #ddd;
                }
                
                .static-tax-breakdown-table td:first-child {
                    font-weight: bold;
                    width: 40%;
                }
                
                .processing-fee-applied {
                    color: #00a32a;
                    font-weight: bold;
                }
                
                .processing-fee-not-applied {
                    color: #999;
                }
                
                .static-tax-summary {
                    background: #fff;
                    border: 1px solid #c3c4c7;
                    padding: 12px;
                    margin: 10px 0;
                    border-radius: 3px;
                }
                
                .static-tax-summary h4 {
                    margin: 0 0 10px 0;
                    color: #1d2327;
                }
            </style>';
        }
    }
    
    /**
     * Display tax in order totals meta box
     */
    public function display_tax_in_totals_metabox($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        $static_tax = $this->get_order_static_tax($order);
        
        if ($static_tax) {
            echo '<tr>';
            echo '<td class="label">' . esc_html($static_tax['label']) . ':</td>';
            echo '<td width="1%"></td>';
            echo '<td class="total">' . wc_price($static_tax['amount']) . '</td>';
            echo '</tr>';
        }
    }
    
    /**
     * Include tax in reports
     */
    public function include_tax_in_reports($data) {
        // This can be enhanced to include static tax data in WooCommerce reports
        return $data;
    }
    
    /**
     * Get static tax information from order (same as email customizer)
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