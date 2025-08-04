<?php
namespace WindroseSubscription\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    // Only exit if we're in a WordPress context
    if (function_exists('add_action')) {
        exit;
    }
}

if (class_exists('WC_Email')) {
    class WC_Email_Windrose_Subscription_Paused extends \WC_Email {
    public function __construct() {
        $this->id             = 'windrose_subscription_paused';
        $this->title          = __( 'Subscription Paused', 'windros-subscription' );
        $this->description    = __( 'This email is sent to the customer when their subscription is paused.', 'windros-subscription' );
        $this->template_html  = 'emails/windrose-subscription-paused.php';
        $this->template_plain = 'emails/plain/windrose-subscription-paused.php';
        $this->customer_email = true;
        $this->placeholders   = array();
        
        // Set default heading and subject
        $this->heading = __( 'Your subscription has been paused', 'windros-subscription' );
        $this->subject = __( 'Your subscription has been paused', 'windros-subscription' );
        
        parent::__construct();
        
        // Ensure the email is enabled
        $this->ensure_email_enabled();
    }

    private function ensure_email_enabled() {
        $enabled_emails = get_option('woocommerce_email_settings', array());
        if (!isset($enabled_emails['windrose_subscription_paused_enabled']) || $enabled_emails['windrose_subscription_paused_enabled'] !== 'yes') {
            $enabled_emails['windrose_subscription_paused_enabled'] = 'yes';
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
        $this->object = $subscription;// error_log('Windrose Subscription: Paused email enabled: ' . ($this->is_enabled() ? 'Yes' : 'No'));
        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {return;
        }
        // error_log('Windrose Subscription: Sending paused email to: ' . $this->get_recipient());
        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
    }

    public function get_content_html() {
        $template_path = plugin_dir_path(__DIR__) . 'templates/';
        return wc_get_template_html( $this->template_html, array(
            'subscription' => $this->object,
            'email_heading' => $this->get_heading(),
            'email' => $this,
        ), '', $template_path );
    }

    public function get_content_plain() {
        $template_path = plugin_dir_path(__DIR__) . 'templates/';
        return wc_get_template_html( $this->template_plain, array(
            'subscription' => $this->object,
            'email_heading' => $this->get_heading(),
            'email' => $this,
        ), '', $template_path );
    }
}
}