<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;


// Define the function to run during plugin activation
function windrose_plugin_activate() {
    error_log( 'Activating Windrose' );

    // Create a custom database table for subscription
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $subscription_main_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_MAIN_TABLE;
    $create_main_table_query = "CREATE TABLE $subscription_main_table (
        id bigint(9) NOT NULL AUTO_INCREMENT,
        order_id text NOT NULL,
        product_id text NOT NULL,
        user_id text NOT NULL,
        payment_token text NULL,
        schedule mediumint(9) NOT NULL,
        quantity mediumint(9) NOT NULL,
        status text NOT NULL,
        total_orders int NOT NULL,
        active_date text NULL,
        time_stamp timestamp NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    dbDelta($create_main_table_query);


    $subscription_order_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_ORDER_TABLE;

    $create_order_table_query = "CREATE TABLE $subscription_order_table (
        id bigint(9) NOT NULL AUTO_INCREMENT,
        subscription_id int NOT NULL,
        main_order_id int NOT NULL,
        user_id int NOT NULL,
        product_id int NOT NULL,
        quantity int NOT NULL,
        payment_token text NOT NULL,
        attempts int NOT NULL,
        status text NOT NULL,
        sequence int NOT NULL,
        time_stamp bigint(20) NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    dbDelta($create_order_table_query);

    // Create subscription payment logs table
    $subscription_payment_logs_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_PAYMENT_LOGS_TABLE;

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

    dbDelta($create_payment_logs_table_query);

    // Set initial database version
    add_option('windrose_db_version', '1.2');

    flush_rewrite_rules();
    
}

?>