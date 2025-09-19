<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;



function windrose_plugin_uninstall() {

    error_log( 'Uninstalling Windrose' );
    // Global database object
    global $wpdb;

    if(WINDROS_DROP_TABLES){
        // Define table names (replace with your actual custom table names)
        $subscription_main_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_MAIN_TABLE;
        $subscription_order_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_ORDER_TABLE;
        // $table_name_2 = $wpdb->prefix . 'custom_table_2';

        // Prepare the SQL queries to drop the tables        
        $sql = "DROP TABLE IF EXISTS {$subscription_main_table}, {$subscription_order_table};";

        // Execute the queries
        $wpdb->query( $sql );

        

    }

    // Clean up options
    delete_option( 'windrose_endpoints_flushed' );
    delete_option( 'windrose_db_version' );
    
}
?>