# Bilingual Email Template for Windrose Subscription

## Overview
The Windrose Subscription plugin now supports bilingual email templates with English and Arabic text for ALL customer-facing emails. The Arabic text is displayed first (right-aligned), and the English text is displayed second (left-aligned) with proper RTL (right-to-left) direction support.

## Features
- **Bilingual Content**: Both English and Arabic versions of email content
- **Sequential Layout**: Arabic section first (right-aligned), English section second (left-aligned)
- **Clear Separation**: Visual divider between the two language sections
- **RTL Support**: Arabic text displays with proper right-to-left direction
- **Responsive Design**: Mobile-friendly layout that maintains clear separation
- **Customizable**: Arabic content can be edited in WooCommerce email settings

## Setup

### 1. Email Settings
1. Go to **WooCommerce > Settings > Emails**
2. Click on any of the following customer emails:
   - **Subscription Activated** email
   - **Subscription Cancelled** email
   - **Subscription Order Failed** email
   - **Subscription Order Processed** email
   - **Subscription Paused** email
   - **Subscription Skipped** email
3. You'll see new fields:
   - **Email heading**: English version (existing)
   - **Email heading (Arabic)**: Arabic version (new)
   - **Subject**: English version (existing)

### 2. Arabic Content
Fill in the Arabic versions of your email content:
- **Email heading (Arabic)**: Arabic version of the email heading
- The body content is automatically translated in the template

## Template Structure

### HTML Email Template
The HTML email template (`templates/emails/windrose-subscription-activated.php`) includes:
- **Bilingual Email Header**: Both English and Arabic headings displayed prominently
- **Arabic Section First**: Complete Arabic content with right-aligned text
- **Visual Divider**: Clear border line separating the two sections
- **English Section Second**: Complete English content with left-aligned text
- Each section contains: greeting, subscription details, and closing message

### Plain Text Template
The plain text template (`templates/emails/plain/windrose-subscription-activated.php`) includes:
- **Arabic Section First**: Complete Arabic content
- Clear divider line with "--- English Version ---"
- **English Section Second**: Complete English content below
- Maintains readability in plain text format

### CSS Styling
The CSS file (`assets/css/bilingual-email.css`) provides:
- Sequential layout with Arabic section first, English section second
- Clear visual separators with borders and spacing
- Proper RTL support for Arabic text
- Responsive design for mobile devices
- Consistent spacing and typography
- Subscription-specific styling for detail rows

## Customization

### Adding New Bilingual Content
To add new bilingual content:

1. **In the email class** (`WindroseSubscriptionActivatedEmail.php`):
   ```php
   'new_field_arabic' => array(
       'title' => __('New Field (Arabic)', 'windros-subscription'),
       'type' => 'text',
       'description' => __('Arabic version', 'windros-subscription'),
       'default' => '',
   ),
   ```

2. **In the template**:
   ```php
   <div class="bilingual-content">
       <div class="english-content">
           <p><?php esc_html_e('English text', 'windros-subscription'); ?></p>
       </div>
       <div class="arabic-content">
           <p><?php esc_html_e('Arabic text', 'windros-subscription'); ?></p>
       </div>
   </div>
   ```

### Styling Modifications
Edit `assets/css/bilingual-email.css` to customize:
- Font sizes and families
- Spacing and margins
- Colors and borders
- Responsive breakpoints
- Subscription detail styling

## Language Support

### Arabic Translations
The plugin includes Arabic translations for:
- Email headings and subjects
- Subscription detail labels
- Greeting messages
- Content paragraphs
- Closing messages

### Adding More Languages
To add support for additional languages:
1. Create new `.po` files in the `languages/` directory
2. Translate the strings
3. Compile to `.mo` files using `msgfmt`
4. Update the template to include the new language

## Technical Details

### CSS Classes Used
- `.arabic-section`: Container for Arabic content with RTL direction
- `.english-section`: Container for English content with LTR direction
- `.subscription-details`: Container for subscription information
- `.arabic-details` / `.english-details`: Language-specific detail containers
- `.detail-row`: Individual detail row styling
- `.detail-label` / `.detail-value`: Label and value styling

### Responsive Behavior
- **Desktop**: Sequential layout with Arabic section first, English section second
- **Mobile**: Maintains sequential layout with centered text alignment for better readability
- **Breakpoint**: 600px width

### Browser Compatibility
- Modern browsers with CSS Flexbox support
- RTL support for Arabic text
- Fallback to standard layout for older browsers

## Troubleshooting

### Common Issues
1. **Arabic text not displaying properly**: Ensure the CSS file is loading correctly
2. **Layout broken on mobile**: Check responsive CSS rules
3. **RTL not working**: Verify the `direction: rtl` CSS property
4. **Translations not showing**: Check if the .mo file is compiled and in the correct location

### Debug Mode
Enable WordPress debug mode to see any PHP errors:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Files Modified/Added

### New Files
- `assets/css/bilingual-email.css` - Bilingual email styling
- `languages/windros-subscription-ar.po` - Arabic translations source
- `languages/windros-subscription-ar.mo` - Compiled Arabic translations
- `BILINGUAL_EMAIL_README.md` - This documentation

### Modified Files
- `includes/emails/WindroseSubscriptionActivatedEmail.php` - Added Arabic field support
- `templates/emails/windrose-subscription-activated.php` - Implemented bilingual template
- `templates/emails/windrose-subscription-cancelled.php` - Implemented bilingual template
- `templates/emails/windrose-subscription-order-failed.php` - Implemented bilingual template
- `templates/emails/windrose-subscription-order-processed.php` - Implemented bilingual template
- `templates/emails/windrose-subscription-paused.php` - Implemented bilingual template
- `templates/emails/windrose-subscription-skipped.php` - Implemented bilingual template
- `templates/emails/plain/windrose-subscription-activated.php` - Added bilingual plain text

## Support
For issues or questions about the bilingual email template:
1. Check the WordPress debug log
2. Verify CSS file is loading
3. Test with different email clients
4. Check browser developer tools for CSS issues
5. Ensure Arabic translations are properly compiled
