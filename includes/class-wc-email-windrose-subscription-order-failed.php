<?php
namespace WindroseSubscription\Includes;

if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Email_Windrose_Subscription_Order_Failed extends \WC_Email {
    public $fail_reason = '';
    public function __construct() {
        $this->id             = 'windrose_subscription_order_failed';
        $this->title          = __( 'Subscription Order Failed', 'windros-subscription' );
        $this->description    = __( 'This email is sent to the customer when their subscription order payment fails.', 'windros-subscription' );
        $this->template_html  = 'emails/windrose-subscription-order-failed.php';
        $this->template_plain = 'emails/plain/windrose-subscription-order-failed.php';
        $this->customer_email = true;
        $this->placeholders   = array();
        
        // Set default heading and subject
        $this->heading = __( 'Your subscription order payment failed', 'windros-subscription' );
        $this->subject = __( 'Your subscription order payment failed', 'windros-subscription' );
        
        parent::__construct();
        
        // Ensure the email is enabled
        $this->ensure_email_enabled();
    }

    private function ensure_email_enabled() {
        $enabled_emails = get_option('woocommerce_email_settings', array());
        if (!isset($enabled_emails['windrose_subscription_order_failed_enabled']) || $enabled_emails['windrose_subscription_order_failed_enabled'] !== 'yes') {
            $enabled_emails['windrose_subscription_order_failed_enabled'] = 'yes';
            update_option('woocommerce_email_settings', $enabled_emails);
            error_log('Windrose Subscription: Enabled subscription order failed email');
        }
    }

    public function trigger( $subscription_id, $reason = '' ) {
        error_log('Windrose Subscription: Order failed email trigger method called for subscription ID: ' . $subscription_id . ', reason: ' . $reason);
        if ( !$subscription_id ) return;
        global $wpdb;
        $subscription_table = $wpdb->prefix . (defined('WINDROS_SUBSCRIPTION_MAIN_TABLE') ? WINDROS_SUBSCRIPTION_MAIN_TABLE : 'windrose_subscription_main');
        $subscription = $wpdb->get_row($wpdb->prepare("SELECT * FROM $subscription_table WHERE id = %d", $subscription_id));
        if (!$subscription) {
            error_log('Windrose Subscription: No subscription found for order failed email ID: ' . $subscription_id);
            return;
        }
        $user = get_userdata($subscription->user_id);
        $this->recipient = $user ? $user->user_email : '';
        $this->object = $subscription;
        $this->placeholders['{reason}'] = $reason;
        error_log('Windrose Subscription: Order failed email recipient: ' . $this->recipient);
        error_log('Windrose Subscription: Order failed email enabled: ' . ($this->is_enabled() ? 'Yes' : 'No'));
        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
            error_log('Windrose Subscription: Order failed email not sent - disabled or no recipient');
            return;
        }
        error_log('Windrose Subscription: Sending order failed email to: ' . $this->get_recipient());
        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
    }

    public function get_content_html() {
        $template_path = plugin_dir_path(__DIR__) . 'templates/';
        return wc_get_template_html( $this->template_html, array(
            'subscription' => $this->object,
            'email_heading' => $this->get_heading(),
            'email' => $this,
        ), '', $template_path );
    }

    public function get_content_plain() {
        $template_path = plugin_dir_path(__DIR__) . 'templates/';
        return wc_get_template_html( $this->template_plain, array(
            'subscription' => $this->object,
            'email_heading' => $this->get_heading(),
            'email' => $this,
        ), '', $template_path );
    }
} 