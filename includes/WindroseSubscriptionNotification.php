<?php
namespace WindroseSubscription\Includes;

defined( 'WINDROS_INIT' ) || exit;

class WindroseSubscriptionNotification {
    
    public function __construct() {
        error_log('Windrose Subscription: WindroseSubscriptionNotification constructor called');
        self::init();
    }
    
    public static function init() {
        error_log('Windrose Subscription: WindroseSubscriptionNotification init method called');
        add_action('windrose_subscription_main_order_activated', [__CLASS__, 'notify_subscription_activated'], 10, 1);
        add_action('windrose_subscription_order_executed_successfully', [__CLASS__, 'notify_subscription_order_processed'], 10, 1);
        add_action('windrose_subscription_order_execution_failed', [__CLASS__, 'notify_subscription_order_failed'], 10, 2);
        add_action('windrose_subscription_main_order_paused', [__CLASS__, 'notify_subscription_paused'], 10, 1);
        add_action('windrose_subscription_main_order_cancelled', [__CLASS__, 'notify_subscription_cancelled'], 10, 1);
        add_action('windrose_subscription_order_skipped', [__CLASS__, 'notify_subscription_skipped'], 10, 1);
        add_action('windrose_subscription_email_trigger', function($event, $id, $extra = '') {
            error_log('Windrose Subscription: Email trigger called for event: ' . $event . ', ID: ' . $id);
            $mailer = WC()->mailer();
            switch ($event) {
                case 'activated':
                    error_log('Windrose Subscription: Attempting to send activated email for subscription ID: ' . $id);
                    $email = $mailer->emails['WC_Email_Windrose_Subscription_Activated'] ?? null;
                    if ($email) {
                        $email->trigger($id);
                    } else {
                        error_log('Windrose Subscription: Email class WC_Email_Windrose_Subscription_Activated not found');
                    }
                    break;
                case 'order_processed':
                    error_log('Windrose Subscription: Attempting to send order processed email for subscription ID: ' . $id);
                    $email = $mailer->emails['WC_Email_Windrose_Subscription_Order_Processed'] ?? null;
                    if ($email) {
                        $email->trigger($id);
                    } else {
                        error_log('Windrose Subscription: Email class WC_Email_Windrose_Subscription_Order_Processed not found');
                    }
                    break;
                case 'order_failed':
                    error_log('Windrose Subscription: Attempting to send order failed email for subscription ID: ' . $id . ', reason: ' . $extra);
                    $email = $mailer->emails['WC_Email_Windrose_Subscription_Order_Failed'] ?? null;
                    if ($email) {
                        $email->trigger($id, $extra);
                    } else {
                        error_log('Windrose Subscription: Email class WC_Email_Windrose_Subscription_Order_Failed not found');
                    }
                    break;
                case 'paused':
                    error_log('Windrose Subscription: Attempting to send paused email for subscription ID: ' . $id);
                    $email = $mailer->emails['WC_Email_Windrose_Subscription_Paused'] ?? null;
                    if ($email) {
                        $email->trigger($id);
                    } else {
                        error_log('Windrose Subscription: Email class WC_Email_Windrose_Subscription_Paused not found');
                    }
                    break;
                case 'cancelled':
                    error_log('Windrose Subscription: Attempting to send cancelled email for subscription ID: ' . $id);
                    $email = $mailer->emails['WC_Email_Windrose_Subscription_Cancelled'] ?? null;
                    if ($email) {
                        $email->trigger($id);
                    } else {
                        error_log('Windrose Subscription: Email class WC_Email_Windrose_Subscription_Cancelled not found');
                    }
                    break;
                case 'skipped':
                    error_log('Windrose Subscription: Attempting to send skipped email for subscription order ID: ' . $id);
                    $email = $mailer->emails['WC_Email_Windrose_Subscription_Skipped'] ?? null;
                    if ($email) {
                        $email->trigger($id);
                    } else {
                        error_log('Windrose Subscription: Email class WC_Email_Windrose_Subscription_Skipped not found');
                    }
                    break;
            }
        }, 10, 3);
        
        // Check email status on init
        add_action('init', [__CLASS__, 'check_email_status'], 30);
        error_log('Windrose Subscription: All notification hooks registered');
    }

