<?php
namespace WindroseSubscription\Includes\Emails;

if ( ! defined( 'ABSPATH' ) ) {
    // Only exit if we're in a WordPress context
    if (function_exists('add_action')) {
        exit;
    }
}

if (class_exists('WC_Email')) {
class WindroseSubscriptionAdminOrderFailedEmail extends \WC_Email {
        public function __construct() {
            $this->id             = 'windrose_subscription_admin_order_failed';
            $this->title          = __( 'Subscription Order Failed (Admin)', 'windros-subscription' );
            $this->description    = __( 'This email is sent to the admin when a subscription order payment fails.', 'windros-subscription' );
            $this->template_html  = 'emails/windrose-subscription-admin-order-failed.php';
            $this->template_plain = 'emails/plain/windrose-subscription-admin-order-failed.php';
            $this->template_base  = WINDROS_DIR . 'templates/';
            $this->customer_email = false; // This is an admin email
            $this->placeholders   = array();
            
            // Set default heading and subject
            $this->heading = __( 'Subscription order payment failed', 'windros-subscription' );
            $this->subject = __( 'Subscription order payment failed', 'windros-subscription' );
            
            parent::__construct();
            
            // Ensure the email is enabled
            $this->ensure_email_enabled();
        }

        private function ensure_email_enabled() {
            $enabled_emails = get_option('woocommerce_email_settings', array());
            $email_key = 'windrose_subscription_admin_order_failed';
            
            if (!isset($enabled_emails[$email_key . '_enabled']) || $enabled_emails[$email_key . '_enabled'] !== 'yes') {
                $enabled_emails[$email_key . '_enabled'] = 'yes';
                update_option('woocommerce_email_settings', $enabled_emails);
                error_log('Windrose Subscription: Enabled subscription admin order failed email');
            }
        }

        public function trigger( $subscription_id, $reason = '' ) {
            error_log('Windrose Subscription: Admin order failed email trigger method called for subscription ID: ' . $subscription_id);
            if ( !$subscription_id ) return;
            global $wpdb;
            $subscription_table = $wpdb->prefix . (defined('WINDROS_SUBSCRIPTION_MAIN_TABLE') ? WINDROS_SUBSCRIPTION_MAIN_TABLE : 'windrose_subscription_main');
            $subscription = $wpdb->get_row($wpdb->prepare("SELECT * FROM $subscription_table WHERE id = %d", $subscription_id));
            if (!$subscription) {
                error_log('Windrose Subscription: No subscription found for admin order failed email ID: ' . $subscription_id);
                return;
            }
            
            // Set admin email as recipient
            $this->recipient = get_option('admin_email');
            $this->object = $subscription;
            $this->reason = $reason;
            
            error_log('Windrose Subscription: Admin order failed email recipient: ' . $this->recipient);
            error_log('Windrose Subscription: Admin order failed email enabled: ' . ($this->is_enabled() ? 'Yes' : 'No'));
            if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
                error_log('Windrose Subscription: Admin order failed email not sent - disabled or no recipient');
                return;
            }
            error_log('Windrose Subscription: Sending admin order failed email to: ' . $this->get_recipient());
            $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }

        public function get_content_html() {
            return wc_get_template_html( $this->template_html, array(
                'subscription' => $this->object,
                'email_heading' => $this->get_heading(),
                'email' => $this,
                'reason' => $this->reason,
            ) );
        }

        public function get_content_plain() {
            return wc_get_template_html( $this->template_plain, array(
                'subscription' => $this->object,
                'email_heading' => $this->get_heading(),
                'email' => $this,
                'reason' => $this->reason,
            ) );
        }
    }
}