<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Windrose Subscription Order Failed Email (Plain Text)
 *
 * @var $subscription object
 * @var $email_heading string
 * @var $email WC_Email
 */
?>
<?php echo $email_heading; ?>

<?php printf( __( 'Hello, your subscription order (ID: %d) payment has failed.', 'windros-subscription' ), $subscription->id ); ?>

<?php printf( __( 'Product: %s', 'windros-subscription' ), get_the_title($subscription->product_id) ); ?>
<?php printf( __( 'Quantity: %d', 'windros-subscription' ), $subscription->quantity ); ?>
<?php printf( __( 'Schedule: %s', 'windros-subscription' ), defined('WINDROS_FREQUENCY') && isset(WINDROS_FREQUENCY[$subscription->schedule]) ? WINDROS_FREQUENCY[$subscription->schedule] : $subscription->schedule ); ?>

<?php if ( ! empty( $email->placeholders['{reason}'] ) ) : ?>
<?php printf( __( 'Reason: %s', 'windros-subscription' ), $email->placeholders['{reason}'] ); ?>
<?php endif; ?>

<?php echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ); ?> 