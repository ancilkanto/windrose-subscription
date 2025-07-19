<?php
namespace WindroseSubscription\Includes;

defined( 'WINDROS_INIT' ) || exit;

class WindrosePaymentLogger {
    
    /**
     * Create a new payment log entry
     */
    public static function create_log($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . WINDROS_SUBSCRIPTION_PAYMENT_LOGS_TABLE;
        
        // Debug logging
        error_log('Windrose Payment Logger: Attempting to create log for order ' . ($data['wc_order_id'] ?? 'unknown'));
        
        $current_time = current_time('mysql');
        
        $log_data = array(
            'subscription_order_id' => $data['subscription_order_id'] ?? 0,
            'wc_order_id' => $data['wc_order_id'] ?? 0,
            'user_id' => $data['user_id'] ?? 0,
            'payment_token_id' => $data['payment_token_id'] ?? null,
            'payment_token' => $data['payment_token'] ?? null,
            'payment_mode' => $data['payment_mode'] ?? 'unknown',
            'integration_id' => $data['integration_id'] ?? null,
            'intention_id' => $data['intention_id'] ?? null,
            'transaction_id' => $data['transaction_id'] ?? null,
            'amount_cents' => $data['amount_cents'] ?? 0,
            'currency' => $data['currency'] ?? '',
            'status' => $data['status'] ?? 'pending',
            'error_message' => $data['error_message'] ?? null,
            'paymob_response' => $data['paymob_response'] ?? null,
            'created_at' => $current_time,
            'updated_at' => $current_time
        );
        
        $result = $wpdb->insert($table_name, $log_data);
        
        if ($result === false) {
            error_log('Windrose Payment Logger: Failed to insert log entry - ' . $wpdb->last_error);
            return false;
        }
        
        $log_id = $wpdb->insert_id;
        error_log('Windrose Payment Logger: Successfully created log entry with ID: ' . $log_id);
        
        return $log_id;
    }
    
