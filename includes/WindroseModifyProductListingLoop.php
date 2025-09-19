<?php
namespace WindroseSubscription\Includes; 

defined( 'WINDROS_INIT' ) || exit;  




class WindroseModifyProductListingLoop {
    public function __construct() {
        // Add custom select field to product listing loop
        
        // add_action( 'woocommerce_after_shop_loop_item', [$this, 'add_short_description_to_product_loop'], 10 );
        add_action( 'woocommerce_after_shop_loop_item_title', [$this, 'add_short_description_to_product_loop'], 11 );
        add_filter( 'woocommerce_loop_add_to_cart_args', [$this, 'add_ajax_subscription_custom_class'], 10, 2 );

        // Add custom script to include select field value in AJAX request
        add_action( 'wp_footer', [$this, 'add_custom_ajax_script'] );

        // Handle the AJAX request to add the product to the cart
        add_action( 'wp_ajax_custom_add_to_cart_subscription', [$this, 'custom_ajax_add_to_cart'] );
        add_action( 'wp_ajax_nopriv_custom_add_to_cart_subscription', [$this, 'custom_ajax_add_to_cart'] );
    }

    public function add_custom_select_to_product_loop() {
        global $product;
    
        $enable_subscription_fields = false;
        $enable_subscription = get_post_meta( $product->get_id(), '_enable_subscription', true ); // Get the data - Checbox 1
        if( ! empty( $enable_subscription ) && $enable_subscription == 'yes' ){
            $enable_subscription_fields = true;
        }
        if(!$enable_subscription_fields){
            return;
        }
    
        $selected_fequencies = trim(get_post_meta( $product->get_id(), '_subscription_frequencies', true ));
        
    
        ob_start();
        ?>
    
        <div class="subscription-schedule-field">
            <label for="subscription-schedule">
                <?php
                    echo __('Choose Subscription Schedule', 'windros-subscription');
                ?>
            </label>
            <br>
            <select name="subscription-schedule" id="subscription-schedule">
                <?php
                if($selected_fequencies == ''){
                    $selected_fequencies = WINDROS_FREQUENCY;
                    foreach($selected_fequencies as $index => $frequency){
                        echo '<option value="'. $index .'">'. $frequency .'</option>';
                    }
                }else{
                    $selected_fequencies = explode(',', $selected_fequencies); 
                    foreach($selected_fequencies as $frequency){
                        echo '<option value="'.$frequency.'">'.WINDROS_FREQUENCY[$frequency].'</option>';
                    }
                }
                
                ?>
            </select>
            <p>
                <?php
                    echo __('You can adjust, pause or cancel at any time', 'windros-subscription');
                ?>
            </p>
        </div>
    
        <?php 
        echo ob_get_clean();
    }

    public function add_short_description_to_product_loop(){
        global $product;

        $enable_subscription_fields = false;
        $enable_subscription = get_post_meta( $product->get_id(), '_enable_subscription', true ); // Get the data - Checbox 1
        if( ! empty( $enable_subscription ) && $enable_subscription == 'yes' ){
            $enable_subscription_fields = true;
        }
        if(!$enable_subscription_fields){
            return;
        }
        
        if($product->get_short_description() !== null && !empty($product->get_short_description())){
            echo '<p class="product-short-desc">'.$product->get_short_description().'</p>';
        }
    }


    public function add_ajax_subscription_custom_class( $wp_parse_args, $product ) {
        
        if ( ! is_product() ) {
            
            $enable_subscription = get_post_meta( $product->get_id(), '_enable_subscription', true ); // Get the data - Checbox 1
            if( ! empty( $enable_subscription ) && $enable_subscription == 'yes' ){
                $wp_parse_args['class'] = $wp_parse_args['class'].'_subscription';
            }
        }
        return $wp_parse_args;
    }

    // Add custom script to include select field value in AJAX request
    
