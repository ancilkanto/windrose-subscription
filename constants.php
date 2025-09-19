<?php
defined( 'ABSPATH' ) || exit;

! defined( 'WINDROS_DIR' ) && define( 'WINDROS_DIR', plugin_dir_path( __FILE__ ) );
! defined( 'WINDROS_URL' ) && define( 'WINDROS_URL', plugin_dir_url(__FILE__) );
! defined( 'WINDROS_INIT' ) && define( 'WINDROS_INIT', plugin_basename( __FILE__ ) );
! defined( 'WINDROS_INC' ) && define( 'WINDROS_INC', WINDROS_DIR . 'includes/' );

$windros_subscription_frequencies = array(
    '1' => esc_html('Every 1 Day'),
    '7' => esc_html('Every 1 Week'),
    '14' => esc_html('Every 2 Weeks'),
    '21' => esc_html('Every 3 Weeks'),
    '28' => esc_html('Every 4 Weeks')
);
$windros_subscription_frequencies_ar = array(
    '1' => esc_html('Every 1 Day Arabic'),
    '7' => esc_html('Every 1 Week Arabic'),
    '14' => esc_html('Every 2 Weeks Arabic'),
    '21' => esc_html('Every 3 Weeks Arabic'),
    '28' => esc_html('Every 4 Weeks Arabic')
);
// Check if current language is Arabic
$current_language = get_locale();
$is_arabic = (strpos($current_language, 'ar') === 0);

// Use Arabic array if language is Arabic, otherwise use English array
$windros_subscription_frequencies_final = $is_arabic ? $windros_subscription_frequencies_ar : $windros_subscription_frequencies;

! defined( 'WINDROS_FREQUENCY' ) && define( 'WINDROS_FREQUENCY', $windros_subscription_frequencies_final );
! defined( 'WINDROS_SUBSCRIPTION_STATUS' ) && define( 'WINDROS_SUBSCRIPTION_STATUS', array(
    'processing' => esc_html__('Processing', 'windros-subscription'),
    'active' => esc_html__('Active', 'windros-subscription'),
    'paused' => esc_html__('Paused', 'windros-subscription'),
    'cancel' => esc_html__('Cancelled', 'windros-subscription'),
    'expired' => esc_html__('Expired', 'windros-subscription')
));
! defined( 'WINDROS_SUBSCRIPTION_ORDER_STATUS' ) && define( 'WINDROS_SUBSCRIPTION_ORDER_STATUS', array(
    'upcoming' => esc_html__('Upcoming', 'windros-subscription'),
    'past' => esc_html__('Past', 'windros-subscription'),
    'skipped' => esc_html__('Skipped', 'windros-subscription'),
    'cancelled' => esc_html__('Cancelled', 'windros-subscription')
));
! defined( 'WINDROS_SUBSCRIPTION_MAIN_TABLE' ) && define( 'WINDROS_SUBSCRIPTION_MAIN_TABLE', 'windrose_subscriptions' );
! defined( 'WINDROS_SUBSCRIPTION_ORDER_TABLE' ) && define( 'WINDROS_SUBSCRIPTION_ORDER_TABLE', 'windrose_subscription_orders' );
! defined( 'WINDROS_SUBSCRIPTION_PAYMENT_LOGS_TABLE' ) && define( 'WINDROS_SUBSCRIPTION_PAYMENT_LOGS_TABLE', 'windrose_subscription_payment_logs' );
! defined( 'WINDROS_DROP_TABLES' ) && define( 'WINDROS_DROP_TABLES', true );
