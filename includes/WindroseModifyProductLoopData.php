<?php
namespace WindroseSubscription\Includes; 

defined( 'WINDROS_INIT' ) || exit;      // Exit if accessed directly.


    class WindroseModifyProductLoopData {

    public function __construct() {
        add_filter( 'woocommerce_get_price_html', [$this, 'subscription_price_suffix'], 100, 2 );

        add_action( 'woocommerce_product_query', [$this, 'windros_WC_exclude_subscription_products'] );  

        add_filter( 'woocommerce_product_add_to_cart_text', [$this, 'windros__WC_add_to_cart_button_text'], 10, 2);  
        
        add_filter( 'woocommerce_product_single_add_to_cart_text', [$this, 'windros__WC_add_to_cart_button_text'], 10, 2);  

        // Modify add-to-cart link for subscription products in related products section
        add_filter( 'woocommerce_loop_add_to_cart_link', [$this, 'modify_subscription_add_to_cart_link'], 100, 3 );
        add_filter( 'woocommerce_product_add_to_cart_url', [$this, 'modify_subscription_add_to_cart_url'], 10, 2 );

        // Enqueue JavaScript for related products handling
        add_action( 'wp_enqueue_scripts', [$this, 'enqueue_related_products_script'] );

    }

        public function subscription_price_suffix( $price_html, $product ){
            $enable_subscription = get_post_meta( $product->get_id(), '_enable_subscription', true ); // Get the data - Checbox 1
            if( ! empty( $enable_subscription ) && $enable_subscription == 'yes' ){                        
                $price_html .= ' ' .  __('/ Week', 'windros-subscription');
            }
            return $price_html;
        }



        // Change add to cart text on product archives page
        
        public function windros__WC_add_to_cart_button_text($text, $product) {
            $enable_subscription = get_post_meta( $product->get_id(), '_enable_subscription', true ); // Get the data - Checbox 1
            if( ! empty( $enable_subscription ) && $enable_subscription == 'yes' ){  
                return __( 'Subscribe Now', 'windros-subscription' );
            }else{
                return $text;
            }
        }


        /**
         * Exclude subscription products from main WooCommerce Query on the shop page
         */
        public function windros_WC_exclude_subscription_products( $query ) {

            $meta_query = (array) $query->get( 'meta_query' );

            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key'       => '_enable_subscription',
                    'value'     => 'yes',
                    'compare'   => 'NOT LIKE'
                ),
                array(
                    'key'       => '_enable_subscription',
                    'compare'   => 'NOT EXISTS'
                )
            );


            $query->set( 'meta_query', $meta_query );

        }

        /**
         * Modify add-to-cart link for subscription products in related products section
         * Redirects to product detail page instead of adding to cart directly
         */
        public function modify_subscription_add_to_cart_link( $link, $product, $args ) {
            // Check if this is a subscription product
            $enable_subscription = get_post_meta( $product->get_id(), '_enable_subscription', true );
            
            if ( ! empty( $enable_subscription ) && $enable_subscription == 'yes' ) {
                // Check if we're in the related products section
                
                // Change the link to go to the product detail page instead of adding to cart
                $product_url = get_permalink( $product->get_id() );
                
                // Remove AJAX classes to prevent AJAX behavior
                $class = isset( $args['class'] ) ? $args['class'] : 'button';
                $class = str_replace( 'ajax_add_to_cart', '', $class );
                $class = str_replace( 'add_to_cart_button', '', $class );
                $class = trim( $class );
                
                // Remove AJAX-related attributes
                $attributes = isset( $args['attributes'] ) ? $args['attributes'] : array();
                unset( $attributes['data-product_id'] );
                unset( $attributes['data-product_sku'] );
                unset( $attributes['data-success_message'] );
                
                // Extract the existing attributes and modify the href
                $aria_describedby = isset( $args['aria-describedby_text'] ) ? sprintf( 'aria-describedby="woocommerce_loop_add_to_cart_link_describedby_%s"', esc_attr( $product->get_id() ) ) : '';
                
                $link = sprintf(
                    '<a href="%s" %s data-quantity="%s" class="%s" %s>%s</a>',
                    esc_url( $product_url ),
                    $aria_describedby,
                    esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
                    esc_attr( $class ),
                    ! empty( $attributes ) ? wc_implode_html_attributes( $attributes ) : '',
                    esc_html( $product->add_to_cart_text() )
                );
                

                
                
            }
            
            return $link;
        }

        public function modify_subscription_add_to_cart_url( $url, $product ) {
            $enable_subscription = get_post_meta( $product->get_id(), '_enable_subscription', true );
            if ( ! empty( $enable_subscription ) && $enable_subscription == 'yes' ) {
                return get_permalink( $product->get_id() );
            }
            return $url;
        }

        /**
         * Enqueue JavaScript for related products handling
         */
        public function enqueue_related_products_script() {
            // Only enqueue on single product pages where related products are shown
            if ( is_product() || is_cart() ) {
                wp_enqueue_script(
                    'windrose-related-products-subscription',
                    plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/related-products-subscription.js',
                    array( 'jquery' ),
                    '1.0.0',
                    true
                );

                // Get all subscription product IDs
                $subscription_products = get_posts( array(
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'meta_query' => array(
                        array(
                            'key' => '_enable_subscription',
                            'value' => 'yes',
                            'compare' => '='
                        )
                    )
                ) );

                // Pass subscription product IDs to JavaScript
                wp_localize_script( 'windrose-related-products-subscription', 'windroseSubscriptionProducts', $subscription_products );
                
            }
        }


    }


