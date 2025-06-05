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
    <div class="sa-helper-chatbot-button">
        <span class="sa-helper-chatbot-icon">?</span>
    </div>
    
    <div class="sa-helper-chatbot-popup">
        <div class="sa-helper-chatbot-header">
            <div class="sa-helper-chatbot-title"><?php echo esc_html($title); ?></div>
            <div class="sa-helper-chatbot-close">&times;</div>
        </div>
        
        <div class="sa-helper-chatbot-messages">
            <div class="sa-helper-chatbot-message bot">
                <?php echo esc_html($welcome_message); ?>
            </div>
        </div>
        
        <div class="sa-helper-chatbot-input-container">
            <input type="text" class="sa-helper-chatbot-input" placeholder="Type your message...">
            <button class="sa-helper-chatbot-send">Send</button>
        </div>
    </div>
</div>
