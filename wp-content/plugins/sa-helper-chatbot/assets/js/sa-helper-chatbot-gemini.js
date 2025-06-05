/**
 * Helper functions for Gemini API integration
 */
(function($) {
    'use strict';
    
    // Escape HTML to prevent XSS
    window.saHelperEscapeHtml = function(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };
    
    // Format URLs and basic markdown in text
    window.saHelperFormatText = function(text) {
        if (!text) return '';
        
        // First escape any HTML
        text = saHelperEscapeHtml(text);
        
        // Convert URLs to clickable links
        text = text.replace(
            /(https?:\/\/[^\s]+)/g, 
            '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
        );
        
        // Convert basic markdown formatting
        // Bold: **text** or __text__
        text = text.replace(/(\*\*|__)(.*?)\1/g, '<strong>$2</strong>');
        
        // Italic: *text* or _text_
        text = text.replace(/(\*|_)(.*?)\1/g, '<em>$2</em>');
        
        // Convert line breaks to <br>
        text = text.replace(/\n/g, '<br>');
        
        return text;
    };
    
    // Add feedback buttons to bot responses
    window.saHelperAddFeedbackButtons = function($messageDiv) {
        var $feedback = $('<div class="sa-helper-chatbot-feedback">' +
            '<p>Was this helpful?</p>' +
            '<button class="sa-helper-feedback-btn" data-value="yes">👍 Yes</button>' +
            '<button class="sa-helper-feedback-btn" data-value="no">👎 No</button>' +
            '</div>');
        
        $messageDiv.append($feedback);
        
        // Add click handlers for feedback buttons
        $feedback.find('.sa-helper-feedback-btn').on('click', function() {
            var feedback = $(this).data('value');
            var messageId = $messageDiv.data('id');
            
            // Send feedback to server
            $.ajax({
                url: sa_helper_chatbot.ajax_url,
                type: 'POST',
                data: {
                    action: 'sa_helper_chatbot_feedback',
                    message_id: messageId || 'unknown',
                    feedback: feedback,
                    nonce: sa_helper_chatbot.nonce
                }
            });
            
            // Show thank you message
            $feedback.html('<p>Thank you for your feedback!</p>');
        });
    };
    
})(jQuery);
