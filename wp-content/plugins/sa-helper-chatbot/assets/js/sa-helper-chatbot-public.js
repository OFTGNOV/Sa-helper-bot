/**
 * Public scripts for SA Helper Chatbot
 */
(function($) {
    'use strict';

    // Cached DOM elements for performance
    let $chatButton, $chatPopup, $chatInput, $chatMessages, $chatClose, $chatSend;
    
    // Conversation persistence across pages using sessionStorage
    let conversationHistory = [];
    const STORAGE_KEY = 'sa_helper_chatbot_session_conversation';
    const MAX_HISTORY_ITEMS = 20;
    
    // Performance constants
    const ANIMATION_DURATION = 300;
    const FOCUS_DELAY = 350;
    const SCROLL_DELAY = 50;

    $(document).ready(function() {
        // Initialize performance: cache DOM elements once
        initializeDOMCache();
        
        // Initialize conversation history from sessionStorage
        loadConversationHistory();
        
        // Bind event handlers
        bindEventHandlers();

        // Apply initial styling on page load after DOM is ready
        applyThemeSpecificStyling();
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
            
            if (isOpening) {
                setTimeout(() => {
                    $chatInput.focus();
                    applyThemeSpecificStyling();
                }, FOCUS_DELAY);
                
                if (!$chatButton.hasClass('history-loaded')) {
                    loadConversationIntoChat();
                    $chatButton.addClass('history-loaded');
                }
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
    }

    /**
     * Send message function with optimized AJAX handling
     */
    function sendMessage() {
        const message = $chatInput.val().trim();
        
        if (message === '') return;
        
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
    }    /**
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
    }/**
     * Theme-specific styling (optimized)
     */
    function applyThemeSpecificStyling() {
        const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if ($chatInput) { // Add a check for $chatInput
            $chatInput.css('color', isDarkMode ? '#e0e0e0' : '#333333');
        }
    }

    // Listen for theme changes
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', applyThemeSpecificStyling);
    }

})(jQuery);