    /**
     * Update an existing payment log entry
     */
    public static function update_log($log_id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . WINDROS_SUBSCRIPTION_PAYMENT_LOGS_TABLE;
        
        $update_data = array(
            'updated_at' => current_time('mysql')
        );
        
        // Merge provided data with update data
        $update_data = array_merge($update_data, $data);
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $log_id),
            null,
            array('%d')
        );
        
        if ($result === false) {
            error_log('Windrose Payment Logger: Failed to update log entry - ' . $wpdb->last_error);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get payment logs for a subscription order
     */
    public static function get_logs_by_subscription_order($subscription_order_id, $limit = 10) {
        global $wpdb;
        $table_name = $wpdb->prefix . WINDROS_SUBSCRIPTION_PAYMENT_LOGS_TABLE;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE subscription_order_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d",
            $subscription_order_id,
            $limit
        ));
    }
    
    /**
     * Get payment logs for a WooCommerce order
     */
    public static function get_logs_by_wc_order($wc_order_id, $limit = 10) {
        global $wpdb;
        $table_name = $wpdb->prefix . WINDROS_SUBSCRIPTION_PAYMENT_LOGS_TABLE;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE wc_order_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d",
            $wc_order_id,
            $limit
        ));
    }
    
    /**
     * Get payment logs for a user
     */
    public static function get_logs_by_user($user_id, $limit = 20) {
        global $wpdb;
        $table_name = $wpdb->prefix . WINDROS_SUBSCRIPTION_PAYMENT_LOGS_TABLE;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d",
            $user_id,
            $limit
        ));
    }
    
    /**
     * Get payment logs by status
     */
    public static function get_logs_by_status($status, $limit = 50) {
        global $wpdb;
        $table_name = $wpdb->prefix . WINDROS_SUBSCRIPTION_PAYMENT_LOGS_TABLE;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE status = %s 
             ORDER BY created_at DESC 
             LIMIT %d",
            $status,
            $limit
        ));
    }
    
    /**
     * Get recent payment logs
     */
    public static function get_recent_logs($limit = 20) {
        global $wpdb;
        $table_name = $wpdb->prefix . WINDROS_SUBSCRIPTION_PAYMENT_LOGS_TABLE;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        ));
    }
    
    /**
     * Get payment statistics
     */
    public static function get_payment_statistics($days = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . WINDROS_SUBSCRIPTION_PAYMENT_LOGS_TABLE;
        
        $date_limit = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                status,
                COUNT(*) as count,
                SUM(amount_cents) as total_amount_cents
             FROM $table_name 
             WHERE created_at >= %s 
             GROUP BY status",
            $date_limit
        ));
        
        $result = array(
            'total_transactions' => 0,
            'successful_transactions' => 0,
            'failed_transactions' => 0,
            'total_amount_cents' => 0,
            'successful_amount_cents' => 0,
            'success_rate' => 0
        );
        
        foreach ($stats as $stat) {
            $result['total_transactions'] += $stat->count;
            $result['total_amount_cents'] += $stat->total_amount_cents;
            
            if ($stat->status === 'success') {
                $result['successful_transactions'] = $stat->count;
                $result['successful_amount_cents'] = $stat->total_amount_cents;
            } elseif ($stat->status === 'failed') {
                $result['failed_transactions'] = $stat->count;
            }
        }
        
        if ($result['total_transactions'] > 0) {
            $result['success_rate'] = round(($result['successful_transactions'] / $result['total_transactions']) * 100, 2);
        }
        
        return $result;
    }
    
    /**
     * Log payment initiation
     */
    public static function log_payment_initiation($subscription_order, $order_id, $payment_token, $payment_mode, $integration_ids) {
        $order = wc_get_order( $order_id );
        $log_data = array(
            'subscription_order_id' => $subscription_order->id,
            'wc_order_id' => $order->get_id(),
            'user_id' => $order->get_customer_id(),
            'payment_token_id' => $payment_token->token_id ?? null,
            'payment_token' => $payment_token->token ?? null,
            'payment_mode' => $payment_mode,
            'integration_id' => implode(',', $integration_ids),
            'amount_cents' => $order->get_total() * 1000,
            'currency' => get_woocommerce_currency(),
            'status' => 'initiated',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        return self::create_log($log_data);
    }
    
    /**
     * Log payment intention creation
     */
    public static function log_intention_created($log_id, $intention_response) {
        $intention_id = null;
        if (isset($intention_response['id'])) {
            $intention_id = $intention_response['id'];
        }
        
        $update_data = array(
            'intention_id' => $intention_id,
            'status' => 'intention_created',
            'paymob_response' => json_encode($intention_response)
        );
        
        if (isset($intention_response['error_occured']) && $intention_response['error_occured'] === 'true') {
            $update_data['status'] = 'intention_failed';
            $update_data['error_message'] = $intention_response['data']['message'] ?? 'Intention creation failed';
        }
        
        return self::update_log($log_id, $update_data);
    }
    
    /**
     * Log payment processing
     */
    public static function log_payment_processing($log_id, $payment_response) {
        $update_data = array(
            'transaction_id' => $payment_response['id'] ?? null,
            'status' => 'processing',
            'paymob_response' => json_encode($payment_response)
        );
        
        if (isset($payment_response['error_occured']) && $payment_response['error_occured'] === 'true') {
            $update_data['status'] = 'failed';
            $update_data['error_message'] = $payment_response['data']['message'] ?? 'Payment processing failed';
        } elseif (isset($payment_response['success']) && $payment_response['success'] === 'true') {
            $update_data['status'] = 'success';
        }
        
        return self::update_log($log_id, $update_data);
    }
    
    /**
     * Log payment error
     */
    public static function log_payment_error($log_id, $error_message, $additional_data = null) {
        $update_data = array(
            'status' => 'error',
            'error_message' => $error_message
        );
        
        if ($additional_data) {
            $update_data['paymob_response'] = json_encode($additional_data);
        }
        
        return self::update_log($log_id, $update_data);
    }
} 