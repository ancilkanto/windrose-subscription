<?php
namespace WindroseSubscription\Includes;
use WindroseSubscription\Templates\WindroseSubscriptionListTemplate;
use WindroseSubscription\Templates\WindroseSubscriptionDetailsTemplate;

defined( 'WINDROS_INIT' ) || exit;      // Exit if accessed directly.

class WindroseMyAccountInit {
    public function __construct() {
        // Register endpoints early with high priority
        add_action( 'init', [$this, 'windrose_add_my_account_endpoint'], 0 );
        
        // Register query vars with high priority
        add_filter( 'query_vars', [$this, 'windrose_my_account_query_vars'], 0 );
        
        // IMPORTANT: Register WooCommerce query vars
        add_filter( 'woocommerce_get_query_vars', [$this, 'windrose_wc_get_query_vars'] );
        
        // Add new tab to My Account
        add_filter( 'woocommerce_account_menu_items', [$this, 'windrose_subscription_add_my_account_tab'], 10, 1 );

        // Add content for new tab
        add_action( 'woocommerce_account_subscriptions_endpoint', [$this, 'windrose_subscription_add_my_account_tab_content'] );
        add_action( 'woocommerce_account_view-subscription_endpoint', [$this, 'subscription_detail_content'] );

        // Make the subscription tab active when viewing an subscription detail
        add_filter( 'woocommerce_account_menu_item_classes', [$this, 'subscriptions_set_active_menu_item'], 10, 2 );
        
        // Set page titles for custom endpoints
        add_filter( 'woocommerce_endpoint_subscriptions_title', [$this, 'subscriptions_endpoint_title'], 10, 2 );
        add_filter( 'woocommerce_endpoint_view-subscription_title', [$this, 'view_subscription_endpoint_title'], 10, 2 );
        
        // Also add a filter for the page title directly
        add_filter( 'the_title', [$this, 'custom_endpoint_titles'], 10, 2 );
        add_filter( 'woocommerce_page_title', [$this, 'custom_wc_page_title'], 10, 1 );
        
        // Force flush rewrite rules if needed
        add_action( 'init', [$this, 'maybe_flush_rewrite_rules'], 20 );
        
        // Add admin notice for rewrite rules
        add_action( 'admin_notices', [$this, 'admin_notice_rewrite_rules'] );
        
        // Handle manual flush request
        add_action( 'admin_init', [$this, 'handle_manual_flush'] );
    }

    /**
     * Add menu items to My Account page
     */
    public function windrose_subscription_add_my_account_tab( $items ) {
        $new_items = array_slice($items, 0, 2, true);
        $new_items['subscriptions'] = __( 'Subscriptions', 'windros-subscription' );
        $new_items += array_slice($items, 2, null, true);
        return $new_items;
    }

    /**
     * Register rewrite endpoints
     */
    public function windrose_add_my_account_endpoint() {
        add_rewrite_endpoint( 'subscriptions', EP_ROOT | EP_PAGES );
        add_rewrite_endpoint( 'view-subscription', EP_ROOT | EP_PAGES );
    }

    /**
     * Register query vars for WordPress
     */
    public function windrose_my_account_query_vars( $vars ) {
        $vars[] = 'subscriptions';
        $vars[] = 'view-subscription';
        return $vars;
    }

    /**
     * CRITICAL: Register query vars for WooCommerce
     * This is what makes WC()->query->get_current_endpoint() work
     */
    public function windrose_wc_get_query_vars( $query_vars ) {
        $query_vars['subscriptions'] = 'subscriptions';
        $query_vars['view-subscription'] = 'view-subscription';
        return $query_vars;
    }

    /**
     * Maybe flush rewrite rules
     */
    public function maybe_flush_rewrite_rules() {
        $version = '1.0.0'; // Update this when you need to flush rules
        $option_name = 'windrose_endpoints_version';
        
        if ( get_option( $option_name ) !== $version ) {
            flush_rewrite_rules();
            update_option( $option_name, $version );
            
            // Debug: Check if endpoints are registered
            $this->debug_endpoints();
        }
    }

    /**
     * Handle manual flush request from admin
     */
    public function handle_manual_flush() {
        if ( isset( $_GET['windrose_flush_rules'] ) && current_user_can( 'manage_options' ) ) {
            flush_rewrite_rules();
            delete_option( 'windrose_endpoints_version' );
            wp_redirect( admin_url( 'admin.php?windrose_flushed=1' ) );
            exit;
        }
    }

