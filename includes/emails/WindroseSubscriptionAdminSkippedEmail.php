<?php
namespace WindroseSubscription\Includes\Emails;

if ( ! defined( 'ABSPATH' ) ) {
    // Only exit if we're in a WordPress context
    if (function_exists('add_action')) {
        exit;
    }
}

if (class_exists('WC_Email')) {
class WindroseSubscriptionAdminSkippedEmail extends \WC_Email {
        public function __construct() {
            $this->id             = 'windrose_subscription_admin_skipped';
            $this->title          = __( 'Subscription Skipped (Admin)', 'windros-subscription' );
            $this->description    = __( 'This email is sent to the admin when a subscription order is skipped.', 'windros-subscription' );
            $this->template_html  = 'emails/windrose-subscription-admin-skipped.php';
            $this->template_plain = 'emails/plain/windrose-subscription-admin-skipped.php';
            $this->customer_email = false; // This is an admin email
            $this->placeholders   = array();
            
            // Set default heading and subject
            $this->heading = __( 'Subscription order skipped', 'windros-subscription' );
            $this->subject = __( 'Subscription order skipped', 'windros-subscription' );
            
            parent::__construct();
            
            // Ensure the email is enabled
            $this->ensure_email_enabled();
        }

        private function ensure_email_enabled() {
            $enabled_emails = get_option('woocommerce_email_settings', array());
            $email_key = 'windrose_subscription_admin_skipped';
            
            if (!isset($enabled_emails[$email_key . '_enabled']) || $enabled_emails[$email_key . '_enabled'] !== 'yes') {
                $enabled_emails[$email_key . '_enabled'] = 'yes';
                update_option('woocommerce_email_settings', $enabled_emails);
                error_log('Windrose Subscription: Enabled subscription admin skipped email');
            }
        }

        public function trigger( $subscription_order_id ) {
            error_log('Windrose Subscription: Admin skipped email trigger method called for subscription order ID: ' . $subscription_order_id);
            if ( !$subscription_order_id ) return;
            global $wpdb;
            $subscription_order_table = $wpdb->prefix . (defined('WINDROS_SUBSCRIPTION_ORDER_TABLE') ? WINDROS_SUBSCRIPTION_ORDER_TABLE : 'windrose_subscription_order');
            $subscription_order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $subscription_order_table WHERE id = %d", $subscription_order_id));
            if (!$subscription_order) {
                error_log('Windrose Subscription: No subscription order found for admin skipped email ID: ' . $subscription_order_id);
                return;
            }
            
            // Get the subscription details
            $subscription_table = $wpdb->prefix . (defined('WINDROS_SUBSCRIPTION_MAIN_TABLE') ? WINDROS_SUBSCRIPTION_MAIN_TABLE : 'windrose_subscription_main');
            $subscription = $wpdb->get_row($wpdb->prepare("SELECT * FROM $subscription_table WHERE id = %d", $subscription_order->subscription_id));
            if (!$subscription) {
                error_log('Windrose Subscription: No subscription found for admin skipped email order ID: ' . $subscription_order_id);
                return;
            }
            
            // Set admin email as recipient
            $this->recipient = get_option('admin_email');
            $this->object = $subscription;
            $this->subscription_order = $subscription_order;
            
            error_log('Windrose Subscription: Admin skipped email recipient: ' . $this->recipient);
            error_log('Windrose Subscription: Admin skipped email enabled: ' . ($this->is_enabled() ? 'Yes' : 'No'));
            if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
                error_log('Windrose Subscription: Admin skipped email not sent - disabled or no recipient');
                return;
            }
            error_log('Windrose Subscription: Sending admin skipped email to: ' . $this->get_recipient());
            $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }

        public function get_content_html() {
            $template_path = windrose_get_email_template_path();
            return wc_get_template_html( $this->template_html, array(
                'subscription' => $this->object,
                'subscription_order' => $this->subscription_order,
                'email_heading' => $this->get_heading(),
                'email' => $this,
            ), '', $template_path );
        }

        public function get_content_plain() {
            $template_path = windrose_get_email_template_path();
            return wc_get_template_html( $this->template_plain, array(
                'subscription' => $this->object,
                'subscription_order' => $this->subscription_order,
                'email_heading' => $this->get_heading(),
                'email' => $this,
            ), '', $template_path );
        }
    }
}