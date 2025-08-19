<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Windrose Subscription Admin Skipped Email (HTML)
 *
 * @var $subscription_order object|null
 * @var $email_heading string
 * @var $email WC_Email
 */
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>
    <?php
    $order_id = isset( $subscription_order->id ) ? $subscription_order->id : 12345; // fallback for preview
    printf(
        esc_html__( 'A subscription order (ID: %d) has been skipped.', 'windros-subscription' ),
        $order_id
    );
    ?>
</p>

<h2><?php esc_html_e( 'Order Details', 'windros-subscription' ); ?></h2>

<table class="td" cellspacing="0" cellpadding="6" border="1" style="width: 100%; border-collapse: collapse; border: 1px solid #e5e5e5;">
    <tr>
        <th scope="row" style="border: 1px solid #e5e5e5;"><?php esc_html_e( 'Order ID:', 'windros-subscription' ); ?></th>
        <td style="border: 1px solid #e5e5e5;"><?php echo isset( $subscription_order->id ) ? esc_html( $subscription_order->id ) : '12345'; ?></td>
    </tr>
    <tr>
        <th scope="row" style="border: 1px solid #e5e5e5;"><?php esc_html_e( 'Subscription ID:', 'windros-subscription' ); ?></th>
        <td style="border: 1px solid #e5e5e5;"><?php echo isset( $subscription_order->subscription_id ) ? esc_html( $subscription_order->subscription_id ) : '888'; ?></td>
    </tr>
    <tr>
        <th scope="row" style="border: 1px solid #e5e5e5;"><?php esc_html_e( 'Customer:', 'windros-subscription' ); ?></th>
        <td style="border: 1px solid #e5e5e5;">
            <?php
            $user = ( isset( $subscription_order->user_id ) ) ? get_userdata( $subscription_order->user_id ) : null;
            echo $user ? esc_html( $user->display_name ) : 'Preview Customer';
            ?>
        </td>
    </tr>
    <tr>
        <th scope="row" style="border: 1px solid #e5e5e5;"><?php esc_html_e( 'Product:', 'windros-subscription' ); ?></th>
        <td style="border: 1px solid #e5e5e5;">
            <?php
            echo isset( $subscription_order->product_id )
                ? esc_html( get_the_title( $subscription_order->product_id ) )
                : 'Sample Product';
            ?>
        </td>
    </tr>
    <tr>
        <th scope="row" style="border: 1px solid #e5e5e5;"><?php esc_html_e( 'Quantity:', 'windros-subscription' ); ?></th>
        <td style="border: 1px solid #e5e5e5;"><?php echo isset( $subscription_order->quantity ) ? esc_html( $subscription_order->quantity ) : 1; ?></td>
    </tr>
</table>

<p style="padding-top: 10px;">
    <?php esc_html_e( 'This is an automated notification from your subscription system.', 'windros-subscription' ); ?>
</p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>