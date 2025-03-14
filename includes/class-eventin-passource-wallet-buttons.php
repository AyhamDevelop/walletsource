<?php
/**
 * Enhanced wallet button functionality
 *
 * This file contains detailed implementation for creating and displaying
 * "Add to Wallet" buttons for Apple Wallet and Google Pay.
 *
 * @since      1.0.0
 * @package    Eventin_PassSource
 */

class Eventin_PassSource_Wallet_Buttons {

    /**
     * Button style setting
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $button_style    The button style (apple, google, or both).
     */
    private $button_style;

    /**
     * Enable checkout button setting
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $enable_checkout_button    Whether to enable button on checkout.
     */
    private $enable_checkout_button;

    /**
     * Enable email button setting
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $enable_email_button    Whether to enable button in emails.
     */
    private $enable_email_button;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $settings = get_option('eventin_passource_settings', array());
        $this->button_style = isset($settings['button_style']) ? $settings['button_style'] : 'both';
        $this->enable_checkout_button = isset($settings['enable_checkout_button']) ? $settings['enable_checkout_button'] : 'yes';
        $this->enable_email_button = isset($settings['enable_email_button']) ? $settings['enable_email_button'] : 'yes';
        
        // Hook into checkout success page
        add_action('eventin_after_checkout_content', array($this, 'display_wallet_button_on_checkout'), 10, 1);
        
        // Hook into confirmation email
        add_filter('eventin_email_template_content', array($this, 'add_wallet_button_to_email'), 10, 3);
        
