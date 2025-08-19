<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Windrose Subscription Admin Paused Email (HTML)
 *
 * @var $subscription object|null
 * @var $email_heading string
 * @var $email WC_Email
 */
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>
    <?php
    $sub_id = isset( $subscription->id ) ? $subscription->id : 12345; // fallback for preview
    printf(
        esc_html__( 'A subscription (ID: %d) has been paused.', 'windrose-subscription' ),
        $sub_id
    );
    ?>
</p>

<h2><?php esc_html_e( 'Subscription Details', 'windrose-subscription' ); ?></h2>

<table class="td" cellspacing="0" cellpadding="6" border="1" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; border: 1px solid #e5e5e5;">
    <tbody>
        <tr>
            <th scope="row" style="text-align:left; border: 1px solid #e5e5e5;"><?php esc_html_e( 'Customer', 'windros-subscription' ); ?></th>
            <td style="text-align:left; border: 1px solid #e5e5e5;">
                <?php
                $user = isset( $subscription->user_id ) ? get_userdata( $subscription->user_id ) : null;
                echo $user ? esc_html( $user->display_name ) : 'Preview Customer';
                ?>
            </td>
        </tr>
        <tr>
            <th scope="row" style="text-align:left; border: 1px solid #e5e5e5;"><?php esc_html_e( 'Product', 'windros-subscription' ); ?></th>
            <td style="text-align:left; border: 1px solid #e5e5e5;">
                <?php
                echo isset( $subscription->product_id )
                    ? esc_html( get_the_title( $subscription->product_id ) )
                    : 'Sample Product';
                ?>
            </td>
        </tr>
        <tr>
            <th scope="row" style="text-align:left; border: 1px solid #e5e5e5;"><?php esc_html_e( 'Quantity', 'windros-subscription' ); ?></th>
            <td style="text-align:left; border: 1px solid #e5e5e5;">
                <?php echo isset( $subscription->quantity ) ? esc_html( $subscription->quantity ) : 1; ?>
            </td>
        </tr>
        <tr>
            <th scope="row" style="text-align:left; border: 1px solid #e5e5e5;"><?php esc_html_e( 'Pause Date', 'windros-subscription' ); ?></th>
            <td style="text-align:left; border: 1px solid #e5e5e5;">
                <?php
                echo isset( $subscription->updated_at )
                    ? esc_html( date( 'Y-m-d H:i:s', strtotime( $subscription->updated_at ) ) )
                    : esc_html( date( 'Y-m-d H:i:s' ) ); // fallback for preview
                ?>
            </td>
        </tr>
    </tbody>
</table>

<p style="padding-top: 10px;">
    <?php esc_html_e( 'This is an automated notification from your subscription system.', 'windrose-subscription' ); ?>
</p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>