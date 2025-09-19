<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Windrose Subscription Cancelled Email (Plain Text)
 *
 * @var $subscription object
 * @var $email_heading string
 * @var $email WC_Email
 */
?>
<?php echo $email_heading; ?>

<?php printf( __( 'Hello, your subscription (ID: %d) has been cancelled.', 'windros-subscription' ), $subscription->id ); ?>

<?php printf( __( 'Product: %s', 'windros-subscription' ), get_the_title($subscription->product_id) ); ?>
<?php printf( __( 'Quantity: %d', 'windros-subscription' ), $subscription->quantity ); ?>
<?php printf( __( 'Schedule: %s', 'windros-subscription' ), defined('WINDROS_FREQUENCY') && isset(WINDROS_FREQUENCY[$subscription->schedule]) ? WINDROS_FREQUENCY[$subscription->schedule] : $subscription->schedule ); ?>

<?php echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ); ?> 