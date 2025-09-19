<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Windrose Subscription Activated Email (Plain Text)
 *
 * @var $subscription object
 * @var $email_heading string
 * @var $email_heading_arabic string
 * @var $email WC_Email
 */
?>
<?php echo $email_heading_arabic; ?>

<?php printf( __( 'مرحباً، اشتراكك (الرقم: %d) نشط الآن.', 'windros-subscription' ), $subscription->id ); ?>

<?php printf( __( 'المنتج: %s', 'windros-subscription' ), get_the_title($subscription->product_id) ); ?>
<?php printf( __( 'الكمية: %d', 'windros-subscription' ), $subscription->quantity ); ?>
<?php printf( __( 'الجدول الزمني: %s', 'windros-subscription' ), defined('WINDROS_FREQUENCY') && isset(WINDROS_FREQUENCY[$subscription->schedule]) ? WINDROS_FREQUENCY[$subscription->schedule] : $subscription->schedule ); ?>
<?php printf( __( 'تاريخ التوصيل القادم: %s', 'windros-subscription' ), windrose_get_next_delivery_date($subscription->id) ); ?>

<?php _e( 'تم تفعيل اشتراكك بنجاح! ستتلقى تحديثات منتظمة حول حالة طلباتك.', 'windros-subscription' ); ?>
<?php _e( 'شكراً لك على اختيار خدمة الاشتراك لدينا!', 'windros-subscription' ); ?>

--- English Version ---

<?php echo $email_heading; ?>

<?php printf( __( 'Hello, your subscription (ID: %d) is now active.', 'windros-subscription' ), $subscription->id ); ?>

<?php printf( __( 'Product: %s', 'windros-subscription' ), get_the_title($subscription->product_id) ); ?>
<?php printf( __( 'Quantity: %d', 'windros-subscription' ), $subscription->quantity ); ?>
<?php printf( __( 'Schedule: %s', 'windros-subscription' ), defined('WINDROS_FREQUENCY') && isset(WINDROS_FREQUENCY[$subscription->schedule]) ? WINDROS_FREQUENCY[$subscription->schedule] : $subscription->schedule ); ?>
<?php printf( __( 'Next Delivery Date: %s', 'windros-subscription' ), windrose_get_next_delivery_date($subscription->id) ); ?>

<?php _e( 'Your subscription has been successfully activated! You will receive regular updates about your order status.', 'windros-subscription' ); ?>
<?php _e( 'Thank you for choosing our subscription service!', 'windros-subscription' ); ?>

<?php echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ); ?> 