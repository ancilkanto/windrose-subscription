<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Windrose Subscription Admin Activated Email (Plain Text)
 *
 * @var $subscription object
 * @var $email_heading string
 * @var $email WC_Email
 */
?>
<?php echo $email_heading; ?>

<?php printf( __( 'A new subscription (ID: %d) has been activated.', 'windros-subscription' ), $subscription->id ); ?>

<?php printf( __( 'Customer: %s', 'windros-subscription' ), get_userdata($subscription->user_id)->display_name ); ?>
<?php printf( __( 'Customer Email: %s', 'windros-subscription' ), get_userdata($subscription->user_id)->user_email ); ?>
<?php printf( __( 'Product: %s', 'windros-subscription' ), get_the_title($subscription->product_id) ); ?>
<?php printf( __( 'Quantity: %d', 'windros-subscription' ), $subscription->quantity ); ?>
<?php printf( __( 'Schedule: %s', 'windros-subscription' ), defined('WINDROS_FREQUENCY') && isset(WINDROS_FREQUENCY[$subscription->schedule]) ? WINDROS_FREQUENCY[$subscription->schedule] : $subscription->schedule ); ?>
<?php printf( __( 'Activation Date: %s', 'windros-subscription' ), date('Y-m-d H:i:s', strtotime($subscription->created_at)) ); ?>

<?php echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ); ?> 

