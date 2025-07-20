<?php
namespace WindroseSubscription\Includes;

use stdClass;
use WP_CLI;
use WP_CLI_Command;
use WC_Order_Item_Product;

defined( 'WINDROS_INIT' ) || exit;  

class WindroseCLI extends WP_CLI_Command {

    public function create_subscription_order($args, $associative_args) {
        WP_CLI::log("Started Subscription Order Creation");

        // Use the same logic as the cron manager
        $cron_manager = new WindroseCronManager();
        $task_status = $cron_manager->process_subscription_orders();

        // Show detailed results
        if (isset($task_status['total_due']) && $task_status['total_due'] > 0) {
            WP_CLI::log("Total due subscriptions: " . $task_status['total_due']);
            WP_CLI::log("Successfully processed: " . count($task_status['success']));
            WP_CLI::log("Failed to process: " . count($task_status['error']));
        }

        if (empty($task_status['error'])) {
            $success_count = count($task_status['success']);
            if ($success_count > 0) {
                WP_CLI::success($success_count . " Subscription Orders Created Successfully.");
            } else {
                WP_CLI::success("No subscription orders were due for processing.");
            }
        } else {
            $error_count = count($task_status['error']);
            WP_CLI::warning($error_count . " orders failed to create.");
            
            // Log detailed errors
            foreach ($task_status['error'] as $error) {
                WP_CLI::log("Error for subscription #{$error['subscription_id']}: {$error['error']}");
            }
        }

        // Show summary
        $total_processed = count($task_status['success']) + count($task_status['error']);
        WP_CLI::log("Summary: " . count($task_status['success']) . " successful, " . count($task_status['error']) . " failed (Total: $total_processed)");
    }

    /**
     * Check cron status
     */
    public function cron_status($args, $associative_args) {
        $status = WindroseCronManager::get_cron_status();
        
        WP_CLI::log("Windrose Subscription Cron Status:");
        WP_CLI::log("Scheduled: " . ($status['is_scheduled'] ? 'Yes' : 'No'));
        
        if ($status['next_run']) {
            WP_CLI::log("Next Run: " . date('Y-m-d H:i:s', $status['next_run']));
        }
        
        if (!empty($status['last_run'])) {
            WP_CLI::log("Last Run: " . date('Y-m-d H:i:s', $status['last_run']['timestamp']));
            WP_CLI::log("Last Results: " . count($status['last_run']['results']['success']) . " successful, " . count($status['last_run']['results']['error']) . " failed");
        } else {
            WP_CLI::log("Last Run: Never");
        }
    }

    /**
     * Manually trigger cron processing
     */
    public function run_cron($args, $associative_args) {
        WP_CLI::log("Manually triggering subscription cron...");
        
        $cron_manager = new WindroseCronManager();
        $task_status = $cron_manager->process_subscription_orders();
        
        WP_CLI::success("Cron completed: " . count($task_status['success']) . " successful, " . count($task_status['error']) . " failed");
    }

    /**
     * Test subscription creation (for debugging)
     */
    public function test_subscription($args, $associative_args) {
        if (empty($args[0])) {
            WP_CLI::error("Please provide a subscription ID to test");
            return;
        }

        $subscription_id = intval($args[0]);
        
        WP_CLI::log("Testing subscription #{$subscription_id}");
        
        global $wpdb;
        $subscription_order_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_ORDER_TABLE;
        
        $subscription = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $subscription_order_table WHERE id = %d",
            $subscription_id
        ));

        if (!$subscription) {
            WP_CLI::error("Subscription not found");
            return;
        }

        WP_CLI::log("Found subscription: " . json_encode($subscription, JSON_PRETTY_PRINT));
    }

    /**
     * Check pending subscriptions
     */
    public function pending_subscriptions($args, $associative_args) {
        global $wpdb;
        $subscription_order_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_ORDER_TABLE;
        
        $current_timestamp = windrose_get_timestamp_object(0)->timestamp;
        
        // Get total count of due orders
        $total_due = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $subscription_order_table 
             WHERE time_stamp <= %d AND status = 'upcoming'",
            $current_timestamp
        ));

        // Get upcoming orders (next 7 days)
        $upcoming_7_days = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $subscription_order_table 
             WHERE time_stamp > %d AND time_stamp <= %d AND status = 'upcoming'",
            $current_timestamp,
            $current_timestamp + (7 * 24 * 60 * 60)
        ));

        // Get upcoming orders (next 30 days)
        $upcoming_30_days = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $subscription_order_table 
             WHERE time_stamp > %d AND time_stamp <= %d AND status = 'upcoming'",
            $current_timestamp,
            $current_timestamp + (30 * 24 * 60 * 60)
        ));

        WP_CLI::log("Windrose Subscription Status:");
        WP_CLI::log("Due for processing now: " . $total_due);
        WP_CLI::log("Due in next 7 days: " . $upcoming_7_days);
        WP_CLI::log("Due in next 30 days: " . $upcoming_30_days);

        if ($total_due > 0) {
            WP_CLI::warning("There are $total_due subscriptions due for processing. Run 'wp windrose-cli create_subscription_order' to process them.");
        } else {
            WP_CLI::success("No subscriptions are currently due for processing.");
        }

        // Show sample of due subscriptions if any
        if ($total_due > 0) {
            $sample_subscriptions = $wpdb->get_results($wpdb->prepare(
                "SELECT id, subscription_id, user_id, product_id, time_stamp 
                 FROM $subscription_order_table 
                 WHERE time_stamp <= %d AND status = 'upcoming' 
                 ORDER BY time_stamp ASC 
                 LIMIT 5",
                $current_timestamp
            ));

            WP_CLI::log("\nSample of due subscriptions:");
            foreach ($sample_subscriptions as $sub) {
                $product = wc_get_product($sub->product_id);
                $product_name = $product ? $product->get_name() : 'Unknown Product';
                $due_date = date('Y-m-d H:i:s', $sub->time_stamp);
                WP_CLI::log("  - ID: {$sub->id}, Product: $product_name, Due: $due_date");
            }

            if ($total_due > 5) {
                WP_CLI::log("  ... and " . ($total_due - 5) . " more");
            }
        }
    }
}

