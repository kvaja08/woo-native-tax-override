# WooCommerce Static Tax Plugin

A comprehensive WordPress plugin that adds a configurable static tax amount to all WooCommerce orders. The tax appears consistently across all WooCommerce areas including cart, checkout, order confirmation, admin orders, and email notifications.

## Features

✅ **Configurable static tax amount** (default: $25.00)  
✅ **Customizable tax label** (default: "Processing Fee")  
✅ **Enable/disable functionality** without deactivating plugin  
✅ **Cart integration** - appears in shopping cart totals  
✅ **Checkout integration** - displays during checkout process  
✅ **Order thank you page** - shows on order confirmation  
✅ **Admin order details** - visible in WordPress admin  
✅ **Email notifications** - included in all WooCommerce emails  
✅ **Order reports** - integrated with WooCommerce reporting  
✅ **Admin interface** - easy configuration through WordPress admin  

## Installation

1. Upload the `woocommerce-static-tax` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **WooCommerce > Static Tax** to configure settings

## Configuration

### Admin Settings

Navigate to **WooCommerce > Static Tax** in your WordPress admin to configure:

- **Tax Amount**: Set the fixed dollar amount (e.g., 25 for $25.00)
- **Tax Label**: Customize the display name (e.g., "Processing Fee", "Service Charge")
- **Enable/Disable**: Toggle the tax on/off without deactivating the plugin

### Default Settings

- **Amount**: $25.00
- **Label**: Processing Fee
- **Status**: Enabled

## Where the Tax Appears

The static tax will be displayed in:

1. **Shopping Cart**
   - Added to cart totals
   - Highlighted with special styling

2. **Checkout Page**
   - Included in order review
   - Notice displayed to customer

3. **Order Thank You Page**
   - Detailed breakdown after purchase
   - Clear fee explanation

4. **Admin Order Details**
   - Comprehensive tax information
   - Order notes with fee details
   - Additional column in orders list

5. **Email Notifications**
   - Customer order emails
   - Admin notification emails
   - Both HTML and plain text formats

6. **Reports & Analytics**
   - Integrated with WooCommerce reports
   - Order preview popups

## Technical Implementation

### Hooks & Filters Used

The plugin uses the following WordPress/WooCommerce hooks:

**Cart & Checkout:**
- `woocommerce_cart_calculate_fees`
- `woocommerce_cart_totals_fee_html`
- `woocommerce_review_order_before_payment`

**Order Processing:**
- `woocommerce_checkout_create_order`
- `woocommerce_checkout_order_processed`

**Display:**
- `woocommerce_order_details_after_order_table`
- `woocommerce_admin_order_data_after_billing_address`
- `woocommerce_email_order_details`

**Admin Interface:**
- `manage_edit-shop_order_columns`
- `woocommerce_admin_order_preview_get_order_details`

### Plugin Structure

```
woocommerce-static-tax/
├── woocommerce-static-tax.php (main plugin file)
├── includes/
│   ├── class-plugin-config.php (settings management)
│   ├── class-static-tax-calculator.php (native tax integration)
│   ├── class-email-customizer.php (email templates)
│   └── class-admin-interface.php (admin interface)
└── README.md
```

## Customization

### Programmatic Configuration

You can override plugin settings programmatically:

```php
// Filter the tax amount
add_filter('wc_static_tax_amount', function($amount) {
    return 30; // Set to $30
});

// Filter the tax label
add_filter('wc_static_tax_label', function($label) {
    return 'Custom Service Fee';
});

// Conditionally disable the tax
add_filter('wc_static_tax_enabled', function($enabled) {
    // Disable for specific user roles, products, etc.
    return current_user_can('wholesale_customer') ? false : $enabled;
});
```

### Styling Customization

The plugin includes CSS classes for custom styling:

```css
/* Cart and checkout styling */
.static-tax-amount {
    color: #2ea2cc;
    font-weight: bold;
}

.static-tax-notice {
    border-radius: 3px;
}

/* Order details styling */
.static-tax-details {
    border-radius: 5px;
}

/* Admin styling */
.static-tax-admin-section {
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 15px;
    margin: 15px 0;
    border-radius: 5px;
}
```

## Requirements

- WordPress 5.0+
- WooCommerce 5.0+
- PHP 7.4+

## Support

### Troubleshooting

**Tax not appearing in cart:**
- Check if WooCommerce is active
- Ensure plugin is enabled in settings
- Verify cart is not empty

**Tax missing from emails:**
- Check email template compatibility
- Review WooCommerce email settings
- Test with default theme

**Admin interface issues:**
- Clear cache if using caching plugins
- Check user permissions (requires `manage_woocommerce` capability)

### Compatibility

This plugin is compatible with:
- All standard WooCommerce themes
- Most WooCommerce extensions
- Popular caching plugins
- Multisite installations

## Developer Notes

### Database Storage

The plugin stores the following order meta:
- `_static_tax_amount` - The applied tax amount
- `_static_tax_label` - The tax label used
- `_static_tax_applied` - Flag for native tax implementation

### Plugin Options

Settings are stored in the `wc_static_tax_settings` option:
```php
array(
    'tax_amount' => 25,
    'tax_label' => 'Processing Fee',
    'enabled' => 1
)
```

### Uninstallation

The plugin preserves settings when deactivated. To completely remove:
1. Deactivate the plugin
2. Delete plugin files
3. Manually remove the `wc_static_tax_settings` option if desired

## Changelog

### Version 1.0.0
- Initial release
- Configurable static tax amount
- Full WooCommerce integration
- Admin interface
- Email template support
- Comprehensive documentation

## License

GPL v2 or later

## Author

Created for WooCommerce stores requiring a fixed processing fee or service charge on all orders.