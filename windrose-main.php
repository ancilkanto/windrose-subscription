<?php
/* Plugin Name: Windrose Subscription
* Plugin URI: https://github.com/ancilkanto/windros-subscription
 * Description: <code><strong>Windrose Subscription</strong></code> allows enabling automatic recurring payments on your products. Once you buy a subscription-based product, the plugin will renew the payment automatically based on your own settings.
 * Version: 1.0
 * Author: Ancil K Anto
 * Author URI: https://ancil.dev/
 * Text Domain: windros-subscription
 * Domain Path: /languages/
 */



defined( 'ABSPATH' ) || exit;

// Define Plugin Constants
require_once plugin_dir_path( __FILE__ ).'constants.php';

// Check and update database if needed
add_action('plugins_loaded', 'windrose_check_database_version');

function windrose_check_database_version() {
    $current_db_version = get_option('windrose_db_version', '1.0');
    $plugin_db_version = '1.2'; // New version with fixed payment logs table indexes
    
    if (version_compare($current_db_version, $plugin_db_version, '<')) {
        windrose_create_payment_logs_table();
        update_option('windrose_db_version', $plugin_db_version);
    }
}

function windrose_create_payment_logs_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

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
}


// Register the activation hook
register_activation_hook(__FILE__, 'windrose_plugin_activate');
require_once WINDROS_DIR.'install-plugin.php';

// Register the deactivation hook
register_deactivation_hook(__FILE__, 'windrose_plugin_deactivate');
register_uninstall_hook( __FILE__, 'windrose_plugin_uninstall' );
require_once WINDROS_DIR.'uninstall-plugin.php';

// Deactivation function
function windrose_plugin_deactivate() {
    // Flush rewrite rules to remove custom endpoints
    flush_rewrite_rules();
    
    // Delete the endpoints flushed option
    delete_option( 'windrose_endpoints_flushed' );
}

// Manual flush rewrite rules function
function windrose_manual_flush_rewrite_rules() {
    if ( isset( $_GET['windrose_flush_rules'] ) && current_user_can( 'manage_options' ) ) {
        flush_rewrite_rules();
        delete_option( 'windrose_endpoints_flushed' );
        wp_redirect( admin_url( 'admin.php?page=wc-settings&windrose_flushed=1' ) );
        exit;
    }
}
add_action( 'admin_init', 'windrose_manual_flush_rewrite_rules' );



require_once 'vendor/autoload.php';



