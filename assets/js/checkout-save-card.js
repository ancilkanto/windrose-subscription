/**
 * Windrose Subscription - Checkout Save Card Checkbox Handler
 * Handles automatic checking of save_card checkbox on WooCommerce Block Checkout
 * with support for Shadow DOM elements created by Paymob payment gateway
 */

(function($) {
    'use strict';

    // Function to search through Shadow DOM
    function searchShadowDOM(root, selectors) {
        // Search in current root
        for (let i = 0; i < selectors.length; i++) {
            const element = root.querySelector(selectors[i]);
            if (element) {
                return element;
            }
        }
        
        // Search for shadow roots
        const shadowHosts = root.querySelectorAll('*');
        for (let j = 0; j < shadowHosts.length; j++) {
            const host = shadowHosts[j];
            if (host.shadowRoot) {
                const result = searchShadowDOM(host.shadowRoot, selectors);
                if (result) {
                    return result;
                }
            }
        }
        
        return null;
    }

    // Enhanced function to find checkbox with better shadow DOM support
    function findCheckboxInShadowDOM() {
        const selectors = [
            '#save_card',
            '#save-card', 
            'input[name="save_card"]',
            'input[name="save-card"]',
            'input[type="checkbox"][name*="save"]',
            '.wc-block-components-checkout-payment-method input[type="checkbox"]',
            '.wc-block-components-checkout-payment-method input[name*="save"]',
            '.wc-block-components-checkout-payment-method input[id*="save"]'
        ];
        
        // Method 1: Try regular DOM first
        for (let i = 0; i < selectors.length; i++) {
            const element = document.querySelector(selectors[i]);
            if (element) {
                return element;
            }
        }
        
        // Method 2: Search through all shadow roots more thoroughly
        const allElements = document.querySelectorAll('*');
        for (let i = 0; i < allElements.length; i++) {
            const element = allElements[i];
            
            // Check if this element has a shadow root
            if (element.shadowRoot) {
                // Search inside this shadow root
                for (let j = 0; j < selectors.length; j++) {
                    const shadowElement = element.shadowRoot.querySelector(selectors[j]);
                    if (shadowElement) {
                        return shadowElement;
                    }
                }
                
                // Recursively search deeper shadow roots
                const deeperResult = searchShadowDOM(element.shadowRoot, selectors);
                if (deeperResult) {
                    return deeperResult;
                }
            }
        }
        
        // Method 3: Look for any element with shadow root that might contain our checkbox
        const shadowRoots = [];
        const walker = document.createTreeWalker(
            document.body,
            NodeFilter.SHOW_ELEMENT,
            {
                acceptNode: function(node) {
                    if (node.shadowRoot) {
                        shadowRoots.push(node.shadowRoot);
                    }
                    return NodeFilter.FILTER_ACCEPT;
                }
            }
        );
        
        // Walk through the DOM to find all shadow roots
        while (walker.nextNode()) {}
        
        // Search in each shadow root
        for (let i = 0; i < shadowRoots.length; i++) {
            for (let j = 0; j < selectors.length; j++) {
                const element = shadowRoots[i].querySelector(selectors[j]);
                if (element) {
                    return element;
                }
            }
        }
        
        return null;
    }

    

    // Start intercepting Paymob AJAX calls immediately
    interceptPaymobAjax();
    

    // Function to intercept Paymob AJAX calls
    function interceptPaymobAjax() {
        // Store the original XMLHttpRequest open method
        const originalOpen = XMLHttpRequest.prototype.open;
        const originalSend = XMLHttpRequest.prototype.send;
        
        // Override the open method to intercept requests
        XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
            // Store the URL for later use
            this._url = url;
            return originalOpen.apply(this, arguments);
        };
        
        // Override the send method to intercept responses
        XMLHttpRequest.prototype.send = function(data) {
            // Check if this is a Paymob update_pixel_data request by checking the data
            if (data && typeof data === 'string' && data.includes('action=update_pixel_data')) {
                
                // Store the original onreadystatechange
                const originalOnReadyStateChange = this.onreadystatechange;
                
                this.onreadystatechange = function() {
                    // Call the original handler if it exists
                    if (originalOnReadyStateChange) {
                        originalOnReadyStateChange.apply(this, arguments);
                    }
                    
                    // Check if the request is complete and successful
                    if (this.readyState === 4 && this.status === 200) {
                        // Paymob update_pixel_data request completed successfully
                        setTimeout(function() {
                            
                            checkSaveCardCheckbox();
                        }, 1000); // Wait 500ms for DOM to update
                    }
                };
            }
            
            return originalSend.apply(this, arguments);
        };
        
    }

    

    // Function to check the save-card checkbox
    function checkSaveCardCheckbox() {
        // Try multiple possible selectors for WooCommerce Block Checkout
        const selectors = [
            '#save_card',
            '#save-card', 
            'input[name="save_card"]',
            'input[name="save-card"]',
            'input[type="checkbox"][name*="save"]',
            '.wc-block-components-checkout-payment-method input[type="checkbox"]',
            '.wc-block-components-checkout-payment-method input[name*="save"]',
            '.wc-block-components-checkout-payment-method input[id*="save"]'
        ];
        
        let saveCardCheckbox = null;
        
        // Try each selector in regular DOM first
        for (let i = 0; i < selectors.length; i++) {
            const element = $(selectors[i]);
            if (element.length > 0) {
                saveCardCheckbox = element;
                break;
            }
        }
        
        // If not found in regular DOM, search through Shadow DOM
        if (!saveCardCheckbox) {
            const shadowResult = findCheckboxInShadowDOM();
            if (shadowResult) {
                saveCardCheckbox = $(shadowResult);
            }
        }
        
        
        if (saveCardCheckbox && saveCardCheckbox.length > 0) {
            // Check if checkbox is already checked
            if (saveCardCheckbox.prop('checked')) {
                return;
            }
            // Element exists, check it using multiple methods to ensure visual update
            
            // Method 1: Set checked property
            saveCardCheckbox.prop('checked', true);
                    
            saveCardCheckbox.trigger('click');
            
            
            // Method 4: Simulate actual click for web components
            if (saveCardCheckbox[0] && typeof saveCardCheckbox[0].click === 'function') {
                try {            
                    saveCardCheckbox[0].click();
                    saveCardCheckbox[0].trigger('click');
                } catch (e) {
                    // Direct click failed, continue with other methods
                }
            }
            
            // Method 5: Dispatch native events for better compatibility
            if (saveCardCheckbox[0]) {
                try {
                    const element = saveCardCheckbox[0];
                    
                    // Create and dispatch change event
                    const changeEvent = new Event('change', { bubbles: true, cancelable: true });
                    element.dispatchEvent(changeEvent);
                    
                    // Create and dispatch input event
                    const inputEvent = new Event('input', { bubbles: true, cancelable: true });
                    element.dispatchEvent(inputEvent);
                    
                    // Create and dispatch click event
                    const clickEvent = new Event('click', { bubbles: true, cancelable: true });
                    element.dispatchEvent(clickEvent);

                    element.trigger('click');
                    
                    
                } catch (e) {
                    // Native event dispatch failed, continue with other methods
                }
            }

            const checkboxElement = saveCardCheckbox[0];
            if (checkboxElement) {
                const nearbyHiddenDivs = checkboxElement.closest('div')?.querySelectorAll('div.hidden');
                if (nearbyHiddenDivs && nearbyHiddenDivs.length > 0) {
                    // Remove hidden class from all nearby hidden divs
                    nearbyHiddenDivs.forEach(div => {
                        div.classList.remove('hidden');
                    });
                }
            }
            
            // No continuous monitoring - let user have full control after initial check
            return true;
        }
        
        return false;
    }

   

})(jQuery);
