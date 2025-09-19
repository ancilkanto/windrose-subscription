<?php
namespace WindroseSubscription\Includes\Emails;

if ( ! defined( 'ABSPATH' ) ) {
    // Only exit if we're in a WordPress context
    if (function_exists('add_action')) {
        exit;
    }
}

if (class_exists('WC_Email')) {
    class WindroseSubscriptionAdminPausedEmail extends \WC_Email {
        public function __construct() {
            $this->id             = 'windrose_subscription_admin_paused';
            $this->title          = __( 'Subscription Paused (Admin)', 'windros-subscription' );
            $this->description    = __( 'This email is sent to the admin when a subscription is paused.', 'windros-subscription' );
            $this->template_html  = 'emails/windrose-subscription-admin-paused.php';
            $this->template_plain = 'emails/plain/windrose-subscription-admin-paused.php';
            $this->template_base  = WINDROS_DIR . 'templates/';
            $this->customer_email = false; // This is an admin email
            $this->placeholders   = array();
            
            // Set default heading and subject
            $this->heading = __( 'Subscription paused', 'windros-subscription' );
            $this->subject = __( 'Subscription paused', 'windros-subscription' );
            
            parent::__construct();
            
            // Ensure the email is enabled
            $this->ensure_email_enabled();
        }

        /**
         * Initialize form fields for WooCommerce email settings
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'         => __( 'Enable/Disable', 'windros-subscription' ),
                    'type'          => 'checkbox',
                    'label'         => __( 'Enable this email notification', 'windros-subscription' ),
                    'default'       => 'yes'
                ),
                'recipient' => array(
                    'title'         => __( 'Recipient(s)', 'windros-subscription' ),
                    'type'          => 'text',
                    'description'   => __( 'Enter recipients (comma separated) for this email. Defaults to admin email.', 'windros-subscription' ),
                    'placeholder'   => get_option('admin_email'),
                    'default'       => get_option('admin_email'),
                    'desc_tip'    => true,
                ),
                'subject' => array(
                    'title'         => __( 'Subject', 'windros-subscription' ),
                    'type'          => 'text',
                    'description'   => __( 'This controls the email subject line. Leave blank to use the default subject.', 'windros-subscription' ),
                    'placeholder'   => $this->get_default_subject(),
                    'default'       => '',
                    'desc_tip'    => true,
                ),
                'heading' => array(
                    'title'         => __( 'Email Heading', 'windros-subscription' ),
                    'type'          => 'text',
                    'description'   => __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading.', 'windros-subscription' ),
                    'placeholder'   => $this->get_default_heading(),
                    'default'       => '',
                    'desc_tip'    => true,
                ),
                'email_type' => array(
                    'title'         => __( 'Email type', 'windros-subscription' ),
                    'type'          => 'select',
                    'description'   => __( 'Choose which format of email to send.', 'windros-subscription' ),
                    'default'       => 'html',
                    'class'         => 'email_type wc-enhanced-select',
                    'options'       => $this->get_email_type_options()
                )
            );
        }

        /**
         * Get default subject
         */
        public function get_default_subject() {
            return __( 'Subscription paused', 'windros-subscription' );
        }

        /**
         * Get default heading
         */
        public function get_default_heading() {
            return __( 'Subscription paused', 'windros-subscription' );
        }

        /**
         * Get recipient email addresses
         * Handles comma-separated email addresses
         */
        public function get_recipient() {
            $recipient = $this->get_option('recipient', get_option('admin_email'));
            
            // If recipient is empty, fallback to admin email
            if (empty($recipient)) {
                $recipient = get_option('admin_email');
            }
            
            return $recipient;
        }

        private function ensure_email_enabled() {
            $enabled_emails = get_option('woocommerce_email_settings', array());
            $email_key = 'windrose_subscription_admin_paused';
            
            if (!isset($enabled_emails[$email_key . '_enabled']) || $enabled_emails[$email_key . '_enabled'] !== 'yes') {
                $enabled_emails[$email_key . '_enabled'] = 'yes';
                update_option('woocommerce_email_settings', $enabled_emails);
                error_log('Windrose Subscription: Enabled subscription admin paused email');
            }
        }

        public function trigger( $subscription_id ) {
            error_log('Windrose Subscription: Admin paused email trigger method called for subscription ID: ' . $subscription_id);
            if ( !$subscription_id ) return;
            global $wpdb;
            $subscription_table = $wpdb->prefix . (defined('WINDROS_SUBSCRIPTION_MAIN_TABLE') ? WINDROS_SUBSCRIPTION_MAIN_TABLE : 'windrose_subscription_main');
            $subscription = $wpdb->get_row($wpdb->prepare("SELECT * FROM $subscription_table WHERE id = %d", $subscription_id));
            if (!$subscription) {
                error_log('Windrose Subscription: No subscription found for admin paused email ID: ' . $subscription_id);
                return;
            }
            
            // Set recipient from settings or fallback to admin email
            $this->recipient = $this->get_recipient();
            $this->object = $subscription;
            
            error_log('Windrose Subscription: Admin paused email recipient: ' . $this->recipient);
            error_log('Windrose Subscription: Admin paused email enabled: ' . ($this->is_enabled() ? 'Yes' : 'No'));
            if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
                error_log('Windrose Subscription: Admin paused email not sent - disabled or no recipient');
                return;
            }
            error_log('Windrose Subscription: Sending admin paused email to: ' . $this->get_recipient());
            $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
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
                'updated_at' => current_time('mysql'),
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
                'updated_at' => current_time('mysql'),
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