class MainWindroseClass {
    public function __construct() {
        // Load text domain early to prevent translation loading errors
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('windros-subscription', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function init() {
        $this->load_includes();
    }

    public function load_includes() {
        new WindroseSubscription\Includes\AdminProductSubscriptionOptions();  
        new WindroseSubscription\Includes\WindroseRegisterShortcodes();  
        new WindroseSubscription\Includes\WindroseModifyProductLoopData();
        new WindroseSubscription\Includes\WindroseModifyProductListingLoop();
        new WindroseSubscription\Includes\WindroseModifyProductSinglePage();
        new WindroseSubscription\Includes\WindroseSubscriptionCart();
        new WindroseSubscription\Includes\WindroseSubscriptionCheckout();
        new WindroseSubscription\Includes\WindroseCreateSubscription();
        new WindroseSubscription\Includes\WindroseMyAccountInit();
        new WindroseSubscription\Includes\WindroseActivateSubscription();
        new WindroseSubscription\Includes\WindroseCreateSubscriptionOrder();
        new WindroseSubscription\Includes\WindroseUpdateSubscription();
        new WindroseSubscription\Includes\WindrosePauseSubscription();
        new WindroseSubscription\Includes\WindroseCancelSubscription();
        new WindroseSubscription\Includes\WindroseSkipSubscription();
        new WindroseSubscription\Includes\WindroseReactivateSubscription();
        // admin side
        new WindroseSubscription\Includes\WindroseAdminSubscriptionList();
        new WindroseSubscription\Includes\WindroseAdminSubscriptionDetailView();
        // cron management
        new WindroseSubscription\Includes\WindroseCronManager();
        new WindroseSubscription\Includes\WindroseCronSettings();
        new WindroseSubscription\Includes\WindroseSubscriptionOrderPaymentInit();
        // payment logging
        new WindroseSubscription\Includes\WindrosePaymentLogger();
        // subscription logs admin page
        new WindroseSubscription\Includes\WindroseSubscriptionLogs();
        // database updater
        new WindroseSubscription\Includes\WindroseDatabaseUpdater();
        new WindroseSubscription\Includes\WindroseSubscriptionNotification();

        
    }

    
}

new MainWindroseClass();




// Add a custom label next to the product title in WooCommerce admin products list
add_action('manage_product_posts_custom_column', 'show_custom_label_in_product_column', 100, 2);

// Display custom label next to the product title in WooCommerce admin
function show_custom_label_in_product_column($column, $post_id) {
    if ($column === 'name') { // 'name' is the product title column
        // Get the product object
        $product = wc_get_product($post_id);

        // Define your custom label
        $custom_label = '';
        $enable_subscription = get_post_meta( $post_id, '_enable_subscription', true ); // Get the data - Checbox 1
        if( ! empty( $enable_subscription ) && $enable_subscription == 'yes' ){
            $custom_label = '<strong> - <span class="post-state">Subscription Product</span></strong>';
        }

        // Display the product title with the custom label appended
        echo $custom_label;
    }
}


function windrose_get_day_with_suffix($day) {
    if (!in_array(($day % 100), array(11, 12, 13))) {
        switch ($day % 10) {
            case 1: return $day . __('st', 'windros-subscription');
            case 2: return $day . __('nd', 'windros-subscription');
            case 3: return $day . __('rd', 'windros-subscription');
        }
    }
    return $day . __('th', 'windros-subscription');
}

function windrose_get_subscrption_products() {
    $subscription_products = array();
    $products = new WP_Query( array (
        'post_type'         => 'product',
        'post_status'       => 'publish',
        'posts_per_page'    => '-1',
        'meta_query'        => array(
            'relation'  => 'AND',
            array(
                'key'       => '_enable_subscription',
                'value'     => 'yes',
                'compare'   => '='
            )
        )
    ));

    if ( $products->have_posts() ) {
        while ( $products->have_posts() ) : $products->the_post(); 
            $subscription_products[] = (object) array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
            );
        endwhile;
    }

    return (object) $subscription_products;
}

function windrose_get_customers() {
    // Define the query to get all users with the customer role
    $args = [
        'role'    => 'customer',
        'orderby' => 'ID',
        'order'   => 'ASC',
        'fields'  => 'all', // Get full user data
    ];

    $user_query = new WP_User_Query($args);
    $customers = [];

    if (!empty($user_query->get_results())) {
        foreach ($user_query->get_results() as $user) {
            $user_id = $user->ID;
            $billing_first_name = get_user_meta($user_id, 'billing_first_name', true);
            $billing_last_name = get_user_meta($user_id, 'billing_last_name', true);

            // Add customer data to the array
            $customers[] = (object) array(
                'user_id'           => $user_id,
                'customer_name'     => $billing_first_name.' '.$billing_last_name
            );
        }
    }

    return (object) $customers;
}


function windrose_get_timestamp_object($offest = 0, $date = null) {
    // Always use GMT+0
    $timezone = 'Etc/GMT';
    date_default_timezone_set($timezone);

    // Get the base date - use provided date or current date
    $base_date = $date ? $date : date('Y-m-d');
    $current_date = date('Y-m-d H:i:s');

    // Get the daily cron time option (format: 'HH:MM')
    $cron_time = get_option('windrose_daily_cron_time', '03:00');
    list($cron_hour, $cron_minute) = explode(':', $cron_time);
    $cron_hour = intval($cron_hour);
    $cron_minute = intval($cron_minute);

    // Subtract 3 hours for subscription trigger timestamp
    $target_hour = ($offest == 0) ? $cron_hour : ($cron_hour - 3);
    if ($target_hour < 0) {
        $target_hour += 24;
    }

    // Build the timestamp for the base date at (cron_time - 3 hours)
    $target_time = sprintf('%02d:%02d:00', $target_hour, $cron_minute);
    $target_datetime = $base_date . ' ' . $target_time;
    $timestamp = strtotime($target_datetime);

    // Add offset days if needed
    if ($offest != 0) {
        $timestamp = strtotime("+{$offest} days", $timestamp);
    }

    return (object) array(
        'date' => $current_date,
        'timestamp' => $timestamp
    );
}



// CALL TO CUSTOM CLI COMMAND for Cron

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command('windrose-cli', 'WindroseSubscription\Includes\WindroseCLI');
}

// Email template helper function
if (!function_exists('windrose_get_email_template_path')) {
    function windrose_get_email_template_path() {
        return plugin_dir_path(__FILE__) . 'templates/';
    }
}

