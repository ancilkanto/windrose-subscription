<?php
namespace WindroseSubscription\Includes;

defined( 'WINDROS_INIT' ) || exit;

class WindroseDatabaseUpdater {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_database_updater_page']);
        add_action('admin_init', [$this, 'handle_database_update']);
    }

    /**
     * Add database updater page to admin menu
     */
    public function add_database_updater_page() {
        add_submenu_page(
            'woocommerce',
            'Database Updater',
            'DB Updater',
            'manage_woocommerce',
            'windrose-db-updater',
            [$this, 'render_updater_page']
        );
    }

    /**
     * Handle database update action
     */
    public function handle_database_update() {
        if (isset($_POST['windrose_update_database']) && wp_verify_nonce($_POST['_wpnonce'], 'windrose_update_db')) {
            $this->create_payment_logs_table();
            echo '<div class="notice notice-success"><p>Database updated successfully!</p></div>';
        }
    }

    /**
     * Render the updater page
     */
    public function render_updater_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . WINDROS_SUBSCRIPTION_PAYMENT_LOGS_TABLE;
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        ?>
        <div class="wrap">
            <h1>Windrose Subscription Database Updater</h1>
            
            <div class="card">
                <h2>Payment Logs Table Status</h2>
                <p>
                    <?php if ($table_exists): ?>
                        <span style="color: green;">✅ Table exists</span>
                    <?php else: ?>
                        <span style="color: red;">❌ Table missing</span>
                    <?php endif; ?>
                </p>
                
                <?php if (!$table_exists): ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('windrose_update_db'); ?>
                        <p>
                            <input type="submit" name="windrose_update_database" class="button button-primary" value="Create Payment Logs Table" />
                        </p>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Create payment logs table
     */
    public function create_payment_logs_table() {
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

        $result = dbDelta($create_payment_logs_table_query);
        
        // Update database version
        update_option('windrose_db_version', '1.1');
        
        return $result;
    }
} 