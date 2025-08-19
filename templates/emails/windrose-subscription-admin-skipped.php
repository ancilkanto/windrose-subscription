<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Windrose Subscription Admin Skipped Email (HTML)
 *
 * @var $subscription_order object|null
 * @var $email_heading string
 * @var $email WC_Email
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo esc_html( $email_heading ?? 'Admin Email Test' ); ?></title>
</head>
<body style="font-family: Arial, sans-serif; margin: 20px; background-color: #f7f7f7;">
    <div style="max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        
        <h1 style="color: #333; text-align: center;">
            <?php echo esc_html( $email_heading ?? 'Subscription Order Skipped' ); ?>
        </h1>
        
        <p style="font-size: 16px; color: #666; margin-bottom: 20px;">
            <?php
            $order_id = isset($subscription_order->id) ? $subscription_order->id : 12345; // fallback for preview
            printf(
                esc_html__('A subscription order (ID: %d) has been skipped.', 'windros-subscription'),
                $order_id
            );
            ?>
        </p>
        
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <h3 style="color: #333; margin-top: 0;"><?php esc_html_e('Order Details', 'windros-subscription'); ?></h3>
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6;"><strong><?php esc_html_e('Order ID:', 'windros-subscription'); ?></strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6;">
                        <?php echo isset($subscription_order->id) ? esc_html($subscription_order->id) : '12345'; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6;"><strong><?php esc_html_e('Subscription ID:', 'windros-subscription'); ?></strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6;">
                        <?php echo isset($subscription_order->subscription_id) ? esc_html($subscription_order->subscription_id) : '888'; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6;"><strong><?php esc_html_e('Customer:', 'windros-subscription'); ?></strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6;">
                        <?php
                        $user = ( isset($subscription_order->user_id) ) ? get_userdata($subscription_order->user_id) : null;
                        echo $user ? esc_html($user->display_name) : 'Preview Customer';
                        ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6;"><strong><?php esc_html_e('Product:', 'windros-subscription'); ?></strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6;">
                        <?php
                        echo isset($subscription_order->product_id)
                            ? esc_html(get_the_title($subscription_order->product_id))
                            : 'Sample Product';
                        ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px;"><strong><?php esc_html_e('Quantity:', 'windros-subscription'); ?></strong></td>
                    <td style="padding: 8px;">
                        <?php echo isset($subscription_order->quantity) ? esc_html($subscription_order->quantity) : 1; ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <p style="text-align: center; color: #666; font-size: 14px;">
            <?php esc_html_e('This is an automated notification from your subscription system.', 'windros-subscription'); ?>
        </p>
    </div>
</body>
</html> 