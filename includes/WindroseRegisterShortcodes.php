<?php
namespace WindroseSubscription\Includes; 
use WP_Query;

defined( 'WINDROS_INIT' ) || exit;      // Exit if accessed directly.


    class WindroseRegisterShortcodes {
        
        public function __construct() {
            add_shortcode('windros-subscription-products', [$this, 'render_subscription_products']);
            add_shortcode('windros-products', [$this, 'render_products']);
        }
        // function that runs when shortcode is called


        public function render_subscription_products($atts) { 

            // Attributes 
            $atts = shortcode_atts(
                array(
                    'columns'   => '4',
                    'limit'     => '20'
                ),
                $atts, 'windros-subscription-products'
            );
            global $woocommerce_loop;
            

            $woocommerce_loop['columns'] = $atts['columns'];

            $products = new WP_Query( array (
                'post_type'         => 'product',
                'post_status'       => 'publish',
                'posts_per_page'    => $atts['limit'],
                'meta_query'        => array(
                    'relation'  => 'AND',
                    array(
                        'key'       => '_enable_subscription',
                        'value'     => 'yes',
                        'compare'   => '='
                    )
                )
            ));

            ob_start();

            if ( $products->have_posts() ) { ?>

                <?php woocommerce_product_loop_start(); ?>

                    <?php while ( $products->have_posts() ) : $products->the_post(); ?>

                        <?php wc_get_template_part( 'content', 'product' ); ?>

                    <?php endwhile; // end of the loop. ?>

                <?php woocommerce_product_loop_end(); ?>

                <?php
            } else {
                do_action( "woocommerce_shortcode_products_loop_no_results", $atts );
                echo "<p>". __('There is no subscription products available.', 'windros-subscription') ."</p>";
            }

            woocommerce_reset_loop();
            wp_reset_postdata();

            return '<div class="row woocommerce windrose-subscription-loop columns-' . $atts['columns'] . '">' . ob_get_clean() . '</div>';
        
        
            
        }

        public function render_products($atts) {
            // Attributes 
            $atts = shortcode_atts(
                array(
                    'columns'   => '4',
                    'limit'     => '20'
                ),
                $atts, 'windros-products'
            );
            global $woocommerce_loop;
            

            $woocommerce_loop['columns'] = $atts['columns'];

            $products = new WP_Query( array (
                'post_type'         => 'product',
                'post_status'       => 'publish',
                'posts_per_page'    => $atts['limit'],
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_enable_subscription',
                        'value'   => 'yes',
                        'compare' => '!='
                    ),
                    array(
                        'key'     => '_enable_subscription',
                        'compare' => 'NOT EXISTS'
                    )
                )
            ));

            ob_start();

            if ( $products->have_posts() ) { ?>

                <?php woocommerce_product_loop_start(); ?>

                    <?php while ( $products->have_posts() ) : $products->the_post(); ?>

                        <?php wc_get_template_part( 'content', 'product' ); ?>

                    <?php endwhile; // end of the loop. ?>

                <?php woocommerce_product_loop_end(); ?>

                <?php
            } else {
                do_action( "woocommerce_shortcode_products_loop_no_results", $atts );
                echo "<p>". __('There is no products available.', 'windros-subscription') ."</p>";
            }

            woocommerce_reset_loop();
            wp_reset_postdata();

            return '<div class="row woocommerce windrose-product-loop columns-' . $atts['columns'] . '">' . ob_get_clean() . '</div>';
        
        
        }
    }


    



