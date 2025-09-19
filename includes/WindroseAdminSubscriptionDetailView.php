<?php
namespace WindroseSubscription\Includes;
use WindroseSubscription\Templates\WindroseAdminSubscriptionDetailsTemplate;

defined( 'WINDROS_INIT' ) || exit;  


class WindroseAdminSubscriptionDetailView {
    public function __construct() {
        add_action('admin_menu', [$this, 'subscription_details_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_notices', [$this, 'display_admin_action_notice']);
        
    }

    public function subscription_details_menu() {
        add_submenu_page(
            '', 
            'Subscriptions',
            'Subscriptions',
            'manage_woocommerce',
            'windrose-subscription-detail',
            [$this, 'subscription_detail_view'],
        );
    }

    public function subscription_detail_view() {
        wp_enqueue_script('windros-admin');

        echo '<div class="wrap">';
        echo '<h1>'. __('Subscription Details', 'windros-subscription') .'</h1>';
        echo '<br><a href="'. get_admin_url(null, 'admin.php?page=windrose-subscriptions') .'">';
        echo '<input type="button" id="doaction" class="button action" value="Go Back">';
        echo '</a>';        
        $subscription_id = $_GET['id'];
        $subscription_detail = new WindroseAdminSubscriptionDetailsTemplate();
        ?>
            <div id="windrose-subscription-data" class="postbox windrose-postbox">
                <div class="inside">
                    <div class="panel-wrap windrose">
                        <?php $subscription_detail->display_subscription_details($subscription_id); ?>
                        <div class="clear"></div>                            
                    </div>
                    
                    <div class="panel-wrap windrose">
                        <?php $subscription_detail->display_subscription_upcoming_delivery($subscription_id); ?>
                        <div class="clear"></div>                            
                    </div>
                    <div class="panel-wrap windrose">
                        <?php $subscription_detail->display_subscription_previous_deliveries($subscription_id); ?>
                        <div class="clear"></div>                            
                    </div>
                </div>
            </div>
        <?php
        echo '<br><a href="'. get_admin_url(null, 'admin.php?page=windrose-subscriptions') .'">';
        echo '<input type="button" id="doaction" class="button action" value="Go Back">';
        echo '</a>';
        
        echo '</div>';
    }

    public function enqueue_assets() {
        // Ensure WooCommerce is active to use its assets
        wp_enqueue_style('windrose-admin', WINDROS_URL . '/assets/stylesheets/admin-style.css');
        // wp_enqueue_script('windrose-admin', WINDROS_URL . '/assets/javascripts/admin.js', array('jquery'), false, null);
        
    }

    // Hook into admin_notices to display the transient message
    public function display_admin_action_notice() {
        // Check if the transient is set and display the notice
        if ($notice = get_transient('windrose_admin_action_notice')) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html($notice); ?></p>
            </div>
            <?php
            // Delete the transient to prevent repeated notices
            delete_transient('windrose_admin_action_notice');
        }
    }
}
