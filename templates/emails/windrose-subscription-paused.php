<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Windrose Subscription Paused Email (HTML)
 *
 * @var $subscription object
 * @var $email_heading string
 * @var $email WC_Email
 */
?>
<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<p><?php printf( __( 'Your subscription (ID: %d) has been <strong>paused</strong>.', 'windros-subscription' ), $subscription->id ); ?></p>
<p><?php printf( __( 'Product: %s', 'windros-subscription' ), get_the_title($subscription->product_id) ); ?></p>
<p><?php printf( __( 'Quantity: %d', 'windros-subscription' ), $subscription->quantity ); ?></p>
<p><?php printf( __( 'Schedule: %s', 'windros-subscription' ), defined('WINDROS_FREQUENCY') && isset(WINDROS_FREQUENCY[$subscription->schedule]) ? WINDROS_FREQUENCY[$subscription->schedule] : $subscription->schedule ); ?></p>
<?php do_action( 'woocommerce_email_footer', $email ); ?> 