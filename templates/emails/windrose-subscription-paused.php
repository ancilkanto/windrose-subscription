<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Windrose Subscription Paused Email (HTML)
 *
 * @var $subscription object
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
        <p><?php printf(wp_kses_post(__('تم <strong>إيقاف</strong> اشتراكك (الرقم: %d) مؤقتاً.', 'windros-subscription')), $subscription->id); ?></p>
    </div>

    <div class="subscription-details">
        <div class="arabic-details">
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('المنتج:', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html(get_the_title($subscription->product_id)); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('الكمية:', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html($subscription->quantity); ?></span>
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
        <p><?php esc_html_e('تم إيقاف اشتراكك مؤقتاً. يمكنك إعادة تفعيله في أي وقت من لوحة التحكم الخاصة بك.', 'windros-subscription'); ?></p>
    </div>
</div> <!-- End Arabic Section -->

<!-- English Section Second -->
<div class="english-section">
    
    <!-- English Email Heading -->
    <div style="background-color: #ef722f; padding: 20px; text-align: center; margin-bottom: 20px;">
        <h1 style="color: white; margin: 0; font-size: 24px;"><?php echo esc_html($email_heading); ?></h1>
    </div>
    
    <div class="english-content">
        <p><?php printf(wp_kses_post(__('Your subscription (ID: %d) has been <strong>paused</strong>.', 'windros-subscription')), $subscription->id); ?></p>
    </div>

    <div class="subscription-details">
        <div class="english-details">
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('Product:', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html(get_the_title($subscription->product_id)); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('Quantity:', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html($subscription->quantity); ?></span>
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
        <p><?php esc_html_e('Your subscription has been paused. You can reactivate it anytime from your dashboard.', 'windros-subscription'); ?></p>
    </div>
</div> <!-- End English Section -->

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);
?> 