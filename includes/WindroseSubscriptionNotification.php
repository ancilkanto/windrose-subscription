<?php
namespace WindroseSubscription\Includes;

defined( 'WINDROS_INIT' ) || exit;

class WindroseSubscriptionNotification {
    
    public function __construct() {
        self::init();
    }
    
    public static function init() {
        add_action('windrose_subscription_main_order_activated', [__CLASS__, 'notify_subscription_activated'], 10, 1);
        add_action('windrose_subscription_order_executed_successfully', [__CLASS__, 'notify_subscription_order_processed'], 10, 1);
        add_action('windrose_subscription_order_execution_failed', [__CLASS__, 'notify_subscription_order_failed'], 10, 2);
        add_action('windrose_subscription_main_order_paused', [__CLASS__, 'notify_subscription_paused'], 10, 1);
        add_action('windrose_subscription_main_order_cancelled', [__CLASS__, 'notify_subscription_cancelled'], 10, 1);
        add_action('windrose_subscription_order_skipped', [__CLASS__, 'notify_subscription_skipped'], 10, 1);
        add_action('windrose_subscription_email_trigger', function($event, $id, $extra = '') {
            $mailer = WC()->mailer();
            
            switch ($event) {
                case 'activated':
                    // Send customer email
                    $email = $mailer->emails['WindroseSubscriptionActivatedEmail'] ?? null;
                    if ($email) {
                        $email->trigger($id);
                    }
                    // Send admin email
                    $admin_email = $mailer->emails['WindroseSubscriptionAdminActivatedEmail'] ?? null;
                    if ($admin_email) {
                        $admin_email->trigger($id);
                    }
                    break;
                case 'order_processed':
                    $email = $mailer->emails['WindroseSubscriptionOrderProcessedEmail'] ?? null;
                    if ($email) {
                        $email->trigger($id);
                    }
                    break;
                case 'order_failed':
                    // Send customer email
                    $email = $mailer->emails['WindroseSubscriptionOrderFailedEmail'] ?? null;
                    if ($email) {
                        $email->trigger($id, $extra);
                    }
                    // Send admin email
                    $admin_email = $mailer->emails['WindroseSubscriptionAdminOrderFailedEmail'] ?? null;
                    if ($admin_email) {
                        $admin_email->trigger($id, $extra);
                    }
                    break;
                case 'paused':
                    // Send customer email
                    $email = $mailer->emails['WindroseSubscriptionPausedEmail'] ?? null;
                    if ($email) {
                        $email->trigger($id);
                    }
                    // Send admin email
                    $admin_email = $mailer->emails['WindroseSubscriptionAdminPausedEmail'] ?? null;
                    if ($admin_email) {
                        $admin_email->trigger($id);
                    }
                    break;
                case 'cancelled':
                    // Send customer email
                    $email = $mailer->emails['WindroseSubscriptionCancelledEmail'] ?? null;
                    if ($email) {
                        $email->trigger($id);
                    }
                    // Send admin email
                    $admin_email = $mailer->emails['WindroseSubscriptionAdminCancelledEmail'] ?? null;
                    if ($admin_email) {
                        $admin_email->trigger($id);
                    }
                    break;
                case 'skipped':
                    // Send customer email
                    $email = $mailer->emails['WindroseSubscriptionSkippedEmail'] ?? null;
                    if ($email) {
                        $email->trigger($id);
                    }
                    // Send admin email
                    $admin_email = $mailer->emails['WindroseSubscriptionAdminSkippedEmail'] ?? null;
                    if ($admin_email) {
                        $admin_email->trigger($id);
                    }
                    break;
            }
        }, 10, 3);
        
        // Check email status on init
        add_action('init', [__CLASS__, 'check_email_status'], 30);
    }

    public static function notify_subscription_activated($subscription_id) {
        do_action('windrose_subscription_email_trigger', 'activated', $subscription_id);
    }
    public static function notify_subscription_order_processed($subscription_id) {
        do_action('windrose_subscription_email_trigger', 'order_processed', $subscription_id);
    }
    public static function notify_subscription_order_failed($subscription_id, $reason) {
        do_action('windrose_subscription_email_trigger', 'order_failed', $subscription_id, $reason);
    }
    public static function notify_subscription_paused($subscription_id) {
        do_action('windrose_subscription_email_trigger', 'paused', $subscription_id);
    }
    public static function notify_subscription_cancelled($subscription_id) {
        do_action('windrose_subscription_email_trigger', 'cancelled', $subscription_id);
    }
    public static function notify_subscription_skipped($subscription_order_id) {
        do_action('windrose_subscription_email_trigger', 'skipped', $subscription_order_id);
    }

    public static function check_email_status() {
        // This method can be used for email status checking if needed
        // Currently disabled to prevent continuous logging
    }

    public static function test_html_email_directly($subscription_id) {
        // Test if WooCommerce is loaded
        if (!function_exists('WC')) {
            return false;
        }
        
        // Test if mailer is available
        $mailer = WC()->mailer();
        if (!$mailer) {
            return false;
        }
        
        // Test if our email class is registered
        $email = $mailer->emails['WindroseSubscriptionActivatedEmail'] ?? null;
        if (!$email) {
            return false;
        }
        
        // Test HTML content generation
        $html_content = $email->get_content_html();
        
        // Check if content contains HTML tags
        if (strpos($html_content, '<html>') !== false) {
            // HTML email confirmed
        }
        
        $email->trigger($subscription_id);
        return true;
    }

    public static function test_email_system_directly($subscription_id) {
        // Test if WooCommerce is loaded
        if (!function_exists('WC')) {
            return false;
        }
        
        // Test if mailer is available
        $mailer = WC()->mailer();
        if (!$mailer) {
            return false;
        }
        
        // Test if our email class is registered
        $email = $mailer->emails['WindroseSubscriptionActivatedEmail'] ?? null;
        if (!$email) {
            return false;
        }
        
        $email->trigger($subscription_id);
        return true;
    }

    public static function test_subscription_activation_email($subscription_id) {
        self::notify_subscription_activated($subscription_id);
    }

    public static function test_subscription_order_processed_email($subscription_id) {
        self::notify_subscription_order_processed($subscription_id);
    }

    public static function test_subscription_order_failed_email($subscription_id, $reason = 'Test failure reason') {
        self::notify_subscription_order_failed($subscription_id, $reason);
    }

    public static function test_subscription_paused_email($subscription_id) {
        self::notify_subscription_paused($subscription_id);
    }

    public static function test_subscription_cancelled_email($subscription_id) {
        self::notify_subscription_cancelled($subscription_id);
    }

    public static function test_subscription_skipped_email($subscription_order_id) {
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