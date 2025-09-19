<?php
namespace WindroseSubscription\Includes; 

defined( 'WINDROS_INIT' ) || exit;      // Exit if accessed directly.


    class WindroseModifyProductLoopData {

        public function __construct() {
            add_filter( 'woocommerce_get_price_html', [$this, 'subscription_price_suffix'], 100, 2 );

            add_action( 'woocommerce_product_query', [$this, 'windros_WC_exclude_subscription_products'] );  

            add_filter( 'woocommerce_product_add_to_cart_text', [$this, 'windros__WC_add_to_cart_button_text'], 10, 2);  
            
            // add_filter( 'woocommerce_product_single_add_to_cart_text', [$this, 'windros__WC_add_to_cart_button_text'], 10, 2);  

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
        


    }


