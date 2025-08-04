<?php
namespace WindroseSubscription\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    // Only exit if we're in a WordPress context
    if (function_exists('add_action')) {
        exit;
    }
}

if (class_exists('WC_Email')) {
    class WC_Email_Windrose_Subscription_Cancelled extends \WC_Email {
    public function __construct() {
        $this->id             = 'windrose_subscription_cancelled';
        $this->title          = __( 'Subscription Cancelled', 'windros-subscription' );
        $this->description    = __( 'This email is sent to the customer when their subscription is cancelled.', 'windros-subscription' );
        $this->template_html  = 'emails/windrose-subscription-cancelled.php';
        $this->template_plain = 'emails/plain/windrose-subscription-cancelled.php';
        $this->customer_email = true;
        $this->placeholders   = array();
        
        // Set default heading and subject
        $this->heading = __( 'Your subscription has been cancelled', 'windros-subscription' );
        $this->subject = __( 'Your subscription has been cancelled', 'windros-subscription' );
        
        parent::__construct();
        
        // Ensure the email is enabled
        $this->ensure_email_enabled();
    }

    private function ensure_email_enabled() {
        $enabled_emails = get_option('woocommerce_email_settings', array());
        if (!isset($enabled_emails['windrose_subscription_cancelled_enabled']) || $enabled_emails['windrose_subscription_cancelled_enabled'] !== 'yes') {
            $enabled_emails['windrose_subscription_cancelled_enabled'] = 'yes';
            update_option('woocommerce_email_settings', $enabled_emails);}
    }

    public function trigger( $subscription_id ) {if ( !$subscription_id ) return;
        global $wpdb;
        $subscription_table = $wpdb->prefix . (defined('WINDROS_SUBSCRIPTION_MAIN_TABLE') ? WINDROS_SUBSCRIPTION_MAIN_TABLE : 'windrose_subscription_main');
        $subscription = $wpdb->get_row($wpdb->prepare("SELECT * FROM $subscription_table WHERE id = %d", $subscription_id));
        if (!$subscription) {return;
        }
        $user = get_userdata($subscription->user_id);
        $this->recipient = $user ? $user->user_email : '';
        $this->object = $subscription;
        
        // Explicitly set heading and subject
        $this->heading = __( 'Your subscription has been cancelled', 'windros-subscription' );
        $this->subject = __( 'Your subscription has been cancelled', 'windros-subscription' );
        
        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
            return;
        }
        // error_log('Windrose Subscription: Sending cancelled email to: ' . $this->get_recipient());
        
        // Force HTML content type
        add_filter('wp_mail_content_type', function() {
            return 'text/html';
        });
        
        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        
        // Remove the filter after sending
        remove_all_filters('wp_mail_content_type');
    }

    public function get_content_html() {
        $template_path = plugin_dir_path(__DIR__) . 'templates/';$content = wc_get_template_html( $this->template_html, array(
            'subscription' => $this->object,
            'email_heading' => $this->get_heading(),
            'email' => $this,
        ), '', $template_path );
        
        // error_log('Windrose Subscription: Cancelled generated HTML content length: ' . strlen($content));
        return $content;
    }

    public function get_content_plain() {
        $template_path = plugin_dir_path(__DIR__) . 'templates/';
        return wc_get_template_html( $this->template_plain, array(
            'subscription' => $this->object,
            'email_heading' => $this->get_heading(),
            'email' => $this,
        ), '', $template_path );
    }

    public function get_headers() {
        $headers = parent::get_headers();
        
        // Ensure headers is an array
        if (!is_array($headers)) {
            $headers = array();
        }
        
        // Ensure HTML content type
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        // error_log('Windrose Subscription: Cancelled email headers set to HTML: ' . print_r($headers, true));
        return $headers;
    }
}
}