        // Add button images to assets
        add_action('init', array($this, 'ensure_button_images_exist'));
    }

    /**
     * Display wallet button on checkout success page
     *
     * @since    1.0.0
     * @param    int    $order_id    The order ID.
     */
    public function display_wallet_button_on_checkout($order_id) {
        // Check if wallet button is enabled for checkout
        if ($this->enable_checkout_button !== 'yes') {
            return;
        }
        
        // Get pass URL from order meta
        $pass_url = get_post_meta($order_id, '_eventin_passource_pass_url', true);
        
        if (empty($pass_url)) {
            return;
        }
        
        // Display wallet buttons
        echo $this->get_wallet_buttons_html($pass_url, $this->button_style);
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
        // Check if wallet button is enabled for emails
        if ($this->enable_email_button !== 'yes') {
            return $content;
        }
        
        // Get pass URL from order meta
        $pass_url = get_post_meta($order_id, '_eventin_passource_pass_url', true);
        
        if (empty($pass_url)) {
            return $content;
        }
        
        // Generate wallet buttons HTML for email
        $buttons_html = $this->get_wallet_buttons_html($pass_url, $this->button_style, true);
        
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
            $html .= '<div style="margin: 20px 0; text-align: center; padding: 15px; background-color: #f9f9f9; border-radius: 5px; border: 1px solid #e0e0e0;">';
            $html .= '<p style="margin-bottom: 15px; font-weight: bold; font-size: 16px; color: #333;">' . __('Add this ticket to your mobile wallet:', 'eventin-passource') . '</p>';
        } else {
            // Regular wrapper for website
            $html .= '<div class="eventin-passource-wallet-buttons">';
            $html .= '<h3>' . __('Add to Mobile Wallet', 'eventin-passource') . '</h3>';
            $html .= '<p>' . __('Download your ticket to Apple Wallet or Google Pay for easy access.', 'eventin-passource') . '</p>';
        }
        
        // Apple Wallet button
        if ($button_style === 'apple' || $button_style === 'both') {
            if ($for_email) {
                $html .= '<a href="' . esc_url($pass_url) . '" style="display: inline-block; margin: 10px 5px;" target="_blank">';
                $html .= '<img src="' . $this->get_apple_wallet_button_url() . '" style="max-width: 160px; height: auto;" alt="Add to Apple Wallet">';
                $html .= '</a>';
            } else {
                $html .= '<a href="' . esc_url($pass_url) . '" class="eventin-passource-apple-button" target="_blank">';
                $html .= '<img src="' . $this->get_apple_wallet_button_url() . '" alt="Add to Apple Wallet">';
                $html .= '</a>';
            }
        }
        
        // Google Pay button
        if ($button_style === 'google' || $button_style === 'both') {
            if ($for_email) {
                $html .= '<a href="' . esc_url($pass_url) . '" style="display: inline-block; margin: 10px 5px;" target="_blank">';
                $html .= '<img src="' . $this->get_google_wallet_button_url() . '" style="max-width: 160px; height: auto;" alt="Add to Google Wallet">';
                $html .= '</a>';
            } else {
                $html .= '<a href="' . esc_url($pass_url) . '" class="eventin-passource-google-button" target="_blank">';
                $html .= '<img src="' . $this->get_google_wallet_button_url() . '" alt="Add to Google Wallet">';
                $html .= '</a>';
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Get Apple Wallet button image URL
     *
     * @since    1.0.0
     * @return   string    Button image URL.
     */
    private function get_apple_wallet_button_url() {
        return EVENTIN_PASSOURCE_URL . 'assets/images/add-to-apple-wallet.png';
    }

    /**
     * Get Google Wallet button image URL
     *
     * @since    1.0.0
     * @return   string    Button image URL.
     */
    private function get_google_wallet_button_url() {
        return EVENTIN_PASSOURCE_URL . 'assets/images/add-to-google-wallet.png';
    }

    /**
     * Ensure button images exist in assets directory
     *
     * @since    1.0.0
     */
    public function ensure_button_images_exist() {
        $images_dir = EVENTIN_PASSOURCE_PATH . 'assets/images/';
        
        // Create directory if it doesn't exist
        if (!file_exists($images_dir)) {
            wp_mkdir_p($images_dir);
        }
        
        // Check and create Apple Wallet button
        $apple_button_path = $images_dir . 'add-to-apple-wallet.png';
        if (!file_exists($apple_button_path)) {
            $this->create_apple_wallet_button($apple_button_path);
        }
        
        // Check and create Google Wallet button
        $google_button_path = $images_dir . 'add-to-google-wallet.png';
        if (!file_exists($google_button_path)) {
            $this->create_google_wallet_button($google_button_path);
        }
    }

    /**
     * Create Apple Wallet button image
     *
     * @since    1.0.0
     * @param    string    $path    Path to save the image.
     */
    private function create_apple_wallet_button($path) {
        // Use a default image from PassSource or create one
        $default_image = 'https://www.passsource.com/images/add-to-apple-wallet-button.png';
        
        $response = wp_remote_get($default_image);
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $image_data = wp_remote_retrieve_body($response);
            file_put_contents($path, $image_data);
        } else {
            // If we can't download, create a basic image
            $this->create_basic_button_image($path, 'Add to Apple Wallet', '#000000');
        }
    }

    /**
     * Create Google Wallet button image
     *
     * @since    1.0.0
     * @param    string    $path    Path to save the image.
     */
    private function create_google_wallet_button($path) {
        // Use a default image from PassSource or create one
        $default_image = 'https://www.passsource.com/images/add-to-google-wallet-button.png';
        
        $response = wp_remote_get($default_image);
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $image_data = wp_remote_retrieve_body($response);
            file_put_contents($path, $image_data);
        } else {
            // If we can't download, create a basic image
            $this->create_basic_button_image($path, 'Add to Google Wallet', '#4285F4');
        }
    }

    /**
     * Create a basic button image
     *
     * @since    1.0.0
     * @param    string    $path       Path to save the image.
     * @param    string    $text       Button text.
     * @param    string    $bg_color   Background color.
     */
    private function create_basic_button_image($path, $text, $bg_color) {
        // Only create if GD is available
        if (!function_exists('imagecreatetruecolor')) {
            return;
        }
        
        // Create image
        $width = 160;
        $height = 40;
        $image = imagecreatetruecolor($width, $height);
        
        // Set colors
        $bg = $this->hex_to_rgb($bg_color);
        $background = imagecolorallocate($image, $bg['r'], $bg['g'], $bg['b']);
        $text_color = imagecolorallocate($image, 255, 255, 255);
        
        // Fill background
        imagefill($image, 0, 0, $background);
        
        // Add text
        $font_size = 4;
        $text_width = imagefontwidth($font_size) * strlen($text);
        $text_height = imagefontheight($font_size);
        $x = ($width - $text_width) / 2;
        $y = ($height - $text_height) / 2;
        
        imagestring($image, $font_size, $x, $y, $text, $text_color);
        
        // Save image
        imagepng($image, $path);
        imagedestroy($image);
    }

    /**
     * Convert hex color to RGB
     *
     * @since    1.0.0
     * @param    string    $hex    Hex color code.
     * @return   array             RGB values.
     */
    private function hex_to_rgb($hex) {
        $hex = str_replace('#', '', $hex);
        
        if (strlen($hex) === 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        
        return array('r' => $r, 'g' => $g, 'b' => $b);
    }
}

// Initialize the wallet buttons
new Eventin_PassSource_Wallet_Buttons();
