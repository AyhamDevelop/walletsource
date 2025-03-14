<?php
/**
 * The class responsible for extracting data from Eventin Pro.
 *
 * @since      1.0.0
 * @package    Eventin_PassSource
 */

class Eventin_PassSource_Data {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Nothing to initialize
    }

    /**
     * Extract ticket data from Eventin Pro after checkout
     *
     * @since    1.0.0
     * @param    int       $order_id    The order ID.
     * @param    array     $data        The checkout data.
     */
    public function extract_ticket_data($order_id, $data) {
        // Check if wallet pass generation is enabled
        $settings = get_option('eventin_passource_settings', array());
        $enable_checkout_button = isset($settings['enable_checkout_button']) ? $settings['enable_checkout_button'] : 'yes';
        
        if ($enable_checkout_button !== 'yes') {
            return;
        }
        
        // Get order details
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log('Eventin PassSource: Order not found - ' . $order_id);
            return;
        }
        
        // Get event and attendee information
        $ticket_data = $this->get_ticket_data_from_order($order);
        
        if (!empty($ticket_data)) {
            // Trigger action to generate wallet pass
            do_action('eventin_passource_data_extracted', $ticket_data, $order_id);
        }
    }

    /**
     * Get ticket data from order
     *
     * @since    1.0.0
     * @param    WC_Order  $order    The WooCommerce order.
     * @return   array               Ticket data.
     */
    private function get_ticket_data_from_order($order) {
        $ticket_data = array();
        
        // Loop through order items to find Eventin tickets
        foreach ($order->get_items() as $item) {
            // Check if this is an Eventin ticket
            $event_id = $item->get_meta('_etn_event_id');
            if (!$event_id) {
                continue;
            }
            
            // Get event details
            $event = get_post($event_id);
            if (!$event) {
                continue;
            }
            
            // Get attendee information
            $attendee_id = $this->get_attendee_id_from_order($order->get_id(), $event_id);
            if (!$attendee_id) {
                continue;
            }
            
            $attendee = get_post($attendee_id);
            if (!$attendee) {
                continue;
            }
            
            // Get QR code data
            $qr_code = get_post_meta($attendee_id, '_etn_qr_code', true);
            
            // Build ticket data array
            $ticket_data = array(
                'event_id' => $event_id,
                'event_title' => $event->post_title,
                'event_date' => get_post_meta($event_id, 'etn_start_date', true) . ' ' . get_post_meta($event_id, 'etn_start_time', true),
                'event_location' => get_post_meta($event_id, 'etn_venue', true),
                'event_description' => wp_trim_words($event->post_content, 100),
                'attendee_id' => $attendee_id,
                'attendee_name' => get_post_meta($attendee_id, 'etn_name', true),
                'ticket_type' => $item->get_name(),
                'purchase_date' => $order->get_date_created()->date('Y-m-d H:i:s'),
                'qr_code' => $qr_code,
            );
            
            // We only process the first ticket for now
            break;
        }
        
        return $ticket_data;
    }

    /**
     * Get attendee ID from order and event
     *
     * @since    1.0.0
     * @param    int       $order_id    The order ID.
     * @param    int       $event_id    The event ID.
     * @return   int                    Attendee ID.
     */
    private function get_attendee_id_from_order($order_id, $event_id) {
        global $wpdb;
        
        // Query to find attendee ID
        $query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_etn_order_id' 
            AND meta_value = %d 
            AND post_id IN (
                SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = '_etn_event_id' 
                AND meta_value = %d
            )
            LIMIT 1",
            $order_id,
            $event_id
        );
        
        $attendee_id = $wpdb->get_var($query);
        
        return $attendee_id;
    }
}
