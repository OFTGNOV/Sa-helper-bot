/**
 * Public scripts for SA Helper Chatbot
 */
(function($) {
    'use strict';

    // Conversation persistence across pages
    let conversationHistory = [];
    const STORAGE_KEY = 'sa_helper_chatbot_conversation';
    const MAX_HISTORY_ITEMS = 20;    $(document).ready(function() {
        // Initialize conversation history from localStorage
        loadConversationHistory();
        
        // Toggle chatbot popup
        $('.sa-helper-chatbot-button').on('click keydown', function(e) {
            // Handle both click and Enter/Space key presses
            if (e.type === 'click' || (e.type === 'keydown' && (e.which === 13 || e.which === 32))) {
                e.preventDefault();
                $('.sa-helper-chatbot-popup').slideToggle(300);
                $(this).toggleClass('active');
                
                // Update ARIA attributes
                const isOpen = $(this).hasClass('active');
                $('.sa-helper-chatbot-popup').attr('aria-hidden', !isOpen);
                
                // Focus management
                if (isOpen) {
                    setTimeout(() => {
                        $('.sa-helper-chatbot-input').focus();
                    }, 350);
                }
                
                // Load conversation history when chat opens
                if (!$(this).hasClass('history-loaded')) {
                    loadConversationIntoChat();
                    $(this).addClass('history-loaded');                }
            }
        });
        
        // Close chatbot popup
        $('.sa-helper-chatbot-close').on('click keydown', function(e) {
            // Handle both click and Enter/Space key presses
            if (e.type === 'click' || (e.type === 'keydown' && (e.which === 13 || e.which === 32))) {
                e.preventDefault();
                $('.sa-helper-chatbot-popup').slideUp(300);
                $('.sa-helper-chatbot-button').removeClass('active');
                $('.sa-helper-chatbot-popup').attr('aria-hidden', true);
                
                // Return focus to chat button
                setTimeout(() => {
                    $('.sa-helper-chatbot-button').focus();
                }, 350);
            }
        });        // Send message when clicking the send button
        $('.sa-helper-chatbot-send').on('click', function() {
            sendMessage();
        });
        
        // Send message when pressing Enter
        $('.sa-helper-chatbot-input').on('keypress', function(e) {
            if (e.which === 13 && !e.shiftKey) { // Enter key (not Shift+Enter)
                e.preventDefault();
                sendMessage();
            }
        });

        // Handle Escape key to close chat
        $(document).on('keydown', function(e) {
            if (e.which === 27 && $('.sa-helper-chatbot-popup').is(':visible')) { // Escape key
                $('.sa-helper-chatbot-close').trigger('click');
            }
        });// Function to send message
        function sendMessage() {
            const $input = $('.sa-helper-chatbot-input');
            const message = $input.val().trim();
            
            if (message === '') return; // Added return to prevent empty messages
            
            // Clear input
            $input.val('');
            
            // Add user message to chat and history
            addMessage('user', message);
            addToConversationHistory('user', message);
            
            // Show typing indicator
            showTypingIndicator();

            // Get enhanced page content
            const pageContent = getEnhancedPageContent();
            
            // Send message to server
            $.ajax({
                url: saHelperChatbot.ajax_url,
                type: 'POST',
                data: {
                    action: 'sa_helper_chatbot_message',
                    nonce: saHelperChatbot.nonce,
                    message: message,
                    page_content: pageContent,
                    page_url: window.location.href,
                    page_title: document.title
                },
                success: function(response) {
                    // Hide typing indicator
                    hideTypingIndicator();
                    
                    if (response.success) {
                        // Add bot response to chat and history
                        const botReply = response.data.response;
                        const messageElement = addMessage('bot', botReply);
                        addToConversationHistory('bot', botReply);
                        
                        // Add feedback options - don't add feedback for error responses
                        if (!response.data.error) {
                            addFeedbackOptions(messageElement, message, botReply);
                        }
                    } else {
                        const errorMsg = 'Sorry, I encountered an error. Please try again.';
                        addMessage('bot', errorMsg);
                        addToConversationHistory('bot', errorMsg);
                    }
                    
                    // Scroll to bottom
                    scrollToBottom();
                },
                error: function() {
                    // Hide typing indicator
                    hideTypingIndicator();
                    
                    const errorMsg = 'Sorry, I encountered an error. Please try again.';
                    addMessage('bot', errorMsg);
                    addToConversationHistory('bot', errorMsg);
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

        // Enhanced page content extraction
        function getEnhancedPageContent() {
            let pageContent = '';
            
            try {
                // Priority order for content extraction
                const contentSelectors = [
                    'main',
                    '[role="main"]',
                    '.content',
                    '.main-content',
                    '#content',
                    '#main',
                    'article',
                    '.post-content',
                    '.entry-content',
                    '.page-content'
                ];
                
                let contentElement = null;
                
                // Find the best content container
                for (const selector of contentSelectors) {
                    contentElement = document.querySelector(selector);
                    if (contentElement && contentElement.innerText.trim().length > 100) {
                        break;
                    }
                }
                
                // Fallback to body if no specific content found
                if (!contentElement) {
                    contentElement = document.body;
                }
                
                if (contentElement) {
                    // Extract text content, removing script and style elements
                    const clonedElement = contentElement.cloneNode(true);
                    
                    // Remove unwanted elements
                    const unwantedSelectors = [
                        'script', 'style', 'nav', 'header', 'footer', 
                        '.sidebar', '.widget', '.advertisement', '.ads',
                        '.sa-helper-chatbot-container', '.cookie-notice'
                    ];
                    
                    unwantedSelectors.forEach(selector => {
                        const elements = clonedElement.querySelectorAll(selector);
                        elements.forEach(el => el.remove());
                    });
                    
                    pageContent = clonedElement.innerText || clonedElement.textContent || '';
                    
                    // Clean up whitespace and limit length
                    pageContent = pageContent.replace(/\s+/g, ' ').trim();
                    
                    // Limit to 4000 characters to avoid overly long requests
                    if (pageContent.length > 4000) {
                        pageContent = pageContent.substring(0, 4000) + '...';
                    }
                }
            } catch (e) {
                console.error("Error extracting page content:", e);
                pageContent = document.title || '';
            }
            
            return pageContent;
        }

        // Conversation history management
        function loadConversationHistory() {
            try {
                const stored = localStorage.getItem(STORAGE_KEY);
                if (stored) {
                    conversationHistory = JSON.parse(stored);
                    // Clean old conversations (older than 24 hours)
                    const cutoffTime = Date.now() - (24 * 60 * 60 * 1000);
                    conversationHistory = conversationHistory.filter(item => 
                        item.timestamp > cutoffTime
                    );
                }
            } catch (e) {
                console.error("Error loading conversation history:", e);
                conversationHistory = [];
            }
        }

        function saveConversationHistory() {
            try {
                // Limit history size
                if (conversationHistory.length > MAX_HISTORY_ITEMS) {
                    conversationHistory = conversationHistory.slice(-MAX_HISTORY_ITEMS);
                }
                localStorage.setItem(STORAGE_KEY, JSON.stringify(conversationHistory));
            } catch (e) {
                console.error("Error saving conversation history:", e);
            }
        }

        function addToConversationHistory(sender, message) {
            conversationHistory.push({
                sender: sender,
                message: message,
                timestamp: Date.now(),
                page_url: window.location.href
            });
            saveConversationHistory();
        }

        function loadConversationIntoChat() {
            const $messages = $('.sa-helper-chatbot-messages');
            
            // Clear existing messages except welcome message
            $messages.find('.sa-helper-chatbot-message:not(.welcome-message)').remove();
            
            // Add conversation history to chat
            conversationHistory.forEach(item => {
                if (item.page_url === window.location.href) {
                    // Only show messages from current page
                    const $message = $('<div>')
                        .addClass('sa-helper-chatbot-message')
                        .addClass(item.sender)
                        .addClass('history-message')
                        .text(item.message);
                    $messages.append($message);
                }
            });
            
            scrollToBottom();
        }

        // Clear conversation history function (can be called externally)
        window.saHelperClearHistory = function() {
            conversationHistory = [];
            localStorage.removeItem(STORAGE_KEY);
            $('.sa-helper-chatbot-messages').find('.history-message').remove();
        };
    });

})(jQuery);
