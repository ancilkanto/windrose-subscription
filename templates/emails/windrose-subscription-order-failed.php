<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Windrose Subscription Order Failed Email (HTML)
 *
 * @var $subscription object
 * @var $fail_reason string
 * @var $email_heading string
 * @var $email WC_Email
 */
?>
<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<p><?php printf( __( 'Payment for your subscription order (ID: %d) has <strong>failed</strong>.', 'windros-subscription' ), $subscription->id ); ?></p>
<p><?php printf( __( 'Product: %s', 'windros-subscription' ), get_the_title($subscription->product_id) ); ?></p>
<p><?php printf( __( 'Quantity: %d', 'windros-subscription' ), $subscription->quantity ); ?></p>
<p><?php printf( __( 'Schedule: %s', 'windros-subscription' ), $subscription->schedule ); ?></p>
<?php if (!empty($fail_reason)) : ?>
<p><strong><?php _e('Reason:', 'windros-subscription'); ?></strong> <?php echo esc_html($fail_reason); ?></p>
<?php endif; ?>
<?php do_action( 'woocommerce_email_footer', $email ); ?> 