<?php
/**
 * Plugin Name: SA Helper Chatbot
 * Plugin URI: 
 * Description: A custom AI chatbot that provides information about our company, website navigation, and recent news. Features enhanced Gemini API integration with intelligent fallback methods and conversation persistence.
 * Version: 1.0.0
 * Update Message: Minor bug fixes and changes to CSS.
 * Author: Tamai Richards
 * Author URI: https://oftgnov.github.io/Tamai.com/
 * Text Domain: sa-helper-chatbot
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('SA_HELPER_VERSION', '1.0.0');
define('SA_HELPER_PATH', plugin_dir_path(__FILE__));
define('SA_HELPER_URL', plugin_dir_url(__FILE__));
define('SA_HELPER_BASENAME', plugin_basename(__FILE__));

/**
 * Plugin activation hook
 */
function sa_helper_chatbot_activate() {
    // Set default options if they don't exist
    $default_options = array(
        'general' => array(
            'enable' => true,
            'title' => 'Helper Bot',
            'welcome_message' => '**Hello!** How can I help you today? 😊'
        ),
        'gemini_api' => array(
            'enable' => false,
            'api_key' => '',
            'model' => 'gemini-1.5-pro',
            'include_page_content' => true,
            'temperature' => 0.7,
            'max_tokens' => 800
        )
    );
    
    $existing_options = get_option('sa_helper_chatbot_options', array());
    $merged_options = array_replace_recursive($default_options, $existing_options);
    update_option('sa_helper_chatbot_options', $merged_options);
    
    // Set default knowledge base if empty
    $default_knowledge = array(
        'company_info' => 'Welcome to our company! We provide excellent services and support.',
        'website_navigation' => 'Use the main menu to navigate our site. You can find our services, about page, and contact information in the header.',
        'recent_news' => 'Stay tuned for our latest updates and announcements.',
        'faq' => 'Here are some frequently asked questions and their answers.'
    );
    
    $existing_knowledge = get_option('sa_helper_chatbot_knowledge', array());
    if (empty($existing_knowledge)) {
        update_option('sa_helper_chatbot_knowledge', $default_knowledge);
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    do_action('sa_helper_chatbot_activated');
}

/**
 * Plugin deactivation hook
 */
function sa_helper_chatbot_deactivate() {
    // Clean up any scheduled events
    wp_clear_scheduled_hook('sa_helper_chatbot_cleanup');
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    do_action('sa_helper_chatbot_deactivated');
}

/**
 * Plugin uninstall hook (defined in separate uninstall.php file)
 */

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'sa_helper_chatbot_activate');
register_deactivation_hook(__FILE__, 'sa_helper_chatbot_deactivate');

/**
 * Check if required PHP extensions are available
 */
function sa_helper_check_requirements() {
    $required_extensions = array('curl', 'json');
    $missing_extensions = array();
    
    foreach ($required_extensions as $ext) {
        if (!extension_loaded($ext)) {
            $missing_extensions[] = $ext;
        }
    }      if (!empty($missing_extensions)) {
        add_action('admin_notices', function() use ($missing_extensions) {
            $message = sprintf(
                /* translators: %s: List of missing PHP extensions */
                __('SA Helper Chatbot requires the following PHP extensions to use Gemini API: %s. Please contact your host to enable these extensions.', 'sa-helper-chatbot'),
                '<strong>' . esc_html(implode(', ', $missing_extensions)) . '</strong>'
            );
            printf('<div class="notice notice-error"><p>%s</p></div>', wp_kses_post($message));
        });
        return false;
    } 
    return true;
}

/**
 * Begins execution of the plugin after all plugins are loaded.
 */
function sa_helper_chatbot_init() {
    // Only run the plugin if requirements are met
    if (sa_helper_check_requirements()) {
        // Load the main plugin class file
        require_once SA_HELPER_PATH . 'includes/class-sa-helper-chatbot.php';
        
        // Instantiate and run the plugin
        $plugin = new SA_Helper_Chatbot();
        $plugin->run();
    }
}

// Start the plugin on plugins_loaded hook
add_action('plugins_loaded', 'sa_helper_chatbot_init');