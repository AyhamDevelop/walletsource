<?php
/**
 * Enhanced PassSource API integration
 *
 * This file contains detailed implementation for integrating with PassSource API
 * to generate digital wallet passes for Apple Wallet and Google Pay.
 *
 * @since      1.0.0
 * @package    Eventin_PassSource
 */

class Eventin_PassSource_API_Handler {

    /**
     * PassSource API base URL
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_base_url    The base URL for PassSource API.
     */
    private $api_base_url = 'https://www.passsource.com/api/';

    /**
     * PassSource client hash
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $client_hash    The client hash for authentication.
     */
    private $client_hash;

    /**
     * PassSource template hash
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $template_hash    The template hash for pass creation.
     */
    private $template_hash;

    /**
     * Debug mode
     *
     * @since    1.0.0
     * @access   private
     * @var      bool    $debug_mode    Whether to enable debug logging.
     */
    private $debug_mode;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $settings = get_option('eventin_passource_settings', array());
        $this->client_hash = isset($settings['client_hash']) ? $settings['client_hash'] : '';
        $this->template_hash = isset($settings['template_hash']) ? $settings['template_hash'] : '';
        $this->debug_mode = isset($settings['debug_mode']) ? ($settings['debug_mode'] === 'yes') : false;
        
        // Hook into data extraction to generate passes
        add_action('eventin_passource_data_extracted', array($this, 'generate_wallet_pass'), 10, 2);
    }

    /**
     * Generate wallet pass using PassSource API
     *
     * @since    1.0.0
     * @param    array     $ticket_data    The ticket data from Eventin Pro.
     * @param    int       $order_id       The order ID.
     * @return   mixed                     Pass URL on success, false on failure.
     */
    public function generate_wallet_pass($ticket_data, $order_id) {
        // Check if credentials are set
        if (empty($this->client_hash) || empty($this->template_hash)) {
            $this->log_error('Missing PassSource credentials. Please configure the plugin settings.');
            return false;
        }

        // Check if pass already exists for this order/attendee
        $existing_pass_url = $this->get_existing_pass_url($order_id, $ticket_data['attendee_id']);
        if ($existing_pass_url) {
            $this->log_debug('Using existing pass for order #' . $order_id . ', attendee #' . $ticket_data['attendee_id']);
            return $existing_pass_url;
        }

        // Format data for PassSource API
        $pass_data = $this->format_pass_data($ticket_data);
        
        // Call PassSource API to create pass
        $result = $this->create_pass($pass_data);
        
        if (is_wp_error($result)) {
            $this->log_error('Failed to create pass: ' . $result->get_error_message());
            return false;
        }
        
        if (isset($result['success']) && $result['success'] && isset($result['passUrl'])) {
            // Store pass URL and data in order meta
            $this->store_pass_data($order_id, $ticket_data['attendee_id'], $result);
            
            $this->log_debug('Successfully created pass for order #' . $order_id . ', attendee #' . $ticket_data['attendee_id']);
            
            return $result['passUrl'];
        }
        
        $this->log_error('PassSource API returned error: ' . (isset($result['message']) ? $result['message'] : 'Unknown error'));
        return false;
    }

    /**
     * Format ticket data for PassSource API
     *
     * @since    1.0.0
     * @param    array     $ticket_data    The ticket data from Eventin Pro.
     * @return   array                     Formatted data for PassSource API.
     */
    private function format_pass_data($ticket_data) {
        // Get site name for organization
        $site_name = get_bloginfo('name');
        
        // Generate a unique serial number
        $serial_number = $this->generate_serial_number($ticket_data);
        
        // Format event date for display
        $event_date = $ticket_data['event_date'];
        
        // Map Eventin Pro data to PassSource fields
        $pass_data = array(
            'templateHash' => $this->template_hash,
            'clientHash' => $this->client_hash,
            'serialNumber' => $serial_number,
            'fields' => array(
                // Header fields
                'structure_headerFields_eventName_value' => $ticket_data['event_title'],
                'structure_headerFields_eventName_label' => __('Event', 'eventin-passource'),
                
                // Primary fields
                'structure_primaryFields_eventDate_value' => $event_date,
                'structure_primaryFields_eventDate_label' => __('Date & Time', 'eventin-passource'),
                'structure_primaryFields_eventLocation_value' => $ticket_data['event_location'],
                'structure_primaryFields_eventLocation_label' => __('Location', 'eventin-passource'),
                
                // Secondary fields
                'structure_secondaryFields_attendeeName_value' => $ticket_data['attendee_name'],
                'structure_secondaryFields_attendeeName_label' => __('Attendee', 'eventin-passource'),
                'structure_secondaryFields_ticketType_value' => $ticket_data['ticket_type'],
                'structure_secondaryFields_ticketType_label' => __('Ticket', 'eventin-passource'),
                
                // Auxiliary fields
                'structure_auxiliaryFields_purchaseDate_value' => $ticket_data['purchase_date'],
                'structure_auxiliaryFields_purchaseDate_label' => __('Purchased', 'eventin-passource'),
                
                // Back fields
                'structure_backFields_description_value' => $ticket_data['event_description'],
                'structure_backFields_description_label' => __('Event Details', 'eventin-passource'),
                'structure_backFields_terms_value' => $this->get_terms_text(),
                'structure_backFields_terms_label' => __('Terms & Conditions', 'eventin-passource'),
                
                // Organization info
                'organizationName' => $site_name,
                
                // Barcode
                'barcode_message' => $ticket_data['qr_code'],
                'barcode_format' => 'PKBarcodeFormatQR',
                'barcode_altText' => __('Scan to verify ticket', 'eventin-passource'),
            ),
        );

        // Apply filters to allow customization
        return apply_filters('eventin_passource_pass_data', $pass_data, $ticket_data);
    }

    /**
     * Create pass using PassSource API
     *
     * @since    1.0.0
     * @param    array     $pass_data    The formatted pass data.
     * @return   mixed                   API response or WP_Error on failure.
     */
    private function create_pass($pass_data) {
        $url = $this->api_base_url . 'pass/create.php';
        
        $this->log_debug('Sending request to PassSource API: ' . wp_json_encode($pass_data));
        
        $args = array(
            'body' => wp_json_encode($pass_data),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        $this->log_debug('PassSource API response code: ' . $response_code);
        $this->log_debug('PassSource API response body: ' . $body);
        
        if ($response_code !== 200) {
            return new WP_Error('api_error', 'PassSource API returned status code: ' . $response_code);
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Failed to parse API response: ' . json_last_error_msg());
        }
        
        return $data;
    }

    /**
     * Generate a unique serial number for the pass
     *
     * @since    1.0.0
     * @param    array     $ticket_data    The ticket data.
     * @return   string                    Serial number.
     */
    private function generate_serial_number($ticket_data) {
        // Create a unique identifier based on event ID, attendee ID, and a timestamp
        $base = $ticket_data['event_id'] . '-' . $ticket_data['attendee_id'] . '-' . time();
        
        // Hash it to create a clean serial number
        return md5($base);
    }

    /**
     * Get terms and conditions text
     *
     * @since    1.0.0
     * @return   string    Terms text.
     */
    private function get_terms_text() {
        $settings = get_option('eventin_passource_settings', array());
        $terms_text = isset($settings['terms_text']) ? $settings['terms_text'] : '';
        
        if (empty($terms_text)) {
            // Default terms text
            $terms_text = __('This ticket is subject to the event terms and conditions. This ticket cannot be replaced if lost, stolen or destroyed. Unauthorized resale or transfer of this ticket may result in cancellation without refund.', 'eventin-passource');
        }
        
        return $terms_text;
    }

    /**
     * Store pass data in order meta
     *
     * @since    1.0.0
     * @param    int       $order_id      The order ID.
     * @param    int       $attendee_id   The attendee ID.
     * @param    array     $pass_data     The pass data from API response.
     */
    private function store_pass_data($order_id, $attendee_id, $pass_data) {
        // Store pass URL in order meta
        update_post_meta($order_id, '_eventin_passource_pass_url', $pass_data['passUrl']);
        
        // Store pass URL for specific attendee
        update_post_meta($order_id, '_eventin_passource_pass_url_' . $attendee_id, $pass_data['passUrl']);
        
        // Store serialNumber and hashedSerialNumber if available
        if (isset($pass_data['serialNumber'])) {
            update_post_meta($order_id, '_eventin_passource_serial_' . $attendee_id, $pass_data['serialNumber']);
        }
        
        if (isset($pass_data['hashedSerialNumber'])) {
            update_post_meta($order_id, '_eventin_passource_hashed_serial_' . $attendee_id, $pass_data['hashedSerialNumber']);
        }
    }

    /**
     * Get existing pass URL for an order/attendee
     *
     * @since    1.0.0
     * @param    int       $order_id      The order ID.
     * @param    int       $attendee_id   The attendee ID.
     * @return   string                   Pass URL or empty string if not found.
     */
    private function get_existing_pass_url($order_id, $attendee_id) {
        // Try to get attendee-specific pass URL first
        $pass_url = get_post_meta($order_id, '_eventin_passource_pass_url_' . $attendee_id, true);
        
        if (empty($pass_url)) {
            // Fall back to order-level pass URL
            $pass_url = get_post_meta($order_id, '_eventin_passource_pass_url', true);
        }
        
        return $pass_url;
    }

    /**
     * Log debug message
     *
     * @since    1.0.0
     * @param    string    $message    Debug message.
     */
    private function log_debug($message) {
        if ($this->debug_mode) {
            error_log('Eventin PassSource Debug: ' . $message);
        }
    }

    /**
     * Log error message
     *
     * @since    1.0.0
     * @param    string    $message    Error message.
     */
    private function log_error($message) {
        error_log('Eventin PassSource Error: ' . $message);
    }

    /**
     * Verify PassSource API credentials
     *
     * @since    1.0.0
     * @return   array    Verification result.
     */
    public function verify_credentials() {
        if (empty($this->client_hash) || empty($this->template_hash)) {
            return array(
                'success' => false,
                'message' => __('Missing client hash or template hash', 'eventin-passource'),
            );
        }
        
        // Create a simple test request to verify credentials
        $test_data = array(
            'clientHash' => $this->client_hash,
            'templateHash' => $this->template_hash,
            'action' => 'verify',
        );
        
        $url = $this->api_base_url . 'template/info.php';
        
        $args = array(
            'body' => wp_json_encode($test_data),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            return array(
                'success' => false,
                'message' => __('API returned status code: ', 'eventin-passource') . $response_code,
            );
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'message' => __('Failed to parse API response', 'eventin-passource'),
            );
        }
        
        if (isset($data['success']) && $data['success']) {
            return array(
                'success' => true,
                'message' => __('API credentials verified successfully', 'eventin-passource'),
                'template_info' => isset($data['template']) ? $data['template'] : array(),
            );
        }
        
        return array(
            'success' => false,
            'message' => isset($data['message']) ? $data['message'] : __('Unknown error', 'eventin-passource'),
        );
    }
}

// Initialize the API handler
new Eventin_PassSource_API_Handler();
