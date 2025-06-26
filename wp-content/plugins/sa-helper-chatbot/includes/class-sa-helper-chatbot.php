<?php
/**
 * The core plugin class.
 *
 * This is used to define admin-specific hooks, internationalization, and
 * public-facing site hooks.
 *
 * @package    SA_Helper_Chatbot
 */
class SA_Helper_Chatbot {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power the plugin.
     *
     * @access   protected
     * @var      SA_Helper_Chatbot_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @access   private
     */
    private function load_dependencies() {
        // The class responsible for orchestrating the actions and filters of the core plugin
        require_once SA_HELPER_PATH . 'includes/class-sa-helper-chatbot-loader.php';
        
        // The class responsible for defining all actions that occur in the admin area
        require_once SA_HELPER_PATH . 'admin/class-sa-helper-chatbot-admin.php';
          // The class responsible for defining all actions that occur in the public-facing side of the site
        require_once SA_HELPER_PATH . 'includes/class-sa-helper-chatbot-public.php';        // The class responsible for handling chatbot responses
        require_once SA_HELPER_PATH . 'includes/class-sa-helper-chatbot-ai.php';
          // Include API testing functionality (admin only) if file exists
        if (is_admin()) {
            $api_test_file = SA_HELPER_PATH . 'admin/class-sa-helper-chatbot-api-test.php';
            if (file_exists($api_test_file)) {
                require_once $api_test_file;
            }
            
            // Include dashboard functionality
            $dashboard_file = SA_HELPER_PATH . 'admin/class-sa-helper-chatbot-dashboard.php';
            if (file_exists($dashboard_file)) {
                require_once $dashboard_file;
            }
        }
        
        $this->loader = new SA_Helper_Chatbot_Loader();
    }

    /**
     * Register all of the hooks related to the admin area functionality of the plugin.
     *
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new SA_Helper_Chatbot_Admin();
        
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_menu_page');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     *
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new SA_Helper_Chatbot_Public();
        
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('wp_footer', $plugin_public, 'display_chatbot');
          // AJAX hooks for the chatbot
        // IMPORTANT: Ensure 'process_message' method in SA_Helper_Chatbot_Public includes nonce verification for security.
        $this->loader->add_action('wp_ajax_sa_helper_chatbot_message', $plugin_public, 'process_message');
        $this->loader->add_action('wp_ajax_nopriv_sa_helper_chatbot_message', $plugin_public, 'process_message');
          // AJAX hooks for feedback
        // IMPORTANT: Ensure 'process_feedback' method in SA_Helper_Chatbot_Public includes nonce verification for security.
        $this->loader->add_action('wp_ajax_sa_helper_chatbot_feedback', $plugin_public, 'process_feedback');
        $this->loader->add_action('wp_ajax_nopriv_sa_helper_chatbot_feedback', $plugin_public, 'process_feedback');
        
        // AJAX hooks for getting initial suggestions
        $this->loader->add_action('wp_ajax_sa_helper_chatbot_initial_suggestions', $plugin_public, 'get_initial_suggestions');
        $this->loader->add_action('wp_ajax_nopriv_sa_helper_chatbot_initial_suggestions', $plugin_public, 'get_initial_suggestions');
        
        // AJAX hooks for getting contextual suggestions
        $this->loader->add_action('wp_ajax_sa_helper_chatbot_contextual_suggestions', $plugin_public, 'get_contextual_suggestions');
        $this->loader->add_action('wp_ajax_nopriv_sa_helper_chatbot_contextual_suggestions', $plugin_public, 'get_contextual_suggestions');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        $this->loader->run();
    }
}
