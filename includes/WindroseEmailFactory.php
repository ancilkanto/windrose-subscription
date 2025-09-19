<?php
namespace WindroseSubscription\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    // Only exit if we're in a WordPress context
    if (function_exists('add_action')) {
        exit;
    }
}

/**
 * Factory class for creating Windrose email instances
 * Handles WC_Email dependency and ensures proper loading
 */
class WindroseEmailFactory {
    
    /**
     * Get customer email class names
     */
    public static function getCustomerEmailClasses() {
        return [
            'WindroseSubscriptionActivatedEmail',
            'WindroseSubscriptionOrderProcessedEmail',
            'WindroseSubscriptionOrderFailedEmail',
            'WindroseSubscriptionPausedEmail',
            'WindroseSubscriptionCancelledEmail',
            'WindroseSubscriptionSkippedEmail',
        ];
    }
    
    /**
     * Get admin email class names
     */
    public static function getAdminEmailClasses() {
        return [
            'WindroseSubscriptionAdminActivatedEmail',
            'WindroseSubscriptionAdminCancelledEmail',
            'WindroseSubscriptionAdminOrderFailedEmail',
            'WindroseSubscriptionAdminPausedEmail',
            'WindroseSubscriptionAdminSkippedEmail',
        ];
    }
    
    /**
     * Get all email class names
     */
    public static function getAllEmailClasses() {
        return array_merge(
            self::getCustomerEmailClasses(),
            self::getAdminEmailClasses()
        );
    }
    
    /**
     * Create email instance by class name
     * 
     * @param string $class_name
     * @return object|null
     */
    public static function createEmail($class_name) {
        // Ensure WooCommerce is loaded
        if (!class_exists('WC_Email')) {
            return null;
        }
        
        $full_class_name = 'WindroseSubscription\Includes\Emails\\' . $class_name;
        
        try {
            return new $full_class_name();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Check if email class can be instantiated
     * 
     * @param string $class_name
     * @return bool
     */
    public static function canCreateEmail($class_name) {
        return class_exists('WC_Email') && class_exists('WindroseSubscription\Includes\Emails\\' . $class_name);
    }
} 