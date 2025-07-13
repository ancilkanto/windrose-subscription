# Windrose Subscription Plugin

A comprehensive WooCommerce subscription plugin that enables automatic recurring payments for products, specifically designed to work with the Paymob payment gateway.

## Description

**Windrose Subscription** allows enabling automatic recurring payments on your WooCommerce products. Once a customer purchases a subscription-based product, the plugin will automatically renew payments based on your configured settings. This plugin provides a complete alternative to WooCommerce Subscriptions, specifically tailored for the Paymob payment gateway ecosystem.

## Features

### 🛍️ Product Subscription Management
- Mark products as subscription products with custom settings
- Support for multiple subscription frequencies:
  - Every 1 Week
  - Every 2 Weeks  
  - Every 3 Weeks
  - Every 4 Weeks
- Custom product options for subscription configuration

### 💳 Paymob Payment Gateway Integration
- Seamless integration with Paymob payment gateway
- Automatic recurring payments using saved payment tokens
- Support for both test and live payment environments
- Payment intention creation and processing through Paymob API
- Integration with existing Paymob card token system

### 🔄 Subscription Lifecycle Management
- **Subscription Statuses**: Processing, Active, Paused, Cancelled, Expired
- **Order Statuses**: Upcoming, Past, Skipped, Cancelled
- **Customer Actions**: Pause, Cancel, Skip, Reactivate subscriptions
- Complete subscription lifecycle tracking

### 👤 Customer Features
- My Account subscription management interface
- Subscription details and history view
- Customer-facing subscription controls
- Subscription status monitoring

### ⚙️ Admin Features
- Comprehensive admin subscription list view
- Detailed subscription management interface
- Product subscription options in WooCommerce admin
- Subscription analytics and reporting

### 🤖 Automation
- CLI commands for subscription order creation
- Automated payment processing
- Cron job integration for recurring billing
- Background task processing

## Installation

### Prerequisites
- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- Paymob for WooCommerce plugin
- PHP 7.0 or higher

### Installation Steps

1. **Upload the Plugin**
   - Upload the `windrose-subscription` folder to `/wp-content/plugins/`
   - Or install via WordPress admin panel

2. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Windrose Subscription" and click "Activate"

3. **Configure Paymob Settings**
   - Ensure Paymob for WooCommerce is properly configured
   - Verify payment tokens are being saved for customers

4. **Set Up Subscription Products**
   - Edit any product in WooCommerce
   - Enable subscription option in product settings
   - Configure subscription frequency and settings

## Configuration

### Database Tables
The plugin automatically creates two main database tables:

#### `wp_windrose_subscriptions`
- Main subscription data storage
- Contains subscription metadata, status, and scheduling information

#### `wp_windrose_subscription_orders`
- Individual subscription order records
- Tracks payment attempts, sequence numbers, and order status

### Subscription Frequencies
```php
$windros_subscription_frequencies = array(
    '7' => 'Every 1 Week',
    '14' => 'Every 2 Weeks', 
    '21' => 'Every 3 Weeks',
    '28' => 'Every 4 Weeks'
);
```

### Subscription Statuses
- **Processing**: Initial subscription setup
- **Active**: Subscription is active and billing
- **Paused**: Subscription temporarily paused
- **Cancelled**: Subscription cancelled by customer or admin
- **Expired**: Subscription has expired

## Usage

### Setting Up Subscription Products

1. **Enable Subscription on Product**
   - Edit a product in WooCommerce admin
   - Check "Enable Subscription" option
   - Select subscription frequency
   - Save product

2. **Customer Purchase Flow**
   - Customer adds subscription product to cart
   - Completes checkout with Paymob payment
   - Payment token is saved for future billing
   - Initial subscription is created

3. **Recurring Billing**
   - Plugin automatically creates orders based on schedule
   - Payments are processed using saved tokens
   - Failed payments are retried according to settings

### CLI Commands

The plugin includes WP-CLI commands for subscription management:

```bash
# Create subscription orders for upcoming renewals
wp windrose-cli create_subscription_order
```

### Hooks and Actions

The plugin provides various hooks for customization:

```php
// Triggered when payment is initiated
do_action('windrose_subscription_initiate_payment', $subscription_order, $order);

// Triggered on successful payment
do_action('windrose_subscription_order_executed_successfully', $subscription_order_id);

// Triggered on failed payment
do_action('windrose_subscription_order_execution_failed', $subscription_order_id, $reason);
```

## Technical Architecture

### Namespace Structure
```
WindroseSubscription\Includes\
├── AdminProductSubscriptionOptions
├── WindroseRegisterShortcodes
├── WindroseModifyProductLoopData
├── WindroseModifyProductListingLoop
├── WindroseModifyProductSinglePage
├── WindroseSubscriptionCart
├── WindroseSubscriptionCheckout
├── WindroseCreateSubscription
├── WindroseMyAccountInit
├── WindroseActivateSubscription
├── WindroseCreateSubscriptionOrder
├── WindroseUpdateSubscription
├── WindrosePauseSubscription
├── WindroseCancelSubscription
├── WindroseSkipSubscription
├── WindroseReactivateSubscription
├── WindroseAdminSubscriptionList
├── WindroseAdminSubscriptionDetailView
└── WindroseCLI
```

### File Structure
```
windrose-subscription/
├── windrose-main.php          # Main plugin file
├── constants.php              # Plugin constants and definitions
├── install-plugin.php         # Installation and database setup
├── uninstall-plugin.php       # Cleanup on uninstall
├── composer.json              # Composer dependencies
├── includes/                  # Core functionality classes
├── templates/                 # Frontend and admin templates
├── assets/                    # CSS, JS, and media files
└── traits/                    # Reusable traits
```

## Paymob Integration

### Payment Flow
1. **Token Retrieval**: Gets saved payment token from `wp_paymob_cards_token`
2. **Payment Intention**: Creates payment intention via Paymob API
3. **Payment Processing**: Processes payment using saved token
4. **Status Update**: Updates subscription and order status based on result

### API Endpoints Used
- `https://oman.paymob.com/v1/intention/` - Create payment intention
- `https://oman.paymob.com/api/acceptance/payments/pay/` - Process payment

### Configuration Requirements
- Paymob API keys (Secret, Public)
- Integration IDs for test and live environments
- Proper HMAC configuration for security

## Support

For support and documentation:
- **Author**: Ancil K Anto
- **Website**: https://ancil.dev/
- **Plugin URI**: https://github.com/ancilkanto/windros-subscription

## Version History

- **Version 1.0**: Initial release with core subscription functionality

## License

This plugin is custom-developed for WooCommerce integration with Paymob payment gateway.

---

**Note**: This plugin requires the Paymob for WooCommerce plugin to be installed and properly configured for full functionality. 