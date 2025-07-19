<?php
// Debug script for Windrose Subscription Cron and Payment Logs
// Add this to your theme's functions.php temporarily

function windrose_debug_cron_and_logs() {
    echo "<h2>Windrose Subscription Debug</h2>";
    
    // 1. Check if payment logs table exists
    global $wpdb;
    $table_name = $wpdb->prefix . 'windrose_subscription_payment_logs';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    echo "<h3>1. Payment Logs Table Status</h3>";
    echo "Table exists: " . ($table_exists ? 'YES' : 'NO') . "<br>";
    
    if (!$table_exists) {
        echo "Creating table...<br>";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $create_payment_logs_table_query = "CREATE TABLE $table_name (
            id bigint(9) NOT NULL AUTO_INCREMENT,
            subscription_order_id bigint(9) NOT NULL,
            wc_order_id bigint(9) NOT NULL,
            user_id bigint(9) NOT NULL,
            payment_token_id bigint(9) NULL,
            payment_token text NULL,
            payment_mode text NOT NULL,
            integration_id text NULL,
            intention_id text NULL,
            transaction_id text NULL,
            amount_cents bigint(9) NOT NULL,
            currency text NOT NULL,
            status text NOT NULL,
            error_message text NULL,
            paymob_response text NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY subscription_order_id (subscription_order_id),
            KEY wc_order_id (wc_order_id),
            KEY user_id (user_id),
            KEY status (status(50)),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        $result = dbDelta($create_payment_logs_table_query);
        echo "Table creation result: " . print_r($result, true) . "<br>";
        
        // Check again
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        echo "Table exists after creation: " . ($table_exists ? 'YES' : 'NO') . "<br>";
    }
    
    // 2. Check for due subscription orders
    echo "<h3>2. Due Subscription Orders</h3>";
    $subscription_order_table = $wpdb->prefix . 'windrose_subscription_orders';
    $current_timestamp = time();
    
    $total_due = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $subscription_order_table 
         WHERE time_stamp <= %d AND status = 'upcoming'",
        $current_timestamp
    ));
    
    echo "Total due orders: " . $total_due . "<br>";
    echo "Current timestamp: " . $current_timestamp . " (" . date('Y-m-d H:i:s', $current_timestamp) . ")<br>";
    
    if ($total_due > 0) {
        $due_orders = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $subscription_order_table 
             WHERE time_stamp <= %d AND status = 'upcoming' 
             ORDER BY time_stamp ASC 
             LIMIT 5",
            $current_timestamp
        ));
        
        echo "Sample due orders:<br>";
        foreach ($due_orders as $order) {
            echo "- ID: {$order->id}, User: {$order->user_id}, Product: {$order->product_id}, Due: " . date('Y-m-d H:i:s', $order->time_stamp) . "<br>";
        }
    }
    
    // 3. Check payment tokens
    echo "<h3>3. Payment Tokens</h3>";
    $paymob_tokens_table = $wpdb->prefix . 'paymob_cards_token';
    $tokens_exist = $wpdb->get_var("SHOW TABLES LIKE '$paymob_tokens_table'") == $paymob_tokens_table;
    
    echo "Paymob tokens table exists: " . ($tokens_exist ? 'YES' : 'NO') . "<br>";
    
    if ($tokens_exist) {
        $token_count = $wpdb->get_var("SELECT COUNT(*) FROM $paymob_tokens_table");
        echo "Total tokens: " . $token_count . "<br>";
        
        if ($token_count > 0) {
            $sample_tokens = $wpdb->get_results("SELECT * FROM $paymob_tokens_table LIMIT 3");
            echo "Sample tokens:<br>";
            foreach ($sample_tokens as $token) {
                echo "- User ID: {$token->user_id}, Token ID: {$token->id}<br>";
            }
        }
    }
    
    // 4. Test payment logger
    echo "<h3>4. Test Payment Logger</h3>";
    if ($table_exists) {
        // Test inserting a record
        $test_data = array(
            'subscription_order_id' => 999,
            'wc_order_id' => 999,
            'user_id' => 1,
            'payment_mode' => 'test',
            'amount_cents' => 100000,
            'currency' => 'USD',
            'status' => 'test',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table_name, $test_data);
        
        if ($result !== false) {
            echo "Test log insert successful. ID: " . $wpdb->insert_id . "<br>";
            
            // Clean up test record
            $wpdb->delete($table_name, array('id' => $wpdb->insert_id));
            echo "Test record cleaned up.<br>";
        } else {
            echo "Test log insert failed: " . $wpdb->last_error . "<br>";
        }
    }
    
    // 5. Run cron manually
    echo "<h3>5. Manual Cron Test</h3>";
    if (isset($_GET['run_cron'])) {
        echo "Running cron manually...<br>";
        
        // Include the cron manager
        require_once WP_CONTENT_DIR . '/plugins/windrose-subscription/includes/WindroseCronManager.php';
        $cron_manager = new WindroseSubscription\Includes\WindroseCronManager();
        $result = $cron_manager->process_subscription_orders();
        
        echo "Cron result:<br>";
        echo "Success: " . count($result['success']) . "<br>";
        echo "Errors: " . count($result['error']) . "<br>";
        echo "Total due: " . ($result['total_due'] ?? 'N/A') . "<br>";
        
        if (!empty($result['error'])) {
            echo "Errors:<br>";
            foreach ($result['error'] as $error) {
                echo "- " . $error['error'] . "<br>";
            }
        }
    } else {
        echo "<a href='?run_cron=1'>Run Cron Manually</a><br>";
    }
    
    // 6. Check existing payment logs
    echo "<h3>6. Existing Payment Logs</h3>";
    if ($table_exists) {
        $existing_logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 5");
        
        if (!empty($existing_logs)) {
            echo "Recent payment logs:<br>";
            foreach ($existing_logs as $log) {
                echo "- ID: {$log->id}, Order: {$log->wc_order_id}, Status: {$log->status}, Created: {$log->created_at}<br>";
            }
        } else {
            echo "No payment logs found.<br>";
        }
    }
}

// Run the debug function
add_action('admin_notices', 'windrose_debug_cron_and_logs');
?> 