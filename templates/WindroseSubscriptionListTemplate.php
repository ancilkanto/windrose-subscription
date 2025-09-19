<?php
namespace WindroseSubscription\Templates;

defined( 'WINDROS_INIT' ) || exit;      // Exit if accessed directly.


class WindroseSubscriptionListTemplate {

	
	public function subscription_list() {

		

		wp_enqueue_style('windrose-myaccount', WINDROS_URL .'assets/stylesheets/my-account.css'); 

		$customer = wp_get_current_user();

		global $wpdb;

		// Define your custom table name (considering WordPress table prefix)
		$subscription_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_MAIN_TABLE;

		// Query to get all rows from the custom table
		$subscription_query = $wpdb->prepare( "SELECT * FROM $subscription_table WHERE user_id = %d ORDER BY id DESC", $customer->ID );
		$subscription_list = $wpdb->get_results( $subscription_query );
		
		if ( !empty($subscription_list) ) {
			echo '<p>' . __( 'Here is the list of all your subscriptions!', 'windros-subscription' ) . '</p>';
			ob_start();
		?>
				<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
					<thead>
						<tr>
							<th scope="col" style="width: 45%;" class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><span
									class="nobr"><?php echo __('Subscription', 'windros-subscription'); ?></span></th>
							<th scope="col" class="woocommerce-orders-table__header woocommerce-orders-table__header-order-date"><span
									class="nobr"><?php echo __('Upcoming', 'windros-subscription'); ?></span></th>
							<th scope="col" class="woocommerce-orders-table__header woocommerce-orders-table__header-order-status"><span
									class="nobr"><?php echo __('Status', 'windros-subscription'); ?></span></th>
							<th scope="col" class="woocommerce-orders-table__header woocommerce-orders-table__header-order-actions">
								<span class="nobr"><?php echo __('Actions', 'windros-subscription'); ?></span></th>
						</tr>
					</thead>

					<tbody>
						<?php
							foreach ( $subscription_list as $subscription_item ) {
								
								// Get the product object
								$product = wc_get_product( $subscription_item->product_id );
								$product_title = $product->get_name();
								// Get product thumbnail (this returns the URL)
								$product_thumbnail_url = get_the_post_thumbnail_url( $subscription_item->product_id, 'thumbnail' );
								// If no thumbnail exists, use WooCommerce placeholder image
								if ( !$product_thumbnail_url ) {
									$product_thumbnail_url = wc_placeholder_img_src(); // Placeholder image
								}
								// $subscription_schedule = $subscription_item->schedule;
								$subscription_qty = $subscription_item->quantity;
								$subscription_price = $product->get_price() * $subscription_qty;
								$formatted_price = wc_price( $subscription_price );
								$product_price_html = $formatted_price . '&nbsp;' . __('for', 'windros-subscription') . '&nbsp;' . WINDROS_FREQUENCY[$subscription_item->schedule];
								
								$subscription_order_table = $wpdb->prefix . WINDROS_SUBSCRIPTION_ORDER_TABLE;
								$upcoming_order_data = $wpdb->get_row( 
									$wpdb->prepare( "SELECT time_stamp FROM $subscription_order_table WHERE subscription_id = %d AND status = 'upcoming'", $subscription_item->id )
								);
								$date = '--';
								$raw_date = '--';
								if($upcoming_order_data){										
									$timestamp = $upcoming_order_data->time_stamp;
									$date = date('F d, Y', $timestamp);
									$raw_date = $upcoming_order_data->created_at;
								}

								?>
									<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order">
										<th class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number" data-title="Order"
											scope="row">
											<div class="subsc-prdt-detail">
												<div class="sub-prdt-thumb">
													<img src="<?php echo esc_url($product_thumbnail_url);?>" alt="<?php echo esc_attr($product_title); ?>">
												</div>
												<div class="sub-prdt-details">
													<h3 class="sub-prdt-name"><?php echo esc_html($product_title); ?></h3>
													<h6 class="sub-prdt-price-schedule"><?php echo $product_price_html; ?></h6>
													<p><?php echo __('Quantity: ', 'windros-subscription') . $subscription_qty; ?></p>
												</div>
											</div>												
										</th>
										<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date" data-title="Date">

											<time datetime="<?php echo esc_attr($raw_date); ?>"><?php echo esc_html($date); ?></time>
											
										</td>
										<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-status" data-title="Status">
											<span class="subscription-status subscription-status_<?php echo esc_attr($subscription_item->status); ?>">
												<?php
													echo WINDROS_SUBSCRIPTION_STATUS[$subscription_item->status];
												?>
											</span>
										</td>
										<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions"
											data-title="Actions">

											<a href="<?php echo esc_url( wc_get_endpoint_url( 'view-subscription', $subscription_item->id ) ) ?>" class="woocommerce-button button view"
												aria-label="View subscription number <?php echo esc_attr( $subscription_item->id );?>"><?php echo __('View Details', 'windros-subscription' ); ?></a>
										</td>
									</tr>
								<?php
							}
						?>
					</tbody>
				</table>
			<?php

			echo ob_get_clean();
			
		} else {
			echo '<p style="padding-bottom: 20px;">' . __( 'Sorry! No Subscriptions Available.', 'windros-subscription' ) . '</p>';
			echo '<a href="' . home_url() . '/subscription-products/" class="woocommerce-button button">' . __( 'View Subscription Products', 'windros-subscription' ) . '</a>';
		}

		
		
	}
}

