<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Windrose Subscription Order Processed Email (Plain Text)
 *
 * @var $subscription object
 * @var $email_heading string
 * @var $email WC_Email
 */
?>
<?php echo $email_heading; ?>

<?php printf( __( 'Hello, your subscription order (ID: %d) has been processed successfully.', 'windros-subscription' ), $subscription->id ); ?>

<?php printf( __( 'Product: %s', 'windros-subscription' ), get_the_title($subscription->product_id) ); ?>
<?php printf( __( 'Quantity: %d', 'windros-subscription' ), $subscription->quantity ); ?>
<?php printf( __( 'Schedule: %s', 'windros-subscription' ), defined('WINDROS_FREQUENCY') && isset(WINDROS_FREQUENCY[$subscription->schedule]) ? WINDROS_FREQUENCY[$subscription->schedule] : $subscription->schedule ); ?>

<?php echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ); ?> 