// Helper function to get next delivery date for a subscription
if (!function_exists('windrose_get_next_delivery_date')) {
    function windrose_get_next_delivery_date($subscription_id) {
        global $wpdb;
        $subscription_order_table = $wpdb->prefix . (defined('WINDROS_SUBSCRIPTION_ORDER_TABLE') ? WINDROS_SUBSCRIPTION_ORDER_TABLE : 'windrose_subscription_order');
        
        $upcoming_order = $wpdb->get_row($wpdb->prepare(
            "SELECT time_stamp FROM $subscription_order_table 
             WHERE subscription_id = %d AND status = 'upcoming' 
             ORDER BY time_stamp ASC LIMIT 1",
            $subscription_id
        ));
        
        if ($upcoming_order) {
            return date('F d, Y', $upcoming_order->time_stamp);
        }
        
        return __('No upcoming deliveries', 'windros-subscription');
    }
}



function windrose_register_email_classes($emails) {
    // Customer emails
    $emails['WindroseSubscriptionActivatedEmail'] = new WindroseSubscription\Includes\Emails\WindroseSubscriptionActivatedEmail();
    $emails['WindroseSubscriptionOrderProcessedEmail'] = new WindroseSubscription\Includes\Emails\WindroseSubscriptionOrderProcessedEmail();
    $emails['WindroseSubscriptionOrderFailedEmail'] = new WindroseSubscription\Includes\Emails\WindroseSubscriptionOrderFailedEmail();
    $emails['WindroseSubscriptionPausedEmail'] = new WindroseSubscription\Includes\Emails\WindroseSubscriptionPausedEmail();
    $emails['WindroseSubscriptionCancelledEmail'] = new WindroseSubscription\Includes\Emails\WindroseSubscriptionCancelledEmail();
    $emails['WindroseSubscriptionSkippedEmail'] = new WindroseSubscription\Includes\Emails\WindroseSubscriptionSkippedEmail();
    
    // Admin emails — use class names as keys!
    $emails['WindroseSubscriptionAdminActivatedEmail'] = new WindroseSubscription\Includes\Emails\WindroseSubscriptionAdminActivatedEmail();
    $emails['WindroseSubscriptionAdminOrderFailedEmail'] = new WindroseSubscription\Includes\Emails\WindroseSubscriptionAdminOrderFailedEmail();
    $emails['WindroseSubscriptionAdminPausedEmail'] = new WindroseSubscription\Includes\Emails\WindroseSubscriptionAdminPausedEmail();
    $emails['WindroseSubscriptionAdminCancelledEmail'] = new WindroseSubscription\Includes\Emails\WindroseSubscriptionAdminCancelledEmail();
    $emails['WindroseSubscriptionAdminSkippedEmail'] = new WindroseSubscription\Includes\Emails\WindroseSubscriptionAdminSkippedEmail();
    
    return $emails;
}
add_filter('woocommerce_email_classes', 'windrose_register_email_classes');


function windrose_get_arabic_product_title($product_id) {
    if ($product_id == null || $product_id == '12345'){
        return null;
    }

    $product = wc_get_product($product_id);
    if(!$product){
        return null;
    }

    $sku = $product->get_sku();
    $arabic_title = '';
    if ($sku) {
        // Get products with same SKU excluding current product ID
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'post__not_in' => array($product_id),
            'meta_query' => array(
                array(
                    'key' => '_sku',
                    'value' => $sku,
                    'compare' => '='
                )
            )
        );
        
        $products = get_posts($args);
        
        // Get Arabic title from first matching product
        if (!empty($products)) {
            $arabic_title = get_the_title($products[0]->ID);
        }
    }
    return $arabic_title;
}

function windrose_get_arabic_date($date_string) {
    if ($date_string == null){
        return $date_string;
    }

    if (class_exists('IntlDateFormatter')) {
        $formatter = new IntlDateFormatter(
            'ar', // Arabic locale
            IntlDateFormatter::LONG, // Date type
            IntlDateFormatter::NONE  // Time type
        );
        
        $timestamp = strtotime($date_string);
        return $formatter->format($timestamp);
    }
    
    // Fallback to basic Arabic month names
    $months = [
        'January' => 'يناير',
        'February' => 'فبراير',
        'March' => 'مارس',
        'April' => 'أبريل',
        'May' => 'مايو',
        'June' => 'يونيو',
        'July' => 'يوليو',
        'August' => 'أغسطس',
        'September' => 'سبتمبر',
        'October' => 'أكتوبر',
        'November' => 'نوفمبر',
        'December' => 'ديسمبر'
    ];
    
    $date = date('j F Y', strtotime($date_string));
    foreach ($months as $english => $arabic) {
        $date = str_replace($english, $arabic, $date);
    }
    
    // Convert numbers to Arabic numerals
    $arabic_numerals = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $english_numerals = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    
    return str_replace($english_numerals, $arabic_numerals, $date);
}

add_filter('woocommerce_order_created_via', function($via, $order){
    if ($order->get_created_via() === 'cron') {
        return 'Created via CRON';
    }
    return $via;
}, 10, 2);