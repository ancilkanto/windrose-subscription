/**
 * Windrose Subscription - Related Products Handler
 * Prevents AJAX add-to-cart behavior for subscription products in related products section
 */

(function($) {
    'use strict';
    

    $(document).ready(function() {
        // Function to check if a product is a subscription product
        function isSubscriptionProduct(productId) {
            // This will be set by PHP when the page loads
            return window.windroseSubscriptionProducts && window.windroseSubscriptionProducts.includes(productId);
        }

        // Function to check if we're in related products section
        function isRelatedProductsSection() {
            isRelatedProductsSection = false;
            if($('.related.products').length > 0){
                isRelatedProductsSection = true;
            }

            if($('.wc-block-grid__products').length > 0){
                isRelatedProductsSection = true;
            }

            return isRelatedProductsSection;
        }

        // Handle click events on add-to-cart buttons in related products
        $(document).on('click', '.related.products .add_to_cart_button, .related.products .ajax_add_to_cart, .wc-block-grid__products .add_to_cart_button, .wc-block-grid__products .ajax_add_to_cart', function(e) {
            var $button = $(this);
            var productId = $button.data('product_id');
            
            // Check if this is a subscription product and we're in related products
            if (productId && isSubscriptionProduct(productId) && isRelatedProductsSection()) {
                // Prevent the default AJAX behavior
                e.preventDefault();
                e.stopPropagation();
                
                // Get the product URL and redirect
                var productUrl = $button.attr('href');
                if (productUrl) {
                    window.location.href = productUrl;
                }
            }
        });

        // Also handle the case where WooCommerce might add event listeners after page load
        $(document).on('click', '.related.products a[href*="add-to-cart"], .wc-block-grid__products a[href*="add-to-cart"]', function(e) {
            var $link = $(this);
            var href = $link.attr('href');
            
            // Extract product ID from href if it contains add-to-cart
            var productIdMatch = href.match(/add-to-cart=(\d+)/);
            if (productIdMatch) {
                var productId = parseInt(productIdMatch[1]);
                
                // Check if this is a subscription product and we're in related products
                if (isSubscriptionProduct(productId) && isRelatedProductsSection()) {
                    // Prevent the default behavior
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Redirect to product page
                    var productUrl = href.replace(/[?&]add-to-cart=\d+.*$/, '');
                    if (productUrl === href) {
                        // If no clean URL found, construct it
                        productUrl = window.location.origin + '/?post_type=product&p=' + productId;
                    }
                    window.location.href = productUrl;
                }
            }
        });

        setTimeout(function(){
            window.windroseSubscriptionProducts.forEach(productId => {
                
                
                $('a[data-product_id="'+productId+'"]').removeClass('ajax_add_to_cart');
            });
        }, 1000);

        $(window).on('load', function(){
            
        });
        
    });

})(jQuery);
