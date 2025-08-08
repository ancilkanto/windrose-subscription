<?php
namespace WindroseSubscription\Includes;

defined( 'WINDROS_INIT' ) || exit;

class WindroseCronManager {
    
    public function __construct() {
        add_action('windrose_subscription_cron', [$this, 'process_subscription_orders']);
        
        // Cleanup on plugin deactivation
        register_deactivation_hook(WINDROS_INIT, [$this, 'clear_cron_events']);
    }

    /**
     * Schedule cron events
     */
    public function schedule_cron_events() {
        // Get the custom daily cron time
        $daily_cron_time = get_option('windrose_daily_cron_time', '02:00');
        
        // Parse the time to get hour and minute
        $time_parts = explode(':', $daily_cron_time);
        $hour = intval($time_parts[0]);
        $minute = intval($time_parts[1]);
        
        // Calculate the next run time for today
        $next_run = strtotime("today {$hour}:{$minute}:00");
        
        // If the time has already passed today, schedule for tomorrow
        if ($next_run <= time()) {
            $next_run = strtotime("tomorrow {$hour}:{$minute}:00");
        }
        
        // Clear existing cron first
        wp_clear_scheduled_hook('windrose_subscription_cron');
        
        // Schedule the daily cron at the custom time
        if (!wp_next_scheduled('windrose_subscription_cron')) {
            wp_schedule_event($next_run, 'daily', 'windrose_subscription_cron');
        }
    }

    /**
     * Clear cron events on plugin deactivation
     */
    public function clear_cron_events() {
        wp_clear_scheduled_hook('windrose_subscription_cron');
    }

    /**
     * Process subscription orders - called by both cron and CLI
     */
    public function process_subscription_orders() {
        // Check if cron is already running to prevent infinite loops
        $running_status = self::get_cron_running_status();
        if ($running_status['is_running']) {
            error_log('Windrose Cron: Already running, skipping execution. Started: ' . date('Y-m-d H:i:s', $running_status['started_at']));
            return array(
                'success' => array(),
                'error' => array(array('subscription_id' => 0, 'error' => 'Cron already running')),
                'total_processed' => 0,
                'total_due' => 0
            );
        }
        
        // Start tracking execution
        $this->start_cron_execution();
        
        // Debug logging
        error_log('Windrose Cron: Starting subscription order processing');
        
        try {
            global $wpdb;
            $subscription_order_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_ORDER_TABLE;

            $current_timestamp = windrose_get_timestamp_object(0)->timestamp;
            
            // Debug logging for timestamp
            error_log('Windrose Cron: Current timestamp: ' . $current_timestamp . ' (' . date('Y-m-d H:i:s', $current_timestamp) . ')');
            
            // Get total count of due orders
            $total_due = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $subscription_order_table 
                 WHERE time_stamp <= %d AND status = 'upcoming'",
                $current_timestamp
            ));

            // Debug logging for due orders
            error_log('Windrose Cron: Total due orders found: ' . $total_due);

            if ($total_due == 0) {
                error_log('Windrose Cron: No due orders found, ending execution');
                $this->end_cron_execution();
                return array('success' => array(), 'error' => array());
            }

            $task_status = array(
                'success' => array(),
                'error' => array(),
                'total_processed' => 0,
                'total_due' => $total_due
            );

            // Process in batches of 50
            $batch_size = 50;
            $offset = 0;
            $processed = 0;
            $start_time = time();
            $max_execution_time = 300; // 5 minutes max

