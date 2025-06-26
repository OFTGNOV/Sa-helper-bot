/**
 * Public scripts for SA Helper Chatbot
 */
(function($) {
    'use strict';    // Cached DOM elements for performance
    let $chatButton, $chatPopup, $chatInput, $chatMessages, $chatClose, $chatSend, $chatSuggestions, $suggestionsToggle;
    
    // Conversation persistence across pages using sessionStorage
    let conversationHistory = [];
    const STORAGE_KEY = 'sa_helper_chatbot_session_conversation';
    const MAX_HISTORY_ITEMS = 20;
    
    // Performance constants
    const ANIMATION_DURATION = 300;
    const FOCUS_DELAY = 350;
    const SCROLL_DELAY = 50;
    
    // Suggestions state
    let suggestionsVisible = false;
    let suggestionsEnabled = true;

    $(document).ready(function() {
        // Initialize performance: cache DOM elements once
        initializeDOMCache();
        
        // Initialize conversation history from sessionStorage
        loadConversationHistory();
        
        // Bind event handlers
        bindEventHandlers();        // Apply initial styling on page load after DOM is ready
        applyThemeSpecificStyling();
        
        // Load initial suggestions when chat opens for the first time
        loadInitialSuggestions();
    });
      /**
     * Cache DOM elements for performance optimization
     */
    function initializeDOMCache() {
        $chatButton = $('.sa-helper-chatbot-button');
        $chatPopup = $('.sa-helper-chatbot-popup');
        $chatInput = $('.sa-helper-chatbot-input');
        $chatMessages = $('.sa-helper-chatbot-messages');
        $chatClose = $('.sa-helper-chatbot-close');
        $chatSend = $('.sa-helper-chatbot-send');
        $chatSuggestions = $('.sa-helper-chatbot-suggestions');
        $suggestionsToggle = $('.suggestions-toggle');
    }
      /**
     * Bind all event handlers in one place for better organization
     */
    function bindEventHandlers() {
        // Toggle chatbot popup with unified handler
        $chatButton.on('click keydown', handleChatToggle);
        
        // Close chatbot popup with unified handler  
        $chatClose.on('click keydown', handleChatClose);
        
        // Send message handlers
        $chatSend.on('click', sendMessage);
        $chatInput.on('keypress', handleInputKeypress);
        
        // Activity tracking
        $chatInput.on('input focus', handleInputActivity);
        
        // Global escape key handler
        $(document).on('keydown', handleGlobalKeydown);
    }
      /**
     * Unified chat toggle handler
     */
    function handleChatToggle(e) {
        if (e.type === 'click' || (e.type === 'keydown' && (e.which === 13 || e.which === 32))) {
            e.preventDefault();
            
            const isOpening = !$chatButton.hasClass('active');
            
            $chatPopup.slideToggle(ANIMATION_DURATION);
            $chatButton.toggleClass('active');
            $chatPopup.attr('aria-hidden', !isOpening);
            
            // Handle mobile body scroll prevention
            if (isOpening) {
                $('body').addClass('sa-helper-chatbot-open');
                setTimeout(() => {
                    $chatInput.focus();
                    applyThemeSpecificStyling();
                }, FOCUS_DELAY);
                  if (!$chatButton.hasClass('history-loaded')) {
                    loadConversationIntoChat();
                    $chatButton.addClass('history-loaded');
                    
                    // Load initial suggestions if conversation is empty
                    if (conversationHistory.length === 0) {
                        setTimeout(loadInitialSuggestions, 500);
                    }
                }
            } else {
                $('body').removeClass('sa-helper-chatbot-open');
            }
        }
    }
      /**
     * Unified chat close handler
     */
    function handleChatClose(e) {
        if (e.type === 'click' || (e.type === 'keydown' && (e.which === 13 || e.which === 32))) {
            e.preventDefault();
            $chatPopup.slideUp(ANIMATION_DURATION);
            $chatButton.removeClass('active');
            $chatPopup.attr('aria-hidden', true);
            
            // Clean up suggestions state when closing
            hideSuggestions();
            removeShowSuggestionsButton();
            suggestionsEnabled = true; // Reset for next opening
            
            // Remove mobile body scroll prevention
            $('body').removeClass('sa-helper-chatbot-open');
            
            setTimeout(() => {
                $chatButton.focus();
            }, FOCUS_DELAY);
        }
    }
    
    /**
     * Handle input keypress events
     */
    function handleInputKeypress(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    }
    
    /**
     * Handle global keydown events
     */
    function handleGlobalKeydown(e) {
        if (e.which === 27 && $chatPopup.is(':visible')) {
            $chatClose.trigger('click');
        }
    }    /**
     * Send message function with optimized AJAX handling
     */
    function sendMessage() {
        const message = $chatInput.val().trim();
        
        if (message === '') return;
        
        // Hide suggestions when user sends a message
        hideSuggestions();
        
        $chatInput.val('');
        addMessage('user', message);
        addToConversationHistory('user', message);
        showTypingIndicator();

        const pageContent = getEnhancedPageContent();
        performAjaxRequest(message, pageContent);
    }
    
    /**
     * Optimized AJAX request with unified error handling
     */
    function performAjaxRequest(message, pageContent) {
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
                handleAjaxSuccess(response, message);
            },
            error: function() {
                handleAjaxError();
            }
        });
    }
      /**
     * Handle successful AJAX response
     */
    function handleAjaxSuccess(response, userMessage) {
        hideTypingIndicator();
        
        if (response.success) {
            const botReply = response.data.response;
            const messageElement = addMessage('bot', botReply);
            addToConversationHistory('bot', botReply);
            
            // Handle suggestions if provided
            if (response.data.suggestions && response.data.suggestions.length > 0) {
                displaySuggestions(response.data.suggestions);
            }
            
            if (!response.data.error) {
                addFeedbackOptions(messageElement, userMessage, botReply);
            }
        } else {
            const errorMsg = 'Sorry, I encountered an error. Please try again.';
            addMessage('bot', errorMsg);
            addToConversationHistory('bot', errorMsg);
        }
        
        scrollToBottom();
    }
    
    /**
     * Handle AJAX error
     */
    function handleAjaxError() {
        hideTypingIndicator();
        
        const errorMsg = 'Sorry, I encountered an error. Please try again.';
        addMessage('bot', errorMsg);
        addToConversationHistory('bot', errorMsg);
        scrollToBottom();
    }    
    
    /**
     * Add message to chat
     */
    function addMessage(sender, message) {
        const $message = $('<div>').addClass('sa-helper-chatbot-message').addClass(sender);
        
        if (sender === 'bot') {
            $message.html(safeMarkdownRender(message));
            $message.find('a[href^="http"]').attr('target', '_blank').attr('rel', 'noopener noreferrer');
        } else {
            $message.text(message);
        }
        
        $chatMessages.append($message);
        
        setTimeout(() => {
            scrollToBottom();
        }, SCROLL_DELAY);
        
        return $message;
    }

    // Ensure marked.js and DOMPurify are loaded
    if (typeof marked === 'undefined' || typeof DOMPurify === 'undefined') {
        console.error('Markdown rendering libraries (marked.js or DOMPurify) are not loaded.');
        return;
    }

    // Cache configuration for marked.js
    if (!window.markedConfigured) {
        marked.setOptions({
            breaks: true,
            gfm: true,
            sanitize: false // We'll use DOMPurify instead
        });
        window.markedConfigured = true;
    }

    // Add fallback for Markdown rendering
    function safeMarkdownRender(message) {
        try {
            if (typeof message !== 'string' || message.trim() === '') {
                console.warn('Invalid markdown input:', message);
                return message; // Fallback to plain text
            }

            const rawHtml = marked.parse(message);
            const sanitizedHtml = DOMPurify.sanitize(rawHtml, {
                ALLOWED_TAGS: ['p', 'strong', 'em', 'code', 'pre', 'ul', 'ol', 'li', 'a', 'br', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
                ALLOWED_ATTR: ['href', 'title', 'target'],
                ALLOWED_URI_REGEXP: /^(?:(?:(?:f|ht)tps?|mailto|tel|callto|cid|xmpp):|[^a-z]|[a-z+\.\-]+(?:[^a-z+\.\-:]|$))/i
            });

            return sanitizedHtml;
        } catch (e) {
            console.error('Error rendering Markdown:', e);
            return message; // Fallback to plain text
        }
    }

    /**
     * Add feedback options
     */
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
    
    /**
     * Send feedback (optimized)
     */
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
    
    /**
     * Show typing indicator (optimized)
     */
    function showTypingIndicator() {
        const $typing = $('<div>').addClass('sa-helper-chatbot-message bot sa-helper-chatbot-typing')
            .append($('<span>'))
            .append($('<span>'))
            .append($('<span>'));
        
        $chatMessages.append($typing);
        scrollToBottom();
    }
    
    /**
     * Hide typing indicator (optimized)
     */
    function hideTypingIndicator() {
        $('.sa-helper-chatbot-typing').remove();
    }
    
    /**
     * Scroll to bottom with cached element (optimized)
     */
    function scrollToBottom() {
        if ($chatMessages.length) {
            const scrollHeight = $chatMessages[0].scrollHeight;
            $chatMessages.stop().animate({
                scrollTop: scrollHeight
            }, ANIMATION_DURATION);
        }
    }

    /**
     * Enhanced page content extraction (memoized for performance)
     */
    let cachedPageContent = null; // Initialize cachedPageContent here
    function getEnhancedPageContent() {
        if (cachedPageContent !== null) {
            return cachedPageContent;
        }
        
        let pageContent = '';
        
        try {
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
            
            for (const selector of contentSelectors) {
                contentElement = document.querySelector(selector);
                if (contentElement && contentElement.innerText.trim().length > 100) {
                    break;
                }
            }
            
            if (!contentElement) {
                contentElement = document.body;
            }
            
            if (contentElement) {
                const clonedElement = contentElement.cloneNode(true);
                
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
                pageContent = pageContent.replace(/\s+/g, ' ').trim();
                
                if (pageContent.length > 4000) {
                    pageContent = pageContent.substring(0, 4000) + '...';
                }
            }
        } catch (e) {
            if (DEBUG_MODE) {
                console.error("Error extracting page content:", e);
            }
            pageContent = document.title || '';
        }
        
        cachedPageContent = pageContent;
        return pageContent;
    }

    /**
     * Conversation history management (optimized)
     */
    function loadConversationHistory() {
        try {
            const stored = sessionStorage.getItem(STORAGE_KEY);
            if (stored) {
                conversationHistory = JSON.parse(stored);
                if (conversationHistory.length > MAX_HISTORY_ITEMS) {
                    conversationHistory = conversationHistory.slice(-MAX_HISTORY_ITEMS);
                    saveConversationHistory();
                }
            }
        } catch (e) {
            conversationHistory = [];
        }
    }

    function saveConversationHistory() {
        try {
            if (conversationHistory.length > MAX_HISTORY_ITEMS) {
                conversationHistory = conversationHistory.slice(-MAX_HISTORY_ITEMS);
            }
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(conversationHistory));
        } catch (e) {
            // Silent fail for storage issues
        }
    }

    function addToConversationHistory(sender, message) {
        conversationHistory.push({
            sender: sender,
            message: message,
            timestamp: Date.now(),
            page_url: window.location.href,
            page_title: document.title
        });
        saveConversationHistory();
    }    function loadConversationIntoChat() {
        // Clear existing messages except welcome message
        $chatMessages.find('.sa-helper-chatbot-message:not(.welcome-message)').remove();

        // Batch DOM operations for performance
        const messagesHTML = conversationHistory.map(item => {
            const titleAttr = (item.page_url !== window.location.href && item.page_title) 
                ? ` title="From: ${item.page_title}"` : '';
            const crossPageClass = (item.page_url !== window.location.href) 
                ? ' cross-page-message' : '';
            return `<div class="sa-helper-chatbot-message ${item.sender} history-message${crossPageClass}"${titleAttr}></div>`;
        }).join('');

        $chatMessages.append(messagesHTML);

        // Add content safely with Markdown support for bot messages
        conversationHistory.forEach((item, index) => {
            const $messageElement = $chatMessages.find('.history-message').eq(index);
            if (item.sender === 'bot' && typeof marked !== 'undefined' && typeof DOMPurify !== 'undefined') {
                $messageElement.html(safeMarkdownRender(item.message));
                $messageElement.find('a[href^="http"]').attr('target', '_blank').attr('rel', 'noopener noreferrer');
            } else {
                $messageElement.text(item.message);
            }
        });

        setTimeout(() => {
            scrollToBottom();
        }, 100);
    }
    
    /**
     * Theme-specific styling (optimized)
     */
    function applyThemeSpecificStyling() {
        const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if ($chatInput) {
            $chatInput.css({
                color: isDarkMode ? '#e0e0e0' : '#333333',
                backgroundColor: isDarkMode ? '#2d2d2d' : '#f9fafb'
            });
        }
    }

    // Listen for theme changes
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', applyThemeSpecificStyling);
    }

    /**
     * Load initial suggestions when chat first opens
     */
    function loadInitialSuggestions() {
        // Only load if conversation history is empty and suggestions are enabled
        if (conversationHistory.length === 0 && suggestionsEnabled) {
            $.ajax({
                url: saHelperChatbot.ajax_url,
                type: 'POST',
                data: {
                    action: 'sa_helper_chatbot_initial_suggestions',
                    nonce: saHelperChatbot.nonce
                },
                success: function(response) {
                    if (response.success && response.data.suggestions) {
                        displaySuggestions(response.data.suggestions);
                    }
                },
                error: function() {
                    // Silent fail - suggestions are not critical
                }
            });
        }
    }    
    
    /**
     * Display suggestion buttons with toggle functionality
     */
    function displaySuggestions(suggestions) {
        if (!suggestions || suggestions.length === 0 || !suggestionsEnabled) {
            hideSuggestions();
            return;
        }
        
        // Find the input container
        const $inputContainer = $('.sa-helper-chatbot-input-container');
        
        // Create or update suggestions container
        let $suggestionsContainer = $inputContainer.find('.sa-helper-chatbot-suggestions');
        if ($suggestionsContainer.length === 0) {
            $suggestionsContainer = $('<div>').addClass('sa-helper-chatbot-suggestions');
            $inputContainer.append($suggestionsContainer);
            $chatSuggestions = $suggestionsContainer; // Update cached element
        }
        
        // Clear existing suggestions
        $suggestionsContainer.empty();
        
        // Add toggle button
        const $toggleButton = $('<button>')
            .addClass('suggestions-toggle')
            .attr('type', 'button')
            .attr('tabindex', '0')
            .attr('aria-label', 'Hide suggestions')
            .text('Hide')
            .on('click keydown', function(e) {
                if (e.type === 'click' || (e.type === 'keydown' && (e.which === 13 || e.which === 32))) {
                    e.preventDefault();
                    toggleSuggestions();
                }
            });
        
        $suggestionsContainer.append($toggleButton);
        
        // Add title for suggestions
        const $title = $('<div>').addClass('suggestions-title').text('Quick suggestions');
        $suggestionsContainer.append($title);
        
        // Create suggestion buttons
        const $buttonsContainer = $('<div>').addClass('suggestions-buttons');
        suggestions.forEach(function(suggestion, index) {
            const $button = $('<button>')
                .addClass('suggestion-button')
                .attr('type', 'button')
                .attr('tabindex', '0')
                .attr('aria-label', 'Select suggestion: ' + suggestion)
                .text(suggestion)
                .on('click', function() {
                    selectSuggestion(suggestion);
                })
                .on('keydown', function(e) {
                    if (e.which === 13 || e.which === 32) {
                        e.preventDefault();
                        selectSuggestion(suggestion);
                    }
                    // Handle arrow key navigation
                    else if (e.which === 38 || e.which === 40) { // Up/Down arrows
                        e.preventDefault();
                        const $buttons = $suggestionsContainer.find('.suggestion-button');
                        const currentIndex = $buttons.index(this);
                        let nextIndex;
                        
                        if (e.which === 38) { // Up arrow
                            nextIndex = currentIndex > 0 ? currentIndex - 1 : $buttons.length - 1;
                        } else { // Down arrow
                            nextIndex = currentIndex < $buttons.length - 1 ? currentIndex + 1 : 0;
                        }
                        
                        $buttons.eq(nextIndex).focus();
                    }
                    // Escape key to hide suggestions
                    else if (e.which === 27) {
                        hideSuggestions();
                        $chatInput.focus();
                    }
                });
            
            $buttonsContainer.append($button);
        });
        
        $suggestionsContainer.append($buttonsContainer);
        
        // Show with animation
        $suggestionsContainer.addClass('show').show();
        suggestionsVisible = true;
        
        // Update cached elements
        $chatSuggestions = $suggestionsContainer;
        $suggestionsToggle = $toggleButton;
        
        // Auto-hide suggestions after 45 seconds if not interacted with
        setTimeout(function() {
            if ($suggestionsContainer.is(':visible') && !$suggestionsContainer.hasClass('interacted')) {
                hideSuggestions();
            }
        }, 45000);
    }
    /**
     * Hide suggestions
     */
    function hideSuggestions() {
        const $suggestionsContainer = $('.sa-helper-chatbot-suggestions');
        if ($suggestionsContainer.length > 0 && $suggestionsContainer.is(':visible')) {
            $suggestionsContainer.removeClass('show').addClass('hide');
            suggestionsVisible = false;
            
            // Remove after animation completes
            setTimeout(function() {
                $suggestionsContainer.remove();
            }, 200);
        }
    }
    
    /**
     * Toggle suggestions visibility
     */
    function toggleSuggestions() {
        if (suggestionsVisible) {
            hideSuggestions();
            suggestionsEnabled = false;
            
            // Create a "Show Suggestions" button in the input area
            createShowSuggestionsButton();
        } else {
            // Re-enable suggestions and load contextual ones
            suggestionsEnabled = true;
            removeShowSuggestionsButton();
            
            if (conversationHistory.length > 0) {
                showContextualSuggestions();
            } else {
                loadInitialSuggestions();
            }
        }
    }
    
    /**
     * Create a small "Show Suggestions" button when suggestions are disabled
     */
    function createShowSuggestionsButton() {
        const $inputContainer = $('.sa-helper-chatbot-input-container');
        
        // Remove existing button first
        $inputContainer.find('.show-suggestions-btn').remove();
        
        const $showButton = $('<button>')
            .addClass('show-suggestions-btn suggestions-toggle')
            .attr('type', 'button')
            .attr('tabindex', '0')
            .attr('aria-label', 'Show suggestions')
            .text('Show')
            .css({
                'position': 'absolute',
                'top': '-28px',
                'right': '12px'
            })
            .on('click keydown', function(e) {
                if (e.type === 'click' || (e.type === 'keydown' && (e.which === 13 || e.which === 32))) {
                    e.preventDefault();
                    toggleSuggestions();
                }
            });
        
        $inputContainer.append($showButton);
    }
    
    /**
     * Remove the "Show Suggestions" button
     */
    function removeShowSuggestionsButton() {
        $('.show-suggestions-btn').remove();
    }

    /**
     * Handle suggestion selection
     */
    function selectSuggestion(suggestion) {
        // Mark suggestions as interacted
        $('.sa-helper-chatbot-suggestions').addClass('interacted');
        
        // Visual feedback - highlight selected button
        $('.suggestion-button').each(function() {
            if ($(this).text() === suggestion) {
                $(this).addClass('selected');
            }
        });
        
        // Set input value
        $chatInput.val(suggestion);
        
        // Hide suggestions immediately for better UX
        hideSuggestions();
        
        // Focus input for immediate user action
        $chatInput.focus();
        
        // Trigger send message after a short delay to show the selection
        setTimeout(function() {
            sendMessage();
        }, 100);
    }

    // Suggestion timing and intelligence
    let suggestionTimer = null;
    let lastUserActivity = Date.now();
    
    /**
     * Track user activity and show contextual suggestions after inactivity
     */
    function trackUserActivity() {
        lastUserActivity = Date.now();
        
        // Clear existing timer
        if (suggestionTimer) {
            clearTimeout(suggestionTimer);
        }
        
        // Set new timer for showing suggestions after 15 seconds of inactivity
        suggestionTimer = setTimeout(function() {
            // Only show if chat is open, suggestions are enabled, no suggestions are currently visible,
            // and we have conversation history or it's the first time
            if ($chatPopup.is(':visible') && 
                suggestionsEnabled &&
                $('.sa-helper-chatbot-suggestions:visible').length === 0 &&
                !$('.show-suggestions-btn').length && // Don't auto-show if user manually disabled
                conversationHistory.length > 0) {
                
                showContextualSuggestions();
            }
        }, 15000);
    }
    
    /**
     * Show contextual suggestions based on conversation history
     */
    function showContextualSuggestions() {
        // Only show if suggestions are enabled
        if (!suggestionsEnabled) {
            return;
        }
        
        // Get contextual suggestions via AJAX
        $.ajax({
            url: saHelperChatbot.ajax_url,
            type: 'POST',
            data: {
                action: 'sa_helper_chatbot_contextual_suggestions',
                nonce: saHelperChatbot.nonce,
                conversation_length: conversationHistory.length
            },
            success: function(response) {
                if (response.success && response.data.suggestions && response.data.suggestions.length > 0) {
                    displaySuggestions(response.data.suggestions);
                }
            },
            error: function() {
                // Silent fail for contextual suggestions
            }
        });
    }
    /**
     * Enhanced input handler with activity tracking
     */
    function handleInputActivity() {
        trackUserActivity();
        
        // Hide suggestions when user starts typing (only if they're currently visible)
        if ($('.sa-helper-chatbot-suggestions:visible').length > 0) {
            hideSuggestions();
            // Don't disable suggestions entirely, just hide them temporarily
        }
    }
})(jQuery);
