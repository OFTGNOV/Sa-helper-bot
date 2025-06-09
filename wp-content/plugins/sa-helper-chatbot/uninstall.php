<?php
/**
 * Uninstall script for SA Helper Chatbot
 * 
 * This file is executed when the plugin is deleted from the WordPress admin.
 * It removes all plugin data and options.
 *
 * @package SA_Helper_Chatbot
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Remove all plugin options and data
 */
function sa_helper_chatbot_uninstall_cleanup() {
    // Remove plugin options
    delete_option('sa_helper_chatbot_options');
    delete_option('sa_helper_chatbot_knowledge');
    delete_option('sa_helper_chatbot_feedback');
    
    // Remove any transients
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sa_helper_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_sa_helper_%'");
    
    // Clear any scheduled hooks
    wp_clear_scheduled_hook('sa_helper_chatbot_cleanup');
    
    // Remove user meta (if any custom user settings were stored)
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'sa_helper_%'");
    
    // Clear any cached data
    wp_cache_flush();
    
    do_action('sa_helper_chatbot_uninstalled');
}

// Run the cleanup
sa_helper_chatbot_uninstall_cleanup();
