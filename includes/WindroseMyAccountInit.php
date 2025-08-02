<?php
namespace WindroseSubscription\Includes;
use WindroseSubscription\Templates\WindroseSubscriptionListTemplate;
use WindroseSubscription\Templates\WindroseSubscriptionDetailsTemplate;

defined( 'WINDROS_INIT' ) || exit;      // Exit if accessed directly.


class WindroseMyAccountInit {
    public function __construct() {
        // Register the custom endpoint for the new tab immediately
        $this->windrose_add_my_account_endpoint();
        
        // Add new tab to My Account
        add_filter( 'woocommerce_account_menu_items', [$this, 'windrose_subscription_add_my_account_tab'], 10, 1 );

        // Add content for new tab
        add_action( 'woocommerce_account_subscriptions_endpoint', [$this, 'windrose_subscription_add_my_account_tab_content'] );

        add_action( 'woocommerce_account_view-subscription_endpoint', [$this, 'subscription_detail_content'] );

        // Register the custom endpoint for the new tab again on init (for safety)
        add_action( 'init', [$this, 'windrose_add_my_account_endpoint'], 5 );

        // Make the subscription tab active when viewing an subscription detail
        add_filter( 'woocommerce_account_menu_item_classes', [$this, 'subscriptions_set_active_menu_item'], 10, 2 );
        
        // Force flush rewrite rules to ensure endpoints are registered
        add_action( 'init', [$this, 'force_flush_rewrite_rules'], 20 );
        
        // Add admin notice for rewrite rules
        add_action( 'admin_notices', [$this, 'admin_notice_rewrite_rules'] );
            
    }

    public function windrose_subscription_add_my_account_tab( $items ) {
        
        $new_items = array_slice($items, 0, 2, true);
        $new_items['subscriptions'] = __( 'Subscriptions', 'windros-subscription' );
        $new_items += array_slice($items, 2, null, true);
        return $new_items;
    }

    public function windrose_add_my_account_endpoint() {
        add_rewrite_endpoint( 'subscriptions', EP_ROOT | EP_PAGES );
        add_rewrite_endpoint( 'view-subscription', EP_ROOT | EP_PAGES );
        // Flush rewrite rules upon plugin activation is in "install-plugin.php"
    }

    public function force_flush_rewrite_rules() {
        // Only flush if the endpoints haven't been registered yet
        if ( ! get_option( 'windrose_endpoints_flushed' ) ) {
            flush_rewrite_rules();
            update_option( 'windrose_endpoints_flushed', true );
            
            // Debug: Check if endpoints are registered
            $this->debug_endpoints();
        }
    }

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
        
        // Log the status
        error_log( 'Windrose Subscription Endpoints Debug:' );
        error_log( 'Subscriptions endpoint registered: ' . ( $has_subscriptions ? 'Yes' : 'No' ) );
        error_log( 'View-subscription endpoint registered: ' . ( $has_view_subscription ? 'Yes' : 'No' ) );
    }

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

        // Check if endpoints are working by testing the URL
        $test_url = home_url( '/my-account/subscriptions/' );
        $response = wp_remote_get( $test_url, array( 'timeout' => 5 ) );
        
        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 404 ) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>Windrose Subscription:</strong> 
                    The subscription endpoints may not be working properly. 
                    <a href="<?php echo admin_url( 'options-permalink.php' ); ?>">Go to Permalinks Settings</a> 
                    and click "Save Changes", or 
                    <a href="<?php echo admin_url( 'admin.php?windrose_flush_rules=1' ); ?>">click here to flush rewrite rules directly</a>.
                </p>
            </div>
            <?php
        }
    }

    public function windrose_subscription_add_my_account_tab_content() {
        
        $subscription_template = new WindroseSubscriptionListTemplate();
        $subscription_template->subscription_list();
    }

    

    // Add content for the Item Detail view
    public function subscription_detail_content() {
        // Get the item ID from the URL (replace with your custom query)
        $subscription_id = get_query_var( 'view-subscription' );

        

        $subscription_template = new WindroseSubscriptionDetailsTemplate();
        $subscription_template->subscription_details($subscription_id);
        
    }


    public function subscriptions_set_active_menu_item( $classes, $endpoint  ) {
        global $wp;
                
        // Check if we're on the subscription-detail page
        if ( isset( $wp->query_vars['view-subscription'] ) ) {
            // Add the 'is-active' class to the 'custom-items' menu
            if ( $endpoint == 'subscriptions' ) {
                $classes[] = 'is-active';
            }
        }

        return $classes;            
    }            

}


















?>