    /**
     * Debug endpoints registration
     */
    public function debug_endpoints() {
        global $wp_rewrite;
        
        // Check if our endpoints are in the rewrite rules
        $has_subscriptions = false;
        $has_view_subscription = false;
        
        if ( isset( $wp_rewrite->endpoints ) ) {
            foreach ( $wp_rewrite->endpoints as $endpoint ) {
                if ( $endpoint[1] === 'subscriptions' ) {
                    $has_subscriptions = true;
                }
                if ( $endpoint[1] === 'view-subscription' ) {
                    $has_view_subscription = true;
                }
            }
        }
        
        // Also check WooCommerce query vars
        $wc_query_vars = WC()->query->get_query_vars();
        $has_wc_subscriptions = isset( $wc_query_vars['subscriptions'] );
        $has_wc_view_subscription = isset( $wc_query_vars['view-subscription'] );
        
        // Log the status
        error_log( 'Windrose Subscription Endpoints Debug:' );
        error_log( 'WP Subscriptions endpoint registered: ' . ( $has_subscriptions ? 'Yes' : 'No' ) );
        error_log( 'WP View-subscription endpoint registered: ' . ( $has_view_subscription ? 'Yes' : 'No' ) );
        error_log( 'WC Subscriptions query var registered: ' . ( $has_wc_subscriptions ? 'Yes' : 'No' ) );
        error_log( 'WC View-subscription query var registered: ' . ( $has_wc_view_subscription ? 'Yes' : 'No' ) );
        
        // Test current endpoint detection
        if ( function_exists( 'WC' ) && WC()->query ) {
            error_log( 'Current endpoint: ' . var_export( WC()->query->get_current_endpoint(), true ) );
        }
    }

