<?php
namespace WindroseSubscription\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    // Only exit if we're in a WordPress context
    if (function_exists('add_action')) {
        exit;
    }
}

if (class_exists('WC_Email')) {
    if (class_exists('WC_Email')) {
    class WC_Email_Windrose_Subscription_Admin_Activated extends \WC_Email {
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
            update_option('woocommerce_email_settings', $enabled_emails);}
        
        // Set default heading and subject in WooCommerce settings
        if (!isset($enabled_emails[$email_key . '_heading']) || empty($enabled_emails[$email_key . '_heading'])) {
            $enabled_emails[$email_key . '_heading'] = __( 'New subscription activated', 'windros-subscription' );
            update_option('woocommerce_email_settings', $enabled_emails);}
        
        if (!isset($enabled_emails[$email_key . '_subject']) || empty($enabled_emails[$email_key . '_subject'])) {
            $enabled_emails[$email_key . '_subject'] = __( 'New subscription activated', 'windros-subscription' );
            update_option('woocommerce_email_settings', $enabled_emails);}
    }

    public function trigger( $subscription_id ) {if ( !$subscription_id ) {return;
        }
        global $wpdb;
        $subscription_table = $wpdb->prefix . (defined('WINDROS_SUBSCRIPTION_MAIN_TABLE') ? WINDROS_SUBSCRIPTION_MAIN_TABLE : 'windrose_subscription_main');
        $subscription = $wpdb->get_row($wpdb->prepare("SELECT * FROM $subscription_table WHERE id = %d", $subscription_id));
        if (!$subscription) {return;
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

    public function get_headers() {
        $headers = parent::get_headers();
        
        // Ensure headers is an array
        if (!is_array($headers)) {
            $headers = array();
        }
        
        // Add admin-specific headers if needed
        $headers[] = 'X-Windrose-Subscription-Type: Admin Notification';
        
        return $headers;
    }

    public function get_content_html() {
        ob_start();
        wc_get_template(
            $this->template_html,
            array(
                'subscription' => $this->object,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => true,
                'plain_text' => false,
                'email' => $this,
            ),
            '',
            $this->template_base
        );
        return ob_get_clean();
    }

    public function get_content_plain() {
        ob_start();
        wc_get_template(
            $this->template_plain,
            array(
                'subscription' => $this->object,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => true,
                'plain_text' => true,
                'email' => $this,
            ),
            '',
            $this->template_base
        );
        return ob_get_clean();
    }
}
}
}