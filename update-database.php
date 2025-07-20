<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// This script can be run manually to create the payment logs table
// Include it in your theme's functions.php or run it directly

function windrose_create_payment_logs_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Create subscription payment logs table
    $subscription_payment_logs_table = $wpdb->prefix . 'windrose_subscription_payment_logs';

    $create_payment_logs_table_query = "CREATE TABLE $subscription_payment_logs_table (
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
    
    // Check if table was created successfully
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$subscription_payment_logs_table'") == $subscription_payment_logs_table;
    
    if ($table_exists) {
        echo "✅ Payment logs table created successfully!\n";
        return true;
    } else {
        echo "❌ Failed to create payment logs table.\n";
        return false;
    }
}

// Run the function if this file is accessed directly
if (basename(__FILE__) === 'update-database.php') {
    echo "Creating Windrose Subscription Payment Logs Table...\n";
    windrose_create_payment_logs_table();
    echo "Database update completed.\n";
}
?> 