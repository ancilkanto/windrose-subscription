<?php
namespace WindroseSubscription\Includes\Emails;

if ( ! defined( 'ABSPATH' ) ) {
    // Only exit if we're in a WordPress context
    if (function_exists('add_action')) {
        exit;
    }
}

if (class_exists('WC_Email')) {
class WindroseSubscriptionActivatedEmail extends \WC_Email {
        public function __construct() {
            $this->id             = 'windrose_subscription_activated';
            $this->title          = __( 'Subscription Activated', 'windros-subscription' );
            $this->description    = __( 'This email is sent to the customer when their subscription is activated.', 'windros-subscription' );
            $this->template_html  = 'emails/windrose-subscription-activated.php';
            $this->template_plain = 'emails/plain/windrose-subscription-activated.php';
            $this->template_base  = WINDROS_DIR . 'templates/';
            $this->customer_email = true;
            $this->placeholders   = array();
            
            // Set default heading and subject
            $this->heading = __( 'Your subscription is now active!', 'windros-subscription' );
            $this->subject = __( 'Your subscription is now active', 'windros-subscription' );
            
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
                    'placeholder'   => __( 'اشتراكك نشط الآن!', 'windros-subscription' ),
                    'default'       => __( 'اشتراكك نشط الآن!', 'windros-subscription' )
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
            return __( 'Your subscription is now active', 'windros-subscription' );
        }

        /**
         * Get default heading
         */
        public function get_default_heading() {
            return __( 'Your subscription is now active!', 'windros-subscription' );
        }


        /**
         * Get Arabic heading
         */
        public function get_heading_arabic() {
            return $this->get_option( 'heading_arabic', __( 'اشتراكك نشط الآن!', 'windros-subscription' ) );
        }

        private function ensure_email_enabled() {
            $enabled_emails = get_option('woocommerce_email_settings', array());
            $email_key = 'windrose_subscription_activated';
            
            if (!isset($enabled_emails[$email_key . '_enabled']) || $enabled_emails[$email_key . '_enabled'] !== 'yes') {
                $enabled_emails[$email_key . '_enabled'] = 'yes';
                update_option('woocommerce_email_settings', $enabled_emails);
                error_log('Windrose Subscription: Enabled subscription activated email');
            }
            
            // Set default heading and subject in WooCommerce settings
            if (!isset($enabled_emails[$email_key . '_heading']) || empty($enabled_emails[$email_key . '_heading'])) {
                $enabled_emails[$email_key . '_heading'] = __( 'Your subscription is now active!', 'windros-subscription' );
                update_option('woocommerce_email_settings', $enabled_emails);
                error_log('Windrose Subscription: Set default heading for activated email');
            }
            
            if (!isset($enabled_emails[$email_key . '_subject']) || empty($enabled_emails[$email_key . '_subject'])) {
                $enabled_emails[$email_key . '_subject'] = __( 'Your subscription is now active', 'windros-subscription' );
                update_option('woocommerce_email_settings', $enabled_emails);
                error_log('Windrose Subscription: Set default subject for activated email');
            }
        }

        public function trigger( $subscription_id ) {
            error_log('Windrose Subscription: Email trigger method called for subscription ID: ' . $subscription_id);
            if ( !$subscription_id ) {
                error_log('Windrose Subscription: No subscription ID provided');
                return;
            }
            global $wpdb;
            $subscription_table = $wpdb->prefix . (defined('WINDROS_SUBSCRIPTION_MAIN_TABLE') ? WINDROS_SUBSCRIPTION_MAIN_TABLE : 'windrose_subscription_main');
            $subscription = $wpdb->get_row($wpdb->prepare("SELECT * FROM $subscription_table WHERE id = %d", $subscription_id));
            if (!$subscription) {
                error_log('Windrose Subscription: No subscription found for ID: ' . $subscription_id);
                return;
            }
            $user = get_userdata($subscription->user_id);
            $this->recipient = $user ? $user->user_email : '';
            $this->object = $subscription;
            
            // Set heading and subject (English and Arabic)
            $this->heading = $this->get_heading();
            $this->subject = $this->get_subject();
            
            error_log('Windrose Subscription: Email recipient: ' . $this->recipient);
            error_log('Windrose Subscription: Email enabled: ' . ($this->is_enabled() ? 'Yes' : 'No'));
            error_log('Windrose Subscription: Email heading: ' . $this->heading);
            error_log('Windrose Subscription: Email subject: ' . $this->subject);
            if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
                error_log('Windrose Subscription: Email not sent - disabled or no recipient');
                return;
            }
            error_log('Windrose Subscription: Sending email to: ' . $this->get_recipient());
            
            // Force HTML content type
            add_filter('wp_mail_content_type', function() {
                return 'text/html';
            });
            
            $result = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
            error_log('Windrose Subscription: Email send result: ' . ($result ? 'Success' : 'Failed'));
            
            // Remove the filter after sending
            remove_all_filters('wp_mail_content_type');
        }

        public function get_headers() {
            $headers = parent::get_headers();
            
            // Ensure headers is an array
            if (!is_array($headers)) {
                $headers = array();
            }
            
            // Ensure HTML content type
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            error_log('Windrose Subscription: Email headers set to HTML: ' . print_r($headers, true));
            return $headers;
        }

        public function get_content_html() {
            $template_path = windrose_get_email_template_path();
            error_log('Windrose Subscription: HTML template path: ' . $template_path . $this->template_html);
            error_log('Windrose Subscription: Current heading: ' . $this->heading);
            error_log('Windrose Subscription: Current subject: ' . $this->subject);
            
            $content = wc_get_template_html( $this->template_html, array(
                'subscription' => $this->object,
                'email_heading' => $this->get_heading(),
                'email_heading_arabic' => $this->get_heading_arabic(),
                'email' => $this,
            ), '', $template_path );
            
            error_log('Windrose Subscription: Generated HTML content length: ' . strlen($content));
            return $content;
        }

        public function get_content_plain() {
            $template_path = windrose_get_email_template_path();
            error_log('Windrose Subscription: Plain template path: ' . $template_path . $this->template_plain);
            return wc_get_template_html( $this->template_plain, array(
                'subscription' => $this->object,
                'email_heading' => $this->get_heading(),
                'email' => $this,
            ), '', $template_path );
        }
    }
}