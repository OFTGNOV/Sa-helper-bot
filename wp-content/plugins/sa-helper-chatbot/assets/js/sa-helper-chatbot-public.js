/**
 * Public scripts for SA Helper Chatbot
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Toggle chatbot popup
        $('.sa-helper-chatbot-button').on('click', function() {
            $('.sa-helper-chatbot-popup').slideToggle(300);
            $(this).toggleClass('active');
        });

        // Close chatbot popup
        $('.sa-helper-chatbot-close').on('click', function() {
            $('.sa-helper-chatbot-popup').slideUp(300);
            $('.sa-helper-chatbot-button').removeClass('active');
        });

        // Send message when clicking the send button
        $('.sa-helper-chatbot-send').on('click', function() {
            sendMessage();
        });

        // Send message when pressing Enter
        $('.sa-helper-chatbot-input').on('keypress', function(e) {
            if (e.which === 13) {
                sendMessage();
            }
        });

        // Function to send message
        function sendMessage() {
            const $input = $('.sa-helper-chatbot-input');
            const message = $input.val().trim();
            
            if (message === '') return;
            
            // Clear input
            $input.val('');
            
            // Add user message to chat
            addMessage('user', message);
            
            // Show typing indicator
            showTypingIndicator();
            
            // Send message to server
            $.ajax({
                url: saHelperChatbot.ajax_url,
                type: 'POST',
                data: {
                    action: 'sa_helper_chatbot_message',
                    nonce: saHelperChatbot.nonce,
                    message: message
                },
                success: function(response) {
                    // Hide typing indicator
                    hideTypingIndicator();
                    
                    if (response.success) {
                        // Add bot response to chat
                        const botReply = response.data.response;
                        const messageElement = addMessage('bot', botReply);
                        
                        // Add feedback options
                        addFeedbackOptions(messageElement, message, botReply);
                    } else {
                        addMessage('bot', 'Sorry, I encountered an error. Please try again.');
                    }
                    
                    // Scroll to bottom
                    scrollToBottom();
                },
                error: function() {
                    // Hide typing indicator
                    hideTypingIndicator();
                    
                    addMessage('bot', 'Sorry, I encountered an error. Please try again.');
                    scrollToBottom();
                }
            });
        }
        
        // Function to add a message to the chat
        function addMessage(sender, message) {
            const $messages = $('.sa-helper-chatbot-messages');
            const $message = $('<div>').addClass('sa-helper-chatbot-message').addClass(sender).text(message);
            $messages.append($message);
            scrollToBottom();
            return $message;
        }
        
        // Function to add feedback options
        function addFeedbackOptions($messageElement, userMessage, botResponse) {
            const $feedback = $('<div>').addClass('sa-helper-chatbot-feedback');
            
            const $thumbsUp = $('<span>').html('👍').attr('title', 'Helpful');
            const $thumbsDown = $('<span>').html('👎').attr('title', 'Not helpful');
            
            $thumbsUp.on('click', function() {
                sendFeedback('positive', userMessage, botResponse);
                $feedback.html('Thanks for your feedback!');
            });
            
            $thumbsDown.on('click', function() {
                sendFeedback('negative', userMessage, botResponse);
                $feedback.html('Thanks for your feedback!');
            });
            
            $feedback.append($thumbsUp).append($thumbsDown);
            $messageElement.after($feedback);
        }
        
        // Function to send feedback
        function sendFeedback(feedbackType, message, response) {
            $.ajax({
                url: saHelperChatbot.ajax_url,
                type: 'POST',
                data: {
                    action: 'sa_helper_chatbot_feedback',
                    nonce: saHelperChatbot.nonce,
                    feedback: feedbackType,
                    message: message,
                    response: response
                }
            });
        }
        
        // Function to show typing indicator
        function showTypingIndicator() {
            const $messages = $('.sa-helper-chatbot-messages');
            const $typing = $('<div>').addClass('sa-helper-chatbot-message bot sa-helper-chatbot-typing')
                .append($('<span>'))
                .append($('<span>'))
                .append($('<span>'));
            
            $messages.append($typing);
            scrollToBottom();
        }
        
        // Function to hide typing indicator
        function hideTypingIndicator() {
            $('.sa-helper-chatbot-typing').remove();
        }
        
        // Function to scroll to bottom of chat
        function scrollToBottom() {
            const $messages = $('.sa-helper-chatbot-messages');
            $messages.scrollTop($messages[0].scrollHeight);
        }
    });

})(jQuery);
