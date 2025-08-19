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
            $this->template_base  = WINDROS_DIR . 'templates/';
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
                
            }
        }

        public function trigger( $subscription_id ) {
            
            if ( !$subscription_id ) return;
            global $wpdb;
            $subscription_table = $wpdb->prefix . (defined('WINDROS_SUBSCRIPTION_MAIN_TABLE') ? WINDROS_SUBSCRIPTION_MAIN_TABLE : 'windrose_subscription_main');
            $subscription = $wpdb->get_row($wpdb->prepare("SELECT * FROM $subscription_table WHERE id = %d", $subscription_id));
            if (!$subscription) {
                
                return;
            }
            
            // Set admin email as recipient
            $this->recipient = get_option('admin_email');
            $this->object = $subscription;
            
            // Explicitly set heading and subject
            $this->heading = __( 'New subscription activated', 'windros-subscription' );
            $this->subject = __( 'New subscription activated', 'windros-subscription' );
            
            
            
            if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
                
                return;
            }
            
            
            // Force HTML content type
            add_filter('wp_mail_content_type', function() {
                return 'text/html';
            });
            
            $result = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
            
            
            // Remove the filter after sending
            remove_all_filters('wp_mail_content_type');
        }

        public function get_content_html() {
            // Generate safe sample data for previews
            $products = wc_get_products(['limit' => 1, 'status' => 'publish']);
            $product_id = !empty($products) ? $products[0]->get_id() : 1;
            $user_id = get_current_user_id() ?: 1;
            
            $subscription = (object) [
                'id'         => 999,
                'user_id'    => $user_id,
                'product_id' => $product_id,
                'quantity'   => 1,
                'created_at' => current_time('mysql'),
            ];
        
            ob_start();
            wc_get_template(
                $this->template_html,
                [
                    'subscription'  => $subscription,
                    'email_heading' => $this->get_heading(),
                    'email'         => $this,
                ],
                '', // keep empty to let WC search
                $this->template_base // full path
            );
            return ob_get_clean();
        }

        public function get_content_plain() {
            // Generate safe sample data for previews
            $products = wc_get_products(['limit' => 1, 'status' => 'publish']);
            $product_id = !empty($products) ? $products[0]->get_id() : 1;
            $user_id = get_current_user_id() ?: 1;
            
            $subscription = (object) [
                'id'         => 999,
                'user_id'    => $user_id,
                'product_id' => $product_id,
                'quantity'   => 1,
                'created_at' => current_time('mysql'),
            ];
        
            ob_start();
            wc_get_template(
                $this->template_plain,
                [
                    'subscription'  => $subscription,
                    'email_heading' => $this->get_heading(),
                    'email'         => $this,
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
        }
    }
}