<?php
/**
 * Plugin Name: SA Helper Chatbot
 * Plugin URI: 
 * Description: A custom AI chatbot that provides information about our company, website navigation, and recent news.
 * Version: 1.0.0
 * Author: 
 * Author URI: 
 * Text Domain: sa-helper-chatbot
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('SA_HELPER_VERSION', '1.0.3');
define('SA_HELPER_PATH', plugin_dir_path(__FILE__));
define('SA_HELPER_URL', plugin_dir_url(__FILE__));
define('SA_HELPER_BASENAME', plugin_basename(__FILE__));

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
    }
    
    if (!empty($missing_extensions)) {
        add_action('admin_notices', function() use ($missing_extensions) {
            $message = sprintf(
                __('SA Helper Chatbot requires the following PHP extensions to use Gemini API: %s. Please contact your host to enable these extensions.', 'sa-helper-chatbot'),
                '<strong>' . implode(', ', $missing_extensions) . '</strong>'
            );
            printf('<div class="notice notice-error"><p>%s</p></div>', $message);
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