    /**
     * Admin notices
     */
    public function admin_notice_rewrite_rules() {
        // Only show on admin pages and if user has permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Show success message if rules were flushed
        if ( isset( $_GET['windrose_flushed'] ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>Windrose Subscription:</strong> 
                    Rewrite rules have been flushed successfully. The subscription endpoints should now work properly.
                </p>
            </div>
            <?php
            return;
        }

        // Show success message if email test was completed
        if ( isset( $_GET['windrose_email_tested'] ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>Windrose Subscription:</strong> 
                    Subscription activation email test has been triggered. Check the debug log for details.
                </p>
            </div>
            <?php
            return;
        }

        // Show success message if all emails test was completed
        if ( isset( $_GET['windrose_all_emails_tested'] ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>Windrose Subscription:</strong> 
                    All subscription email tests have been triggered. Check the debug log for details.
                </p>
            </div>
            <?php
            return;
        }

        // Show success message if direct email test was completed
        if ( isset( $_GET['windrose_email_direct_tested'] ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>Windrose Subscription:</strong> 
                    Direct email system test has been triggered. Check the debug log for details.
                </p>
            </div>
            <?php
            return;
        }

        // Show success message if HTML email test was completed
        if ( isset( $_GET['windrose_html_email_tested'] ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>Windrose Subscription:</strong> 
                    HTML email test has been triggered. Check the debug log for HTML content details.
                </p>
            </div>
            <?php
            return;
        }

        // Check if endpoints are working
        $test_url = wc_get_account_endpoint_url( 'subscriptions' );
        $wc_query_vars = WC()->query->get_query_vars();
        
        if ( ! isset( $wc_query_vars['subscriptions'] ) || ! isset( $wc_query_vars['view-subscription'] ) ) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>Windrose Subscription:</strong> 
                    The subscription endpoints may not be registered with WooCommerce properly. 
                    <a href="<?php echo admin_url( 'options-permalink.php' ); ?>">Go to Permalinks Settings</a> 
                    and click "Save Changes", or 
                    <a href="<?php echo admin_url( 'admin.php?windrose_flush_rules=1' ); ?>">click here to flush rewrite rules directly</a>.
                </p>
                <p>
                    <small>Debug: WC Query Vars - Subscriptions: <?php echo isset( $wc_query_vars['subscriptions'] ) ? 'Yes' : 'No'; ?>, 
                    View-subscription: <?php echo isset( $wc_query_vars['view-subscription'] ) ? 'Yes' : 'No'; ?></small>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Content for subscriptions list page
     */
    public function windrose_subscription_add_my_account_tab_content() {
        // Debug: Check current endpoint
        error_log( 'Current endpoint on subscriptions page: ' . var_export( WC()->query->get_current_endpoint(), true ) );
        
        $subscription_template = new WindroseSubscriptionListTemplate();
        $subscription_template->subscription_list();
    }

    /**
     * Content for subscription detail view
     */
    public function subscription_detail_content() {
        // Get the item ID from the URL
        $subscription_id = get_query_var( 'view-subscription' );
        
        // Debug: Check current endpoint
        error_log( 'Current endpoint on view-subscription page: ' . var_export( WC()->query->get_current_endpoint(), true ) );
        error_log( 'Subscription ID: ' . $subscription_id );

        $subscription_template = new WindroseSubscriptionDetailsTemplate();
        $subscription_template->subscription_details($subscription_id);
    }

    /**
     * Set active menu item
     */
    public function subscriptions_set_active_menu_item( $classes, $endpoint ) {
        global $wp;
        
        // Check if we're on the subscription-detail page
        if ( isset( $wp->query_vars['view-subscription'] ) ) {
            // Add the 'is-active' class to the 'subscriptions' menu
            if ( $endpoint == 'subscriptions' ) {
                $classes[] = 'is-active';
            }
        }
        
        // Also check using WooCommerce's method
        $current_endpoint = WC()->query->get_current_endpoint();
        if ( $current_endpoint === 'subscriptions' && $endpoint === 'subscriptions' ) {
            $classes[] = 'is-active';
        }
        if ( $current_endpoint === 'view-subscription' && $endpoint === 'subscriptions' ) {
            $classes[] = 'is-active';
        }

        return $classes;
    }

    /**
     * Set the page title for the subscriptions endpoint
     */
    public function subscriptions_endpoint_title( $title, $endpoint ) {
        if ( $endpoint === 'subscriptions' ) {
            $title = __( 'Subscriptions', 'windros-subscription' );
        }
        return $title;
    }

    /**
     * Set the page title for the view-subscription endpoint
     */
    public function view_subscription_endpoint_title( $title, $endpoint ) {
        // The endpoint parameter might not match what we expect
        // Check the current endpoint directly
        $current_endpoint = WC()->query->get_current_endpoint();
        
        if ( $endpoint === 'view-subscription' || $current_endpoint === 'view-subscription' ) {
            $title = __( 'Subscription Details', 'windros-subscription' );
        }
        
        return $title;
    }
    
    /**
     * Filter the page title directly for custom endpoints
     */
    public function custom_endpoint_titles( $title, $id = null ) {
        // Only modify title on My Account page
        if ( ! is_account_page() || ! in_the_loop() ) {
            return $title;
        }
        
        $current_endpoint = WC()->query->get_current_endpoint();
        
        if ( $current_endpoint === 'view-subscription' && $title === 'My account' ) {
            $title = __( 'Subscription Details', 'windros-subscription' );
        }
        
        return $title;
    }
    
    /**
     * Filter WooCommerce page title for custom endpoints
     */
    public function custom_wc_page_title( $title ) {
        if ( ! is_account_page() ) {
            return $title;
        }
        
        $current_endpoint = WC()->query->get_current_endpoint();
        
        if ( $current_endpoint === 'subscriptions' ) {
            return __( 'Subscriptions', 'windros-subscription' );
        }
        
        if ( $current_endpoint === 'view-subscription' ) {
            // Get subscription ID if needed for dynamic title
            $subscription_id = get_query_var( 'view-subscription' );
            if ( $subscription_id ) {
                return sprintf( __( 'Subscription #%s', 'windros-subscription' ), $subscription_id );
            }
            return __( 'Subscription Details', 'windros-subscription' );
        }
        
        return $title;
    }
}

// Additional helper function to test endpoint detection
if ( ! function_exists( 'windrose_test_current_endpoint' ) ) {
    function windrose_test_current_endpoint() {
        if ( function_exists( 'WC' ) && WC()->query ) {
            echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0;">';
            echo '<strong>Debug Info:</strong><br>';
            echo 'Current Endpoint: ' . var_export( WC()->query->get_current_endpoint(), true ) . '<br>';
            echo 'Query Vars: <pre>' . print_r( WC()->query->get_query_vars(), true ) . '</pre>';
            echo 'Global WP Query Vars: <pre>' . print_r( get_query_var( 'subscriptions' ), true ) . '</pre>';
            echo '</div>';
        }
    }
}
