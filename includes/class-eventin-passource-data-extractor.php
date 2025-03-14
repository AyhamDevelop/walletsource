<?php
/**
 * Enhanced data extraction functionality for Eventin Pro
 *
 * This file contains additional methods to extract and process data from Eventin Pro
 * for integration with PassSource wallet passes.
 *
 * @since      1.0.0
 * @package    Eventin_PassSource
 */

/**
 * Class for handling detailed data extraction from Eventin Pro
 */
class Eventin_PassSource_Data_Extractor {

    /**
     * Initialize the class
     */
    public function __construct() {
        // Add hooks for data extraction
        add_action('woocommerce_order_status_completed', array($this, 'process_completed_order'), 10, 1);
        add_action('woocommerce_thankyou', array($this, 'process_thankyou_page'), 10, 1);
        
        // Hook into Eventin Pro ticket generation
        add_action('etn_after_add_to_cart_redirect_url', array($this, 'capture_ticket_generation'), 10, 2);
    }

    /**
     * Process completed orders to generate wallet passes
     *
     * @param int $order_id The WooCommerce order ID
     */
    public function process_completed_order($order_id) {
        // Get order object
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Check if this order contains Eventin tickets
        $has_eventin_tickets = false;
        foreach ($order->get_items() as $item) {
            if ($this->is_eventin_ticket_item($item)) {
                $has_eventin_tickets = true;
                break;
            }
        }

        if (!$has_eventin_tickets) {
            return;
        }

        // Extract ticket data and generate passes
        $this->extract_and_process_tickets($order);
    }

    /**
     * Process thank you page to display wallet buttons
     *
     * @param int $order_id The WooCommerce order ID
     */
    public function process_thankyou_page($order_id) {
        // Similar to process_completed_order but for the thank you page
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Check if passes have already been generated
        $passes_generated = get_post_meta($order_id, '_eventin_passource_processed', true);
        if ($passes_generated) {
            return;
        }

        // Check if this order contains Eventin tickets
        $has_eventin_tickets = false;
        foreach ($order->get_items() as $item) {
            if ($this->is_eventin_ticket_item($item)) {
                $has_eventin_tickets = true;
                break;
            }
        }

        if (!$has_eventin_tickets) {
            return;
        }

        // Extract ticket data and generate passes
        $this->extract_and_process_tickets($order);
        
        // Mark as processed
        update_post_meta($order_id, '_eventin_passource_processed', 'yes');
    }

    /**
     * Capture ticket generation from Eventin Pro
     *
     * @param string $redirect_url The redirect URL
     * @param int $order_id The WooCommerce order ID
     * @return string The unchanged redirect URL
     */
    public function capture_ticket_generation($redirect_url, $order_id) {
        // Schedule a delayed action to process tickets after Eventin has generated them
        wp_schedule_single_event(time() + 10, 'eventin_passource_delayed_processing', array($order_id));
        
        // Add action for our delayed processing
        add_action('eventin_passource_delayed_processing', array($this, 'delayed_ticket_processing'), 10, 1);
        
        return $redirect_url;
    }

    /**
     * Process tickets after a delay to ensure Eventin has completed ticket generation
     *
     * @param int $order_id The WooCommerce order ID
     */
    public function delayed_ticket_processing($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $this->extract_and_process_tickets($order);
    }

    /**
     * Extract and process all tickets from an order
     *
     * @param WC_Order $order The WooCommerce order
     */
    private function extract_and_process_tickets($order) {
        $order_id = $order->get_id();
        $processed_attendees = array();
        
        // Get all attendees for this order
        $attendees = $this->get_attendees_from_order($order_id);
        
        if (empty($attendees)) {
            error_log('Eventin PassSource: No attendees found for order #' . $order_id);
            return;
        }
        
        foreach ($attendees as $attendee) {
            // Skip if we've already processed this attendee
            if (in_array($attendee->ID, $processed_attendees)) {
                continue;
            }
            
            // Extract ticket data
            $ticket_data = $this->extract_ticket_data_from_attendee($attendee);
            
            if (!empty($ticket_data)) {
                // Trigger action to generate wallet pass
                do_action('eventin_passource_data_extracted', $ticket_data, $order_id);
                
                // Add to processed list
                $processed_attendees[] = $attendee->ID;
            }
        }
    }

