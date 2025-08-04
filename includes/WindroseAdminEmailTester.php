<?php
namespace WindroseSubscription\Includes;

defined( 'ABSPATH' ) || exit;

class WindroseAdminEmailTester {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_email_test'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Windrose Subscription Email Tester',
            'Email Tester',
            'manage_options',
            'windrose-email-tester',
            array($this, 'admin_page')
        );
    }
    
    public function handle_email_test() {
        // Handle form submission
        if (isset($_POST['windrose_test_email']) && current_user_can('manage_options')) {
            $subscription_id = isset($_POST['subscription_id']) ? intval($_POST['subscription_id']) : 1;
            $email_type = isset($_POST['email_type']) ? sanitize_text_field($_POST['email_type']) : 'admin';
            
            $this->send_test_email($subscription_id, $email_type);
            
            wp_redirect(admin_url('admin.php?page=windrose-email-tester&test_sent=1&email_type=' . $email_type));
            exit;
        }
        
        // Handle customer email test link
        if (isset($_GET['windrose_test_customer_emails']) && current_user_can('manage_options')) {
            $subscription_id = isset($_GET['subscription_id']) ? intval($_GET['subscription_id']) : 1;
            
            $this->send_test_email($subscription_id, 'customer');
            
            wp_redirect(admin_url('admin.php?page=windrose-email-tester&test_sent=1&email_type=customer'));
            exit;
        }
    }
    
    private function send_test_email($subscription_id, $email_type = 'admin') {
        $results = array();
        
        if ($email_type === 'admin' || $email_type === 'all') {
                    // Test all admin emails using require_once
        $admin_classes = WindroseEmailFactory::getAdminEmailClasses();
        
                    foreach ($admin_classes as $class_name) {
                $file_path = plugin_dir_path(__FILE__) . 'emails/class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
            if (file_exists($file_path)) {
                require_once $file_path;
                $full_class_name = 'WindroseSubscription\Includes\\' . $class_name;
                if (class_exists($full_class_name)) {
                    try {
                        $email = new $full_class_name();
                        if ($class_name === 'WindroseSubscriptionAdminOrderFailedEmail') {
                            $email->trigger($subscription_id, 'Test failure reason');
                        } else {
                            $email->trigger($subscription_id);
                        }
                        $results[] = str_replace('WindroseSubscriptionAdmin', 'Admin ', $class_name) . ': Sent successfully';
                    } catch (Exception $e) {
                        $results[] = str_replace('WindroseSubscriptionAdmin', 'Admin ', $class_name) . ': Error - ' . $e->getMessage();
                    }
                } else {
                    $results[] = str_replace('WindroseSubscriptionAdmin', 'Admin ', $class_name) . ': Class not found after require';
                }
            } else {
                $results[] = str_replace('WindroseSubscriptionAdmin', 'Admin ', $class_name) . ': File not found';
            }
        }
        }
        
        if ($email_type === 'customer' || $email_type === 'all') {
            // Test all customer emails using require_once
            $customer_classes = WindroseEmailFactory::getCustomerEmailClasses();
            
            foreach ($customer_classes as $class_name) {
                $file_path = plugin_dir_path(__FILE__) . 'emails/class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
                if (file_exists($file_path)) {
                    require_once $file_path;
                    $full_class_name = 'WindroseSubscription\Includes\\' . $class_name;
                    if (class_exists($full_class_name)) {
                        try {
                            $email = new $full_class_name();
                            if ($class_name === 'WindroseSubscriptionOrderFailedEmail') {
                                $email->trigger($subscription_id, 'Test failure reason');
                            } else {
                                $email->trigger($subscription_id);
                            }
                            $results[] = str_replace('WindroseSubscription', 'Customer ', $class_name) . ': Sent successfully';
                        } catch (Exception $e) {
                            $results[] = str_replace('WindroseSubscription', 'Customer ', $class_name) . ': Error - ' . $e->getMessage();
                        }
                    } else {
                        $results[] = str_replace('WindroseSubscription', 'Customer ', $class_name) . ': Class not found after require';
                    }
                } else {
                    $results[] = str_replace('WindroseSubscription', 'Customer ', $class_name) . ': File not found';
                }
            }
        }
        
        // Store results in transient for display
        set_transient('windrose_email_test_results', $results, 60);
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Windrose Subscription Email Tester</h1>
            <p>Test both admin and customer email notifications for subscription events.</p>
            
            <?php if (isset($_GET['test_sent'])): ?>
                <div class="notice notice-success">
                    <p><strong>Test emails sent!</strong></p>
                    <?php
                    $email_type = isset($_GET['email_type']) ? sanitize_text_field($_GET['email_type']) : 'admin';
                    $type_label = $email_type === 'admin' ? 'Admin' : ($email_type === 'customer' ? 'Customer' : 'All');
                    echo '<p><strong>Email Type:</strong> ' . esc_html($type_label) . '</p>';
                    ?>
                    <?php
                    $results = get_transient('windrose_email_test_results');
                    if ($results) {
                        echo '<ul>';
                        foreach ($results as $result) {
                            echo '<li>' . esc_html($result) . '</li>';
                        }
                        echo '</ul>';
                        delete_transient('windrose_email_test_results');
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <h2>Test Email Notifications</h2>
                <p>This tool allows you to test email notifications for subscription events. Choose which type of emails to test.</p>
                
                <form method="post" action="">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="subscription_id">Subscription ID</label>
                            </th>
                            <td>
                                <input type="number" id="subscription_id" name="subscription_id" value="1" min="1" class="regular-text">
                                <p class="description">Enter the ID of an existing subscription to test with real data.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="email_type">Email Type</label>
                            </th>
                            <td>
                                <select id="email_type" name="email_type" class="regular-text">
                                    <option value="admin">Admin Emails Only</option>
                                    <option value="customer">Customer Emails Only</option>
                                    <option value="all">All Emails (Admin + Customer)</option>
                                </select>
                                <p class="description">Choose which type of emails to test.</p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php wp_nonce_field('windrose_test_email', 'windrose_email_nonce'); ?>
                    <input type="submit" name="windrose_test_email" class="button button-primary" value="Send Test Emails">
                </form>
            </div>
            
            <div class="card">
                <h2>Quick Test Links</h2>
                <p>You can also test emails using these direct links:</p>
                <p>
                    <a href="<?php echo admin_url('admin.php?windrose_test_admin_emails=1&subscription_id=1'); ?>" class="button button-secondary">Test Admin Emails</a>
                    <a href="<?php echo admin_url('admin.php?page=windrose-email-tester&windrose_test_customer_emails=1&subscription_id=1'); ?>" class="button button-secondary">Test Customer Emails</a>
                </p>
            </div>
            
            <div class="card">
                <h2>Email Settings</h2>
                <p>Configure email settings in <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=email'); ?>">WooCommerce Email Settings</a>.</p>
                <ul>
                    <li><strong>Admin Emails:</strong> Look for emails with "(Admin)" in their names</li>
                    <li><strong>Customer Emails:</strong> Look for emails without "(Admin)" in their names</li>
                </ul>
            </div>
        </div>
        <?php
    }
} 