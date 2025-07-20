<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Windrose Subscription Skipped Email (HTML)
 *
 * @var $order object
 * @var $email_heading string
 * @var $email WC_Email
 */
?>
<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<p><?php printf( __( 'A subscription order (ID: %d) has been <strong>skipped</strong>.', 'windros-subscription' ), $order->id ); ?></p>
<?php if (isset($order->subscription_id)) : ?>
<p><?php printf( __( 'Subscription ID: %d', 'windros-subscription' ), $order->subscription_id ); ?></p>
<?php endif; ?>
<p><?php if (isset($order->quantity)) printf( __( 'Quantity: %d', 'windros-subscription' ), $order->quantity ); ?></p>
<p><?php if (isset($order->status)) printf( __( 'Status: %s', 'windros-subscription' ), $order->status ); ?></p>
<?php do_action( 'woocommerce_email_footer', $email ); ?> 