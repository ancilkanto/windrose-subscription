<?php
namespace WindroseSubscription\Includes;

defined( 'WINDROS_INIT' ) || exit;  

class WindroseCreateSubscriptionOrder {
    public function __construct() {
        add_action( 'windrose_subscription_main_order_activated', [$this, 'create_subscription_order'], 10, 1 );
        add_action( 'windrose_subscription_order_executed_successfully', [$this, 'create_subscription_order'], 10, 1 );
    }

    public function create_subscription_order($subscription_id){
        global $wpdb;

        
        $subscription_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_MAIN_TABLE;
        $subscription_order_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_ORDER_TABLE;

        // Query to get all rows from the custom table
        // Prepare and execute the query to retrieve a single subscription
        $subscription = $wpdb->get_row( 
            $wpdb->prepare( "SELECT * FROM $subscription_table WHERE id = %d AND status = 'active'", $subscription_id ), 
            ARRAY_A // Return the result as an associative array
        );

        $schedule = $subscription['schedule'];
        $active_date = $subscription['active_date'];
        $active_date = date("Y-m-d", strtotime($active_date));

        $timestamp_obj = windrose_get_timestamp_object($schedule, $active_date);

        $sequence = $subscription['total_orders'] + 1;

        if ( !empty($subscription) ) {
            $wpdb->insert($subscription_order_table, array(
                'subscription_id' => $subscription_id,
                'main_order_id' => $subscription['order_id'],
                'user_id' => $subscription['user_id'], 
                'product_id' => $subscription['product_id'], 
                'quantity' => $subscription['quantity'], 
                'payment_token' => $subscription['payment_token'], 
                'attempts' => 0, 
                'status' => 'upcoming', 
                'sequence' => $sequence,
                'time_stamp' => $timestamp_obj->timestamp, 
                'created_at' => $timestamp_obj->date, 
            ));


            $update_data = array(
                'total_orders' => $sequence, 
            );

            $where = array(
                'id' => $subscription_id,
            );

            $format = array('%d');  
            $where_format = array('%d');  

            // Execute the update query
            $subscription_main_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_MAIN_TABLE;
            $wpdb->update( $subscription_main_table, $update_data, $where, $format, $where_format );
        }


    }
}