            while ($processed < $total_due) {
                // Check for timeout
                if ((time() - $start_time) > $max_execution_time) {
                    error_log('Windrose Cron: Timeout reached (' . $max_execution_time . ' seconds), stopping execution');
                    $task_status['error'][] = array(
                        'subscription_id' => 0,
                        'error' => 'Cron timeout reached after ' . $max_execution_time . ' seconds'
                    );
                    break;
                }

                // Get batch of due orders
                $upcoming_order_data = $wpdb->get_results( 
                    $wpdb->prepare( 
                        "SELECT * FROM $subscription_order_table 
                         WHERE time_stamp <= %d AND status = 'upcoming' 
                         ORDER BY time_stamp ASC 
                         LIMIT %d OFFSET %d",
                        $current_timestamp,
                        $batch_size,
                        $offset
                    ),
                    ARRAY_A
                );

                if (empty($upcoming_order_data)) {
                    break; // No more items to process
                }

                // Process this batch
                $batch_result = $this->create_wc_orders($upcoming_order_data, array('success' => array(), 'error' => array()));
                
                // Merge results
                $task_status['success'] = array_merge($task_status['success'], $batch_result['success']);
                $task_status['error'] = array_merge($task_status['error'], $batch_result['error']);
                
                $processed += count($upcoming_order_data);
                $offset += $batch_size;

                // Log progress for large batches
                if ($total_due > 100) {
                    $this->log_batch_progress($processed, $total_due);
                }

                // Prevent infinite loops (safety check)
                if ($offset > 10000) {
                    error_log('Windrose Subscription: Safety limit reached during batch processing');
                    break;
                }
            }

            // Log final results
            $this->log_cron_results($task_status);
            
            // Trigger actions for external integrations
            do_action('windrose_subscription_cron_completed', $task_status);

