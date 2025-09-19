<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Windrose Subscription Order Processed Email (HTML)
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
} else {
    // Fallback to English heading if Arabic is not available
    do_action('woocommerce_email_header', $email_heading, $email);
}
?>

<style type="text/css">
/* Bilingual Email Template Styles for Windrose Subscription */
.woocommerce-email-header__heading {
    text-align: center !important;
    line-height: 1.5;
    white-space: pre-line;
}
.bilingual-email-header {
    text-align: center;
    margin: 20px 0;
    padding: 20px;
    background-color: #f8f8f8;
    border-radius: 5px;
}
.bilingual-email-header .english-heading {
    margin-bottom: 15px;
}
.bilingual-email-header .arabic-heading {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 2px solid #ddd;
    direction: rtl;
    text-align: center;
    font-family: 'Arial', 'Tahoma', sans-serif;
}
.arabic-section {
    text-align: right !important;
    direction: rtl;
    font-family: 'Arial', 'Tahoma', sans-serif;
    margin-bottom: 40px;
}
.english-section {
    text-align: left;
    padding-top: 30px;
    border-top: 3px solid #d0d0d0;
}
.arabic-heading {
    text-align: right !important;
    direction: rtl;
    font-family: 'Arial', 'Tahoma', sans-serif;
    font-size: 1.1em;
    margin-bottom: 20px;
}
.arabic-heading h2 {
    text-align: right !important;
    direction: rtl;
}
.arabic-section h2,
.arabic-section h3,
.arabic-section p,
.arabic-section div {
    text-align: right !important;
    direction: rtl;
}
.arabic-section .td {
    text-align: right !important;
}
.english-heading {
    text-align: left;
    margin-bottom: 20px;
}
.arabic-content {
    text-align: right;
    direction: rtl;
    font-family: 'Arial', 'Tahoma', sans-serif;
    font-size: 0.95em;
    margin-bottom: 15px;
}
.english-content {
    text-align: left;
    margin-bottom: 15px;
}
.bilingual-content .english-content,
.bilingual-content .arabic-content {
    padding: 0;
}
.td .bilingual-content {
    margin: 0;
    padding: 0;
}
.td .bilingual-content .english-content,
.td .bilingual-content .arabic-content {
    padding: 0;
    margin: 0;
}
.subscription-details {
    margin: 20px 0;
}
.subscription-details .arabic-details {
    text-align: right;
    direction: rtl;
    margin-bottom: 20px;
}
.subscription-details .english-details {
    text-align: left;
}
.subscription-details .detail-row {
    margin-bottom: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}
.subscription-details .detail-label {
    font-weight: bold;
    color: #333;
}
.subscription-details .detail-value {
    color: #666;
    margin-left: 10px;
}
.subscription-details .arabic-details .detail-value {
    margin-left: 0;
    margin-right: 10px;
}
@media (max-width: 600px) {
    .arabic-section,
    .english-section {
        text-align: center;
    }
    .arabic-heading,
    .arabic-content {
        direction: ltr;
    }
}
</style>

<!-- Arabic Section First -->
<div class="arabic-section">
    <div class="arabic-content">
        <p><?php printf(wp_kses_post(__(' طلب اشتراك (الرقم   :%d) قيد المعالجة بعد اتمام عملية الدفع  ', 'windros-subscription')), $subscription->id); ?></p>
    </div>

    <div class="subscription-details">
        <div class="arabic-details">
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('المنتج:', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html(windrose_get_arabic_product_title($subscription->product_id)); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('الكمية:', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html($subscription->quantity); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e(' جدول خطة الاشتراك ', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html(defined('WINDROS_FREQUENCY') && isset(WINDROS_FREQUENCY[$subscription->schedule]) ? WINDROS_FREQUENCY[$subscription->schedule] : $subscription->schedule); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('تاريخ التوصيل القادم:', 'windros-subscription'); ?></span>
                <span class="detail-value"><?php echo esc_html(windrose_get_arabic_date(windrose_get_next_delivery_date($subscription->id))); ?></span>
            </div>
        </div>
    </div>

    <div class="arabic-content">
        <p><?php esc_html_e('تم استلام طلبك بنجاح وسيتم معالجته قريباً. ستتلقى تحديثات حول حالة طلبك.', 'windros-subscription'); ?></p>
    </div>
</div> <!-- End Arabic Section -->

<!-- English Section Second -->
<div class="english-section">
    
    <!-- English Email Heading -->
    <div style="background-color: #ef722f; padding: 20px; text-align: center; margin-bottom: 20px;">
        <h1 style="color: white; margin: 0; font-size: 24px;"><?php echo esc_html($email_heading); ?></h1>
    </div>
    
    <div class="english-content">
        <p><?php printf(wp_kses_post(__('Your subscription order (ID: %d) is now <strong>processing</strong> after successful payment.', 'windros-subscription')), $subscription->id); ?></p>
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
        <p><?php esc_html_e('Your order has been received successfully and will be processed soon. You will receive updates about your order status.', 'windros-subscription'); ?></p>
    </div>
</div> <!-- End English Section -->

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);
?> 