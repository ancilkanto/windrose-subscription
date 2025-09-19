<?php
namespace WindroseSubscription\Includes;

defined( 'WINDROS_INIT' ) || exit;  


class WindroseCreateSubscription {
    public function __construct() {
        // Save the custom field value to the order
        add_action( 'windrose_subscription_main_order_created', [$this, 'initiate_subscription'], 10, 4 );

        // Hook into WooCommerce after the order meta is updated (after the order is created)
        add_action('woocommerce_thankyou', [$this, 'update_subscription_order_data_after_payment'], 10, 1);
    }


    public function initiate_subscription( $order_id, $product_id, $subscription_schedule, $quantity ) {
        
        
        $order = wc_get_order($order_id);
        $customer_id = $order->get_user_id(); 
        $status = 'processing';

        // Always use GMT+0
        $timezone = 'Etc/GMT';
        date_default_timezone_set($timezone);


        global $wpdb;
        $subscription_main_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_MAIN_TABLE;
        

        $wpdb->insert($subscription_main_table, array(
            'order_id' => $order_id,
            'product_id' => $product_id,
            'user_id' => $customer_id, 
            'payment_token' => '', 
            'schedule' => $subscription_schedule, 
            'quantity' => $quantity, 
            'status' => $status, 
            'total_orders' => 0, 
            'active_date' => '', 
            'time_stamp' => date("Y-m-d H:i:s"), 
        ));
        
    }

    

    public function update_subscription_order_data_after_payment($order_id) {

        // Get the order object using the order ID
        $order = wc_get_order($order_id);
        // Get the user/customer ID (returns 0 for guest checkouts)
        $user_id = $order->get_user_id();

        global $wpdb;
        $subscription_main_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_MAIN_TABLE;
        
        $data = array(
            'user_id' => $user_id,
            'payment_token' => 'PAYMENT-TOKEN-HERE', 
        );

        if( !($this->is_saved_card($user_id) )){
            $data['status'] = 'cancel';

            $subscription_data = $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT id
                    FROM {$wpdb->prefix}windrose_subscription_main
                    WHERE order_id = %d
                    ",
                    $order_id
                )
            );
            do_action('windrose_subscription_main_order_cancelled', $subscription_data->id);
            // Send notification that subscription was cancelled due to no saved card
            do_action('windrose_subscription_order_execution_failed', $subscription_data->id, 'Subscription cancelled! Unable to find a saved payment card for recurring orders.');
        }

        $where = array(
            'order_id' => $order_id,
        );

        $format = array('%s');  
        $where_format = array('%s');  

        // Execute the update query
        $updated = $wpdb->update( $subscription_main_table, $data, $where, $format, $where_format );
        
        // Hook to invoke on 
        do_action('windrose_subscription_main_order_created_successfully', $order);
    }

    public function is_saved_card($user_id){
        global $wpdb;
        

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT id, token
                FROM {$wpdb->prefix}paymob_cards_token
                WHERE user_id = %d
                ORDER BY id DESC
                LIMIT 1
                ",
                $user_id
            )
        );

        if ($result) {
            return true;
        } 

        return false;
    }
}