    public static function notify_subscription_activated($subscription_id) {
        error_log('Windrose Subscription: notify_subscription_activated called for subscription ID: ' . $subscription_id);
        do_action('windrose_subscription_email_trigger', 'activated', $subscription_id);
    }
    public static function notify_subscription_order_processed($subscription_id) {
        error_log('Windrose Subscription: notify_subscription_order_processed called for subscription ID: ' . $subscription_id);
        do_action('windrose_subscription_email_trigger', 'order_processed', $subscription_id);
    }
    public static function notify_subscription_order_failed($subscription_id, $reason) {
        error_log('Windrose Subscription: notify_subscription_order_failed called for subscription ID: ' . $subscription_id . ', reason: ' . $reason);
        do_action('windrose_subscription_email_trigger', 'order_failed', $subscription_id, $reason);
    }
    public static function notify_subscription_paused($subscription_id) {
        error_log('Windrose Subscription: notify_subscription_paused called for subscription ID: ' . $subscription_id);
        do_action('windrose_subscription_email_trigger', 'paused', $subscription_id);
    }
    public static function notify_subscription_cancelled($subscription_id) {
        error_log('Windrose Subscription: notify_subscription_cancelled called for subscription ID: ' . $subscription_id);
        do_action('windrose_subscription_email_trigger', 'cancelled', $subscription_id);
    }
    public static function notify_subscription_skipped($subscription_order_id) {
        error_log('Windrose Subscription: notify_subscription_skipped called for subscription order ID: ' . $subscription_order_id);
        do_action('windrose_subscription_email_trigger', 'skipped', $subscription_order_id);
    }

    public static function check_email_status() {
        error_log('Windrose Subscription: Checking email status...');
        
        $mailer = WC()->mailer();
        $enabled_emails = get_option('woocommerce_email_settings', array());
        
        $email_types = array(
            'windrose_subscription_activated' => 'WC_Email_Windrose_Subscription_Activated',
            'windrose_subscription_order_processed' => 'WC_Email_Windrose_Subscription_Order_Processed',
            'windrose_subscription_order_failed' => 'WC_Email_Windrose_Subscription_Order_Failed',
            'windrose_subscription_paused' => 'WC_Email_Windrose_Subscription_Paused',
            'windrose_subscription_cancelled' => 'WC_Email_Windrose_Subscription_Cancelled',
            'windrose_subscription_skipped' => 'WC_Email_Windrose_Subscription_Skipped'
        );
        
        foreach ($email_types as $email_key => $email_class) {
            $enabled = isset($enabled_emails[$email_key . '_enabled']) && $enabled_emails[$email_key . '_enabled'] === 'yes';
            $email_object = $mailer->emails[$email_class] ?? null;
            $registered = $email_object !== null;
            
            error_log("Windrose Subscription: Email {$email_key} - Enabled: " . ($enabled ? 'Yes' : 'No') . ", Registered: " . ($registered ? 'Yes' : 'No'));
        }
    }

    public static function test_html_email_directly($subscription_id) {
        error_log('Windrose Subscription: Testing HTML email directly for subscription ID: ' . $subscription_id);
        
        // Test if WooCommerce is loaded
        if (!function_exists('WC')) {
            error_log('Windrose Subscription: WooCommerce not loaded');
            return false;
        }
        
        // Test if mailer is available
        $mailer = WC()->mailer();
        if (!$mailer) {
            error_log('Windrose Subscription: WooCommerce mailer not available');
            return false;
        }
        
        // Test if our email class is registered
        $email = $mailer->emails['WC_Email_Windrose_Subscription_Activated'] ?? null;
        if (!$email) {
            error_log('Windrose Subscription: Email class not found in mailer');
            return false;
        }
        
        // Test HTML content generation
        $html_content = $email->get_content_html();
        error_log('Windrose Subscription: HTML content length: ' . strlen($html_content));
        error_log('Windrose Subscription: HTML content preview: ' . substr($html_content, 0, 200) . '...');
        
        // Check if content contains HTML tags
        if (strpos($html_content, '<html>') !== false) {
            error_log('Windrose Subscription: HTML content contains <html> tag - HTML email confirmed');
        } else {
            error_log('Windrose Subscription: HTML content does not contain <html> tag - may not be HTML');
        }
        
        error_log('Windrose Subscription: Email class found, testing HTML trigger');
        $email->trigger($subscription_id);
        return true;
    }

    public static function test_email_system_directly($subscription_id) {
        error_log('Windrose Subscription: Testing email system directly for subscription ID: ' . $subscription_id);
        
        // Test if WooCommerce is loaded
        if (!function_exists('WC')) {
            error_log('Windrose Subscription: WooCommerce not loaded');
            return false;
        }
        
        // Test if mailer is available
        $mailer = WC()->mailer();
        if (!$mailer) {
            error_log('Windrose Subscription: WooCommerce mailer not available');
            return false;
        }
        
        // Test if our email class is registered
        $email = $mailer->emails['WC_Email_Windrose_Subscription_Activated'] ?? null;
        if (!$email) {
            error_log('Windrose Subscription: Email class not found in mailer');
            return false;
        }
        
        error_log('Windrose Subscription: Email class found, testing trigger');
        $email->trigger($subscription_id);
        return true;
    }

    public static function test_subscription_activation_email($subscription_id) {
        error_log('Windrose Subscription: Testing activation email for subscription ID: ' . $subscription_id);
        self::notify_subscription_activated($subscription_id);
    }

    public static function test_subscription_order_processed_email($subscription_id) {
        error_log('Windrose Subscription: Testing order processed email for subscription ID: ' . $subscription_id);
        self::notify_subscription_order_processed($subscription_id);
    }

