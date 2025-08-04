<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Windrose Subscription Admin Order Failed Email (HTML)
 *
 * @var $subscription object
 * @var $reason string
 * @var $email_heading string
 * @var $email WC_Email
 */
?>
<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<p><?php printf( __( 'A subscription order payment has <strong>failed</strong> for subscription ID: %d.', 'windros-subscription' ), $subscription->id ); ?></p>
<p><?php printf( __( 'Customer: %s', 'windros-subscription' ), get_userdata($subscription->user_id)->display_name ); ?></p>
<p><?php printf( __( 'Customer Email: %s', 'windros-subscription' ), get_userdata($subscription->user_id)->user_email ); ?></p>
<p><?php printf( __( 'Product: %s', 'windros-subscription' ), get_the_title($subscription->product_id) ); ?></p>
<p><?php printf( __( 'Quantity: %d', 'windros-subscription' ), $subscription->quantity ); ?></p>
<p><?php printf( __( 'Schedule: %s', 'windros-subscription' ), defined('WINDROS_FREQUENCY') && isset(WINDROS_FREQUENCY[$subscription->schedule]) ? WINDROS_FREQUENCY[$subscription->schedule] : $subscription->schedule ); ?></p>
<?php if (!empty($reason)): ?>
<p><?php printf( __( 'Failure Reason: %s', 'windros-subscription' ), $reason ); ?></p>
<?php endif; ?>
<p><?php printf( __( 'Failure Date: %s', 'windros-subscription' ), date('Y-m-d H:i:s') ); ?></p>
<p><?php printf( __( 'Next Delivery Date: %s', 'windros-subscription' ), windrose_get_next_delivery_date($subscription->id) ); ?></p>
<?php do_action( 'woocommerce_email_footer', $email ); ?> 