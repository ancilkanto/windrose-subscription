<?php
namespace WindroseSubscription\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WPML Integration Helper for Windrose Subscription Emails
 * 
 * This class provides helper methods to enable WPML language switcher
 * for subscription email subject and heading fields.
 */
class WindroseWPMLIntegration {
    
    /**
     * Register WPML strings for an email class
     * 
     * @param string $email_id The email ID (e.g., 'windrose_subscription_activated')
     * @param string $default_subject Default subject text
     * @param string $default_heading Default heading text
     * @param string $default_additional_content Default additional content
     */
    public static function register_email_strings($email_id, $default_subject, $default_heading, $default_additional_content = '') {
        if (function_exists('icl_register_string')) {
            // Register subject for WPML translation
            icl_register_string(
                'windros-subscription',
                $email_id . '_subject',
                $default_subject,
                false
            );
            
            // Register heading for WPML translation
            icl_register_string(
                'windros-subscription',
                $email_id . '_heading',
                $default_heading,
                false
            );
            
            // Register additional content for WPML translation
            if (!empty($default_additional_content)) {
                icl_register_string(
                    'windros-subscription',
                    $email_id . '_additional_content',
                    $default_additional_content,
                    false
                );
            }
        }
    }
    
    /**
     * Get translated string from WPML
     * 
     * @param string $email_id The email ID
     * @param string $field_type The field type (subject, heading, additional_content)
     * @param string $original_value The original value
     * @return string The translated value or original if no translation
     */
    public static function get_translated_string($email_id, $field_type, $original_value) {
        if (function_exists('icl_t')) {
            $translated_value = icl_t('windros-subscription', $email_id . '_' . $field_type, $original_value);
            return $translated_value !== $original_value ? $translated_value : $original_value;
        }
        return $original_value;
    }
    
    /**
     * Add WPML custom attributes to form fields
     * 
     * @param string $email_id The email ID
     * @param string $field_type The field type
     * @return array Custom attributes array
     */
    public static function get_wpml_attributes($email_id, $field_type) {
        return array(
            'data-wpml-string' => $email_id . '_' . $field_type,
            'data-wpml-context' => 'windros-subscription'
        );
    }
    
    /**
     * Generate WPML language switcher JavaScript
     * 
     * @return string JavaScript code for WPML language switcher
     */
    public static function get_wpml_script() {
        return "
        jQuery(document).ready(function($) {
            // Add WPML language switcher to translatable fields
            $('input[data-wpml-string], textarea[data-wpml-string]').each(function() {
                var \$field = $(this);
                var stringName = \$field.attr('data-wpml-string');
                var context = \$field.attr('data-wpml-context');
                
                if (typeof icl_ajxurl !== 'undefined' && stringName && context) {
                    // Add WPML translation button
                    var \$wpmlButton = $('<a href=\"#\" class=\"button wpml-translate-button\" style=\"margin-left: 10px;\">' + 
                        '<span class=\"dashicons dashicons-translation\" style=\"margin-top: 3px;\"></span> ' + 
                        '" . esc_js(__("Translate", "sitepress")) . "' + 
                        '</a>');
                    
                    \$field.after(\$wpmlButton);
                    
                    \$wpmlButton.on('click', function(e) {
                        e.preventDefault();
                        
                        // Open WPML string translation interface
                        var url = icl_ajxurl + '?icl_ajx_action=icl_st_popup&icl_st_item_id=' + 
                                 encodeURIComponent(context + '||' + stringName) + 
                                 '&target=' + encodeURIComponent(\$field.val());
                        
                        window.open(url, 'wpml_string_translation', 
                            'width=800,height=600,scrollbars=yes,resizable=yes');
                    });
                }
            });
        });
        ";
    }
    
    /**
     * Check if WPML is active
     * 
     * @return bool True if WPML is active
     */
    public static function is_wpml_active() {
        return function_exists('icl_register_string') && function_exists('icl_t');
    }
    
    /**
     * Get current language code
     * 
     * @return string Current language code
     */
    public static function get_current_language() {
        if (function_exists('ICL_LANGUAGE_CODE')) {
            return ICL_LANGUAGE_CODE;
        }
        return get_locale();
    }
    
    /**
     * Switch language for email sending
     * 
     * @param string $language_code Language code to switch to
     */
    public static function switch_language($language_code) {
        if (function_exists('switch_to_locale')) {
            switch_to_locale($language_code);
        }
        
        if (function_exists('icl_switch_language')) {
            icl_switch_language($language_code);
        }
    }
    
    /**
     * Restore original language
     */
    public static function restore_language() {
        if (function_exists('restore_previous_locale')) {
            restore_previous_locale();
        }
    }
} 