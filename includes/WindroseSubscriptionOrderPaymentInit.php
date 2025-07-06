<?php
namespace WindroseSubscription\Includes;

defined( 'WINDROS_INIT' ) || exit;  

class WindroseSubscriptionOrderPaymentInit {
    public function __construct() {
        add_action( 'windrose_subscription_initiate_payment', [$this, 'subscription_order_payment_init'], 10, 2 );
        add_action( 'windrose_subscription_order_executed_successfully', [$this, 'create_subscription_order'], 10, 1 );
    }

    public function subscription_order_payment_init($subscription_order, $order){
        // Initiate Payment
        $payment_status = false;
        $failed_reason = '';

        $payment_token = $this->get_payment_token($order);

        if($payment_token){
            $token = $payment_token->token;
            $token_id = $payment_token->token_id;

            $paymob_settings = get_option('woocommerce_paymob-main_settings',array());
            
            $apiKey = $paymob_settings['api_key'];
            $PublicKey = $paymob_settings['pub_key'];
            $SecretKey = $paymob_settings['sec_key'];

            $integration_ids = $this->get_payment_integration_ids($paymob_settings['integration_id_hidden'], $paymob_settings['mode']);

            $subscription_items = $order->get_items();
            $order_items = array();

            foreach ( $subscription_items as $item_id => $item ) {                
                $order_items[] = array(
                    'name' => $item->get_name(),
                    'amount' => $item->get_total() * 1000,
                    'quantity' => $item->get_quantity(),
                    'description' => 'Subscription Product'
                );
            }

            $billing_address = array(
                'first_name' => $order->get_billing_first_name(),  
                'last_name' => $order->get_billing_last_name(),
                'phone_number' => $order->get_billing_phone(),
                'city' => $order->get_billing_city(),
                'country' => $order->get_billing_country(),
                'email' => $order->get_billing_email(),
                'state' => $order->get_billing_state(),
                'street' => $order->get_billing_address_1(),
                'building' => '-',
                'floor' => '-',
                'apartment' => $order->get_billing_address_2(),
            );


            // Prepare payment data
            $intention_data = [
                'amount'    => $order->get_total() * 1000, // Adjust if needed
                'currency'  => get_woocommerce_currency(),
                'payment_methods' => $integration_ids,
                'items' => $order_items,
                'billing_data' => $billing_address,
                'extras' => [
                    'ee'=> 22
                ],
                'special_reference' => 'WINDROSE-SUBSCRIPTION-' . $order->get_id(),
            ];

            // Call Paymob Intention API
            $intention_response = wp_remote_post('https://oman.paymob.com/v1/intention/', [
                'headers' => [
                    'Authorization' => 'Token '. $SecretKey,
                ],
                'body' => json_encode($intention_data),
                'timeout' => 45,
            ]);

            if (is_wp_error($intention_response)) {
                return new WP_Error('api_error', 'Error connecting to payment gateway.');
            }

            $body = json_decode(wp_remote_retrieve_body($intention_response), true);

            // 4. Process response
            if (!empty($body['status']) && $body['status'] === 'intended') {
                // Retrieve payment key
                $payment_key = $body['payment_keys'][0]['key'];
                $payment_params = array(
                    'source' => array(
                        'identifier' => $token,
                        'subtype' => 'TOKEN'
                    ),
                    'payment_token' => $payment_key
                );

                // Call Paymob Payment API
                $payment_response = wp_remote_post('https://oman.paymob.com/api/acceptance/payments/pay/', [
                    'headers' => [
                        'Authorization' => 'Token '. $SecretKey,
                    ],
                    'body' => json_encode($payment_params),
                    'timeout' => 45,
                ]);

                if (is_wp_error($payment_response)) {
                    return new WP_Error('api_error', 'Error connecting to payment gateway.');
                }

                $payment_response_body = json_decode(wp_remote_retrieve_body($payment_response), true);
                $transaction_id = isset($payment_response_body['id']) ? $payment_response_body['id'] : false;
                if($transaction_id){
                    
                }

            } else{

            }

        }

        // Trigger on successfull payment
        if($payment_status){
            $this->update_subscription_order_status($subscription_order->id, 'past');
            do_action('windrose_subscription_order_executed_successfully', $subscription_order->subscription_id);
        }else{
            do_action('windrose_subscription_order_execution_failed', $subscription_order->subscription_id, $failed_reason);
        }
    }

    public function get_payment_token($order){
        global $wpdb;

        $customer_id = $order->get_customer_id();

        $result = $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT id, token
                FROM {$wpdb->prefix}paymob_cards_token
                WHERE user_id = %d
                ORDER BY id DESC
                LIMIT 1
                ",
                $customer_id
            )
        );

        if ($result) {
            return (object) [
                'status' => true,
                'token'  => $result->token,
                'token_id'  => $result->id,
            ];
        } else {

            return (object) [
                'status' => false,
                'token'  => null,
                'token_id'  => null,
            ];
        }

    }

    public function get_payment_integration_ids($integrations, $payment_mode){
        $test_ids = [];
        $live_ids = [];

        // Split by comma
        $entries = explode(',', $raw_string);

        foreach ($entries as $entry) {
            $entry = trim($entry);
            // Match pattern like: 7009 : (Card : EGP : test )
            if (preg_match('/^(\d+)\s*:\s*\((.*?)\s*:\s*(.*?)\s*:\s*(test|live)\s*\)$/i', $entry, $matches)) {
                $id = (int) $matches[1];
                $mode = strtolower($matches[4]);

                if ($mode === 'test') {
                    $test_ids[] = $id;
                } elseif ($mode === 'live') {
                    $live_ids[] = $id;
                }
            }
        }

        if($payment_mode === 'test'){
            return $test_ids;
        }else{
            return $live_ids;
        }
    }

    public function update_subscription_order($subscription_order_id, $data){
        global $wpdb;
        $subscription_order_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_ORDER_TABLE;

        $increment_by = 1;
        
        $wpdb->query(
            $wpdb->prepare(
                "
                UPDATE $subscription_order_table
                SET attempts = attempts + %d
                WHERE id = %d
                ",
                $increment_by,
                $subscription_order_id
            )
        );
    }

    public function update_subscription_order_status($subscription_order_id, $status){
        global $wpdb;

        $subscription_order_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_ORDER_TABLE;

        $order_status_data = array(
            'status' => $status
        );

        $order_status_condition = array(
            'id' => $subscription_order_id
        );

        $order_status_format = array('%s');
        $order_status_where_format = array('%d');

        $order_updated = $wpdb->update( $subscription_order_table, $order_status_data, $order_status_condition, $order_status_format, $order_status_where_format );
    }

}