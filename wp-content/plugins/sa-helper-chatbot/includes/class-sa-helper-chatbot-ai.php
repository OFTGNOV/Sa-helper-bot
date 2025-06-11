<?php
/**
 * The AI functionality of the chatbot.
 *
 * @package    SA_Helper_Chatbot
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class SA_Helper_Chatbot_AI
{
    /**
     * The knowledge base data
     *
     * @var array
     */
    private $knowledge;

    /**
     * API settings
     *
     * @var array
     */
    private $api_settings;

    /**
     * Session conversation data
     *
     * @var array
     */
    private $session_data;

    /**
     * Initialize the class
     */
    public function __construct()
    {
        $this->load_knowledge_base();
        $this->load_api_settings();
        $this->init_session();
    }    /**
     * Load knowledge base from WordPress options (cached for performance)
     */
    private function load_knowledge_base()
    {
        // Use WordPress object cache for performance
        $cache_key = 'sa_helper_knowledge_base';
        $cached_knowledge = wp_cache_get($cache_key, 'sa_helper_chatbot');
        
        if ($cached_knowledge !== false) {
            $this->knowledge = $cached_knowledge;
            return;
        }
        
        $this->knowledge = get_option('sa_helper_chatbot_knowledge', array(
            'company_info' => '',
            'website_navigation' => '',
            'recent_news' => '',
            'faq' => ''
        ));

        // Cache for 30 minutes
        wp_cache_set($cache_key, $this->knowledge, 'sa_helper_chatbot', 1800);
    }    
    
    /**
     * Load API settings from WordPress options (cached for performance)
     */
    private function load_api_settings()
    {
        // Use transient cache for API settings
        $cache_key = 'sa_helper_api_settings';
        $cached_settings = get_transient($cache_key);
        
        if ($cached_settings !== false) {
            $this->api_settings = $cached_settings;
            return;
        }
        
        $options = get_option('sa_helper_chatbot_options', array());
        $this->api_settings = isset($options['gemini_api']) ? $options['gemini_api'] : array(
            'api_key' => '',
            'model' => 'gemini-1.5-pro',
            'enable' => false,
            'include_page_content' => true,
            'temperature' => 0.7,
            'max_tokens' => 800
        );

        // Ensure backward compatibility with older model names
        if (isset($this->api_settings['model'])) {
            $this->api_settings['model'] = $this->get_compatible_model_name($this->api_settings['model']);
        }
        
        // Cache for 30 minutes
        set_transient($cache_key, $this->api_settings, 1800);
    }

    /**
     * Initialize session data for conversation persistence
     */
    private function init_session()
    {
        if (!session_id()) {
            session_start();
        }
        
        if (!isset($_SESSION['sa_helper_chatbot'])) {
            $_SESSION['sa_helper_chatbot'] = array(
                'conversation_history' => array(),
                'session_id' => uniqid('sa_chat_', true),
                'started_at' => time()
            );
        }
        
        $this->session_data = &$_SESSION['sa_helper_chatbot'];
    }    
    
    /**
     * Get a response based on the user's message
     *
     * @param string $message The user's message
     * @param string $page_content Optional. The content of the current page.
     * @return string The chatbot's response
     */
    public function get_response($message, $page_content = '') 
    {
        // Allow filtering of the incoming message
        $message = apply_filters('sa_helper_chatbot_filter_message', $message);
        $page_content = apply_filters('sa_helper_chatbot_filter_page_content', $page_content);
        
        // Validate input
        if (empty(trim($message))) {
            error_log('SA Helper Bot: Empty message received');
            return "I didn't receive a message. Could you please try typing your question again?";
        }

        // Store conversation history
        $this->store_user_message($message);
        
        // Log the request for debugging (without sensitive data)
        error_log('SA Helper Bot: Processing message from user, length: ' . strlen($message) . ', page content length: ' . strlen($page_content));
        
        // Allow plugins to modify the behavior before processing
        do_action('sa_helper_chatbot_before_response', $message, $page_content);
        
        if ($this->is_gemini_api_configured()) {
            try {
                // Use the simplified Gemini API call
                $response = $this->get_gemini_response($message, $page_content);
                
                // Check if we got a valid response
                if (!empty($response) && $response !== 'GEMINI_FAILURE') {
                    // Allow filtering of the Gemini response
                    $response = apply_filters('sa_helper_chatbot_filter_gemini_response', $response, $message, $page_content);
                    $this->store_bot_response($response);
                    error_log('SA Helper Bot: Successfully generated Gemini response');
                    
                    // Allow plugins to hook after successful Gemini response
                    do_action('sa_helper_chatbot_after_gemini_response', $response, $message);
                    return $response;
                }
                
                // If Gemini response failed, fall back to keyword matching
                error_log('SA Helper Bot: Gemini response failed, using keyword fallback for message: ' . substr($message, 0, 50) . '...');
                
            } catch (Exception $e) {
                error_log('SA Helper Bot: Gemini API Exception: ' . $e->getMessage() . ' for message: ' . substr($message, 0, 50) . '...');
                do_action('sa_helper_chatbot_gemini_error', $e, $message);
            }
        } else {
            error_log('SA Helper Bot: Gemini API not configured, using keyword fallback');
        }
        
        // Use keyword-based fallback
        $fallback_response = $this->get_keyword_response($message, $page_content);
        
        // Allow filtering of the fallback response
        $fallback_response = apply_filters('sa_helper_chatbot_filter_fallback_response', $fallback_response, $message, $page_content);
        
        $this->store_bot_response($fallback_response);
        error_log('SA Helper Bot: Using keyword-based fallback response');
        
        // Allow plugins to hook after fallback response
        do_action('sa_helper_chatbot_after_fallback_response', $fallback_response, $message);
        
        return $fallback_response;
    }    
    
    /**
     * Check if Gemini API is properly configured
     *
     * @return bool True if API is configured, false otherwise
     */
    public function is_gemini_api_configured()
    {
        return (
            isset($this->api_settings['enable']) &&
            $this->api_settings['enable'] == '1' &&
            !empty($this->api_settings['api_key'])
        );
    }    
    
    /**
     * Get response from Gemini API using simplified approach with caching
     *
     * @param string $message The user's message
     * @param string $page_content The content of the current page
     * @return string The AI-generated response or 'GEMINI_FAILURE'
     */
    private function get_gemini_response($message, $page_content = '') 
    {
        try {
            // Check cache first for similar messages
            $cache_key = $this->generate_response_cache_key($message, $page_content);
            $cached_response = get_transient($cache_key);
            
            if ($cached_response !== false) {
                error_log('SA Helper Bot: Using cached response for similar message');
                return $cached_response;
            }
            
            // Prepare the enhanced message with context
            $enhanced_message = $this->build_enhanced_message($message, $page_content);
            
            // Use the simplified API call approach (same as test file)
            $api_key = $this->api_settings['api_key'];
            $model = $this->get_compatible_model_name($this->api_settings['model'] ?: 'gemini-1.5-pro');
            
            $endpoint = "https://generativelanguage.googleapis.com/v1/models/$model:generateContent?key=$api_key";
            
            $prompt = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $enhanced_message]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => isset($this->api_settings['temperature']) ? (float) $this->api_settings['temperature'] : 0.7,
                    'maxOutputTokens' => isset($this->api_settings['max_tokens']) ? (int) $this->api_settings['max_tokens'] : 800,
                ],
            ];
            
            $args = array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
                'timeout' => 20,
                'body' => json_encode($prompt),
                'method' => 'POST',
            );
            
            $response = wp_remote_post($endpoint, $args);
            
            if (is_wp_error($response)) {
                error_log('SA Helper Bot: API request failed - ' . $response->get_error_message());
                return 'GEMINI_FAILURE';
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            if ($response_code !== 200) {
                error_log('SA Helper Bot: API returned error code: ' . $response_code . ' - ' . $response_body);
                return 'GEMINI_FAILURE';
            }
            
            $response_data = json_decode($response_body, true);
              if (empty($response_data) || !isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
                error_log('SA Helper Bot: Invalid or empty response received from API');
                return 'GEMINI_FAILURE';
            }
            
            $text = $response_data['candidates'][0]['content']['parts'][0]['text'];
            
            // Basic validation
            if (empty(trim($text)) || strlen(trim($text)) < 5) {
                error_log('SA Helper Bot: Response too short or empty');
                return 'GEMINI_FAILURE';
            }
            
            // Format the response
            $formatted_response = $this->format_response($text);
            
            // Cache the response for 1 hour
            set_transient($cache_key, $formatted_response, 3600);
            
            return $formatted_response;
            
        } catch (Exception $e) {
            error_log('SA Helper Bot: Exception in Gemini API call: ' . $e->getMessage());
            return 'GEMINI_FAILURE';
        }
    }    
    
    /**
     * Build enhanced message with context for Gemini
     *
     * @param string $message User's message
     * @param string $page_content Page content
     * @return string Enhanced message with context
     */
    private function build_enhanced_message($message, $page_content = '')
    {
        $context_parts = array(); // Initialize the context parts array

        // Include page content if available and enabled
        if (!empty(trim($page_content)) &&
            isset($this->api_settings['include_page_content']) &&
            $this->api_settings['include_page_content']) {

            $cleaned_content = $this->clean_page_content($page_content);
            if (!empty($cleaned_content)) {
                $context_parts[] = "=== Current Page Content ===\\n" . $cleaned_content;
            }
        }

        // Include knowledge base content
        if (!empty($this->knowledge)) {
            $knowledge_context = "=== Knowledge Base ===\\n";
            foreach ($this->knowledge as $section => $content) {
                if (!empty(trim($content))) {
                    // Sanitize section name for display
                    $section_title = ucwords(str_replace('_', ' ', $section));
                    $knowledge_context .= "Section: " . esc_html($section_title) . "\\n";
                    // Clean and add content, ensuring it's treated as plain text for the prompt
                    $knowledge_context .= wp_strip_all_tags(trim($content)) . "\\n\\n";
                }
            }
            if (strlen(trim($knowledge_context)) > strlen("=== Knowledge Base ===\\n")) { // Check if any content was added
                 $context_parts[] = trim($knowledge_context);
            }
        }

        // Build the complete message
        $enhanced_message = "You are a helpful assistant for the company Software Architects Jamaica. You are a bot that works to help on our website.\\n";
        $enhanced_message .= "Use first-person pronouns.\\n";
        $enhanced_message .= "Please provide accurate, helpful, and concise responses based on the available information.\\n\\n";
        $enhanced_message .= "IMPORTANT: Format your responses using Markdown when appropriate to improve readability. ";
        $enhanced_message .= "Use Markdown for:\\n";
        $enhanced_message .= "- **Bold text** for emphasis and important points\\n";
        $enhanced_message .= "- *Italic text* for subtle emphasis\\n";
        $enhanced_message .= "- `inline code` for technical terms, URLs, or code snippets\\n";
        $enhanced_message .= "- Lists (bulleted with - or numbered with 1.) for structured information\\n";
        $enhanced_message .= "- [Links](URL) when referencing external resources\\n";
        $enhanced_message .= "- Simple line breaks for better text organization\\n\\n";
        
        if (!empty($context_parts)) {
            $enhanced_message .= "Available Information:\\n" . implode("\\n\\n", $context_parts) . "\\n\\n";
        }
        
        $enhanced_message .= "User Question: " . $message . "\\n\\n";
        $enhanced_message .= "Please provide a helpful response based on the available information. ";
        $enhanced_message .= "If the information isn't available, please say so politely and suggest alternatives. ";
        $enhanced_message .= "Remember to use appropriate Markdown formatting to make your response clear and easy to read.";
        
        return $enhanced_message;
    }

    /**
     * Clean and prepare page content for API
     *
     * @param string $page_content Raw page content
     * @return string Cleaned content
     */
    private function clean_page_content($page_content)
    {
        // Remove HTML tags
        $content = wp_strip_all_tags($page_content);
        
        // Remove extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Limit length to prevent API issues
        if (strlen($content) > 2000) {
            $content = substr($content, 0, 2000) . '...';
        }
        
        return trim($content);
    }    
    
    /**
     * Store user message in session for conversation history
     *
     * @param string $message The user's message
     */
    private function store_user_message($message) 
    {
        if (!isset($this->session_data['conversation_history'])) {
            $this->session_data['conversation_history'] = array();
        }
        
        $this->session_data['conversation_history'][] = array(
            'type' => 'user',
            'message' => sanitize_text_field($message),
            'timestamp' => current_time('timestamp')
        );
        
        // Limit conversation history to last 20 messages to prevent memory issues
        if (count($this->session_data['conversation_history']) > 20) {
            $this->session_data['conversation_history'] = array_slice($this->session_data['conversation_history'], -20);
        }
    }

    /**
     * Store bot response in session for conversation history
     *
     * @param string $response The bot's response
     */
    private function store_bot_response($response) 
    {
        if (!isset($this->session_data['conversation_history'])) {
            $this->session_data['conversation_history'] = array();
        }
        
        // Store raw response to preserve Markdown for client-side rendering
        $this->session_data['conversation_history'][] = array(
            'type' => 'bot',
            'message' => $response, // Removed sanitize_text_field to preserve Markdown
            'timestamp' => current_time('timestamp')
        );
        
        // Limit conversation history to last 20 messages to prevent memory issues
        if (count($this->session_data['conversation_history']) > 20) {
            $this->session_data['conversation_history'] = array_slice($this->session_data['conversation_history'], -20);
        }
    }

    /**
     * Ensures model name is compatible with current API
     * 
     * @param string $model_name The model name to check
     * @return string Updated model name
     */
    private function get_compatible_model_name($model_name)
    {
        $model_mapping = [
            // Legacy model names to new model names
            'gemini-pro' => 'gemini-1.5-pro',
            'gemini-ultra' => 'gemini-1.5-pro',
            'gemini-pro-vision' => 'gemini-1.0-pro-vision'
        ];

        // Return mapped model name if exists, otherwise return original
        return isset($model_mapping[$model_name]) ? $model_mapping[$model_name] : $model_name;
    }

    /**
     * Format response text
     *
     * @param string $text Raw response text
     * @return string Formatted response
     */
    private function format_response($text)
    {
        // Trim whitespace
        $text = trim($text);
        
        // Limit length if too long
        if (strlen($text) > 1000) {
            $text = substr($text, 0, 1000);
            // Try to end at a sentence boundary
            $last_period = strrpos($text, '.');
            $last_question = strrpos($text, '?');
            $last_exclamation = strrpos($text, '!');
            
            $boundary = max($last_period, $last_question, $last_exclamation);
            if ($boundary !== false && $boundary > 800) {
                $text = substr($text, 0, $boundary + 1);
            } else {
                $text .= '...';
            }
        }
        
        return $text;
    }    
    
    /**
     * Get response using keyword matching (fallback method)
     *
     * @param string $message The user's message
     * @param string $page_content Optional. The content of the current page.
     * @return string The chatbot's response
     */
    private function get_keyword_response($message, $page_content = '')
    {
        $message_lower = strtolower($message);
        
        // Check knowledge base sections for relevant content
        foreach ($this->knowledge as $section => $content) {
            if (!empty(trim($content))) {
                // Simple keyword matching
                $section_keywords = $this->extract_keywords_from_section($section);
                foreach ($section_keywords as $keyword) {
                    if (strpos($message_lower, strtolower($keyword)) !== false) {
                        return $this->format_knowledge_response($content, $section);
                    }
                }
            }
        }
          // Check page content if available
        if (!empty(trim($page_content))) {
            return "I can see you're asking about something on this page. While I don't have specific information about your question in my knowledge base, you might find the answer in the content on this page. **Is there something specific you'd like to know about our services?**";
        }
        
        // Generic fallback responses with Markdown formatting
        $fallback_responses = array(
            "I'd be **happy to help!** Could you please provide more details about what you're looking for?",
            "That's a great question! While I don't have specific information about that topic, please feel free to browse our website or *contact us directly* for more details.",
            "Thanks for reaching out! I'm here to help with information about our services. **Could you be more specific** about what you need assistance with?",
            "I want to make sure I give you the most **accurate information**. Could you rephrase your question or provide more context?",
        );
        
        return $fallback_responses[array_rand($fallback_responses)];
    }

    /**
     * Extract keywords from a knowledge base section name
     *
     * @param string $section Section name
     * @return array Keywords
     */
    private function extract_keywords_from_section($section)
    {
        $keywords = array();
        
        switch ($section) {
            case 'company_info':
                $keywords = array('company', 'about', 'business', 'who', 'what', 'service', 'services', 'facebook', 'instagram', 'twitter', 'social media');
                break;
            case 'website_navigation':
                $keywords = array('navigate', 'navigation', 'menu', 'page', 'pages', 'find', 'where', 'how', 'go', 'link', 'links');
                break;
            case 'recent_news':
                $keywords = array('news', 'recent', 'update', 'updates', 'announcement', 'new');
                break;
            case 'faq':
                $keywords = array('question', 'questions', 'faq', 'help', 'support', 'problem', 'issue', 'troubleshoot', 'troubleshooting', 'common', 'frequently asked');
                break;
        }
        
        return $keywords;
    }

    /**
     * Format response from knowledge base content
     *
     * @param string $content Knowledge base content
     * @param string $section Section name
     * @return string Formatted response
     */    private function format_knowledge_response($content, $section)
    {
        // Clean up the content
        $content = wp_strip_all_tags($content);
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);
        
        // Limit length
        if (strlen($content) > 500) {
            $content = substr($content, 0, 500);
            $last_period = strrpos($content, '.');
            if ($last_period !== false && $last_period > 400) {
                $content = substr($content, 0, $last_period + 1);
            } else {
                $content .= '...';
            }
        }
        
        // Add markdown formatting to the response
        $section_titles = [
            'company_info' => '**Company Information**',
            'website_navigation' => '**Website Navigation**',
            'recent_news' => '**Recent News**',
            'faq' => '**Frequently Asked Questions**'
        ];
        
        $title = isset($section_titles[$section]) ? $section_titles[$section] : '**Information**';
        
        // Format the response with markdown
        $response = $title . "\n\n" . $content;
        
        // Add some helpful formatting if content is plain text
        if (strpos($content, '.') !== false && strpos($content, '**') === false) {
            // Add emphasis to the first sentence if it doesn't already have formatting
            $sentences = explode('.', $content);
            if (!empty($sentences[0])) {
                $response = $title . "\n\n**" . trim($sentences[0]) . ".** " . implode('.', array_slice($sentences, 1));
            }
        }
        
        return $response;
    }

    /**
     * Get conversation history for the current session
     *
     * @return array Conversation history
     */
    public function get_conversation_history()
    {
        return isset($this->session_data['conversation_history']) ? $this->session_data['conversation_history'] : array();
    }

    /**
     * Clear conversation history
     */
    public function clear_conversation_history()
    {
        $this->session_data['conversation_history'] = array();
        
        // Also reset session metadata
        $this->session_data['session_id'] = uniqid('sa_chat_', true);
        $this->session_data['started_at'] = time();
    }

    /**
     * Get session statistics
     *
     * @return array Session statistics
     */
    public function get_session_stats()
    {
        $history = $this->get_conversation_history();
        return array(            'session_id' => isset($this->session_data['session_id']) ? $this->session_data['session_id'] : '',
            'started_at' => isset($this->session_data['started_at']) ? $this->session_data['started_at'] : 0,
            'total_messages' => count($history),
            'user_messages' => count(array_filter($history, function($item) { return $item['type'] === 'user'; })),
            'bot_messages' => count(array_filter($history, function($item) { return $item['type'] === 'bot'; }))
        );
    }
    
    /**
     * Generate cache key for response caching
     *
     * @param string $message User message
     * @param string $page_content Page content
     * @return string Cache key
     */
    private function generate_response_cache_key($message, $page_content = '')
    {
        // Normalize message for caching (remove extra spaces, convert to lowercase)
        $normalized_message = strtolower(trim(preg_replace('/\s+/', ' ', $message)));
        
        // Create a hash of the normalized message and relevant settings
        $data_to_hash = array(
            'message' => $normalized_message,
            'temperature' => $this->api_settings['temperature'] ?? 0.7,
            'model' => $this->api_settings['model'] ?? 'gemini-1.5-pro',
            'include_page_content' => $this->api_settings['include_page_content'] ?? true,
            'page_content_hash' => !empty($page_content) ? md5($page_content) : ''
        );
        
        return 'sa_helper_response_' . md5(serialize($data_to_hash));
    }
      /**
     * Clear all cached responses (useful when settings change)
     */
    public function clear_response_cache()
    {
        global $wpdb;

        // Use WordPress API to safely clear transients with our prefix
        $transients = wp_cache_get('sa_helper_chatbot_transients');

        if ($transients) {
            foreach ($transients as $transient) {
                wp_cache_delete($transient);
            }
        }

        wp_cache_set('sa_helper_chatbot_transients', []);
        
        // Clear other cached data using WordPress APIs
        delete_transient('sa_helper_api_settings');
        wp_cache_delete('sa_helper_knowledge_base', 'sa_helper_chatbot');
    }
}
