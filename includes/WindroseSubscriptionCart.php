<?php
namespace WindroseSubscription\Includes; 

defined( 'WINDROS_INIT' ) || exit;  

class WindroseSubscriptionCart {
    public function __construct() {
        // Validate and save the custom select field value
        add_filter( 'woocommerce_add_to_cart_validation', [$this, 'validate_subscription_schedule_field'], 10, 3 );

        add_filter( 'woocommerce_add_cart_item_data', [$this, 'add_subscription_schedule_to_cart_item_data'], 10, 2 );

        // Display the custom field value in the cart
        add_filter( 'woocommerce_get_item_data', [$this, 'display_subscription_schedule_cart'], 10, 2 );

    }


    public function validate_subscription_schedule_field( $passed, $product_id, $quantity ) {
        $valid_cart = true;
        if( isset( $_POST['subscription-schedule'] ) && empty( $_POST['subscription-schedule'] ) ) {
            wc_add_notice( __( 'Please select a subscription schedule.', 'windros-subscription' ), 'error' );
            return false;
        }

        if ( !is_user_logged_in() ) {
            if( isset( $_POST['subscription-schedule'] ) && !empty( $_POST['subscription-schedule'] ) ) {
                wc_add_notice( __( 'Please login to subscribe a product.', 'windros-subscription' ), 'error' );
                return false;
            }
        }

        return $passed;
        
    }

    public function add_subscription_schedule_to_cart_item_data( $cart_item_data, $product_id ) {
        if( isset( $_POST['subscription-schedule'] ) ) {
            $cart_item_data['subscription-schedule'] = sanitize_text_field( $_POST['subscription-schedule'] );
        }
        return $cart_item_data;
    }


    public function display_subscription_schedule_cart( $item_data, $cart_item ) {
        if( isset( $cart_item['subscription-schedule'] ) ) {
            $item_data[] = array(
                'name' => __('Subscription Schedule', 'windros-subscription'),
                'value' => wc_clean( WINDROS_FREQUENCY[$cart_item['subscription-schedule']] )
            );
        }
        return $item_data;
    }
}

