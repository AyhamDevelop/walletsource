<?php
/**
 * Plugin Name: Eventin PassSource Integration
 * Plugin URI: https://saharasmokes.ca
 * Description: Integrates Eventin Pro with PassSource to generate digital wallet passes for Apple Wallet and Google Pay.
 * Version: 1.0.0
 * Author: Ayham Hadeed
 * Author URI: https://saharasmokes.ca
 * Text Domain: eventin-passource
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 */
define('EVENTIN_PASSOURCE_VERSION', '1.0.0');

/**
 * Plugin base name.
 */
define('EVENTIN_PASSOURCE_BASENAME', plugin_basename(__FILE__));

/**
 * Plugin path.
 */
define('EVENTIN_PASSOURCE_PATH', plugin_dir_path(__FILE__));

/**
 * Plugin URL.
 */
define('EVENTIN_PASSOURCE_URL', plugin_dir_url(__FILE__));

/**
 * Check if Eventin Pro is active
 */
function eventin_passource_check_dependencies() {
    if (!class_exists('wpeventin')) {
        add_action('admin_notices', 'eventin_passource_dependency_notice');
        return false;
    }
    return true;
}

/**
 * Dependency notice if Eventin Pro is not active
 */
function eventin_passource_dependency_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('Eventin PassSource Integration requires Eventin Pro to be installed and activated.', 'eventin-passource'); ?></p>
    </div>
    <?php
}

/**
 * The code that runs during plugin activation.
 */
function activate_eventin_passource() {
    if (!eventin_passource_check_dependencies()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Eventin PassSource Integration requires Eventin Pro to be installed and activated.', 'eventin-passource'));
    }
    
    // Create necessary database tables or options
    add_option('eventin_passource_settings', array(
        'client_hash' => '',
        'template_hash' => '',
        'enable_checkout_button' => 'yes',
        'enable_email_button' => 'yes',
        'button_style' => 'both',
    ));
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_eventin_passource() {
    // Clean up if needed
}

register_activation_hook(__FILE__, 'activate_eventin_passource');
register_deactivation_hook(__FILE__, 'deactivate_eventin_passource');

/**
 * Load plugin textdomain.
 */
function eventin_passource_load_textdomain() {
    load_plugin_textdomain('eventin-passource', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'eventin_passource_load_textdomain');

/**
 * Initialize the plugin.
 */
function eventin_passource_init() {
    if (!eventin_passource_check_dependencies()) {
        return;
    }

    // Include required files
    require_once EVENTIN_PASSOURCE_PATH . 'includes/class-eventin-passource.php';
    
    // Initialize the main plugin class
    $plugin = new Eventin_PassSource();
    $plugin->run();
}
add_action('plugins_loaded', 'eventin_passource_init', 20);
