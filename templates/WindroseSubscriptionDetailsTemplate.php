<?php
namespace WindroseSubscription\Templates;

defined( 'WINDROS_INIT' ) || exit;      // Exit if accessed directly.


class WindroseSubscriptionDetailsTemplate {

    
    public function subscription_details($subscription_id) {

        
        wp_enqueue_style('windrose-myaccount', WINDROS_URL .'assets/stylesheets/my-account.css'); 
        wp_enqueue_script('windrose-my-account', WINDROS_URL .'assets/javascripts/my-account.js', array('jquery'), '1.0', true); 
        
        
        $customer = wp_get_current_user();

        global $wpdb;

        // Define your custom table name (considering WordPress table prefix)
        $subscription_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_MAIN_TABLE;

        // Query to get all rows from the custom table
        // Prepare and execute the query to retrieve a single subscription
        $subscription = $wpdb->get_row( 
            $wpdb->prepare( "SELECT * FROM $subscription_table WHERE id = %d", $subscription_id ), 
            ARRAY_A // Return the result as an associative array
        );

        if ( $subscription ) {
            if($subscription['user_id'] == $customer->ID){
                $product = wc_get_product( $subscription['product_id'] );
                $product_title = $product->get_name();
                // Get product thumbnail (this returns the URL)
                $product_thumbnail_url = get_the_post_thumbnail_url( $subscription['product_id'], 'full' );
                // If no thumbnail exists, use WooCommerce placeholder image
                if ( !$product_thumbnail_url ) {
                    $product_thumbnail_url = wc_placeholder_img_src(); // Placeholder image
                }
                // $subscription_schedule = $subscription->schedule;
                $subscription_qty = $subscription['quantity'];
                $subscription_price = $product->get_price() * $subscription_qty;
                $formatted_price = wc_price( $subscription_price );
                $product_price_html = $formatted_price . '&nbsp;' . __('for', 'windros-subscription') . '&nbsp;' . WINDROS_FREQUENCY[$subscription['schedule']];

                $subscription_order_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_ORDER_TABLE;
                $upcoming_order_data = $wpdb->get_row( 
                    $wpdb->prepare( "SELECT * FROM $subscription_order_table WHERE subscription_id = %d AND status = 'upcoming'", $subscription_id ), 
                    ARRAY_A // Return the result as an associative array
                );

                
                $past_orders_query = $wpdb->prepare( "SELECT * FROM $subscription_order_table WHERE subscription_id = %d AND status != 'upcoming' ORDER BY sequence DESC", $subscription_id );
                $past_orders = $wpdb->get_results( $past_orders_query );


                ob_start();
                ?>
                    <div class="subsc-detail-page">
                        <div class="subsc-prdt-detail">
                            <div class="sub-prdt-thumb-full">
                                <img src="<?php echo esc_url($product_thumbnail_url);?>" alt="<?php echo esc_attr($product_title); ?>">
                            </div>
                            <div class="sub-prdt-details">
                                <h3 class="woocommerce-order-details__title"><?php echo esc_html($product_title); ?></h3>
                                <h5 class="sub-prdt-price-schedule"><?php echo $product_price_html; ?></h5>
                                <p><?php echo __('Quantity: ', 'windros-subscription') . $subscription_qty; ?></p>
                                <p><?php echo __('Subscription ID:', 'windros-subscription') . '&nbsp;#' . $subscription_id; ?></p>
                                <p class="subscription-status <?php echo esc_attr($subscription['status']); ?>">
                                    <img src="<?php echo WINDROS_URL.'/assets/icons/'. esc_attr($subscription['status']) .'.svg'; ?>" alt="<?php echo esc_attr($subscription['status']); ?>">
                                    <?php
                                        echo WINDROS_SUBSCRIPTION_STATUS[$subscription['status']];
                                    ?>
                                </p>
                            </div>
                        </div>
                        <div class="subscription-actions">
                            <?php 
                                if($subscription['status'] == 'active' || $subscription['status'] == 'processing') {
                                    ?>
                                        <a href="#<?php echo esc_attr( $subscription['id'] ); ?>" class="woocommerce-button edit-subscription"
                                        aria-label="Edit subscription <?php echo esc_attr( $subscription['id'] );?>"><?php _e('Edit Subscription', 'windros-subscription' ); ?></a>
                                    <?php 
                                }
                            
                                if($subscription['status'] == 'active') {
                                    ?>
                                    <a href="#<?php echo esc_attr( $subscription['id'] ); ?>" class="woocommerce-button pause-subscription"
                                    aria-label="Pause subscription <?php echo esc_attr( $subscription['id'] );?>"><?php _e('Pause Subscription', 'windros-subscription' ); ?></a>
                            
                                    <?php 
                                }

                                if($subscription['status'] == 'paused') {
                                    ?>
                                        <?php wp_nonce_field('reactivate_subscription_action', 'reactivate_subscription_nonce'); ?>
                                        <a href="#<?php echo esc_attr( $subscription['id'] ); ?>" class="woocommerce-button reactivate-subscription"
                                        aria-label="Re-activate subscription <?php echo esc_attr( $subscription['id'] );?>" data-subscription-id="<?php echo esc_attr( $subscription['id'] );?>">
                                            <?php _e('Re-activate Subscription', 'windros-subscription' ); ?>
                                        </a>
                                    
                                    <?php
                                }
                                
                                if($subscription['status'] == 'active' || $subscription['status'] == 'processing' || $subscription['status'] == 'paused') {
                                    ?>
                                        <a href="#<?php echo esc_attr( $subscription['id'] ); ?>" class="woocommerce-button cancel-subscription"
                                        aria-label="Cancel subscription <?php echo esc_attr( $subscription['id'] );?>"><?php _e('Cancel Subscription', 'windros-subscription' ); ?></a>
                                    <?php
                                }

                                if($subscription['status'] == 'cancel' || $subscription['status'] == 'expired') {
                                    ?>
                                        <a href="<?php echo esc_url( get_permalink( $subscription['product_id'] ) ) ;?>" class="woocommerce-button"
                                        aria-label="Continue subscription of <?php echo esc_attr( get_the_title( $subscription['product_id'] ) );?>"><?php _e('Continue Subscription', 'windros-subscription' ); ?></a>
                                    <?php
                                }
                            ?>
                        </div>
                        <div class="update-subscription-wrapper windrose-foredrop">
                            <?php
                                $subscription_data = array(
                                    'id' => $subscription['id'],
                                    'quantity' => $subscription['quantity'],
                                    'schedule' => $subscription['schedule'],
                                    'product_name' => $product_title,
                                    'product_id' => $product->get_id(),
                                );
                                $update_subscription_template = new WindroseUpdateSubscriptionTemplate();
                                $update_subscription_template->update_subscription((object) $subscription_data);
                            ?>
                        </div>
                        <div class="pause-subscription-wrapper windrose-foredrop">
                            <?php
                                $subscription_data = array(
                                    'id' => $subscription['id'],                                        
                                );
                                $pause_subscription_template = new WindrosePauseSubscriptionTemplate();
                                $pause_subscription_template->pause_subscription((object) $subscription_data);
                            ?>
                        </div>
                        <div class="cancel-subscription-wrapper windrose-foredrop">
                            <?php
                                $subscription_data = array(
                                    'id' => $subscription['id'],                                        
                                );
                                $cancel_subscription_template = new WindroseCancelSubscriptionTemplate();
                                $cancel_subscription_template->cancel_subscription((object) $subscription_data);
                            ?>
                        </div>
                        
                        <div class="windrose-backdrop"></div>
                        <?php 
                        if(!empty($upcoming_order_data)){
                            $sequence = windrose_get_day_with_suffix($upcoming_order_data['sequence'] + 1);
                            $timestamp = $upcoming_order_data['time_stamp'];
                            $date = date('F d, Y', $timestamp);
                            
                            ?>
                            <div class="skip-subscription-wrapper windrose-foredrop">
                                <?php
                                    $subscription_data = array(
                                        'id' => $subscription['id'],                                        
                                    );
                                    $skip_subscription_template = new WindroseSkipSubscriptionTemplate();
                                    $skip_subscription_template->skip_subscription((object) $subscription_data);
                                ?>
                            </div>
                            <div class="upcoming-delivery">
                                <h3 class="woocommerce-order-details__title"><?php echo __('Upcoming Order', 'windros-subscription'); ?></h3>
                                <div class="upcoming-wrapper">
                                    <div class="upcoming-inner">
                                        <div class="icon-wrapper">
                                            <img src="<?php echo WINDROS_URL.'/assets/icons/calendar_month.svg'; ?>" alt="Calendar">
                                        </div>
                                        <div class="upcoming-details">
                                            <h5 class="woocommerce-order-details__title"><?php echo esc_html($sequence) . '&nbsp;' . __('Delivery', 'windros-subscription') ; ?></h5>
                                            <p class="delivery-date"><?php echo esc_html($date); ?></p>
                                        </div>
                                    </div>
                                    <div class="upcomming-actions">
                                        <a href="#skip-subscription-order" class="woocommerce-button skip-delivery"
                                        aria-label="Skip subscription delivery"><?php _e('Skip Delivery', 'windros-subscription' ); ?></a>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        if(!empty($past_orders) || $subscription['active_date'] != ''){                                                 
                            ?>                            
                            <div class="past-deliveries">
                                <h3 class="woocommerce-order-details__title"><?php echo __('Previous Orders', 'windros-subscription'); ?></h3>
                                <ul class="past-delivery-list">
                                    <?php 
                                        if(!empty($past_orders)){     
                                            foreach($past_orders as $past_order){
                                                $past_sequence = windrose_get_day_with_suffix($past_order->sequence + 1);
                                                $past_timestamp = $past_order->time_stamp;
                                                $past_date = date('F d, Y', $past_timestamp);
                                                ?>
                                                <li class="prev-order-status-<?php echo esc_attr($past_order->status); ?>">
                                                    <h5 class="woocommerce-order-details__title">
                                                        <?php echo esc_html($past_sequence) . '&nbsp;' . __('Delivery', 'windros-subscription') ; ?>
                                                        <?php 
                                                            if($past_order->status != 'past'){
                                                                echo '<sup>' . esc_html(WINDROS_SUBSCRIPTION_ORDER_STATUS[$past_order->status]) . '</sup>';
                                                            }
                                                        ?>
                                                        
                                                    </h5>
                                                    <p class="delivery-date"><?php echo esc_html($past_date); ?></p>
                                                </li>
                                                <?php
                                            }
                                        }
                                        
                                        if($subscription['active_date'] != ''){
                                            $subscription_activated_date = strtotime($subscription['active_date']);
                                            $subscription_active_date = date('F d, Y', $subscription_activated_date);                                    
                                            ?> 
                                            <li class="prev-order-status-past>">
                                                <h5 class="woocommerce-order-details__title">
                                                    <?php echo esc_html(windrose_get_day_with_suffix(1)) . '&nbsp;' . __('Delivery', 'windros-subscription') ; ?>
                                                </h5>
                                                <p class="delivery-date"><?php echo esc_html($subscription_active_date); ?></p>
                                            </li>    
                                            <?php
                                        }
                                    ?>                                                                             
                                </ul>
                            </div>   
                            <?php
                        }
                        ?>                     
                    </div>
                <?php
                
                echo ob_get_clean();
                
            }else{
                // The subscription is not subscribed by the current user.
                echo __('Illegal Access!', 'windros-subscription');
            }
            
        } else {
            echo __('No subscription found.', 'windros-subscription');
        }
        
        

        
        
    }
}