    public static function test_subscription_order_failed_email($subscription_id, $reason = 'Test failure reason') {
        error_log('Windrose Subscription: Testing order failed email for subscription ID: ' . $subscription_id);
        self::notify_subscription_order_failed($subscription_id, $reason);
    }

    public static function test_subscription_paused_email($subscription_id) {
        error_log('Windrose Subscription: Testing paused email for subscription ID: ' . $subscription_id);
        self::notify_subscription_paused($subscription_id);
    }

    public static function test_subscription_cancelled_email($subscription_id) {
        error_log('Windrose Subscription: Testing cancelled email for subscription ID: ' . $subscription_id);
        self::notify_subscription_cancelled($subscription_id);
    }

    public static function test_subscription_skipped_email($subscription_order_id) {
        error_log('Windrose Subscription: Testing skipped email for subscription order ID: ' . $subscription_order_id);
        self::notify_subscription_skipped($subscription_order_id);
    }

    public static function send_subscription_email($id, $event, $extra = '') {
        global $wpdb;
        $subscription_table = $wpdb->prefix . (defined('WINDROS_SUBSCRIPTION_MAIN_TABLE') ? WINDROS_SUBSCRIPTION_MAIN_TABLE : 'windrose_subscription_main');
        $subscription_order_table = $wpdb->prefix . (defined('WINDROS_SUBSCRIPTION_ORDER_TABLE') ? WINDROS_SUBSCRIPTION_ORDER_TABLE : 'windrose_subscription_order');

        // Determine if $id is a subscription or order
        $subscription = $wpdb->get_row($wpdb->prepare("SELECT * FROM $subscription_table WHERE id = %d", $id));
        if (!$subscription && in_array($event, ['order_processed', 'order_failed', 'skipped'])) {
            // Try as order
            $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $subscription_order_table WHERE id = %d", $id));
            if ($order) {
                $subscription = $wpdb->get_row($wpdb->prepare("SELECT * FROM $subscription_table WHERE id = %d", $order->subscription_id));
            }
        }
        if (!$subscription) return;

        $user = get_userdata($subscription->user_id);
        $admin_email = get_option('admin_email');
        $product = function_exists('wc_get_product') ? \wc_get_product($subscription->product_id) : null;
        $product_name = $product ? $product->get_name() : __('(Unknown Product)', 'windros-subscription');
        $customer_email = $user ? $user->user_email : '';
        $customer_name = $user ? $user->display_name : '';

        $subject = '';
        $message = '';
        switch ($event) {
            case 'activated':
                $subject = __('Your subscription is now active', 'windros-subscription');
                $message = sprintf(__('Hello %s,\n\nYour subscription for %s is now active. Thank you!', 'windros-subscription'), $customer_name, $product_name);
                break;
            case 'order_processed':
                $subject = __('Your subscription order is processing', 'windros-subscription');
                $message = sprintf(__('Hello %s,\n\nYour subscription order for %s is now processing after successful payment.', 'windros-subscription'), $customer_name, $product_name);
                break;
            case 'order_failed':
                $subject = __('Subscription order payment failed', 'windros-subscription');
                $message = sprintf(__('Hello %s,\n\nPayment for your subscription order for %s has failed. \n\nReason: %s', 'windros-subscription'), $customer_name, $product_name, $extra);
                break;
            case 'paused':
                $subject = __('Your subscription has been paused', 'windros-subscription');
                $message = sprintf(__('Hello %s,\n\nYour subscription for %s has been paused.', 'windros-subscription'), $customer_name, $product_name);
                break;
            case 'cancelled':
                $subject = __('Your subscription has been cancelled', 'windros-subscription');
                $message = sprintf(__('Hello %s,\n\nYour subscription for %s has been cancelled.', 'windros-subscription'), $customer_name, $product_name);
                break;
            case 'skipped':
                $subject = __('A subscription order was skipped', 'windros-subscription');
                $message = sprintf(__('Hello %s,\n\nA subscription order for %s was skipped.', 'windros-subscription'), $customer_name, $product_name);
                break;
        }
        // Send to customer
        if ($customer_email) {
            \wp_mail($customer_email, $subject, $message);
        }
        // Send to admin
        $admin_subject = sprintf('[Subscription %s] %s - %s', $subscription->id, ucfirst($event), $customer_name);
        $admin_message = sprintf(
            "A subscription event has occurred:\n\n" .
            "Event: %s\n" .
            "Customer: %s <%s>\n" .
            "Product: %s\n" . 
            "Subscription ID: %s\n" .
            "Status: %s\n" .
            "%s", // Extra info if provided
            ucfirst($event),
            $customer_name,
            $customer_email,
            $product_name,
            $subscription->id,
            $subscription->status,
            $extra ? "\nAdditional Info: " . $extra : ""
        );
        \wp_mail($admin_email, $admin_subject, $admin_message);
    }
} 