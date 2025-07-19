<?php
namespace WindroseSubscription\Includes;

defined( 'WINDROS_INIT' ) || exit;  

class WindroseSubscriptionOrderPaymentInit {
    public function __construct() {
        add_action( 'windrose_subscription_initiate_payment', [$this, 'subscription_order_payment_init'], 10, 2 );
        add_action( 'windrose_subscription_order_executed_successfully', [$this, 'create_subscription_order'], 10, 1 );
    }

    public function subscription_order_payment_init($subscription_order, $order_id){
        $order = wc_get_order( $order_id );
        // Debug logging
        error_log('Windrose Payment Init: Starting payment for subscription order ' . $subscription_order->id . ', WC order ' . $order->get_id());
        
        // Initiate Payment
        $payment_status = false;
        $failed_reason = '';
        $log_id = null;

        $payment_token = $this->get_payment_token($order_id);

        error_log('Payment User Token: ' . json_encode($payment_token));
        
        // Debug logging for payment token
        error_log('Windrose Payment Init: Payment token found: ' . ($payment_token->status ? 'yes' : 'no'));

        if($payment_token){
            $token = $payment_token->token;
            $token_id = $payment_token->token_id;

            $paymob_settings = get_option('woocommerce_paymob-main_settings',array());
            
            $apiKey = $paymob_settings['api_key'];
            $PublicKey = $paymob_settings['pub_key'];
            $SecretKey = $paymob_settings['sec_key'];

            $integration_ids = $this->get_payment_integration_ids($paymob_settings['mode']);

            // Debug logging for integration IDs
            error_log('Windrose Payment Init: Payment mode: ' . ($paymob_settings['mode'] ?? 'unknown'));
            error_log('Windrose Payment Init: Integration IDs: ' . json_encode($integration_ids));
            error_log('Windrose Payment Init: Live Integration ID: ' . get_option('windrose_live_integration_id', 'not set'));
            error_log('Windrose Payment Init: Test Integration ID: ' . get_option('windrose_test_integration_id', 'not set'));

            // Log payment initiation
            $log_id = WindrosePaymentLogger::log_payment_initiation(
                $subscription_order, 
                $order, 
                $payment_token->token, 
                $paymob_settings['mode'], 
                $integration_ids
            );
            
            // Debug logging for log creation
            error_log('Windrose Payment Init: Log ID created: ' . ($log_id ? $log_id : 'failed'));

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
            $billing_country_name = WC()->countries->countries[ $order->get_billing_country() ] ?? $order->get_billing_country();
            $billing_country = $order->get_billing_country();
            $billing_state   = $order->get_billing_state();

            // All states
            $states = WC()->countries->states;

            // Default to the raw code
            $billing_state_name = $billing_state;

            // Only if the country has states defined:
            if ( isset($states[$billing_country]) && is_array($states[$billing_country]) ) {
                if ( isset($states[$billing_country][$billing_state]) ) {
                    $billing_state_name = $states[$billing_country][$billing_state];
                }
            } else {
                // Countries without predefined states
                if ( empty($billing_state) ) {
                    $billing_state_name = 'N/A';
                }
            }

            $billing_address = array(
                'first_name' => $order->get_billing_first_name() ?: 'Customer',
                'last_name' => $order->get_billing_last_name() ?: 'Name',
                'phone_number' => '+91' . $order->get_billing_phone() ?: '0000000000',
                'city' => $order->get_billing_city() ?: 'Unknown',
                'country' => $billing_country_name,
                'email' => $order->get_billing_email() ?: 'customer@example.com',
                'state' => $billing_state_name,
                'street' => $order->get_billing_address_1() ?: 'Unknown Street',
                'building' => 'dummy',
                'floor' => 'dummy',
                'apartment' => $order->get_billing_address_2() ?: 'dummy',
            );
            

            // Prepare payment data
            $intention_data = [
                'amount'    => $order->get_total() * 1000, // Convert to cents
                'currency'  => get_option('woocommerce_currency', 'OMR'),
                'payment_methods' => $integration_ids,
                'items' => $order_items,
                'billing_data' => $billing_address,
                'extras' => [
                    'ee'=> 22
                ],
                'special_reference' => 'WINDROSE-SUBSCRIPTION-' . $order->get_id(),
            ];

            // Debug logging for intention data
            error_log('Windrose Payment Init: Intention data: ' . json_encode($intention_data));
            error_log('Windrose Payment Init: Amount: ' . $intention_data['amount']);
            error_log('Windrose Payment Init: Currency: ' . $intention_data['currency']);
            error_log('Windrose Payment Init: Payment methods: ' . json_encode($intention_data['payment_methods']));
            error_log('Windrose Payment Init: Billing data: ' . json_encode($intention_data['billing_data']));

            // Validate required fields
            if (empty($intention_data['amount']) || $intention_data['amount'] <= 0) {
                $failed_reason = 'Invalid amount: ' . $intention_data['amount'];
                $order->add_order_note('Paymob Error: ' . $failed_reason);
                $order->update_status('failed');
                
                if ($log_id) {
                    WindrosePaymentLogger::log_payment_error($log_id, $failed_reason, array('intention_data' => $intention_data));
                }
                return;
            }

            if (empty($intention_data['currency'])) {
                $failed_reason = 'Currency is required';
                $order->add_order_note('Paymob Error: ' . $failed_reason);
                $order->update_status('failed');
                
                if ($log_id) {
                    WindrosePaymentLogger::log_payment_error($log_id, $failed_reason, array('intention_data' => $intention_data));
                }
                return;
            }

            if (empty($intention_data['payment_methods'])) {
                $failed_reason = 'Payment methods array is empty. Integration IDs: ' . json_encode($integration_ids);
                $order->add_order_note('Paymob Error: ' . $failed_reason);
                $order->update_status('failed');
                
                if ($log_id) {
                    WindrosePaymentLogger::log_payment_error($log_id, $failed_reason, array('intention_data' => $intention_data));
                }
                return;
            }

            if (empty($intention_data['billing_data'])) {
                $failed_reason = 'Billing data is incomplete';
                $order->add_order_note('Paymob Error: ' . $failed_reason);
                $order->update_status('failed');
                
                if ($log_id) {
                    WindrosePaymentLogger::log_payment_error($log_id, $failed_reason, array('intention_data' => $intention_data));
                }
                return;
            }
            

            // Call Paymob Intention API
            $intention_response = wp_remote_post('https://oman.paymob.com/v1/intention/', [
                'headers' => [
                    'Authorization' => 'Token '. $SecretKey,
                    'Content-Type'  => 'application/json',
                ],
                'body' => json_encode($intention_data),
                'timeout' => 45,
            ]);
            

            if (is_wp_error($intention_response)) {
                $failed_reason = 'Error connecting to payment gateway for intention creation.';
                $order->add_order_note('Paymob Error: ' . $intention_response->get_error_message());
                $order->update_status('failed');
                
                // Log intention creation error
                if ($log_id) {
                    WindrosePaymentLogger::log_payment_error($log_id, $failed_reason, array(
                        'intention_data' => $intention_data,
                        'wp_error' => $intention_response->get_error_message()
                    ));
                }
                return;
            }

            $intention_body = json_decode(wp_remote_retrieve_body($intention_response), true);

            

            // Log intention creation response
            if ($log_id) {
                WindrosePaymentLogger::log_intention_created($log_id, $intention_body);
            }

            // Check intention response
            
            if (!empty($intention_body['status']) && $intention_body['status'] === 'intended') {
                // Retrieve payment key
                $payment_key = $intention_body['payment_keys'][0]['key'];
                $payment_params = array(
                    'source' => array(
                        'identifier' => $payment_token->token,
                        'subtype' => 'TOKEN'
                    ),
                    'payment_token' => $payment_key
                );

                error_log('Moto Request Body: ' . json_encode($payment_params));

                // Call Paymob Payment API
                $payment_response = wp_remote_post('https://oman.paymob.com/api/acceptance/payments/pay', [
                    'headers' => [
                        'Authorization' => 'Token '. $SecretKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'body' => json_encode($payment_params),
                    'timeout' => 45,
                ]);

                error_log('Moto Response: ' . json_encode($payment_response));

                if (is_wp_error($payment_response)) {
                    $failed_reason = 'Error connecting to payment gateway for payment processing.';
                    $order->add_order_note('Paymob Error: ' . $payment_response->get_error_message());
                    $order->update_status('failed');
                    
                    // Log payment processing error
                    if ($log_id) {
                        WindrosePaymentLogger::log_payment_error($log_id, $failed_reason, array(
                            'payment_params' => $payment_params,
                            'wp_error' => $payment_response->get_error_message()
                        ));
                    }
                    return;
                }

                $payment_response_body = json_decode(wp_remote_retrieve_body($payment_response), true);
                
                // Log payment processing response
                if ($log_id) {
                    WindrosePaymentLogger::log_payment_processing($log_id, $payment_response_body);
                }
                
                // Process payment response
                if ($this->is_payment_successful($payment_response_body)) {
                    $payment_status = true;
                    
                    // Add order note with payment details
                    $this->add_payment_success_note($order, $payment_response_body);
                    
                    // Add merchant order ID note (like normal Paymob plugin)
                    $this->add_merchant_order_id_note($order, $payment_response_body);
                    
                    // Update order status to completed
                    $order->update_status('processing', 'Subscription payment processed successfully via Paymob.');
                    
                    // Add payment method details to order
                    $this->add_payment_method_details($order, $payment_response_body);
                    
                } else {
                    $failed_reason = 'Payment was not successful.';
                    $order->add_order_note('Paymob Payment Failed: ' . $this->get_payment_error_message($payment_response_body));
                    $order->update_status('failed');
                }

            } else {
                $failed_reason = 'Failed to create payment intention.';
                $order->add_order_note('Paymob Intention Error: ' . json_encode($intention_body));
                $order->update_status('failed');
            }

        } else {
            $failed_reason = 'No payment token found for customer.';
            $order->add_order_note('Paymob Error: No saved payment token found for customer ID: ' . $order->get_customer_id());
            $order->update_status('failed');
            
            // Debug logging for token error
            error_log('Windrose Payment Init: No payment token found for customer ' . $order->get_customer_id());
            
            // Log token error
            $log_id = WindrosePaymentLogger::create_log(array(
                'subscription_order_id' => $subscription_order->id,
                'wc_order_id' => $order->get_id(),
                'user_id' => $order->get_customer_id(),
                'payment_mode' => $paymob_settings['mode'] ?? 'unknown',
                'amount_cents' => $order->get_total() * 1000,
                'currency' => get_option('woocommerce_currency', 'OMR'),
                'status' => 'token_error',
                'error_message' => $failed_reason
            ));
            
            // Debug logging for token error log
            error_log('Windrose Payment Init: Token error log ID created: ' . ($log_id ? $log_id : 'failed'));
        }

        // Trigger appropriate actions based on payment status
        if($payment_status){
            $this->update_subscription_order_status($subscription_order->id, 'past');
            do_action('windrose_subscription_order_executed_successfully', $subscription_order->subscription_id);
        } else {
            $this->update_subscription_order_status($subscription_order->id, 'upcoming');
            do_action('windrose_subscription_order_execution_failed', $subscription_order->subscription_id, $failed_reason);
        }
    }

    public function get_payment_token($order_id){
        global $wpdb;
        $order = wc_get_order( $order_id );

        $customer_id = $order->get_customer_id();
        
        error_log('Payment Customer ID: ' . json_encode($customer_id));

        $result = $wpdb->get_row(
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

    public function get_payment_integration_ids($payment_mode){
        // Get Integration IDs from admin settings
        $live_integration_id = intval(get_option('windrose_live_integration_id', 0));
        $test_integration_id = intval(get_option('windrose_test_integration_id', 0));
        
        // Debug logging
        error_log('Windrose Payment Init: Getting integration IDs for mode: ' . $payment_mode);
        error_log('Windrose Payment Init: Live ID: ' . $live_integration_id);
        error_log('Windrose Payment Init: Test ID: ' . $test_integration_id);
        
        // Return appropriate Integration ID based on payment mode
        if($payment_mode === 'test'){
            if (!empty($test_integration_id)) {
                error_log('Windrose Payment Init: Using test integration ID: ' . $test_integration_id);
                return array($test_integration_id);
            } else {
                error_log('Windrose Payment Init: Test integration ID is empty');
                return array();
            }
        } else {
            if (!empty($live_integration_id)) {
                error_log('Windrose Payment Init: Using live integration ID: ' . $live_integration_id);
                return array($live_integration_id);
            } else {
                error_log('Windrose Payment Init: Live integration ID is empty');
                return array();
            }
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

    /**
     * Check if payment was successful
     */
    private function is_payment_successful($payment_response) {
        return isset($payment_response['success']) && 
               $payment_response['success'] === 'true' && 
               isset($payment_response['error_occured']) && 
               $payment_response['error_occured'] === 'false';
    }

    /**
     * Add payment success note to order
     */
    private function add_payment_success_note($order, $payment_response) {
        $note = sprintf(
            "Paymob : Transaction Approved\n" .
            "Payment Method ID: %s\n" .
            "Transaction done by: %s / %s\n" .
            "Transaction ID: %s\n" .
            "Order ID: %s",
            $payment_response['integration_id'] ?? 'N/A',
            $payment_response['source_data']['type'] ?? 'card',
            $payment_response['source_data']['sub_type'] ?? 'N/A',
            $payment_response['id'] ?? 'N/A',
            $payment_response['order'] ?? 'N/A'
        );
        
        $order->add_order_note($note);
    }

    /**
     * Add payment method details to order
     */
    private function add_payment_method_details($order, $payment_response) {
        // Store transaction ID
        $order->update_meta_data('_paymob_transaction_id', $payment_response['id'] ?? '');
        $order->update_meta_data('_paymob_integration_id', $payment_response['integration_id'] ?? '');
        $order->update_meta_data('_paymob_payment_method', $payment_response['source_data']['type'] ?? 'card');
        $order->update_meta_data('_paymob_card_type', $payment_response['source_data']['sub_type'] ?? '');
        $order->update_meta_data('_paymob_amount_cents', $payment_response['amount_cents'] ?? '');
        $order->update_meta_data('_paymob_currency', $payment_response['currency'] ?? '');
        $order->save();
    }

    /**
     * Add merchant order ID note to order
     */
    private function add_merchant_order_id_note($order, $payment_response) {
        if (isset($payment_response['merchant_order_id'])) {
            $order->add_order_note('Paymob : Merchant Order ID Is ' . $payment_response['merchant_order_id']);
        }
    }

    /**
     * Get payment error message
     */
    private function get_payment_error_message($payment_response) {
        if (isset($payment_response['data']['message'])) {
            return $payment_response['data']['message'];
        }
        
        if (isset($payment_response['error_occured']) && $payment_response['error_occured'] === 'true') {
            return 'Payment processing error occurred';
        }
        
        return 'Unknown payment error';
    }

}

