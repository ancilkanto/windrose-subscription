<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Windrose Subscription Admin Cancelled Email (HTML)
 *
 * @var $subscription object
 * @var $email_heading string
 * @var $email WC_Email
 */
?>
<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<p><?php printf( __( 'A subscription (ID: %d) has been <strong>cancelled</strong>.', 'windros-subscription' ), $subscription->id ); ?></p>
<p><?php printf( __( 'Customer: %s', 'windros-subscription' ), get_userdata($subscription->user_id)->display_name ); ?></p>
<p><?php printf( __( 'Customer Email: %s', 'windros-subscription' ), get_userdata($subscription->user_id)->user_email ); ?></p>
<p><?php printf( __( 'Product: %s', 'windros-subscription' ), get_the_title($subscription->product_id) ); ?></p>
<p><?php printf( __( 'Quantity: %d', 'windros-subscription' ), $subscription->quantity ); ?></p>
<p><?php printf( __( 'Schedule: %s', 'windros-subscription' ), defined('WINDROS_FREQUENCY') && isset(WINDROS_FREQUENCY[$subscription->schedule]) ? WINDROS_FREQUENCY[$subscription->schedule] : $subscription->schedule ); ?></p>
<p><?php printf( __( 'Cancellation Date: %s', 'windros-subscription' ), date('Y-m-d H:i:s', strtotime($subscription->updated_at)) ); ?></p>
<p><?php printf( __( 'Next Delivery Date: %s', 'windros-subscription' ), windrose_get_next_delivery_date($subscription->id) ); ?></p>
<?php do_action( 'woocommerce_email_footer', $email ); ?> 