<?php
namespace WindroseSubscription\Includes\Emails;

if ( ! defined( 'ABSPATH' ) ) {
    // Only exit if we're in a WordPress context
    if (function_exists('add_action')) {
        exit;
    }
}

if (class_exists('WC_Email')) {
class WindroseSubscriptionSkippedEmail extends \WC_Email {
        public function __construct() {
            $this->id             = 'windrose_subscription_skipped';
            $this->title          = __( 'Subscription Skipped', 'windros-subscription' );
            $this->description    = __( 'This email is sent to the customer when their subscription order is skipped.', 'windros-subscription' );
            $this->template_html  = 'emails/windrose-subscription-skipped.php';
            $this->template_plain = 'emails/plain/windrose-subscription-skipped.php';
            $this->template_base  = WINDROS_DIR . 'templates/';
            $this->customer_email = true;
            $this->placeholders   = array();
            
            // Set default heading and subject
            $this->heading = __( 'Your subscription order has been skipped', 'windros-subscription' );
            $this->subject = __( 'Your subscription order has been skipped', 'windros-subscription' );
            
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
                'subject' => array(
                    'title'         => __( 'Subject', 'windros-subscription' ),
                    'type'          => 'text',
                    'description'   => __( 'This controls the email subject line. Leave blank to use the default subject.', 'windros-subscription' ),
                    'placeholder'   => $this->get_default_subject(),
                    'default'       => ''
                ),
                'heading' => array(
                    'title'         => __( 'Email Heading', 'windros-subscription' ),
                    'type'          => 'text',
                    'description'   => __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading.', 'windros-subscription' ),
                    'placeholder'   => $this->get_default_heading(),
                    'default'       => ''
                ),
                'heading_arabic' => array(
                    'title'         => __( 'Email Heading (Arabic)', 'windros-subscription' ),
                    'type'          => 'text',
                    'description'   => __( 'Arabic version of the email heading.', 'windros-subscription' ),
                    'placeholder'   => __( 'تم تخطي طلب اشتراكك', 'windros-subscription' ),
                    'default'       => __( 'تم تخطي طلب اشتراكك', 'windros-subscription' )
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
            return __( 'Your subscription order has been skipped', 'windros-subscription' );
        }

        /**
         * Get default heading
         */
        public function get_default_heading() {
            return __( 'Your subscription order has been skipped', 'windros-subscription' );
        }

        /**
         * Get Arabic heading
         */
        public function get_heading_arabic() {
            return $this->get_option( 'heading_arabic', __( 'تم تخطي طلب اشتراكك', 'windros-subscription' ) );
        }

        private function ensure_email_enabled() {
            $enabled_emails = get_option('woocommerce_email_settings', array());
            if (!isset($enabled_emails['windrose_subscription_skipped_enabled']) || $enabled_emails['windrose_subscription_skipped_enabled'] !== 'yes') {
                $enabled_emails['windrose_subscription_skipped_enabled'] = 'yes';
                update_option('woocommerce_email_settings', $enabled_emails);
                error_log('Windrose Subscription: Enabled subscription skipped email');
            }
        }

        public function trigger( $subscription_order_id ) {
            error_log('Windrose Subscription: Skipped email trigger method called for subscription order ID: ' . $subscription_order_id);
            if ( !$subscription_order_id ) return;
            global $wpdb;
            $subscription_order_table = $wpdb->prefix . (defined('WINDROS_SUBSCRIPTION_ORDER_TABLE') ? WINDROS_SUBSCRIPTION_ORDER_TABLE : 'windrose_subscription_order');
            $subscription_order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $subscription_order_table WHERE id = %d", $subscription_order_id));
            if (!$subscription_order) {
                error_log('Windrose Subscription: No subscription order found for skipped email ID: ' . $subscription_order_id);
                return;
            }
            
            // Get the subscription details
            $subscription_table = $wpdb->prefix . (defined('WINDROS_SUBSCRIPTION_MAIN_TABLE') ? WINDROS_SUBSCRIPTION_MAIN_TABLE : 'windrose_subscription_main');
            $subscription = $wpdb->get_row($wpdb->prepare("SELECT * FROM $subscription_table WHERE id = %d", $subscription_order->subscription_id));
            if (!$subscription) {
                error_log('Windrose Subscription: No subscription found for skipped email order ID: ' . $subscription_order_id);
                return;
            }
            
            $user = get_userdata($subscription->user_id);
            $this->recipient = $user ? $user->user_email : '';
            $this->object = $subscription;
            $this->subscription_order = $subscription_order;
            error_log('Windrose Subscription: Skipped email recipient: ' . $this->recipient);
            error_log('Windrose Subscription: Skipped email enabled: ' . ($this->is_enabled() ? 'Yes' : 'No'));
            if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
                error_log('Windrose Subscription: Skipped email not sent - disabled or no recipient');
                return;
            }
            error_log('Windrose Subscription: Sending skipped email to: ' . $this->get_recipient());
            $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }

        public function get_content_html() {
            $template_path = windrose_get_email_template_path();
            return wc_get_template_html( $this->template_html, array(
                'subscription' => $this->object,
                'subscription_order' => $this->subscription_order,
                'email_heading' => $this->get_heading(),
                'email_heading_arabic' => $this->get_heading_arabic(),
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