<?php
namespace WindroseSubscription\Includes;

defined( 'WINDROS_INIT' ) || exit;

class WindroseCronSettings {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_cron_settings_page']);
        add_action('admin_init', [$this, 'init_settings']);
    }

    /**
     * Add cron settings page to admin menu
     */
    public function add_cron_settings_page() {
        add_submenu_page(
            'woocommerce',
            'Subscription Settings',
            'Subscription Settings',
            'manage_woocommerce',
            'windrose-cron-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('windrose_cron_settings', 'windrose_cron_frequency');
        register_setting('windrose_cron_settings', 'windrose_cron_enabled');
        register_setting('windrose_cron_settings', 'windrose_live_integration_id');
        register_setting('windrose_cron_settings', 'windrose_test_integration_id');
        register_setting('windrose_cron_settings', 'windrose_daily_cron_time');
        register_setting('windrose_cron_settings', 'windrose_payment_attempt_threshold');
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        $cron_status = WindroseCronManager::get_cron_status();
        $running_status = WindroseCronManager::get_cron_running_status();
        $today_stats = WindroseCronManager::get_today_statistics();
        $today_due = WindroseCronManager::get_today_due_items();
        $frequency = get_option('windrose_cron_frequency', 'daily');
        $enabled = get_option('windrose_cron_enabled', 'yes');
        $live_integration_id = get_option('windrose_live_integration_id', '');
        $test_integration_id = get_option('windrose_test_integration_id', '');
        $daily_cron_time = get_option('windrose_daily_cron_time', '02:00');
        $payment_attempt_threshold = get_option('windrose_payment_attempt_threshold', '3');
        
        // Handle form submission
        if (isset($_POST['submit'])) {
            $live_integration_id = sanitize_text_field($_POST['windrose_live_integration_id']);
            $test_integration_id = sanitize_text_field($_POST['windrose_test_integration_id']);
            $daily_cron_time = sanitize_text_field($_POST['windrose_daily_cron_time']);
            $payment_attempt_threshold = max(1, min(10, intval($_POST['windrose_payment_attempt_threshold'])));
            
            update_option('windrose_live_integration_id', $live_integration_id);
            update_option('windrose_test_integration_id', $test_integration_id);
            update_option('windrose_daily_cron_time', $daily_cron_time);
            update_option('windrose_payment_attempt_threshold', $payment_attempt_threshold);
            
            // Reschedule crons based on new settings
            $cron_manager = new WindroseCronManager();
            $cron_manager->schedule_cron_events();
            
            echo '<div class="notice notice-success"><p>Settings saved successfully.</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1>Windrose Subscription Settings</h1>
            
            <div class="notice notice-info">
                <p><strong>Note:</strong> The cron system automatically processes subscription renewals. Daily processing is usually sufficient for most subscription types.</p>
            </div>

            <!-- Three Column Layout -->
            <div class="windrose-admin-grid">
                <!-- First Column -->
                <div class="windrose-admin-column">
                    <!-- Real-time Status Dashboard -->
                    <div class="card">
                        <h2>🔄 Live Cron Status</h2>
                        <table class="form-table">
                            <tr>
                                <th>Current Status</th>
                                <td>
                                    <?php if ($running_status['is_running']): ?>
                                        <?php if ($running_status['status'] === 'stuck'): ?>
                                            <span style="color: red; font-weight: bold;">⚠️ STUCK</span>
                                            <br><small>Cron has been running for <?php echo round($running_status['duration'] / 60, 1); ?> minutes</small>
                                        <?php else: ?>
                                            <span style="color: orange; font-weight: bold;">🔄 RUNNING</span>
                                            <br><small>Started <?php echo date('H:i:s', $running_status['started_at']); ?> (<?php echo round($running_status['duration'] / 60, 1); ?> min ago)</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: green; font-weight: bold;">✅ IDLE</span>
                                        <br><small>Ready to process subscriptions</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Daily Cron</th>
                                <td>
                                    <?php 
                                    $daily_next = wp_next_scheduled('windrose_subscription_cron');
                                    if ($daily_next): ?>
                                        <span style="color: green;">✓ Scheduled</span><br>
                                        <small>Next Run: <?php echo date('Y-m-d H:i:s', $daily_next); ?></small><br>
                                        <small>Configured Time: <?php echo esc_html($daily_cron_time); ?> daily (Server Time)</small>
                                    <?php else: ?>
                                        <span style="color: red;">✗ Not Scheduled</span><br>
                                        <small>Configured Time: <?php echo esc_html($daily_cron_time); ?> daily</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Today's Statistics -->
                    <div class="card">
                        <h2>📊 Today's Statistics (<?php echo current_time('Y-m-d'); ?>)</h2>
                        <table class="form-table">
                            <tr>
                                <th>Items Due Today</th>
                                <td>
                                    <strong><?php echo $today_due['total_due']; ?> total</strong>
                                    <br><small>
                                        <?php echo $today_due['due_today']; ?> due today, 
                                        <?php echo $today_due['overdue']; ?> overdue
                                    </small>
                                </td>
                            </tr>
                            <tr>
                                <th>Cron Runs Today</th>
                                <td>
                                    <strong><?php echo $today_stats['runs']; ?> runs</strong>
                                    <?php if ($today_stats['last_run_time'] > 0): ?>
                                        <br><small>Last run: <?php echo date('H:i:s', $today_stats['last_run_time']); ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Processed Today</th>
                                <td>
                                    <strong><?php echo $today_stats['total_processed']; ?> total</strong>
                                    <br><small>
                                        <span style="color: green;"><?php echo $today_stats['successful']; ?> successful</span>, 
                                        <span style="color: red;"><?php echo $today_stats['failed']; ?> failed</span>
                                    </small>
                                </td>
                            </tr>
                            <tr>
                                <th>Success Rate</th>
                                <td>
                                    <?php 
                                    $success_rate = $today_stats['total_processed'] > 0 ? 
                                        round(($today_stats['successful'] / $today_stats['total_processed']) * 100, 1) : 0;
                                    $color = $success_rate >= 95 ? 'green' : ($success_rate >= 80 ? 'orange' : 'red');
                                    ?>
                                    <span style="color: <?php echo $color; ?>; font-weight: bold;">
                                        <?php echo $success_rate; ?>%
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Manual Actions -->
                    <div class="card">
                        <h2>🎯 Manual Actions</h2>
                        <p>
                            <?php if ($running_status['is_running']): ?>
                                <span class="button button-disabled" disabled>Cron is currently running...</span>
                                <br><br>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=windrose-cron-settings&action=force_stop'), 'windrose_force_stop'); ?>" class="button button-secondary" style="color: red;">
                                    Force Stop Running Cron
                                </a>
                            <?php else: ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=windrose-cron-settings&action=run_cron'), 'windrose_run_cron'); ?>" class="button button-primary">
                                    <?php if ($today_due['total_due'] > 0): ?>
                                        Process <?php echo $today_due['total_due']; ?> Due Subscriptions
                                    <?php else: ?>
                                        Run Cron Now
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                            
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=windrose-cron-settings&action=reschedule_cron'), 'windrose_reschedule_cron'); ?>" class="button button-secondary">
                                Reschedule Cron
                            </a>
                        </p>
                    </div>

                    <!-- WP-CLI Commands -->
                    <div class="card">
                        <h2>💻 WP-CLI Commands</h2>
                        <p>You can also manage the cron system using WP-CLI commands:</p>
                        <code>wp windrose-cli cron_status</code> - Check cron status<br>
                        <code>wp windrose-cli run_cron</code> - Manually run cron<br>
                        <code>wp windrose-cli create_subscription_order</code> - Create subscription orders<br>
                        <code>wp windrose-cli pending_subscriptions</code> - Check pending subscriptions<br>
                        <code>wp windrose-cli test_subscription [ID]</code> - Test specific subscription
                    </div>
                </div>

                <!-- Second Column -->
                <div class="windrose-admin-column">
                    <!-- Configuration -->
                    <form method="post" action="">
                        <div class="card">
                            <h2>⚙️ Cron Configuration</h2>
                            <table class="form-table">
                                <tr>
                                    <th>Live Integration ID</th>
                                    <td>
                                        <input type="text" name="windrose_live_integration_id" value="<?php echo esc_attr($live_integration_id); ?>" class="regular-text" placeholder="e.g., 45612" />
                                        <p class="description">
                                            Enter your Live Integration ID from Paymob for production payments.
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Test Integration ID</th>
                                    <td>
                                        <input type="text" name="windrose_test_integration_id" value="<?php echo esc_attr($test_integration_id); ?>" class="regular-text" placeholder="e.g., 7009" />
                                        <p class="description">
                                            Enter your Test Integration ID from Paymob for testing payments.
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Daily Cron Time</th>
                                    <td>
                                        <input type="time" name="windrose_daily_cron_time" value="<?php echo esc_attr($daily_cron_time); ?>" class="regular-text" />
                                        <p class="description">
                                            Set the time for the daily cron to run (WordPress timezone: <?php echo wp_timezone_string(); ?>). 
                                            Recommended: 02:00-04:00 AM for low traffic periods.
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Payment Attempt Threshold</th>
                                    <td>
                                        <input type="number" name="windrose_payment_attempt_threshold" value="<?php echo esc_attr($payment_attempt_threshold); ?>" class="small-text" min="1" max="10" />
                                        <p class="description">
                                            Maximum number of failed payment attempts before cancelling a subscription (1-10). 
                                            Default: 3 attempts.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <p>
                                <input type="submit" name="submit" class="button button-primary" value="Save Settings" />
                            </p>
                        </div>
                    </form>

                    <!-- Pending Subscriptions -->
                    <div class="card">
                        <h2>📋 Pending Subscriptions</h2>
                        <?php
                        global $wpdb;
                        $subscription_order_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_ORDER_TABLE;
                        $current_timestamp = windrose_get_timestamp_object(0)->timestamp;
                        
                        // Get counts
                        $total_due = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM $subscription_order_table 
                             WHERE time_stamp <= %d AND status = 'upcoming'",
                            $current_timestamp
                        ));

                        $upcoming_7_days = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM $subscription_order_table 
                             WHERE time_stamp > %d AND time_stamp <= %d AND status = 'upcoming'",
                            $current_timestamp,
                            $current_timestamp + (7 * 24 * 60 * 60)
                        ));

                        $upcoming_30_days = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM $subscription_order_table 
                             WHERE time_stamp > %d AND time_stamp <= %d AND status = 'upcoming'",
                            $current_timestamp,
                            $current_timestamp + (30 * 24 * 60 * 60)
                        ));
                        ?>
                        <table class="form-table">
                            <tr>
                                <th>Due Now</th>
                                <td>
                                    <?php if ($total_due > 0): ?>
                                        <span style="color: red; font-weight: bold;"><?php echo $total_due; ?> subscriptions</span>
                                        <br><small>These need to be processed immediately</small>
                                    <?php else: ?>
                                        <span style="color: green;">0 subscriptions</span>
                                        <br><small>All subscriptions are up to date</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Due in 7 days</th>
                                <td><?php echo $upcoming_7_days; ?> subscriptions</td>
                            </tr>
                            <tr>
                                <th>Due in 30 days</th>
                                <td><?php echo $upcoming_30_days; ?> subscriptions</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Third Column -->
                <div class="windrose-admin-column">
                    <!-- System Information -->
                    <div class="card">
                        <h2>🔧 System Information</h2>
                        <table class="form-table">
                            <tr>
                                <th>WordPress Cron</th>
                                <td>
                                    <?php 
                                    if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
                                        echo '<span style="color: red;">Disabled</span> - Consider using server cron instead';
                                    } else {
                                        echo '<span style="color: green;">Enabled</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Live Integration ID</th>
                                <td>
                                    <?php if (!empty($live_integration_id)): ?>
                                        <span style="color: green;"><?php echo esc_html($live_integration_id); ?></span>
                                    <?php else: ?>
                                        <span style="color: orange;">Not configured</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Test Integration ID</th>
                                <td>
                                    <?php if (!empty($test_integration_id)): ?>
                                        <span style="color: green;"><?php echo esc_html($test_integration_id); ?></span>
                                    <?php else: ?>
                                        <span style="color: orange;">Not configured</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Server Time (UTC)</th>
                                <td><?php echo gmdate('Y-m-d H:i:s'); ?></td>
                            </tr>
                            <tr>
                                <th>WordPress Time</th>
                                <td><?php echo current_time('Y-m-d H:i:s'); ?></td>
                            </tr>
                            <tr>
                                <th>PHP Memory Limit</th>
                                <td><?php echo ini_get('memory_limit'); ?></td>
                            </tr>
                            <tr>
                                <th>Max Execution Time</th>
                                <td><?php echo ini_get('max_execution_time'); ?> seconds</td>
                            </tr>
                        </table>
                    </div>

                    <!-- Payment Logs -->
                    <div class="card">
                        <h2>💳 Recent Payment Logs</h2>
                        <?php
                        $recent_logs = WindrosePaymentLogger::get_recent_logs(5);
                        $payment_stats = WindrosePaymentLogger::get_payment_statistics(7); // Last 7 days
                        ?>
                        
                        <!-- Payment Statistics -->
                        <h3>Last 7 Days Statistics</h3>
                        <table class="form-table">
                            <tr>
                                <th>Total Transactions</th>
                                <td><?php echo $payment_stats['total_transactions']; ?></td>
                            </tr>
                            <tr>
                                <th>Successful</th>
                                <td>
                                    <span style="color: green;"><?php echo $payment_stats['successful_transactions']; ?></span>
                                    (<?php echo $payment_stats['success_rate']; ?>%)
                                </td>
                            </tr>
                            <tr>
                                <th>Failed</th>
                                <td><span style="color: red;"><?php echo $payment_stats['failed_transactions']; ?></span></td>
                            </tr>
                            <tr>
                                <th>Total Amount</th>
                                <td><?php echo number_format($payment_stats['total_amount_cents'] / 1000, 2); ?> <?php echo get_woocommerce_currency(); ?></td>
                            </tr>
                        </table>

                        <!-- Recent Logs Table -->
                        <h3>Recent Payment Activities</h3>
                        <?php if (!empty($recent_logs)): ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Order ID</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Mode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_logs as $log): ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d H:i', strtotime($log->created_at)); ?></td>
                                            <td>
                                                <a href="<?php echo admin_url('post.php?post=' . $log->wc_order_id . '&action=edit'); ?>" target="_blank">
                                                    #<?php echo $log->wc_order_id; ?>
                                                </a>
                                            </td>
                                            <td><?php echo number_format($log->amount_cents / 1000, 2); ?> <?php echo $log->currency; ?></td>
                                            <td>
                                                <?php 
                                                $status_color = 'black';
                                                switch ($log->status) {
                                                    case 'success':
                                                        $status_color = 'green';
                                                        break;
                                                    case 'failed':
                                                    case 'error':
                                                        $status_color = 'red';
                                                        break;
                                                    case 'processing':
                                                        $status_color = 'orange';
                                                        break;
                                                }
                                                ?>
                                                <span style="color: <?php echo $status_color; ?>; font-weight: bold;">
                                                    <?php echo ucfirst($log->status); ?>
                                                </span>
                                            </td>
                                            <td><?php echo ucfirst($log->payment_mode); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <p>
                                <a href="<?php echo admin_url('admin.php?page=windrose-subscription-logs'); ?>" class="button button-secondary">
                                    View All Payment Logs
                                </a>
                            </p>
                        <?php else: ?>
                            <p>No payment logs found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .windrose-admin-grid {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            align-items: flex-start;
        }
        
        .windrose-admin-column {
            flex: 1;
            min-width: 0; /* Prevents flex items from overflowing */
            max-width: calc(33.333% - 14px); /* Ensures equal width for three columns with gap consideration */
        }
        
        .card {
            background: white;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .card h2 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .button-disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Custom form table column widths */
        .card .form-table th {
            width: 35%; /* Increase first column width */
        }
        
        .card .form-table td {
            width: 65%; /* Decrease second column width */
        }
        
        /* Override WordPress regular-text width for better fit */
        .card .form-table .regular-text {
            width: 200px !important; /* Reduce from default ~400px to 200px */
        }
        
        /* Make time input slightly smaller */
        .card .form-table input[type="time"].regular-text {
            width: 120px !important;
        }
        
        /* Make number input smaller */
        .card .form-table input[type="number"].small-text {
            width: 80px !important;
        }
        
        /* Responsive design for smaller screens */
        @media (max-width: 1400px) {
            .windrose-admin-grid {
                flex-direction: column;
                gap: 20px;
            }
            
            .windrose-admin-column {
                flex: none;
                max-width: 100%;
            }
        }
        </style>

        <script>
        // Auto-refresh the page every 30 seconds to show live status
        setTimeout(function() {
            if (window.location.href.includes('action=run_cron')) {
                // If on run_cron URL, redirect back to base settings page
                window.location.href = 'admin.php?page=windrose-cron-settings';
            } else {
                // Otherwise just refresh current page
                location.reload();
            }
        }, 30000);
        </script>
        <?php

        // Handle manual actions
        $this->handle_manual_actions();
    }

    /**
     * Handle manual actions
     */
    private function handle_manual_actions() {
        if (isset($_GET['action']) && isset($_GET['_wpnonce'])) {
            
            if ($_GET['action'] === 'run_cron' && wp_verify_nonce($_GET['_wpnonce'], 'windrose_run_cron')) {
                $cron_manager = new WindroseCronManager();
                $task_status = $cron_manager->process_subscription_orders();
                
                $message = sprintf(
                    'Cron completed: %d successful, %d failed',
                    count($task_status['success']),
                    count($task_status['error'])
                );
                
                echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
            }
            
            if ($_GET['action'] === 'reschedule_cron' && wp_verify_nonce($_GET['_wpnonce'], 'windrose_reschedule_cron')) {
                wp_clear_scheduled_hook('windrose_subscription_cron');
                wp_schedule_event(time(), 'daily', 'windrose_subscription_cron');
                
                echo '<div class="notice notice-success"><p>Cron rescheduled successfully.</p></div>';
            }

            if ($_GET['action'] === 'force_stop' && wp_verify_nonce($_GET['_wpnonce'], 'windrose_force_stop')) {
                delete_option('windrose_cron_running');
                
                echo '<div class="notice notice-warning"><p>Stuck cron process has been forcefully stopped.</p></div>';
            }
        }
    }
} 