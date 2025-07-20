<?php
namespace WindroseSubscription\Includes;

defined( 'WINDROS_INIT' ) || exit;

class WindroseSubscriptionNotification {
    public static function init() {
        add_action('windrose_subscription_main_order_activated', [__CLASS__, 'notify_subscription_activated'], 10, 1);
        add_action('windrose_subscription_order_executed_successfully', [__CLASS__, 'notify_subscription_order_processed'], 10, 1);
        add_action('windrose_subscription_order_execution_failed', [__CLASS__, 'notify_subscription_order_failed'], 10, 2);
        add_action('windrose_subscription_main_order_paused', [__CLASS__, 'notify_subscription_paused'], 10, 1);
        add_action('windrose_subscription_main_order_cancelled', [__CLASS__, 'notify_subscription_cancelled'], 10, 1);
        add_action('windrose_subscription_order_skipped', [__CLASS__, 'notify_subscription_skipped'], 10, 1);
    }

    public static function notify_subscription_activated($subscription_id) {
        self::send_subscription_email($subscription_id, 'activated');
    }
    public static function notify_subscription_order_processed($subscription_id) {
        self::send_subscription_email($subscription_id, 'order_processed');
    }
    public static function notify_subscription_order_failed($subscription_id, $reason) {
        self::send_subscription_email($subscription_id, 'order_failed', $reason);
    }
    public static function notify_subscription_paused($subscription_id) {
        self::send_subscription_email($subscription_id, 'paused');
    }
    public static function notify_subscription_cancelled($subscription_id) {
        self::send_subscription_email($subscription_id, 'cancelled');
    }
    public static function notify_subscription_skipped($subscription_order_id) {
        self::send_subscription_email($subscription_order_id, 'skipped');
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
                $message = sprintf(__('Hello %s,\n\nPayment for your subscription order for %s has failed. Reason: %s', 'windros-subscription'), $customer_name, $product_name, $extra);
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
        $admin_subject = '[Admin] ' . $subject;
        $admin_message = $message . "\n\nCustomer: $customer_name <$customer_email>\nSubscription ID: $subscription->id";
        \wp_mail($admin_email, $admin_subject, $admin_message);
    }
} 