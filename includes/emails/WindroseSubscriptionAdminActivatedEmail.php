<?php
namespace WindroseSubscription\Includes\Emails;

if ( ! defined( 'ABSPATH' ) ) {
    // Only exit if we're in a WordPress context
    if (function_exists('add_action')) {
        exit;
    }
}

if (class_exists('WC_Email')) {
class WindroseSubscriptionAdminActivatedEmail extends \WC_Email {
        public function __construct() {
            $this->id             = 'windrose_subscription_admin_activated';
            $this->title          = __( 'Subscription Activated (Admin)', 'windros-subscription' );
            $this->description    = __( 'This email is sent to the admin when a subscription is activated.', 'windros-subscription' );
            $this->template_html  = 'emails/windrose-subscription-admin-activated.php';
            $this->template_plain = 'emails/plain/windrose-subscription-admin-activated.php';
            $this->customer_email = false; // This is an admin email
            $this->placeholders   = array();
            
            // Set default heading and subject
            $this->heading = __( 'New subscription activated', 'windros-subscription' );
            $this->subject = __( 'New subscription activated', 'windros-subscription' );
            
            parent::__construct();
            
            // Ensure the email is enabled
            $this->ensure_email_enabled();
        }

        private function ensure_email_enabled() {
            $enabled_emails = get_option('woocommerce_email_settings', array());
            $email_key = 'windrose_subscription_admin_activated';
            
            if (!isset($enabled_emails[$email_key . '_enabled']) || $enabled_emails[$email_key . '_enabled'] !== 'yes') {
                $enabled_emails[$email_key . '_enabled'] = 'yes';
                update_option('woocommerce_email_settings', $enabled_emails);
                error_log('Windrose Subscription: Enabled subscription admin activated email');
            }
        }

        public function trigger( $subscription_id ) {
            error_log('Windrose Subscription: Admin activated email trigger method called for subscription ID: ' . $subscription_id);
            if ( !$subscription_id ) return;
            global $wpdb;
            $subscription_table = $wpdb->prefix . (defined('WINDROS_SUBSCRIPTION_MAIN_TABLE') ? WINDROS_SUBSCRIPTION_MAIN_TABLE : 'windrose_subscription_main');
            $subscription = $wpdb->get_row($wpdb->prepare("SELECT * FROM $subscription_table WHERE id = %d", $subscription_id));
            if (!$subscription) {
                error_log('Windrose Subscription: No subscription found for admin activated email ID: ' . $subscription_id);
                return;
            }
            
            // Set admin email as recipient
            $this->recipient = get_option('admin_email');
            $this->object = $subscription;
            
            // Explicitly set heading and subject
            $this->heading = __( 'New subscription activated', 'windros-subscription' );
            $this->subject = __( 'New subscription activated', 'windros-subscription' );
            
            error_log('Windrose Subscription: Admin activated email recipient: ' . $this->recipient);
            error_log('Windrose Subscription: Admin activated email enabled: ' . ($this->is_enabled() ? 'Yes' : 'No'));
            if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
                error_log('Windrose Subscription: Admin activated email not sent - disabled or no recipient');
                return;
            }
            error_log('Windrose Subscription: Sending admin activated email to: ' . $this->get_recipient());
            
            // Force HTML content type
            add_filter('wp_mail_content_type', function() {
                return 'text/html';
            });
            
            $result = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
            error_log('Windrose Subscription: Admin activated email send result: ' . ($result ? 'Success' : 'Failed'));
            
            // Remove the filter after sending
            remove_all_filters('wp_mail_content_type');
        }

        public function get_content_html() {
            $template_path = windrose_get_email_template_path();
            return wc_get_template_html( $this->template_html, array(
                'subscription' => $this->object,
                'email_heading' => $this->get_heading(),
                'email' => $this,
            ), '', $template_path );
        }

        public function get_content_plain() {
            $template_path = windrose_get_email_template_path();
            return wc_get_template_html( $this->template_plain, array(
                'subscription' => $this->object,
                'email_heading' => $this->get_heading(),
                'email' => $this,
            ), '', $template_path );
        }
    }
}