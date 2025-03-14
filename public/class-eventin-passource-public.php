<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Eventin_PassSource
 */

class Eventin_PassSource_Public {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Nothing to initialize
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'eventin-passource-public',
            EVENTIN_PASSOURCE_URL . 'assets/css/eventin-passource-public.css',
            array(),
            EVENTIN_PASSOURCE_VERSION,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'eventin-passource-public',
            EVENTIN_PASSOURCE_URL . 'assets/js/eventin-passource-public.js',
            array('jquery'),
            EVENTIN_PASSOURCE_VERSION,
            false
        );
    }

    /**
     * Display wallet button on checkout success page
     *
     * @since    1.0.0
     * @param    int    $order_id    The order ID.
     */
    public function display_wallet_button($order_id) {
        // Check if wallet pass generation is enabled
        $settings = get_option('eventin_passource_settings', array());
        $enable_checkout_button = isset($settings['enable_checkout_button']) ? $settings['enable_checkout_button'] : 'yes';
        
        if ($enable_checkout_button !== 'yes') {
            return;
        }
        
        // Get pass URL from order meta
        $pass_url = get_post_meta($order_id, '_eventin_passource_pass_url', true);
        
        if (empty($pass_url)) {
            return;
        }
        
        // Get button style setting
        $button_style = isset($settings['button_style']) ? $settings['button_style'] : 'both';
        
        // Display wallet buttons
        echo $this->get_wallet_buttons_html($pass_url, $button_style);
    }

    /**
     * Add wallet button to confirmation email
     *
     * @since    1.0.0
     * @param    string    $content     The email content.
     * @param    int       $order_id    The order ID.
     * @param    array     $data        Additional data.
     * @return   string                 Modified email content.
     */
    public function add_wallet_button_to_email($content, $order_id, $data) {
        // Check if wallet pass generation is enabled for emails
        $settings = get_option('eventin_passource_settings', array());
        $enable_email_button = isset($settings['enable_email_button']) ? $settings['enable_email_button'] : 'yes';
        
        if ($enable_email_button !== 'yes') {
            return $content;
        }
        
        // Get pass URL from order meta
        $pass_url = get_post_meta($order_id, '_eventin_passource_pass_url', true);
        
        if (empty($pass_url)) {
            return $content;
        }
        
        // Get button style setting
        $button_style = isset($settings['button_style']) ? $settings['button_style'] : 'both';
        
        // Generate wallet buttons HTML
        $buttons_html = $this->get_wallet_buttons_html($pass_url, $button_style, true);
        
        // Find position to insert buttons (before ticket details)
        $ticket_position = strpos($content, '<!-- Ticket Details -->');
        
        if ($ticket_position !== false) {
            // Insert buttons before ticket details
            $content = substr_replace($content, $buttons_html, $ticket_position, 0);
        } else {
            // Append to end if ticket details section not found
            $content .= $buttons_html;
        }
        
        return $content;
    }

    /**
     * Get HTML for wallet buttons
     *
     * @since    1.0.0
     * @param    string     $pass_url       The pass URL.
     * @param    string     $button_style   The button style (apple, google, or both).
     * @param    bool       $for_email      Whether the buttons are for email.
     * @return   string                     Buttons HTML.
     */
    private function get_wallet_buttons_html($pass_url, $button_style = 'both', $for_email = false) {
        $html = '';
        
        if ($for_email) {
            // Email-specific wrapper with inline styles
            $html .= '<div style="margin: 20px 0; text-align: center;">';
            $html .= '<p style="margin-bottom: 15px; font-weight: bold;">' . __('Add this ticket to your mobile wallet:', 'eventin-passource') . '</p>';
        } else {
            // Regular wrapper for website
            $html .= '<div class="eventin-passource-wallet-buttons">';
            $html .= '<h3>' . __('Add to Mobile Wallet', 'eventin-passource') . '</h3>';
        }
        
        // Apple Wallet button
        if ($button_style === 'apple' || $button_style === 'both') {
            if ($for_email) {
                $html .= '<a href="' . esc_url($pass_url) . '" style="display: inline-block; margin: 10px 5px;" target="_blank">';
                $html .= '<img src="' . EVENTIN_PASSOURCE_URL . 'assets/images/add-to-apple-wallet.png" style="max-width: 160px; height: auto;" alt="Add to Apple Wallet">';
                $html .= '</a>';
            } else {
                $html .= '<a href="' . esc_url($pass_url) . '" class="eventin-passource-apple-button" target="_blank">';
                $html .= '<img src="' . EVENTIN_PASSOURCE_URL . 'assets/images/add-to-apple-wallet.png" alt="Add to Apple Wallet">';
                $html .= '</a>';
            }
        }
        
        // Google Pay button
        if ($button_style === 'google' || $button_style === 'both') {
            if ($for_email) {
                $html .= '<a href="' . esc_url($pass_url) . '" style="display: inline-block; margin: 10px 5px;" target="_blank">';
                $html .= '<img src="' . EVENTIN_PASSOURCE_URL . 'assets/images/add-to-google-wallet.png" style="max-width: 160px; height: auto;" alt="Add to Google Wallet">';
                $html .= '</a>';
            } else {
                $html .= '<a href="' . esc_url($pass_url) . '" class="eventin-passource-google-button" target="_blank">';
                $html .= '<img src="' . EVENTIN_PASSOURCE_URL . 'assets/images/add-to-google-wallet.png" alt="Add to Google Wallet">';
                $html .= '</a>';
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
