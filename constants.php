<?php
defined( 'ABSPATH' ) || exit;

! defined( 'WINDROS_DIR' ) && define( 'WINDROS_DIR', plugin_dir_path( __FILE__ ) );
! defined( 'WINDROS_URL' ) && define( 'WINDROS_URL', plugin_dir_url(__FILE__) );
! defined( 'WINDROS_INIT' ) && define( 'WINDROS_INIT', plugin_basename( __FILE__ ) );
! defined( 'WINDROS_INC' ) && define( 'WINDROS_INC', WINDROS_DIR . 'includes/' );

$windros_subscription_frequencies = array(
    '1' => 'Every 1 Day',
    '7' => 'Every 1 Week',
    '14' => 'Every 2 Weeks',
    '21' => 'Every 3 Weeks',
    '28' => 'Every 4 Weeks'
);

! defined( 'WINDROS_FREQUENCY' ) && define( 'WINDROS_FREQUENCY', $windros_subscription_frequencies );
! defined( 'WINDROS_SUBSCRIPTION_STATUS' ) && define( 'WINDROS_SUBSCRIPTION_STATUS', array(
    'processing' => 'Processing',
    'active' => 'Active',
    'paused' => 'Paused',
    'cancel' => 'Cancelled',
    'expired' => 'Expired'
));
! defined( 'WINDROS_SUBSCRIPTION_ORDER_STATUS' ) && define( 'WINDROS_SUBSCRIPTION_ORDER_STATUS', array(
    'upcoming' => 'Upcoming',
    'past' => 'Past',
    'skipped' => 'Skipped',
    'cancelled' => 'Cancelled'
));
! defined( 'WINDROS_SUBSCRIPTION_MAIN_TABLE' ) && define( 'WINDROS_SUBSCRIPTION_MAIN_TABLE', 'windrose_subscriptions' );
! defined( 'WINDROS_SUBSCRIPTION_ORDER_TABLE' ) && define( 'WINDROS_SUBSCRIPTION_ORDER_TABLE', 'windrose_subscription_orders' );
! defined( 'WINDROS_SUBSCRIPTION_PAYMENT_LOGS_TABLE' ) && define( 'WINDROS_SUBSCRIPTION_PAYMENT_LOGS_TABLE', 'windrose_subscription_payment_logs' );
! defined( 'WINDROS_DROP_TABLES' ) && define( 'WINDROS_DROP_TABLES', true );
