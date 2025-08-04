<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Windrose Subscription Skipped Email (HTML)
 *
 * @var $subscription object
 * @var $subscription_order object
 * @var $email_heading string
 * @var $email WC_Email
 */
?>
<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<p><?php printf( __( 'A subscription order (ID: %d) has been <strong>skipped</strong>.', 'windros-subscription' ), $subscription_order->subscription_id ); ?></p>
<?php if (isset($subscription_order->subscription_id)) : ?>
<p><?php printf( __( 'Subscription ID: %d', 'windros-subscription' ), $subscription_order->subscription_id ); ?></p>
<?php endif; ?>
<p><?php if (isset($subscription_order->quantity)) printf( __( 'Quantity: %d', 'windros-subscription' ), $subscription_order->quantity ); ?></p>
<p><?php if (isset($subscription_order->status)) printf( __( 'Status: %s', 'windros-subscription' ), $subscription_order->status ); ?></p>
<p><?php printf( __( 'Product: %s', 'windros-subscription' ), get_the_title($subscription->product_id) ); ?></p>
<p><?php printf( __( 'Schedule: %s', 'windros-subscription' ), defined('WINDROS_FREQUENCY') && isset(WINDROS_FREQUENCY[$subscription->schedule]) ? WINDROS_FREQUENCY[$subscription->schedule] : $subscription->schedule ); ?></p>
<p><?php printf( __( 'Next Delivery Date: %s', 'windros-subscription' ), windrose_get_next_delivery_date($subscription->id) ); ?></p>
<?php do_action( 'woocommerce_email_footer', $email ); ?> 