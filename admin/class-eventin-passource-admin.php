<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Eventin_PassSource
 */

class Eventin_PassSource_Admin {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Nothing to initialize
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'eventin-passource-admin',
            EVENTIN_PASSOURCE_URL . 'assets/css/eventin-passource-admin.css',
            array(),
            EVENTIN_PASSOURCE_VERSION,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'eventin-passource-admin',
            EVENTIN_PASSOURCE_URL . 'assets/js/eventin-passource-admin.js',
            array('jquery'),
            EVENTIN_PASSOURCE_VERSION,
            false
        );
    }

    /**
     * Add settings tab to Eventin settings
     *
     * @since    1.0.0
     * @param    array    $tabs    Existing tabs.
     * @return   array             Modified tabs.
     */
    public function add_settings_tab($tabs) {
        $tabs['passource'] = __('PassSource Integration', 'eventin-passource');
        return $tabs;
    }

    /**
     * Add settings fields to Eventin settings
     *
     * @since    1.0.0
     * @param    array    $fields    Existing fields.
     * @return   array               Modified fields.
     */
    public function add_settings_fields($fields) {
        $fields['passource'] = array(
            'title'  => __('PassSource Integration Settings', 'eventin-passource'),
            'fields' => array(
                'client_hash' => array(
                    'label'    => __('PassSource Client Hash', 'eventin-passource'),
                    'type'     => 'text',
                    'desc'     => __('Enter your PassSource client hash for API authentication.', 'eventin-passource'),
                    'priority' => 1,
                ),
                'template_hash' => array(
                    'label'    => __('PassSource Template Hash', 'eventin-passource'),
                    'type'     => 'text',
                    'desc'     => __('Enter your PassSource template hash for pass creation.', 'eventin-passource'),
                    'priority' => 2,
                ),
                'enable_checkout_button' => array(
                    'label'    => __('Enable Wallet Button on Checkout', 'eventin-passource'),
                    'type'     => 'select',
                    'options'  => array(
                        'yes' => __('Yes', 'eventin-passource'),
                        'no'  => __('No', 'eventin-passource'),
                    ),
                    'desc'     => __('Show "Add to Wallet" button on checkout success page.', 'eventin-passource'),
                    'priority' => 3,
                ),
                'enable_email_button' => array(
                    'label'    => __('Enable Wallet Button in Emails', 'eventin-passource'),
                    'type'     => 'select',
                    'options'  => array(
                        'yes' => __('Yes', 'eventin-passource'),
                        'no'  => __('No', 'eventin-passource'),
                    ),
                    'desc'     => __('Include "Add to Wallet" button in confirmation emails.', 'eventin-passource'),
                    'priority' => 4,
                ),
                'button_style' => array(
                    'label'    => __('Wallet Button Style', 'eventin-passource'),
                    'type'     => 'select',
                    'options'  => array(
                        'apple'  => __('Apple Wallet Only', 'eventin-passource'),
                        'google' => __('Google Pay Only', 'eventin-passource'),
                        'both'   => __('Both Apple and Google', 'eventin-passource'),
                    ),
                    'desc'     => __('Choose which wallet buttons to display.', 'eventin-passource'),
                    'priority' => 5,
                ),
            ),
        );
        
        return $fields;
    }
}