    /**
     * Get all attendees associated with an order
     *
     * @param int $order_id The WooCommerce order ID
     * @return array Array of attendee post objects
     */
    private function get_attendees_from_order($order_id) {
        $attendees = array();
        
        // Query for attendees
        $args = array(
            'post_type' => 'etn-attendee',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_etn_order_id',
                    'value' => $order_id,
                    'compare' => '='
                )
            )
        );
        
        $attendee_query = new WP_Query($args);
        
        if ($attendee_query->have_posts()) {
            $attendees = $attendee_query->posts;
        }
        
        return $attendees;
    }

    /**
     * Extract ticket data from an attendee
     *
     * @param WP_Post $attendee The attendee post object
     * @return array Ticket data array
     */
    private function extract_ticket_data_from_attendee($attendee) {
        $ticket_data = array();
        
        // Get event ID
        $event_id = get_post_meta($attendee->ID, '_etn_event_id', true);
        if (!$event_id) {
            return $ticket_data;
        }
        
        // Get event
        $event = get_post($event_id);
        if (!$event) {
            return $ticket_data;
        }
        
        // Get QR code
        $qr_code = get_post_meta($attendee->ID, '_etn_qr_code', true);
        
        // Get ticket type
        $ticket_id = get_post_meta($attendee->ID, 'ticket_id', true);
        $ticket_title = '';
        if ($ticket_id) {
            $ticket_title = get_the_title($ticket_id);
        }
        
        // Build ticket data
        $ticket_data = array(
            'event_id' => $event_id,
            'event_title' => $event->post_title,
            'event_date' => $this->format_event_date($event_id),
            'event_location' => $this->get_event_location($event_id),
            'event_description' => $this->get_event_description($event),
            'attendee_id' => $attendee->ID,
            'attendee_name' => $this->get_attendee_name($attendee->ID),
            'ticket_type' => $ticket_title ? $ticket_title : 'Standard Ticket',
            'purchase_date' => get_the_date('Y-m-d H:i:s', $attendee->ID),
            'qr_code' => $qr_code,
        );
        
        return $ticket_data;
    }

    /**
     * Format event date in a readable format
     *
     * @param int $event_id The event ID
     * @return string Formatted date
     */
    private function format_event_date($event_id) {
        $start_date = get_post_meta($event_id, 'etn_start_date', true);
        $start_time = get_post_meta($event_id, 'etn_start_time', true);
        $end_date = get_post_meta($event_id, 'etn_end_date', true);
        $end_time = get_post_meta($event_id, 'etn_end_time', true);
        
        $formatted_date = '';
        
        if ($start_date) {
            $formatted_date = date_i18n(get_option('date_format'), strtotime($start_date));
            
            if ($start_time) {
                $formatted_date .= ' ' . date_i18n(get_option('time_format'), strtotime($start_time));
            }
            
            if ($end_date && $end_date !== $start_date) {
                $formatted_date .= ' - ' . date_i18n(get_option('date_format'), strtotime($end_date));
                
                if ($end_time) {
                    $formatted_date .= ' ' . date_i18n(get_option('time_format'), strtotime($end_time));
                }
            } elseif ($end_time) {
                $formatted_date .= ' - ' . date_i18n(get_option('time_format'), strtotime($end_time));
            }
        }
        
        return $formatted_date;
    }

    /**
     * Get event location
     *
     * @param int $event_id The event ID
     * @return string Event location
     */
    private function get_event_location($event_id) {
        $location = get_post_meta($event_id, 'etn_venue', true);
        
        if (empty($location)) {
            // Try to get location from terms if using location taxonomy
            $locations = get_the_terms($event_id, 'etn_location');
            if (!empty($locations) && !is_wp_error($locations)) {
                $location_names = array();
                foreach ($locations as $loc) {
                    $location_names[] = $loc->name;
                }
                $location = implode(', ', $location_names);
            }
        }
        
        return $location;
    }

    /**
     * Get event description
     *
     * @param WP_Post $event The event post object
     * @return string Event description
     */
    private function get_event_description($event) {
        // Get excerpt if available, otherwise use trimmed content
        if (!empty($event->post_excerpt)) {
            return wp_strip_all_tags($event->post_excerpt);
        }
        
        return wp_trim_words(wp_strip_all_tags($event->post_content), 100);
    }

    /**
     * Get attendee name
     *
     * @param int $attendee_id The attendee ID
     * @return string Attendee name
     */
    private function get_attendee_name($attendee_id) {
        $first_name = get_post_meta($attendee_id, 'etn_first_name', true);
        $last_name = get_post_meta($attendee_id, 'etn_last_name', true);
        
        if ($first_name || $last_name) {
            return trim($first_name . ' ' . $last_name);
        }
        
        // Fallback to full name field
        return get_post_meta($attendee_id, 'etn_name', true);
    }

    /**
     * Check if a WooCommerce order item is an Eventin ticket
     *
     * @param WC_Order_Item $item The order item
     * @return bool True if it's an Eventin ticket
     */
    private function is_eventin_ticket_item($item) {
        $event_id = $item->get_meta('_etn_event_id');
        return !empty($event_id);
    }
}

// Initialize the data extractor
new Eventin_PassSource_Data_Extractor();
