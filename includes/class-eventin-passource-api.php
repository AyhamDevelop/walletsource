<?php
/**
 * The class responsible for handling PassSource API integration.
 *
 * @since      1.0.0
 * @package    Eventin_PassSource
 */

class Eventin_PassSource_API {

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
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $settings = get_option('eventin_passource_settings', array());
        $this->client_hash = isset($settings['client_hash']) ? $settings['client_hash'] : '';
        $this->template_hash = isset($settings['template_hash']) ? $settings['template_hash'] : '';
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
        if (empty($this->client_hash) || empty($this->template_hash)) {
            error_log('PassSource API: Missing client hash or template hash');
            return false;
        }

        // Format data for PassSource API
        $pass_data = $this->format_pass_data($ticket_data);
        
        // Call PassSource API to create pass
        $pass_url = $this->create_pass($pass_data);
        
        if ($pass_url) {
            // Store pass URL in order meta
            update_post_meta($order_id, '_eventin_passource_pass_url', $pass_url);
            
            // Log success
            error_log('PassSource API: Successfully created pass for order #' . $order_id);
            
            return $pass_url;
        }
        
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
        // Map Eventin Pro data to PassSource fields
        $pass_data = array(
            'templateHash' => $this->template_hash,
            'clientHash' => $this->client_hash,
            'fields' => array(
                'structure_headerFields_eventName_value' => $ticket_data['event_title'],
                'structure_primaryFields_eventDate_value' => $ticket_data['event_date'],
                'structure_primaryFields_eventLocation_value' => $ticket_data['event_location'],
                'structure_secondaryFields_attendeeName_value' => $ticket_data['attendee_name'],
                'structure_secondaryFields_ticketType_value' => $ticket_data['ticket_type'],
                'barcode_message' => $ticket_data['qr_code'],
                'structure_backFields_description_value' => $ticket_data['event_description'],
                'structure_auxiliaryFields_purchaseDate_value' => $ticket_data['purchase_date'],
            ),
        );

        return $pass_data;
    }

    /**
     * Create pass using PassSource API
     *
     * @since    1.0.0
     * @param    array     $pass_data    The formatted pass data.
     * @return   mixed                   Pass URL on success, false on failure.
     */
    private function create_pass($pass_data) {
        $url = $this->api_base_url . 'pass/create.php';
        
        $args = array(
            'body' => json_encode($pass_data),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            error_log('PassSource API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['success']) && $data['success'] && isset($data['passUrl'])) {
            return $data['passUrl'];
        }
        
        error_log('PassSource API Error: ' . (isset($data['message']) ? $data['message'] : 'Unknown error'));
        return false;
    }
}
