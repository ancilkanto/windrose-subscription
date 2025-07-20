<?php
namespace WindroseSubscription\Includes;

if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Email_Windrose_Subscription_Skipped extends \WC_Email {
    public function __construct() {
        $this->id             = 'windrose_subscription_skipped';
        $this->title          = __( 'Subscription Order Skipped', 'windros-subscription' );
        $this->description    = __( 'This email is sent to the customer when a subscription order is skipped.', 'windros-subscription' );
        $this->template_html  = 'emails/windrose-subscription-skipped.php';
        $this->template_plain = 'emails/plain/windrose-subscription-skipped.php';
        $this->customer_email = true;
        $this->placeholders   = array();
        parent::__construct();
    }

    public function trigger( $subscription_order_id ) {
        if ( !$subscription_order_id ) return;
        global $wpdb;
        $order_table = $wpdb->prefix . (defined('WINDROS_SUBSCRIPTION_ORDER_TABLE') ? WINDROS_SUBSCRIPTION_ORDER_TABLE : 'windrose_subscription_order');
        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $order_table WHERE id = %d", $subscription_order_id));
        if (!$order) return;
        $subscription_table = $wpdb->prefix . (defined('WINDROS_SUBSCRIPTION_MAIN_TABLE') ? WINDROS_SUBSCRIPTION_MAIN_TABLE : 'windrose_subscription_main');
        $subscription = $wpdb->get_row($wpdb->prepare("SELECT * FROM $subscription_table WHERE id = %d", $order->subscription_id));
        if (!$subscription) return;
        $user = get_userdata($subscription->user_id);
        $this->recipient = $user ? $user->user_email : '';
        $this->object = $order;
        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
            return;
        }
        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
    }

    public function get_content_html() {
        return wc_get_template_html( $this->template_html, array(
            'order' => $this->object,
            'email_heading' => $this->get_heading(),
            'email' => $this,
        ), '', plugin_dir_path(__DIR__) . '../templates/' );
    }

    public function get_content_plain() {
        return wc_get_template_html( $this->template_plain, array(
            'order' => $this->object,
            'email_heading' => $this->get_heading(),
            'email' => $this,
        ), '', plugin_dir_path(__DIR__) . '../templates/' );
    }
} 