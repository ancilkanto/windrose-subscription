# WPML Integration for Windrose Subscription Emails

This document explains how to enable WPML language switcher for the subject and heading fields of subscription emails in the Windrose Subscription plugin.

## Overview

The WPML integration allows you to translate the subject, heading, and additional content fields of subscription emails directly from the WooCommerce email settings page. This provides a seamless multilingual experience for your subscription emails.

## Features

- **Language Switcher Buttons**: Translation buttons appear next to subject, heading, and additional content fields
- **WPML String Registration**: Automatically registers email strings with WPML for translation
- **Real-time Translation**: Uses WPML's translation system to display translated content
- **Helper Class**: Provides reusable methods for easy implementation across all email types

## Implementation

### 1. Files Modified/Created

#### New Files:
- `includes/WindroseWPMLIntegration.php` - Helper class for WPML integration
- `WPML_INTEGRATION_README.md` - This documentation file

#### Modified Files:
- `includes/emails/WindroseSubscriptionActivatedEmail.php` - Example implementation
- `includes/emails/WindroseSubscriptionOrderProcessedEmail.php` - Example implementation
- `windrose-main.php` - Added WPML integration check

### 2. How It Works

#### String Registration
When an email class is instantiated, it automatically registers its strings with WPML:

```php
private function register_wpml_strings() {
    WindroseWPMLIntegration::register_email_strings(
        $this->id,                                    // Email ID
        $this->get_default_subject(),                 // Default subject
        $this->get_default_heading(),                 // Default heading
        $this->get_default_additional_content()       // Default additional content
    );
}
```

#### Form Fields Enhancement
The form fields are enhanced with WPML attributes:

```php
'subject' => array(
    'title' => __( 'Subject', 'woocommerce' ),
    'type' => 'text',
    'custom_attributes' => WindroseWPMLIntegration::get_wpml_attributes($this->id, 'subject'),
),
```

#### Translation Retrieval
When sending emails, the system retrieves translated content:

```php
public function get_subject() {
    $subject = $this->get_option_or_transient( 'subject', $this->get_default_subject() );
    $translated_subject = WindroseWPMLIntegration::get_translated_string($this->id, 'subject', $subject);
    
    return apply_filters( 'woocommerce_email_subject_' . $this->id, $this->format_string( $translated_subject ), $this->object, $this );
}
```

## Usage

### For Existing Email Classes

To add WPML support to an existing email class, follow these steps:

1. **Add the necessary imports**:
```php
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WindroseSubscription\Includes\WindroseWPMLIntegration;
```

2. **Register strings in constructor**:
```php
public function __construct() {
    // ... existing constructor code ...
    
    // Register WPML strings for translation
    $this->register_wpml_strings();
}

private function register_wpml_strings() {
    WindroseWPMLIntegration::register_email_strings(
        $this->id,
        $this->get_default_subject(),
        $this->get_default_heading(),
        $this->get_default_additional_content()
    );
}
```

3. **Override init_form_fields method**:
```php
public function init_form_fields() {
    /* translators: %s: list of placeholders */
    $placeholder_text = sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );
    
    $this->form_fields = array(
        'enabled' => array(
            'title' => __( 'Enable/Disable', 'woocommerce' ),
            'type' => 'checkbox',
            'label' => __( 'Enable this email notification', 'woocommerce' ),
            'default' => 'yes',
        ),
        'subject' => array(
            'title' => __( 'Subject', 'woocommerce' ),
            'type' => 'text',
            'desc_tip' => true,
            'description' => $placeholder_text,
            'placeholder' => $this->get_default_subject(),
            'default' => '',
            'custom_attributes' => WindroseWPMLIntegration::get_wpml_attributes($this->id, 'subject'),
        ),
        'heading' => array(
            'title' => __( 'Email heading', 'woocommerce' ),
            'type' => 'text',
            'desc_tip' => true,
            'description' => $placeholder_text,
            'placeholder' => $this->get_default_heading(),
            'default' => '',
            'custom_attributes' => WindroseWPMLIntegration::get_wpml_attributes($this->id, 'heading'),
        ),
        'additional_content' => array(
            'title' => __( 'Additional content', 'woocommerce' ),
            'description' => __( 'Text to appear below the main email content.', 'woocommerce' ) . ' ' . $placeholder_text,
            'css' => 'width:400px; height: 75px;',
            'placeholder' => __( 'N/A', 'woocommerce' ),
            'type' => 'textarea',
            'default' => $this->get_default_additional_content(),
            'desc_tip' => true,
            'custom_attributes' => WindroseWPMLIntegration::get_wpml_attributes($this->id, 'additional_content'),
        ),
        'email_type' => array(
            'title' => __( 'Email type', 'woocommerce' ),
            'type' => 'select',
            'description' => __( 'Choose which format of email to send.', 'woocommerce' ),
            'default' => 'html',
            'class' => 'email_type wc-enhanced-select',
            'options' => $this->get_email_type_options(),
            'desc_tip' => true,
        ),
    );
    
    if ( FeaturesUtil::feature_is_enabled( 'email_improvements' ) ) {
        $this->form_fields['cc'] = $this->get_cc_field();
        $this->form_fields['bcc'] = $this->get_bcc_field();
    }
}
```

