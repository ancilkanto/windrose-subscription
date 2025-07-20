<?php
// Test script to verify payment logs table functionality
// Include this in your theme's functions.php temporarily or run directly

function windrose_test_payment_logs() {
    global $wpdb;
    
    echo "Testing Windrose Payment Logs Table...\n";
    
    // Check if table exists
    $table_name = $wpdb->prefix . 'windrose_subscription_payment_logs';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    echo "Table exists: " . ($table_exists ? 'YES' : 'NO') . "\n";
    
    if (!$table_exists) {
        echo "Table does not exist. Creating it...\n";
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
        echo "Table creation result: " . print_r($result, true) . "\n";
        
        // Check again
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        echo "Table exists after creation: " . ($table_exists ? 'YES' : 'NO') . "\n";
    }
    
    if ($table_exists) {
        // Test inserting a record
        echo "Testing insert...\n";
        
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
            echo "Insert successful. ID: " . $wpdb->insert_id . "\n";
            
            // Test reading the record
            $test_record = $wpdb->get_row("SELECT * FROM $table_name WHERE id = " . $wpdb->insert_id);
            echo "Read test record: " . ($test_record ? 'SUCCESS' : 'FAILED') . "\n";
            
            // Clean up test record
            $wpdb->delete($table_name, array('id' => $wpdb->insert_id));
            echo "Test record cleaned up.\n";
        } else {
            echo "Insert failed: " . $wpdb->last_error . "\n";
        }
    }
    
    echo "Test completed.\n";
}

// Run the test if this file is accessed directly
if (basename(__FILE__) === 'test-payment-logs.php') {
    windrose_test_payment_logs();
}
?> 