<?php
defined( 'ABSPATH' ) || exit;

$windros_subscription_frequencies = array(
    '7' => __('Every 1 Week', 'windros-subscription'),
    '14' => __('Every 2 Weeks', 'windros-subscription'),
    '21' => __('Every 3 Weeks', 'windros-subscription'),
    '28' => __('Every 4 Weeks', 'windros-subscription')
);

! defined( 'WINDROS_DIR' ) && define( 'WINDROS_DIR', plugin_dir_path( __FILE__ ) );
! defined( 'WINDROS_URL' ) && define( 'WINDROS_URL', plugin_dir_url(__FILE__) );
! defined( 'WINDROS_INIT' ) && define( 'WINDROS_INIT', plugin_basename( __FILE__ ) );
! defined( 'WINDROS_INC' ) && define( 'WINDROS_INC', WINDROS_DIR . 'includes/' );
! defined( 'WINDROS_FREQUENCY' ) && define( 'WINDROS_FREQUENCY', $windros_subscription_frequencies );
! defined( 'WINDROS_SUBSCRIPTION_STATUS' ) && define( 'WINDROS_SUBSCRIPTION_STATUS', array(
    'processing' => __('Processing', 'windros-subscription'),
    'active' => __('Active', 'windros-subscription'),
    'paused' => __('Paused', 'windros-subscription'),
    'cancel' => __('Cancelled', 'windros-subscription'),
    'expired' => __('Expired', 'windros-subscription')
));
! defined( 'WINDROS_SUBSCRIPTION_ORDER_STATUS' ) && define( 'WINDROS_SUBSCRIPTION_ORDER_STATUS', array(
    'upcoming' => __('Upcoming', 'windros-subscription'),
    'past' => __('Past', 'windros-subscription'),
    'skipped' => __('Skipped', 'windros-subscription'),
    'cancelled' => __('Cancelled', 'windros-subscription')
));
! defined( 'WINDROS_SUBSCRIPTION_MAIN_TABLE' ) && define( 'WINDROS_SUBSCRIPTION_MAIN_TABLE', 'windrose_subscriptions' );
! defined( 'WINDROS_SUBSCRIPTION_ORDER_TABLE' ) && define( 'WINDROS_SUBSCRIPTION_ORDER_TABLE', 'windrose_subscription_orders' );
! defined( 'WINDROS_SUBSCRIPTION_PAYMENT_LOGS_TABLE' ) && define( 'WINDROS_SUBSCRIPTION_PAYMENT_LOGS_TABLE', 'windrose_subscription_payment_logs' );
! defined( 'WINDROS_DROP_TABLES' ) && define( 'WINDROS_DROP_TABLES', true );
