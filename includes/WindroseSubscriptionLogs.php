<?php
namespace WindroseSubscription\Includes;

defined( 'WINDROS_INIT' ) || exit;

class WindroseSubscriptionLogs {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_subscription_logs_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_get_log_details', [$this, 'get_log_details_ajax']);
    }

    /**
     * Add subscription logs page to admin menu
     */
    public function add_subscription_logs_page() {
        add_submenu_page(
            'woocommerce',
            'Subscription Logs',
            'Subscription Logs',
            'manage_woocommerce',
            'windrose-subscription-logs',
            [$this, 'render_logs_page']
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'woocommerce_page_windrose-subscription-logs') {
            return;
        }

        wp_enqueue_style('windrose-admin-logs', WINDROS_URL . 'assets/css/admin-logs.css', array(), '1.0.0');
        wp_enqueue_script('windrose-admin-logs', WINDROS_URL . 'assets/js/admin-logs.js', array('jquery'), '1.0.0', true);
        
        // Localize script with data
        wp_localize_script('windrose-admin-logs', 'windrose_logs', array(
            'nonce' => wp_create_nonce('windrose_log_details'),
            'base_url' => admin_url('admin.php?page=windrose-subscription-logs'),
            'auto_refresh' => false // Set to true if you want auto-refresh
        ));
    }

    /**
     * Render the logs page
     */
    public function render_logs_page() {
        // Handle actions
        $this->handle_actions();

        // Get filters
        $filters = $this->get_filters();
        
        // Get logs with pagination
        $logs_data = $this->get_logs($filters);
        
        ?>
        <div class="wrap">
            <h1>📋 Subscription Payment Logs</h1>
            
            <!-- Filters -->
            <div class="windrose-logs-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="windrose-subscription-logs">
                    
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="status_filter">Status:</label>
                            <select name="status" id="status_filter">
                                <option value="">All Statuses</option>
                                <option value="initiated" <?php selected($filters['status'], 'initiated'); ?>>Initiated</option>
                                <option value="intention_created" <?php selected($filters['status'], 'intention_created'); ?>>Intention Created</option>
                                <option value="payment_processing" <?php selected($filters['status'], 'payment_processing'); ?>>Payment Processing</option>
                                <option value="success" <?php selected($filters['status'], 'success'); ?>>Success</option>
                                <option value="failed" <?php selected($filters['status'], 'failed'); ?>>Failed</option>
                                <option value="token_error" <?php selected($filters['status'], 'token_error'); ?>>Token Error</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="payment_mode_filter">Payment Mode:</label>
                            <select name="payment_mode" id="payment_mode_filter">
                                <option value="">All Modes</option>
                                <option value="live" <?php selected($filters['payment_mode'], 'live'); ?>>Live</option>
                                <option value="test" <?php selected($filters['payment_mode'], 'test'); ?>>Test</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_from">From Date:</label>
                            <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($filters['date_from']); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_to">To Date:</label>
                            <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($filters['date_to']); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="search">Search:</label>
                            <input type="text" name="search" id="search" value="<?php echo esc_attr($filters['search']); ?>" placeholder="Order ID, User ID, or Reference">
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="button button-primary">Filter</button>
                            <a href="<?php echo admin_url('admin.php?page=windrose-subscription-logs'); ?>" class="button button-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Statistics -->
            <div class="windrose-logs-stats">
                <div class="stat-card">
                    <h3>Total Logs</h3>
                    <span class="stat-number"><?php echo number_format($logs_data['total_count']); ?></span>
                </div>
                <div class="stat-card">
                    <h3>Success Rate</h3>
                    <span class="stat-number"><?php echo $logs_data['success_rate']; ?>%</span>
                </div>
                <div class="stat-card">
                    <h3>Failed</h3>
                    <span class="stat-number"><?php echo $logs_data['failed_count']; ?></span>
                </div>
                <div class="stat-card">
                    <h3>Today's Logs</h3>
                    <span class="stat-number"><?php echo $logs_data['today_count']; ?></span>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="windrose-logs-table">
                <?php if (empty($logs_data['logs'])): ?>
                    <div class="no-logs">
                        <p>No payment logs found matching your criteria.</p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment Mode</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs_data['logs'] as $log): ?>
                                <tr>
                                    <td><?php echo esc_html($log->id); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('post.php?post=' . $log->wc_order_id . '&action=edit'); ?>" target="_blank">
                                            #<?php echo esc_html($log->wc_order_id); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php 
                                        $user = get_user_by('id', $log->user_id);
                                        if ($user) {
                                            echo esc_html($user->display_name);
                                            echo '<br><small>' . esc_html($user->user_email) . '</small>';
                                        } else {
                                            echo '<em>User not found</em>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html($log->currency); ?> <?php echo number_format($log->amount_cents / 1000, 2); ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo esc_attr($log->status); ?>">
                                            <?php echo esc_html($this->get_status_label($log->status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="mode-badge mode-<?php echo esc_attr($log->payment_mode); ?>">
                                            <?php echo esc_html(ucfirst($log->payment_mode)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo esc_html(date('Y-m-d H:i:s', strtotime($log->created_at))); ?>
                                    </td>
                                    <td>
                                        <a href="#" class="button button-small view-log-details" data-log-id="<?php echo esc_attr($log->id); ?>">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($logs_data['total_pages'] > 1): ?>
                        <div class="windrose-pagination">
                            <?php
                            $current_page = $filters['paged'];
                            $total_pages = $logs_data['total_pages'];
                            
                            // Previous page
                            if ($current_page > 1) {
                                $prev_url = add_query_arg('paged', $current_page - 1, $_SERVER['REQUEST_URI']);
                                echo '<a href="' . esc_url($prev_url) . '" class="button">&laquo; Previous</a>';
                            }
                            
                            // Page numbers
                            for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
                                $page_url = add_query_arg('paged', $i, $_SERVER['REQUEST_URI']);
                                $class = $i === $current_page ? 'button button-primary' : 'button';
                                echo '<a href="' . esc_url($page_url) . '" class="' . $class . '">' . $i . '</a>';
                            }
                            
                            // Next page
                            if ($current_page < $total_pages) {
                                $next_url = add_query_arg('paged', $current_page + 1, $_SERVER['REQUEST_URI']);
                                echo '<a href="' . esc_url($next_url) . '" class="button">Next &raquo;</a>';
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Log Details Modal -->
        <div id="log-details-modal" class="windrose-modal">
            <div class="windrose-modal-content">
                <span class="windrose-modal-close">&times;</span>
                <h2>Payment Log Details</h2>
                <div id="log-details-content"></div>
            </div>
        </div>
        
        <?php
    }

    /**
     * Get filters from request
     */
    private function get_filters() {
        return array(
            'status' => sanitize_text_field($_GET['status'] ?? ''),
            'payment_mode' => sanitize_text_field($_GET['payment_mode'] ?? ''),
            'date_from' => sanitize_text_field($_GET['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_GET['date_to'] ?? ''),
            'search' => sanitize_text_field($_GET['search'] ?? ''),
            'paged' => max(1, intval($_GET['paged'] ?? 1)),
            'per_page' => 20
        );
    }

    /**
     * Get logs with pagination
     */
    private function get_logs($filters) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . WINDROS_SUBSCRIPTION_PAYMENT_LOGS_TABLE;
        
        // Build WHERE clause
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($filters['status'])) {
            $where_conditions[] = 'status = %s';
            $where_values[] = $filters['status'];
        }
        
        if (!empty($filters['payment_mode'])) {
            $where_conditions[] = 'payment_mode = %s';
            $where_values[] = $filters['payment_mode'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_conditions[] = 'DATE(created_at) >= %s';
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = 'DATE(created_at) <= %s';
            $where_values[] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_conditions[] = '(wc_order_id LIKE %s OR user_id LIKE %s OR special_reference LIKE %s)';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM $table_name $where_clause";
        if (!empty($where_values)) {
            $count_query = $wpdb->prepare($count_query, $where_values);
        }
        $total_count = $wpdb->get_var($count_query);
        
        // Calculate pagination
        $total_pages = ceil($total_count / $filters['per_page']);
        $offset = ($filters['paged'] - 1) * $filters['per_page'];
        
        // Get logs
        $query = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $query_values = array_merge($where_values, array($filters['per_page'], $offset));
        $logs = $wpdb->get_results($wpdb->prepare($query, $query_values));
        
        // Get statistics
        $stats = $this->get_logs_statistics();
        
        return array(
            'logs' => $logs,
            'total_count' => $total_count,
            'total_pages' => $total_pages,
            'success_rate' => $stats['success_rate'],
            'failed_count' => $stats['failed_count'],
            'today_count' => $stats['today_count']
        );
    }

    /**
     * Get logs statistics
     */
    private function get_logs_statistics() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . WINDROS_SUBSCRIPTION_PAYMENT_LOGS_TABLE;
        
        // Total logs
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Successful logs
        $successful = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'success'");
        
        // Failed logs
        $failed = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status IN ('failed', 'token_error')");
        
        // Today's logs
        $today = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = %s",
            current_time('Y-m-d')
        ));
        
        // Success rate
        $success_rate = $total > 0 ? round(($successful / $total) * 100, 1) : 0;
        
        return array(
            'total' => $total,
            'successful' => $successful,
            'failed_count' => $failed,
            'today_count' => $today,
            'success_rate' => $success_rate
        );
    }

    /**
     * Get status label
     */
    private function get_status_label($status) {
        $labels = array(
            'initiated' => 'Initiated',
            'intention_created' => 'Intention Created',
            'payment_processing' => 'Payment Processing',
            'success' => 'Success',
            'failed' => 'Failed',
            'token_error' => 'Token Error'
        );
        
        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Handle actions
     */
    private function handle_actions() {
        // Handle any actions here if needed
    }

    /**
     * AJAX handler for log details
     */
    public function get_log_details_ajax() {
        check_ajax_referer('windrose_log_details', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Unauthorized access');
        }
        
        $log_id = intval($_POST['log_id']);
        
        if (!$log_id) {
            wp_send_json_error('Invalid log ID');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . WINDROS_SUBSCRIPTION_PAYMENT_LOGS_TABLE;
        
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $log_id));
        
        if (!$log) {
            wp_send_json_error('Log not found');
        }
        
        $user = get_user_by('id', $log->user_id);
        $order = wc_get_order($log->wc_order_id);
        
        ob_start();
        ?>
        <div class="log-details">
            <table class="form-table">
                <tr>
                    <th>Log ID</th>
                    <td><?php echo esc_html($log->id); ?></td>
                </tr>
                <tr>
                    <th>WooCommerce Order</th>
                    <td>
                        <?php if ($order): ?>
                            <a href="<?php echo admin_url('post.php?post=' . $log->wc_order_id . '&action=edit'); ?>" target="_blank">
                                #<?php echo esc_html($log->wc_order_id); ?>
                            </a>
                        <?php else: ?>
                            #<?php echo esc_html($log->wc_order_id); ?> (Order not found)
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Customer</th>
                    <td>
                        <?php if ($user): ?>
                            <?php echo esc_html($user->display_name); ?><br>
                            <small><?php echo esc_html($user->user_email); ?></small>
                        <?php else: ?>
                            User ID: <?php echo esc_html($log->user_id); ?> (User not found)
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Amount</th>
                    <td><?php echo esc_html($log->currency); ?> <?php echo number_format($log->amount_cents / 1000, 2); ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="status-badge status-<?php echo esc_attr($log->status); ?>">
                            <?php echo esc_html(ucfirst($log->status)); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Payment Mode</th>
                    <td><?php echo esc_html(ucfirst($log->payment_mode)); ?></td>
                </tr>
                <tr>
                    <th>Integration ID</th>
                    <td><?php echo esc_html($log->integration_id ?: 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Intention ID</th>
                    <td><?php echo esc_html($log->intention_id ?: 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Transaction ID</th>
                    <td><?php echo esc_html($log->transaction_id ?: 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Created</th>
                    <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($log->created_at))); ?></td>
                </tr>
                <tr>
                    <th>Updated</th>
                    <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($log->updated_at))); ?></td>
                </tr>
                <?php if ($log->error_message): ?>
                <tr>
                    <th>Error Message</th>
                    <td><?php echo esc_html($log->error_message); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($log->paymob_response): ?>
                <tr>
                    <th>Paymob Response</th>
                    <td>
                        <?php 
                        // Try to decode and format JSON
                        $response_data = json_decode($log->paymob_response, true);
                        if ($response_data !== null) {
                            // Format JSON with proper indentation
                            $formatted_json = json_encode($response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                            echo '<div class="json-response">';
                            echo '<button class="copy-json-btn" onclick="copyToClipboard(this, \'' . esc_js($formatted_json) . '\')">Copy JSON</button>';
                            echo '<pre class="json-formatted">' . esc_html($formatted_json) . '</pre>';
                            echo '</div>';
                        } else {
                            // If not valid JSON, show as plain text
                            echo '<div class="json-response">';
                            echo '<button class="copy-json-btn" onclick="copyToClipboard(this, \'' . esc_js($log->paymob_response) . '\')">Copy Text</button>';
                            echo '<pre class="json-plain">' . esc_html($log->paymob_response) . '</pre>';
                            echo '</div>';
                        }
                        ?>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
} 