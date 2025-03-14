# Eventin PassSource Integration - User Documentation

## Installation Instructions

1. Download the plugin ZIP file from the provided location.
2. Log in to your WordPress admin dashboard.
3. Navigate to Plugins > Add New > Upload Plugin.
4. Choose the downloaded ZIP file and click "Install Now".
5. After installation, click "Activate Plugin".

## Requirements

- WordPress 5.0 or higher
- Eventin Pro plugin (latest version recommended)
- WooCommerce plugin (latest version recommended)
- A PassSource account with API credentials

## Configuration Guide

### Step 1: Obtain PassSource API Credentials

1. Create an account at [PassSource](https://www.passsource.com/) if you don't have one.
2. Log in to your PassSource account.
3. Create a pass template for your event tickets.
4. Note down your Client Hash and Template Hash from your account settings.

### Step 2: Configure Plugin Settings

1. In your WordPress admin dashboard, navigate to Eventin > Settings.
2. Click on the "PassSource Integration" tab.
3. Enter your PassSource Client Hash and Template Hash.
4. Configure the following options:
   - Enable Wallet Button on Checkout: Choose whether to display the "Add to Wallet" button on the checkout success page.
   - Enable Wallet Button in Emails: Choose whether to include the "Add to Wallet" button in confirmation emails.
   - Wallet Button Style: Select which wallet buttons to display (Apple Wallet, Google Pay, or both).
5. Click "Save Changes".

## How It Works

Once configured, the plugin will automatically:

1. Extract ticket information from Eventin Pro when a customer purchases an event ticket.
2. Generate a digital wallet pass using PassSource with the event and attendee information.
3. Display "Add to Wallet" buttons on the checkout success page (if enabled).
4. Include "Add to Wallet" buttons in the confirmation email (if enabled).

Customers can click these buttons to add their tickets to Apple Wallet or Google Pay for easy access on their mobile devices.

## Troubleshooting

### Wallet Buttons Not Appearing

- Verify that you've entered the correct PassSource API credentials.
- Check that the "Enable Wallet Button" options are set to "Yes" in the settings.
- Ensure that the order contains Eventin Pro tickets.
- Check if the pass was successfully generated (look for the pass URL in the order meta).

### Pass Generation Failures

- Verify your PassSource API credentials are correct.
- Ensure your PassSource account is active and in good standing.
- Check that your pass template is properly configured in PassSource.
- Enable debug mode in the settings to get more detailed error information.

### Email Integration Issues

- Make sure the "Enable Wallet Button in Emails" option is set to "Yes".
- Check if your email template has been customized, which might affect the button insertion.
- Verify that the confirmation emails are being sent correctly.

## Support

If you encounter any issues or have questions about the plugin, please contact support at [support@example.com](mailto:support@example.com).

---

# Developer Documentation

## Code Structure Overview

The plugin follows a modular structure with separate classes for different functionalities:

- `class-eventin-passource.php`: Main plugin class that initializes all components.
- `class-eventin-passource-loader.php`: Handles action and filter hook registration.
- `class-eventin-passource-admin.php`: Manages admin-side functionality and settings.
- `class-eventin-passource-public.php`: Handles public-facing functionality.
- `class-eventin-passource-data.php`: Extracts data from Eventin Pro.
- `class-eventin-passource-data-extractor.php`: Enhanced data extraction functionality.
- `class-eventin-passource-api.php`: Basic PassSource API integration.
- `class-eventin-passource-api-handler.php`: Enhanced PassSource API functionality.
- `class-eventin-passource-wallet-buttons.php`: Manages wallet button display.
- `class-eventin-passource-test.php`: Testing functionality.

## Available Hooks and Filters

### Actions

- `eventin_passource_data_extracted`: Fired after ticket data is extracted from Eventin Pro.
  - Parameters: `$ticket_data` (array), `$order_id` (int)
  - Use this to perform additional operations with the extracted ticket data.

- `eventin_passource_pass_generated`: Fired after a wallet pass is successfully generated.
  - Parameters: `$pass_url` (string), `$order_id` (int), `$attendee_id` (int)
  - Use this to perform additional operations with the generated pass.

- `eventin_passource_pass_generation_failed`: Fired when pass generation fails.
  - Parameters: `$error_message` (string), `$order_id` (int), `$attendee_id` (int)
  - Use this for custom error handling or notifications.

### Filters

- `eventin_passource_pass_data`: Modify the data sent to PassSource for pass generation.
  - Parameters: `$pass_data` (array), `$ticket_data` (array)
  - Use this to customize the pass content or add additional fields.

- `eventin_passource_wallet_buttons_html`: Modify the HTML for wallet buttons.
  - Parameters: `$buttons_html` (string), `$pass_url` (string), `$button_style` (string), `$for_email` (bool)
  - Use this to customize the appearance of the wallet buttons.

- `eventin_passource_settings_fields`: Modify the plugin settings fields.
  - Parameters: `$fields` (array)
  - Use this to add or modify settings fields.

## Customization Options

### Custom Pass Fields

You can add custom fields to the wallet pass by using the `eventin_passource_pass_data` filter:

```php
add_filter('eventin_passource_pass_data', 'my_custom_pass_fields', 10, 2);
function my_custom_pass_fields($pass_data, $ticket_data) {
    // Add a custom field to the pass
    $pass_data['fields']['structure_backFields_custom_field_value'] = 'Custom value';
    $pass_data['fields']['structure_backFields_custom_field_label'] = 'Custom Field';
    
    return $pass_data;
}
```

### Custom Button Styling

You can customize the appearance of the wallet buttons by using the `eventin_passource_wallet_buttons_html` filter:

```php
add_filter('eventin_passource_wallet_buttons_html', 'my_custom_button_styling', 10, 4);
function my_custom_button_styling($buttons_html, $pass_url, $button_style, $for_email) {
    // Modify the buttons HTML
    $buttons_html = str_replace('class="eventin-passource-wallet-buttons"', 'class="eventin-passource-wallet-buttons my-custom-class"', $buttons_html);
    
    return $buttons_html;
}
```

### Custom Data Extraction

You can extend the data extraction functionality by creating a custom class that extends `Eventin_PassSource_Data_Extractor`:

```php
class My_Custom_Data_Extractor extends Eventin_PassSource_Data_Extractor {
    // Override methods to customize data extraction
    protected function extract_ticket_data_from_attendee($attendee) {
        $ticket_data = parent::extract_ticket_data_from_attendee($attendee);
        
        // Add custom data
        $ticket_data['custom_field'] = get_post_meta($attendee->ID, 'my_custom_field', true);
        
        return $ticket_data;
    }
}

// Initialize your custom extractor
new My_Custom_Data_Extractor();
```

## Testing

The plugin includes a test class (`class-eventin-passource-test.php`) that can be used to verify functionality. To run the tests:

1. Add the following code to a PHP file in your WordPress installation:

```php
require_once WP_PLUGIN_DIR . '/eventin-passource/includes/class-eventin-passource-test.php';
$tester = new Eventin_PassSource_Test();
$tester->run_all_tests();
```

2. Visit the page containing this code to see the test results.

Alternatively, you can access the test functionality by visiting:
`https://your-site.com/wp-admin/admin.php?page=eventin-settings&tab=passource&run_tests=true`

## Extending the Plugin

The plugin is designed to be extensible. You can create add-ons or extensions by:

1. Using the provided hooks and filters.
2. Extending the existing classes.
3. Creating new classes that interact with the plugin's components.

For example, you could create an extension that adds support for additional wallet pass types or integrates with other event management plugins.