            return $task_status;
            
        } catch (Exception $e) {
            error_log('Windrose Subscription Cron Error: ' . $e->getMessage());
            return array(
                'success' => array(),
                'error' => array(array('subscription_id' => 0, 'error' => $e->getMessage())),
                'total_processed' => 0,
                'total_due' => 0
            );
        } finally {
            // Always end execution tracking
            $this->end_cron_execution();
        }
    }

    /**
     * Create WooCommerce orders for subscription renewals
     */
    private function create_wc_orders($subscription_order_data, $task_status) {
        if (empty($subscription_order_data)) {
            return $task_status;
        }

        // Process all orders in the batch iteratively instead of recursively
        foreach ($subscription_order_data as $subscription_order_array) {
            $subscription_order = (object) $subscription_order_array;
            
            // Debug logging for order creation
            error_log('Windrose Cron: Creating WC order for subscription order ID: ' . $subscription_order->id);

            try {
                $order = wc_create_order();
                $order->set_customer_id($subscription_order->user_id);
                
                $product_id = intval($subscription_order->product_id);
                $quantity = intval($subscription_order->quantity);
                $product = wc_get_product($product_id);

                if ($product) {
                    $item = new \WC_Order_Item_Product();
                    $item->set_product($product);
                    $item->set_quantity($quantity);
                    $item->set_subtotal($product->get_price() * $quantity);
                    $item->set_total($product->get_price() * $quantity);
                    $order->add_item($item);
                }

                // Set customer data
                $customer_data = $this->get_customer_data($subscription_order);
                $order->set_address($customer_data->billing_address, 'billing');
                $order->set_address($customer_data->shipping_address, 'shipping');

                // Calculate and add Aramex shipping
                $aramex_rate = $this->calculate_aramex_shipping($order, $customer_data);
                if ($aramex_rate) {
                    // Calculate Discount if any
                    $discount = $this->calculate_discount($order, $aramex_rate['amount']);
                    error_log('Discount Amount: ' . $discount);

                    if ($discount > 0) {
                        $fee_item = new \WC_Order_Item_Fee();
                        $fee_item->set_name('Discount');
                        $fee_item->set_amount(-1 * floatval($discount));
                        $fee_item->set_total(-1 * floatval($discount));
                        $fee_item->set_tax_status('none');
                        $order->add_item($fee_item);
                    }

                    // Set Shipping Cost
                    $shipping_item = new \WC_Order_Item_Shipping();
                    $shipping_item->set_method_title($aramex_rate['label']);
                    $shipping_item->set_method_id('aramex');
                    $shipping_item->set_total($aramex_rate['amount']);
                    $order->add_item($shipping_item);

                    
                }

                

                // Set payment method
                $order->set_payment_method('paymob-pixel');
                $order->set_payment_method_title('Debit/Credit Card Payment');

                // Calculate totals and save
                $order->calculate_totals();
                $order->save();
                $order->update_status('pending');
                
                // Set WooCommerce order attribution meta data
                $order->update_meta_data('_wc_order_attribution_source_type', 'automated');
                $order->update_meta_data('_wc_order_attribution_utm_source', 'Automated');
                
                // Debug logging for order creation
                error_log('Windrose Cron: WC order created with ID: ' . $order->get_id());

                // Trigger payment processing
                error_log('Windrose Cron: Triggering payment initiation for order: ' . $order->get_id());
                do_action('windrose_subscription_initiate_payment', $subscription_order, $order->get_id());

                $task_status['success'][] = array(
                    'order_id' => $order->get_id(),
                    'subscription_id' => $subscription_order->id
                );
                
                // Debug logging for success
                error_log('Windrose Cron: Successfully processed subscription order: ' . $subscription_order->id);

            } catch (Exception $e) {
                error_log('Windrose Cron: Error processing subscription order ' . $subscription_order->id . ': ' . $e->getMessage());
                $task_status['error'][] = array(
                    'subscription_id' => $subscription_order->id,
                    'error' => $e->getMessage()
                );
            }
        }

        return $task_status;
    }

    /**
     * Get customer data for order creation
     */
    private function get_customer_data($subscription_order) {
        $customer_data = new \stdClass();

        // Get user object for fallback data
        $user = get_user_by('id', $subscription_order->user_id);

        $customer_data->billing_address = array(
            'first_name' => get_user_meta($subscription_order->user_id, 'billing_first_name', true) ?: $user->first_name ?: 'Customer',
            'last_name'  => get_user_meta($subscription_order->user_id, 'billing_last_name', true) ?: $user->last_name ?: 'Name',
            'company'    => get_user_meta($subscription_order->user_id, 'billing_company', true) ?: '',
            'address_1'  => get_user_meta($subscription_order->user_id, 'billing_address_1', true) ?: 'dumy',
            'address_2'  => get_user_meta($subscription_order->user_id, 'billing_address_2', true) ?: 'dumy',
            'city'       => get_user_meta($subscription_order->user_id, 'billing_city', true) ?: 'Unknown',
            'state'      => get_user_meta($subscription_order->user_id, 'billing_state', true) ?: 'Unknown',
            'postcode'   => get_user_meta($subscription_order->user_id, 'billing_postcode', true) ?: '00000',
            'country'    => get_user_meta($subscription_order->user_id, 'billing_country', true) ?: 'EG',
            'email'      => get_user_meta($subscription_order->user_id, 'billing_email', true) ?: $user->user_email ?: 'customer@example.com',
            'phone'      => get_user_meta($subscription_order->user_id, 'billing_phone', true) ?: '0000000000',
        );

        $customer_data->shipping_address = array(
            'first_name' => get_user_meta($subscription_order->user_id, 'shipping_first_name', true) ?: $user->first_name ?: 'Customer',
            'last_name'  => get_user_meta($subscription_order->user_id, 'shipping_last_name', true) ?: $user->last_name ?: 'Name',
            'company'    => get_user_meta($subscription_order->user_id, 'shipping_company', true) ?: '',
            'address_1'  => get_user_meta($subscription_order->user_id, 'shipping_address_1', true) ?: 'dumy',
            'address_2'  => get_user_meta($subscription_order->user_id, 'shipping_address_2', true) ?: 'dumy',
            'city'       => get_user_meta($subscription_order->user_id, 'shipping_city', true) ?: 'Unknown',
            'state'      => get_user_meta($subscription_order->user_id, 'shipping_state', true) ?: 'Unknown',
            'postcode'   => get_user_meta($subscription_order->user_id, 'shipping_postcode', true) ?: '00000',
            'country'    => get_user_meta($subscription_order->user_id, 'shipping_country', true) ?: 'EG',
            'phone'      => get_user_meta($subscription_order->user_id, 'shipping_phone', true) ?: '0000000000',
        );

        return $customer_data;
    }

    /**
     * Calculate Aramex shipping rate for a given order and customer data using the Aramex plugin's model directly
     * @param WC_Order $order
     * @param object $customer_data
     * @return object|null Array with rate info or null
     */
    private function calculate_aramex_shipping($order, $customer_data) {
        try {
            // Load the Aramex calculator model
            if (!class_exists('Aramex_Aramecalculator_Method_Model')) {
                require_once WP_PLUGIN_DIR . '/aramex-shipping-woocommerce/includes/aramexcalculator/class-aramex-woocommerce-aramexcalculator_model.php';
            }
            
            $items = $order->get_items('line_item');
            if (empty($items)) return null;
            
            $first_item = reset($items);
            $product = $first_item->get_product();
            $product_id = $product ? $product->get_id() : 0;
            $currency = get_woocommerce_currency();
                        
            
            $post = array(
                'country_code' => $customer_data->shipping_address['country'],
                'city' => $customer_data->shipping_address['city'],
                'post_code' => $customer_data->shipping_address['postcode'],
                'product_id' => $product_id,
                'currency' => $currency,
                'current_product_quantity' => $first_item->get_quantity(),
                'no-die' => true,
            );
            
            $model = new \Aramex_Aramecalculator_Method_Model();
            
            // Capture output since the model prints and dies
            ob_start();
            $model->rateCalculator($post);
            $output = ob_get_contents();
            ob_end_clean();
            
            
            
            $rates = json_decode($output, true);
            
            if (is_array($rates)) {
                // Find the first valid rate (success type)                
                foreach ($rates as $rate) {
                    if (is_array($rate) && isset($rate['amount'])) {                        
                        return $rate;
                    }
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log('Aramex shipping calculation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Log cron execution results
     */
    private function log_cron_results($task_status) {
        $log_message = sprintf(
            'Windrose Subscription Cron: %d successful, %d failed',
            count($task_status['success']),
            count($task_status['error'])
        );
        
        error_log($log_message);
        
        // Store in WordPress options for admin display
        update_option('windrose_last_cron_run', array(
            'timestamp' => current_time('timestamp'),
            'results' => $task_status,
            'date' => current_time('Y-m-d')
        ));

        // Update today's statistics
        $this->update_today_statistics($task_status);
    }

    /**
     * Update today's cron statistics
     */
    private function update_today_statistics($task_status) {
        $today = current_time('Y-m-d');
        $today_stats = get_option('windrose_today_cron_stats', array());
        
        if (!isset($today_stats[$today])) {
            $today_stats[$today] = array(
                'total_processed' => 0,
                'successful' => 0,
                'failed' => 0,
                'runs' => 0,
                'last_run_time' => 0
            );
        }

        $today_stats[$today]['total_processed'] += count($task_status['success']) + count($task_status['error']);
        $today_stats[$today]['successful'] += count($task_status['success']);
        $today_stats[$today]['failed'] += count($task_status['error']);
        $today_stats[$today]['runs'] += 1;
        $today_stats[$today]['last_run_time'] = current_time('timestamp');

        // Keep only last 30 days of stats
        $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
        foreach ($today_stats as $date => $stats) {
            if ($date < $thirty_days_ago) {
                unset($today_stats[$date]);
            }
        }

        update_option('windrose_today_cron_stats', $today_stats);
    }

    /**
     * Start cron execution tracking
     */
    public function start_cron_execution() {
        update_option('windrose_cron_running', array(
            'started_at' => current_time('timestamp'),
            'pid' => getmypid(),
            'status' => 'running'
        ));
    }

    /**
     * End cron execution tracking
     */
    public function end_cron_execution() {
        delete_option('windrose_cron_running');
    }

    /**
     * Get current day statistics
     */
    public static function get_today_statistics() {
        $today = current_time('Y-m-d');
        $today_stats = get_option('windrose_today_cron_stats', array());
        
        if (isset($today_stats[$today])) {
            return $today_stats[$today];
        }

        return array(
            'total_processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'runs' => 0,
            'last_run_time' => 0
        );
    }

    /**
     * Get cron running status
     */
    public static function get_cron_running_status() {
        $running = get_option('windrose_cron_running', false);
        
        if (!$running) {
            return array(
                'is_running' => false,
                'started_at' => 0,
                'duration' => 0,
                'status' => 'idle'
            );
        }

        $duration = current_time('timestamp') - $running['started_at'];
        
        // Check if cron is stuck (running for more than 30 minutes)
        $is_stuck = $duration > (30 * 60);
        
        return array(
            'is_running' => true,
            'started_at' => $running['started_at'],
            'duration' => $duration,
            'status' => $is_stuck ? 'stuck' : 'running',
            'pid' => $running['pid']
        );
    }

    /**
     * Get items due for today
     */
    public static function get_today_due_items() {
        global $wpdb;
        $subscription_order_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_ORDER_TABLE;
        
        $today_start = strtotime(current_time('Y-m-d') . ' 00:00:00');
        $today_end = strtotime(current_time('Y-m-d') . ' 23:59:59');
        
        $due_today = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $subscription_order_table 
             WHERE time_stamp >= %d AND time_stamp <= %d AND status = 'upcoming'",
            $today_start,
            $today_end
        ));

        $overdue = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $subscription_order_table 
             WHERE time_stamp < %d AND status = 'upcoming'",
            $today_start
        ));

        return array(
            'due_today' => intval($due_today),
            'overdue' => intval($overdue),
            'total_due' => intval($due_today) + intval($overdue)
        );
    }

    /**
     * Log batch processing progress
     */
    private function log_batch_progress($processed, $total) {
        $percentage = round(($processed / $total) * 100, 2);
        $log_message = sprintf(
            'Windrose Subscription Batch Progress: %d/%d (%s%%) processed',
            $processed,
            $total,
            $percentage
        );
        error_log($log_message);
    }

    /**
     * Get cron status for admin display
     */
    public static function get_cron_status() {
        $last_run = get_option('windrose_last_cron_run', array());
        $daily_next = wp_next_scheduled('windrose_subscription_cron');
        $enable_hourly = get_option('windrose_enable_hourly_cron', 'no');
        
        return array(
            'last_run' => $last_run,
            'daily_next' => $daily_next,
            'hourly_next' => false, // No hourly cron scheduled
            'daily_scheduled' => $daily_next !== false,
            'hourly_scheduled' => false,
            'hourly_enabled' => $enable_hourly === 'yes',
            'is_scheduled' => $daily_next !== false // Primary cron status
        );
    }

    /**
     * Emergency stop function to force stop any running cron
     */
    public static function emergency_stop() {
        error_log('Windrose Cron: Emergency stop triggered');
        
        // Delete the cron running option
        delete_option('windrose_cron_running');
        
        // Clear any scheduled cron events
        wp_clear_scheduled_hook('windrose_subscription_cron');
        
        return true;
    }

    private function calculate_discount($order, $shipping_cost) {
        // Get discount threshold from options
        $discount_threshold = intval(get_option('windrose_shipping_discount_threshold'));

        // Get shipping country from order
        $shipping_address = $order->get_address('shipping');
        $shipping_country = isset($shipping_address['country']) ? $shipping_address['country'] : '';

        // Get cart subtotal
        $cart_subtotal = $order->get_subtotal();

        error_log('Order: ' . json_encode($order));
        error_log('Discount Threshold: ' . $discount_threshold);
        error_log('Shipping Country: ' . $shipping_country);
        error_log('Cart Subtotal: ' . $cart_subtotal);

        // Check if country belongs to "GCC" zone
        $is_gcc_country = false;
        $zones = \WC_Shipping_Zones::get_zones();
        foreach ($zones as $zone) {
            if ($zone['zone_name'] === 'GCC') {
                foreach ($zone['zone_locations'] as $location) {
                    if (isset($location->type) && isset($location->code) && $location->type === 'country' && $location->code === $shipping_country) {
                        $is_gcc_country = true;
                        break 2; // Exit both loops
                    }
                }
            }
        }

        error_log('Is GCC Country: ' . $is_gcc_country);

        // If threshold met and country is GCC, apply discount (e.g., 10)
        if ($discount_threshold > 0 && $cart_subtotal >= $discount_threshold && $is_gcc_country) {
            return $shipping_cost;
        }
        return 0;
    }
} 