<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Windrose Subscription Skipped Email (HTML)
 *
 * @var $subscription object
 * @var $subscription_order object
 * @var $email_heading string
 * @var $email_heading_arabic string
 * @var $email WC_Email
 */
?>
<?php
/*
 * @hooked WC_Emails::email_header() Output the email header
 */
// First header with Arabic heading
if (!empty($email_heading_arabic)) {
    do_action('woocommerce_email_header', $email_heading_arabic, $email);
}
?>

<link rel="stylesheet" href="<?php echo esc_url(plugins_url('assets/css/bilingual-email.css', dirname(dirname(__FILE__)))); ?>" type="text/css" />

<!-- Arabic Section First -->
<div class="arabic-section">
    <div class="arabic-content">
        <p><?php printf(wp_kses_post(__('تم <strong>تخطي</strong> طلب اشتراك (الرقم: %d).', 'windros-subscription')), $subscription_order->subscription_id); ?></p>
    </div>

    <div class="subscription-details">
        <div class="arabic-details">
            <?php if (isset($subscription_order->subscription_id)) : ?>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('رقم الاشتراك:', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html($subscription_order->subscription_id); ?></span>
            </div>
            <?php endif; ?>
            <?php if (isset($subscription_order->quantity)) : ?>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('الكمية:', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html($subscription_order->quantity); ?></span>
            </div>
            <?php endif; ?>
            <?php if (isset($subscription_order->status)) : ?>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('الحالة:', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html($subscription_order->status); ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('المنتج:', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html(get_the_title($subscription->product_id)); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('الجدول الزمني:', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html(defined('WINDROS_FREQUENCY') && isset(WINDROS_FREQUENCY[$subscription->schedule]) ? WINDROS_FREQUENCY[$subscription->schedule] : $subscription->schedule); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('تاريخ التوصيل القادم:', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html(windrose_get_next_delivery_date($subscription->id)); ?></span>
            </div>
        </div>
    </div>

    <div class="arabic-content">
        <p><?php esc_html_e('تم تخطي هذا الطلب. إذا كان لديك أي أسئلة حول سبب التخطي، يرجى الاتصال بفريق الدعم لدينا.', 'windros-subscription'); ?></p>
    </div>
</div> <!-- End Arabic Section -->

<!-- English Section Second -->
<div class="english-section">
    
    <!-- English Email Heading -->
    <div style="background-color: #ef722f; padding: 20px; text-align: center; margin-bottom: 20px;">
        <h1 style="color: white; margin: 0; font-size: 24px;"><?php echo esc_html($email_heading); ?></h1>
    </div>
    
    <div class="english-content">
        <p><?php printf(wp_kses_post(__('A subscription order (ID: %d) has been <strong>skipped</strong>.', 'windros-subscription')), $subscription_order->subscription_id); ?></p>
    </div>

    <div class="subscription-details">
        <div class="english-details">
            <?php if (isset($subscription_order->subscription_id)) : ?>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('Subscription ID:', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html($subscription_order->subscription_id); ?></span>
            </div>
            <?php endif; ?>
            <?php if (isset($subscription_order->quantity)) : ?>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('Quantity:', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html($subscription_order->quantity); ?></span>
            </div>
            <?php endif; ?>
            <?php if (isset($subscription_order->status)) : ?>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('Status:', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html($subscription_order->status); ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('Product:', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html(get_the_title($subscription->product_id)); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('Schedule:', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html(defined('WINDROS_FREQUENCY') && isset(WINDROS_FREQUENCY[$subscription->schedule]) ? WINDROS_FREQUENCY[$subscription->schedule] : $subscription->schedule); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('Next Delivery Date:', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html(windrose_get_next_delivery_date($subscription->id)); ?></span>
            </div>
        </div>
    </div>

    <div class="english-content">
        <p><?php esc_html_e('This order has been skipped. If you have any questions about why it was skipped, please contact our support team.', 'windros-subscription'); ?></p>
    </div>
</div> <!-- End English Section -->

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);
?> 