    public function add_custom_ajax_script() {
        // global $product;
    
        // $enable_subscription_fields = false;
        // $enable_subscription = get_post_meta( $product->get_id(), '_enable_subscription', true ); // Get the data - Checbox 1
        // if( ! empty( $enable_subscription ) && $enable_subscription == 'yes' ){
        //     $enable_subscription_fields = true;
        // }
        // if(!$enable_subscription_fields){
        //     return;
        // }

        if (  !is_product() ) {  // Only on subscription loop
            ?>
            <script type="text/javascript">
                jQuery(function($) {
                    // Trigger when Add to Cart button is clicked

                    $('.ajax_add_to_cart_subscription').each(function(){
                        var productLink = $(this).closest('.product').find('.woocommerce-LoopProduct-link').attr('href');
                        $(this).attr('href', productLink);
                    });
                    
                    // $(document).on('click', '.ajax_add_to_cart_subscription', function(e) {
                    //     e.preventDefault();
                        
                    //     var $button = $(this);
                    //     var product_id = $button.data('product_id');
                    //     var select_value = parseInt( $button.closest('.product').find('select#subscription-schedule').val());

                    //     if (!select_value) {
                    //         alert('Please select schedule.');
                    //         return false; // Prevent adding to cart if no option is selected
                    //     }

                    //     // AJAX call to WooCommerce to add the item to the cart
                    //     $.ajax({
                    //         type: 'POST',
                    //         url: wc_add_to_cart_params.ajax_url, // WooCommerce AJAX URL
                    //         data: {
                    //             action: 'custom_add_to_cart_subscription',
                    //             product_id: product_id,
                    //             quantity: 1,
                    //             subscription_schedule: select_value // Add custom select value
                    //         },
                    //         beforeSend: function(response) {
                    //             $button.removeClass('added').addClass('loading');
                    //         },
                    //         success: function(response) {
                    //             $button.removeClass('loading').addClass('added');
                    //             // Trigger WooCommerce notices update
                    //             $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);
                    //             $('.wc-block-mini-cart__button').trigger('click');
                    //         }
                    //     });
                    // });
                });
            </script>
            <?php
        }
    }


    public function custom_ajax_add_to_cart() {
        $product_id = absint( $_POST['product_id'] );
        $quantity = empty( $_POST['quantity'] ) ? 1 : wc_stock_amount( $_POST['quantity'] );
        $custom_select_value = sanitize_text_field( $_POST['subscription_schedule'] );
    
        $cart_item_data = array();
        if ( ! empty( $custom_select_value ) ) {
            $cart_item_data['subscription-schedule'] = $custom_select_value;
        }
        
        $cart = WC()->cart; // The WC_Cart Object
        

        if ( ! $cart->is_empty() ) {
            // Loop through cart items
            foreach( $cart->get_cart() as $cart_item_key => $cart_item ) {
                // If the cart item is not the current defined product ID
                
                if( isset($cart_item['subscription-schedule']) ) {
                    wc_add_notice( __( 'You cannot purchase multiple subscriptions at the same time.', 'windros-subscription' ), 'error' );
                    wp_send_json_error();
                } 
                
                
            }
        }
        

        // Add product to cart
        $new_cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, 0, array(), $cart_item_data );
    
        if ( $new_cart_item_key ) {
            // Get the updated cart fragments to refresh the cart in the front-end
            
            

            // When cart is not empty 
            // if ( ! $cart->is_empty() ) {
            //     // Loop through cart items
            //     foreach( $cart->get_cart() as $cart_item_key => $cart_item ) {
            //         // If the cart item is not the current defined product ID
            //         if( $product_id == $cart_item['product_id'] ) {
            //             $cart->set_quantity( $new_cart_item_key, 1 ); // Set the quantity to 1
            //         } 
                    
            //     }
            // }
            $data = array(
                'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array() ),
                'cart_hash' => WC()->cart->get_cart_hash(),
            );
            wp_send_json( $data );
        } else {
            wp_send_json_error();
        }
    
        wp_die();
    }

    
}
