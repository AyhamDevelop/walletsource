<?php
/**
 * Test script for Eventin PassSource integration
 *
 * This file contains test functions to validate the functionality
 * of the Eventin PassSource integration plugin.
 *
 * @since      1.0.0
 * @package    Eventin_PassSource
 */

class Eventin_PassSource_Test {

    /**
     * Run all tests
     */
    public function run_all_tests() {
        $this->test_settings();
        $this->test_api_connection();
        $this->test_data_extraction();
        $this->test_pass_generation();
        $this->test_button_rendering();
    }

    /**
     * Test plugin settings
     */
    public function test_settings() {
        echo "=== Testing Plugin Settings ===\n";
        
        // Check if settings exist
        $settings = get_option('eventin_passource_settings', array());
        
        if (empty($settings)) {
            echo "❌ Settings not found. Please configure the plugin settings.\n";
            return;
        }
        
        // Check required settings
        $required_settings = array(
            'client_hash' => 'PassSource Client Hash',
            'template_hash' => 'PassSource Template Hash',
        );
        
        $missing_settings = array();
        
        foreach ($required_settings as $key => $label) {
            if (empty($settings[$key])) {
                $missing_settings[] = $label;
            }
        }
        
        if (!empty($missing_settings)) {
            echo "❌ Missing required settings: " . implode(', ', $missing_settings) . "\n";
        } else {
            echo "✅ All required settings are configured.\n";
        }
        
        // Check optional settings
        echo "Optional settings:\n";
        echo "- Button Style: " . (isset($settings['button_style']) ? $settings['button_style'] : 'both') . "\n";
        echo "- Enable Checkout Button: " . (isset($settings['enable_checkout_button']) ? $settings['enable_checkout_button'] : 'yes') . "\n";
        echo "- Enable Email Button: " . (isset($settings['enable_email_button']) ? $settings['enable_email_button'] : 'yes') . "\n";
    }

    /**
     * Test API connection to PassSource
     */
    public function test_api_connection() {
        echo "\n=== Testing PassSource API Connection ===\n";
        
        // Create API handler instance
        $api_handler = new Eventin_PassSource_API_Handler();
        
        // Verify credentials
        $result = $api_handler->verify_credentials();
        
        if ($result['success']) {
            echo "✅ API connection successful: " . $result['message'] . "\n";
            
            if (isset($result['template_info'])) {
                echo "Template information:\n";
                foreach ($result['template_info'] as $key => $value) {
                    if (is_string($value)) {
                        echo "- $key: $value\n";
                    }
                }
            }
        } else {
            echo "❌ API connection failed: " . $result['message'] . "\n";
        }
    }

    /**
     * Test data extraction from Eventin Pro
     */
    public function test_data_extraction() {
        echo "\n=== Testing Eventin Pro Data Extraction ===\n";
        
        // Check if Eventin Pro is active
        if (!class_exists('Etn')) {
            echo "❌ Eventin Pro is not active. Please activate it to test data extraction.\n";
            return;
        }
        
        // Get a recent order with Eventin tickets
        $order_id = $this->get_recent_eventin_order();
        
        if (!$order_id) {
            echo "❌ No recent orders with Eventin tickets found. Please create a test order.\n";
            return;
        }
        
        echo "✅ Found recent order #$order_id with Eventin tickets.\n";
        
        // Create data extractor instance
        $data_extractor = new Eventin_PassSource_Data_Extractor();
        
        // Get attendees from order
        $attendees = $this->get_attendees_from_order($order_id);
        
        if (empty($attendees)) {
            echo "❌ No attendees found for order #$order_id.\n";
            return;
        }
        
        echo "✅ Found " . count($attendees) . " attendees for order #$order_id.\n";
        
        // Extract ticket data from first attendee
        $attendee = $attendees[0];
        $ticket_data = $this->extract_ticket_data_from_attendee($attendee);
        
        if (empty($ticket_data)) {
            echo "❌ Failed to extract ticket data from attendee #" . $attendee->ID . ".\n";
            return;
        }
        
        echo "✅ Successfully extracted ticket data from attendee #" . $attendee->ID . ".\n";
        echo "Extracted data:\n";
        foreach ($ticket_data as $key => $value) {
            echo "- $key: $value\n";
        }
    }

