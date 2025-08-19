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
            $this->template_base  = WINDROS_DIR . 'templates/';
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
            $subscription_order = (object) [
                'id'             => 999,
                'subscription_id' => 888,
                'user_id'        => get_current_user_id(),
                'product_id'     => wc_get_products(['limit' => 1 ])[0]->get_id() ?? 0,
                'quantity'       => 1,
                'created_at'     => current_time('mysql'),
                'updated_at'     => current_time('mysql'),
            ];
        
            ob_start();
            wc_get_template(
                $this->template_html,
                [
                    'subscription_order' => $subscription_order,
                    'email_heading'      => $this->get_heading(),
                    'email'              => $this,
                ],
                '', // keep empty to let WC search
                $this->template_base // full path
            );
            return ob_get_clean();
        }

        public function get_content_plain() {
            $subscription_order = (object) [
                'id'             => 999,
                'subscription_id' => 888,
                'user_id'        => get_current_user_id(),
                'product_id'     => wc_get_products(['limit' => 1 ])[0]->get_id() ?? 0,
                'quantity'       => 1,
                'created_at'     => current_time('mysql'),
                'updated_at'     => current_time('mysql'),
            ];
        
            ob_start();
            wc_get_template(
                $this->template_plain,
                [
                    'subscription_order' => $subscription_order,
                    'email_heading'      => $this->get_heading(),
                    'email'              => $this,
                ],
                '', // keep empty to let WC search
                $this->template_base // full path
            );
            return ob_get_clean();
        }

        public function get_content() {
            return $this->get_content_html();
        }

        public function setup_locale() {
            parent::setup_locale();
            
            // Set up sample data for previews if no object is set
            if (!$this->object) {
                $this->object = (object) array(
                    'id' => 123,
                    'user_id' => 1,
                    'product_id' => 1,
                    'quantity' => 2,
                    'schedule' => '7',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                );
            }
            
            // Set up sample subscription order if not set
            if (!isset($this->subscription_order)) {
                $this->subscription_order = (object) array(
                    'id' => 456,
                    'subscription_id' => 123,
                    'user_id' => 1,
                    'product_id' => 1,
                    'quantity' => 2,
                    'status' => 'skipped'
                );
            }
        }
    }
}