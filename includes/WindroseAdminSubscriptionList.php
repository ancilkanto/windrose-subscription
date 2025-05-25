<?php
namespace WindroseSubscription\Includes;
use WindroseSubscription\Templates\WindroseAdminSubscriptionListTemplate;

defined( 'WINDROS_INIT' ) || exit;  


class WindroseAdminSubscriptionList {
    public function __construct() {
        add_action('admin_menu', [$this, 'subscription_listing_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_select2_assets']);
        add_action('admin_footer', [$this, 'initialize_select2_filters']);
        add_action('admin_notices', [$this, 'display_bulk_action_notice']);
    }


    public function subscription_listing_menu() {
        add_submenu_page(
            'woocommerce', 
            'Subscriptions',
            'Subscriptions',
            'manage_woocommerce',
            'windrose-subscriptions',
            [$this, 'subscription_list_view'],
        );
    }
    
    public function subscription_list_view() {
        echo '<div class="wrap"><h1>'. __('Subscriptions', 'windros-subscription') .'</h1>';
        
        $subscription_list = new WindroseAdminSubscriptionListTemplate();
        $subscription_list->prepare_items();                
        $subscription_list->display();                                              
        
        echo '</div>';
    }

    public function enqueue_select2_assets() {
        // Ensure WooCommerce is active to use its assets
        wp_enqueue_style('windrose-admin', WINDROS_URL . '/assets/stylesheets/admin-style.css');
        if (class_exists('WooCommerce')) {
            wp_enqueue_style('select2-css', WC()->plugin_url() . '/assets/css/select2.css');
            wp_enqueue_script('select2-js', WC()->plugin_url() . '/assets/js/select2/select2.full.min.js', array('jquery'), WC_VERSION, true);
        }
    }

    public function initialize_select2_filters() {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                
                if($('.woocommerce_page_windrose-subscriptions .bulkactions').length){
                    var bulkActionMarkup = $('.bulkactions').html();
                    bulkActionMarkup = '<form method="post"><input type="hidden" name="subscriptions" class="subscription_ids">' + bulkActionMarkup + '</form>';
                    $('.bulkactions').html(bulkActionMarkup);

                    

                    $('.subscription-bulk-select, .subscriptions #cb-select-all-1, .subscriptions #cb-select-all-2').on('change', function(){
                        var bulkSelect = [];
                        $('.subscription-bulk-select').each(function(){
                            if($(this).is(":checked")){                                    
                                bulkSelect.push($(this).attr('value'));
                            }
                        });                            
                        $('.subscription_ids').val(bulkSelect.join(","));
                    });
                }
                // $('#filter_status').select2({
                //     placeholder: 'Select a Status',
                //     allowClear: true,
                //     minimumResultsForSearch: Infinity // hides the search box for small lists
                // });
                // $('#product_filter').select2({
                //     // placeholder: 'Select a Product',
                //     // allowClear: false,
                //     // minimumResultsForSearch: Infinity
                // });
            });
        </script>
        <?php
    }

    // Hook into admin_notices to display the transient message
    public function display_bulk_action_notice() {
        // Check if the transient is set and display the notice
        if ($notice = get_transient('windrose_bulk_action_notice')) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html($notice); ?></p>
            </div>
            <?php
            // Delete the transient to prevent repeated notices
            delete_transient('windrose_bulk_action_notice');
        }
    }
    
    
}
