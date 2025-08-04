<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Windrose Subscription Admin Skipped Email (HTML)
 *
 * @var $subscription_order object
 * @var $email_heading string
 * @var $email WC_Email
 */
?>
<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<p><?php printf( __( 'A subscription order (ID: %d) has been <strong>skipped</strong>.', 'windros-subscription' ), $subscription_order->id ); ?></p>
<p><?php printf( __( 'Subscription ID: %d', 'windros-subscription' ), $subscription_order->subscription_id ); ?></p>
<p><?php printf( __( 'Customer: %s', 'windros-subscription' ), get_userdata($subscription_order->user_id)->display_name ); ?></p>
<p><?php printf( __( 'Customer Email: %s', 'windros-subscription' ), get_userdata($subscription_order->user_id)->user_email ); ?></p>
<p><?php printf( __( 'Product: %s', 'windros-subscription' ), get_the_title($subscription_order->product_id) ); ?></p>
<p><?php printf( __( 'Quantity: %d', 'windros-subscription' ), $subscription_order->quantity ); ?></p>
<p><?php printf( __( 'Next Delivery Date: %s', 'windros-subscription' ), windrose_get_next_delivery_date($subscription_order->subscription_id) ); ?></p>
<?php do_action( 'woocommerce_email_footer', $email ); ?> 