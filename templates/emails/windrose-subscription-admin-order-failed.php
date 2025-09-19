<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Windrose Subscription Admin Order Failed Email (HTML)
 *
 * @var WC_Subscription|object|null $subscription
 * @var string $reason
 * @var string $email_heading
 * @var WC_Email $email
 */

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
    <?php
    $sub_id = isset( $subscription->id ) ? $subscription->id : 12345; // fallback for preview
    printf(
        esc_html__( 'A subscription order payment has failed for subscription ID: %d.', 'windros-subscription' ),
        $sub_id
    );
    ?>
</p>

<h2><?php esc_html_e( 'Subscription details', 'windros-subscription' ); ?></h2>

<table class="td" cellspacing="0" cellpadding="6" border="1" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; border: 1px solid #e5e5e5;" >
    <tbody>
        <tr>
            <th class="td" scope="row" style="border: 1px solid #e5e5e5;"><?php esc_html_e( 'Customer', 'windros-subscription' ); ?></th>
            <td class="td" style="border: 1px solid #e5e5e5;">
                <?php
                $user = ( isset( $subscription->user_id ) ) ? get_userdata( $subscription->user_id ) : null;
                echo $user ? esc_html( $user->display_name ) : esc_html__( 'Preview Customer', 'windros-subscription' );
                ?>
            </td>
        </tr>
        <tr>
            <th class="td" scope="row" style="border: 1px solid #e5e5e5;"><?php esc_html_e( 'Product', 'windros-subscription' ); ?></th>
            <td class="td" style="border: 1px solid #e5e5e5;">
                <?php
                echo isset( $subscription->product_id )
                    ? esc_html( get_the_title( $subscription->product_id ) )
                    : esc_html__( 'Sample Product', 'windros-subscription' );
                ?>
            </td>
        </tr>
        <tr>
            <th class="td" scope="row" style="border: 1px solid #e5e5e5;"><?php esc_html_e( 'Quantity', 'windros-subscription' ); ?></th>
            <td class="td" style="border: 1px solid #e5e5e5;">
                <?php echo isset( $subscription->quantity ) ? esc_html( $subscription->quantity ) : 1; ?>
            </td>
        </tr>
        <tr>
            <th class="td" scope="row" style="border: 1px solid #e5e5e5;"><?php esc_html_e( 'Failure Date', 'windros-subscription' ); ?></th>
            <td class="td" style="border: 1px solid #e5e5e5;">
                <?php
                echo esc_html(
                    isset( $subscription->updated_at )
                        ? wc_format_datetime( new WC_DateTime( $subscription->updated_at ) )
                        : wc_format_datetime( new WC_DateTime() ) // fallback: now
                );
                ?>
            </td>
        </tr>
    </tbody>
</table>

<?php if ( ! empty( $reason ) ) : ?>
    <h2 style="margin-top: 20px;"><?php esc_html_e( 'Failure details', 'windros-subscription' ); ?></h2>
    <p><strong><?php esc_html_e( 'Reason:', 'windros-subscription' ); ?></strong> <?php echo esc_html( $reason ); ?></p>
<?php endif; ?>

<p><?php esc_html_e( 'This is an automated notification from your subscription system.', 'windros-subscription' ); ?></p>

<?php
do_action( 'woocommerce_email_footer', $email );