4. **Override admin_options method**:
```php
public function admin_options() {
    // Do admin actions.
    $this->admin_actions();
    ?>
    <?php wc_back_header( $this->get_title(), __( 'Return to emails', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=email' ) ); ?>

    <?php echo wp_autop( wp_kses_post( $this->get_description() ) ); ?>

    <?php do_action( 'woocommerce_email_settings_before', $this ); ?>

    <table class="form-table">
        <?php $this->generate_settings_html(); ?>
    </table>

    <?php do_action( 'woocommerce_email_settings_after', $this ); ?>

    <script type="text/javascript">
    <?php echo WindroseWPMLIntegration::get_wpml_script(); ?>
    </script>

    <?php
    // ... rest of the admin_options method (template management) ...
}
```

5. **Override get methods to use translations**:
```php
public function get_subject() {
    $subject = $this->get_option_or_transient( 'subject', $this->get_default_subject() );
    $translated_subject = WindroseWPMLIntegration::get_translated_string($this->id, 'subject', $subject);
    
    return apply_filters( 'woocommerce_email_subject_' . $this->id, $this->format_string( $translated_subject ), $this->object, $this );
}

public function get_heading() {
    $heading = $this->get_option_or_transient( 'heading', $this->get_default_heading() );
    $translated_heading = WindroseWPMLIntegration::get_translated_string($this->id, 'heading', $heading);
    
    return apply_filters( 'woocommerce_email_heading_' . $this->id, $this->format_string( $translated_heading ), $this->object, $this );
}

public function get_additional_content() {
    $additional_content = $this->get_option_or_transient( 'additional_content', $this->get_default_additional_content() );
    $translated_content = WindroseWPMLIntegration::get_translated_string($this->id, 'additional_content', $additional_content);
    
    return apply_filters( 'woocommerce_email_additional_content_' . $this->id, $this->format_string( $translated_content ), $this->object, $this );
}
```

### For New Email Classes

When creating a new email class, simply follow the same pattern as the existing examples. The helper class makes it easy to implement WPML support consistently across all email types.

## Admin Interface

### Email Settings Page

When you visit **WooCommerce > Settings > Emails** and click on a subscription email, you'll see:

1. **Translation Buttons**: Next to subject, heading, and additional content fields
2. **WPML Integration**: Seamless integration with WPML's string translation system
3. **Language Context**: Proper context for WPML string management

### Translation Workflow

1. Navigate to **WooCommerce > Settings > Emails**
2. Click on a subscription email (e.g., "Subscription Activated")
3. Click the "Translate" button next to any field
4. WPML's string translation interface opens
5. Add translations for your target languages
6. Save translations
7. The translated content will be used when sending emails

## Technical Details

### WPML String Context

Strings are registered with the following context:
- **Context**: `windros-subscription`
- **Name Pattern**: `{email_id}_{field_type}` (e.g., `windrose_subscription_activated_subject`)

### JavaScript Integration

The integration includes JavaScript that:
- Detects translatable fields using `data-wpml-string` attributes
- Adds translation buttons dynamically
- Opens WPML's string translation interface
- Handles the translation workflow seamlessly

### Language Detection

The system automatically:
- Detects the current language context
- Switches language when sending emails
- Restores the original language after sending

## Troubleshooting

### WPML Not Detected

If WPML is not detected:
1. Ensure WPML is properly installed and activated
2. Check that the `icl_register_string` and `icl_t` functions are available
3. Verify WPML String Translation module is active

### Translation Buttons Not Appearing

If translation buttons don't appear:
1. Check browser console for JavaScript errors
2. Ensure WPML's `icl_ajxurl` variable is defined
3. Verify the email class has proper WPML attributes

### Translations Not Working

If translations aren't working:
1. Check WPML String Translation for registered strings
2. Verify translations are saved in WPML
3. Check the string context and name in WPML settings

## Support

For issues related to this WPML integration:
1. Check this documentation first
2. Review the example implementations
3. Verify WPML configuration
4. Check WordPress and WooCommerce compatibility

## Compatibility

- **WPML**: 4.0+
- **WooCommerce**: 5.0+
- **WordPress**: 5.0+
- **PHP**: 7.4+

## Changelog

### Version 1.0.0
- Initial implementation
- WPML integration helper class
- Example implementations for activated and order processed emails
- JavaScript integration for translation buttons
- Comprehensive documentation 