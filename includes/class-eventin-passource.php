<?php
/**
 * The main plugin class
 *
 * @since      1.0.0
 * @package    Eventin_PassSource
 */

class Eventin_PassSource {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Eventin_PassSource_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * Define the core functionality of the plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the core plugin.
         */
        require_once EVENTIN_PASSOURCE_PATH . 'includes/class-eventin-passource-loader.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once EVENTIN_PASSOURCE_PATH . 'admin/class-eventin-passource-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing side of the site.
         */
        require_once EVENTIN_PASSOURCE_PATH . 'public/class-eventin-passource-public.php';

        /**
         * The class responsible for handling PassSource API integration.
         */
        require_once EVENTIN_PASSOURCE_PATH . 'includes/class-eventin-passource-api.php';

        /**
         * The class responsible for handling data extraction from Eventin Pro.
         */
        require_once EVENTIN_PASSOURCE_PATH . 'includes/class-eventin-passource-data.php';

        $this->loader = new Eventin_PassSource_Loader();
    }

    /**
     * Register all of the hooks related to the admin area functionality of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Eventin_PassSource_Admin();

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        
        // Add settings page
        $this->loader->add_filter('eventin_settings_tabs', $plugin_admin, 'add_settings_tab');
        $this->loader->add_filter('eventin_settings_fields', $plugin_admin, 'add_settings_fields');
    }

    /**
     * Register all of the hooks related to the public-facing functionality of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Eventin_PassSource_Public();
        $plugin_api = new Eventin_PassSource_API();
        $plugin_data = new Eventin_PassSource_Data();

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Hook into Eventin Pro checkout process
        $this->loader->add_action('eventin_after_checkout_complete', $plugin_data, 'extract_ticket_data', 10, 2);
        
        // Generate wallet pass after ticket data extraction
        $this->loader->add_action('eventin_passource_data_extracted', $plugin_api, 'generate_wallet_pass', 10, 2);
        
        // Add wallet button to checkout success page
        $this->loader->add_action('eventin_after_checkout_content', $plugin_public, 'display_wallet_button', 10, 1);
        
        // Add wallet button to confirmation email
        $this->loader->add_filter('eventin_email_template_content', $plugin_public, 'add_wallet_button_to_email', 10, 3);
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }
}