    /**
     * Test pass generation
     */
    public function test_pass_generation() {
        echo "\n=== Testing Pass Generation ===\n";
        
        // Get a recent order with Eventin tickets
        $order_id = $this->get_recent_eventin_order();
        
        if (!$order_id) {
            echo "❌ No recent orders with Eventin tickets found. Please create a test order.\n";
            return;
        }
        
        // Get attendees from order
        $attendees = $this->get_attendees_from_order($order_id);
        
        if (empty($attendees)) {
            echo "❌ No attendees found for order #$order_id.\n";
            return;
        }
        
        // Extract ticket data from first attendee
        $attendee = $attendees[0];
        $ticket_data = $this->extract_ticket_data_from_attendee($attendee);
        
        if (empty($ticket_data)) {
            echo "❌ Failed to extract ticket data from attendee #" . $attendee->ID . ".\n";
            return;
        }
        
        // Create API handler instance
        $api_handler = new Eventin_PassSource_API_Handler();
        
        // Generate pass
        $pass_url = $api_handler->generate_wallet_pass($ticket_data, $order_id);
        
        if ($pass_url) {
            echo "✅ Successfully generated pass for order #$order_id, attendee #" . $attendee->ID . ".\n";
            echo "Pass URL: $pass_url\n";
        } else {
            echo "❌ Failed to generate pass for order #$order_id, attendee #" . $attendee->ID . ".\n";
        }
    }

    /**
     * Test button rendering
     */
    public function test_button_rendering() {
        echo "\n=== Testing Button Rendering ===\n";
        
        // Get a recent order with Eventin tickets
        $order_id = $this->get_recent_eventin_order();
        
        if (!$order_id) {
            echo "❌ No recent orders with Eventin tickets found. Please create a test order.\n";
            return;
        }
        
        // Check if pass URL exists
        $pass_url = get_post_meta($order_id, '_eventin_passource_pass_url', true);
        
        if (empty($pass_url)) {
            echo "❌ No pass URL found for order #$order_id. Please generate a pass first.\n";
            return;
        }
        
        // Create wallet buttons instance
        $wallet_buttons = new Eventin_PassSource_Wallet_Buttons();
        
        // Test button rendering for website
        $buttons_html = $wallet_buttons->get_wallet_buttons_html($pass_url, 'both', false);
        
        if (!empty($buttons_html)) {
            echo "✅ Successfully rendered buttons for website.\n";
            echo "Button HTML length: " . strlen($buttons_html) . " characters\n";
        } else {
            echo "❌ Failed to render buttons for website.\n";
        }
        
        // Test button rendering for email
        $buttons_html_email = $wallet_buttons->get_wallet_buttons_html($pass_url, 'both', true);
        
        if (!empty($buttons_html_email)) {
            echo "✅ Successfully rendered buttons for email.\n";
            echo "Email button HTML length: " . strlen($buttons_html_email) . " characters\n";
        } else {
            echo "❌ Failed to render buttons for email.\n";
        }
    }

    /**
     * Get a recent order with Eventin tickets
     *
     * @return int|false Order ID or false if not found
     */
    private function get_recent_eventin_order() {
        global $wpdb;
        
        // Query to find orders with Eventin tickets
        $query = "
            SELECT p.ID
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-completed', 'wc-processing')
            AND oim.meta_key = '_etn_event_id'
            ORDER BY p.post_date DESC
            LIMIT 1
        ";
        
        return $wpdb->get_var($query);
    }

    /**
     * Get attendees from order
     *
     * @param int $order_id Order ID
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
     * @param WP_Post $attendee Attendee post object
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
     * @param int $event_id Event ID
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
     * @param int $event_id Event ID
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
     * @param WP_Post $event Event post object
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
     * @param int $attendee_id Attendee ID
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
}

// Run tests if this file is executed directly
if (isset($_GET['run_tests']) && $_GET['run_tests'] === 'true') {
    $tester = new Eventin_PassSource_Test();
    $tester->run_all_tests();
}
