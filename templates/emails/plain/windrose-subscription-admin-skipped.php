<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Windrose Subscription Admin Skipped Email (Plain Text)
 *
 * @var $subscription_order object
 * @var $email_heading string
 * @var $email WC_Email
 */
?>
<?php echo $email_heading; ?>

<?php printf( __( 'A subscription order (ID: %d) has been skipped.', 'windros-subscription' ), $subscription_order->id ); ?>

<?php printf( __( 'Subscription ID: %d', 'windros-subscription' ), $subscription_order->subscription_id ); ?>
<?php printf( __( 'Customer: %s', 'windros-subscription' ), get_userdata($subscription_order->user_id)->display_name ); ?>
<?php printf( __( 'Customer Email: %s', 'windros-subscription' ), get_userdata($subscription_order->user_id)->user_email ); ?>
<?php printf( __( 'Product: %s', 'windros-subscription' ), get_the_title($subscription_order->product_id) ); ?>
<?php printf( __( 'Quantity: %d', 'windros-subscription' ), $subscription_order->quantity ); ?>
<?php printf( __( 'Skip Date: %s', 'windros-subscription' ), date('Y-m-d H:i:s', strtotime($subscription_order->updated_at)) ); ?>
<?php printf( __( 'Next Delivery Date: %s', 'windros-subscription' ), windrose_get_next_delivery_date($subscription_order->subscription_id) ); ?>

<?php echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ); ?> 