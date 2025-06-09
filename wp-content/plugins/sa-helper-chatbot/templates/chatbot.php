<?php
/**
 * Template for the chatbot interface
 *
 * @package    SA_Helper_Chatbot
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>
<div class="sa-helper-chatbot-container">
    <div class="sa-helper-chatbot-button" role="button" aria-label="Open chat support" tabindex="0">
        <span class="sa-helper-chatbot-icon">Need Help?</span>
    </div>
    
    <div class="sa-helper-chatbot-popup" role="dialog" aria-labelledby="chatbot-title" aria-hidden="true">
        <div class="sa-helper-chatbot-header">
            <div class="sa-helper-chatbot-title" id="chatbot-title"><?php echo esc_html($title); ?></div>
            <div class="sa-helper-chatbot-close" role="button" aria-label="Close chat" tabindex="0">&times;</div>
        </div>
          <div class="sa-helper-chatbot-messages" role="log" aria-live="polite" aria-label="Chat messages">
            <div class="sa-helper-chatbot-message bot">
                <?php echo esc_html($welcome_message); ?>
            </div>
        </div>
        
        <div class="sa-helper-chatbot-input-container">
            <input 
                type="text" 
                class="sa-helper-chatbot-input" 
                placeholder="<?php echo esc_attr__('Type your message...', 'sa-helper-chatbot'); ?>"
                aria-label="Chat message input"
                maxlength="500"
            >
            <button 
                class="sa-helper-chatbot-send"
                aria-label="Send message"
                type="button"
            >
                <?php echo esc_html__('Send', 'sa-helper-chatbot'); ?>
            </button>
        </div>
    </div>
</div>
