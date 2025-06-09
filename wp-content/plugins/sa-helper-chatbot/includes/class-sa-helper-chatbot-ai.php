<?php

/**
 * The AI functionality of the chatbot.
 *
 * @package    SA_Helper_Chatbot
 */
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
    }

    /**
     * Load knowledge base from WordPress options
     */
    private function load_knowledge_base()
    {
        $this->knowledge = get_option('sa_helper_chatbot_knowledge', array(
            'company_info' => '',
            'website_navigation' => '',
            'recent_news' => '',
            'faq' => ''
        ));
    }

    /**
     * Load API settings from WordPress options
     */
    private function load_api_settings()
    {
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
    }    /**
     * Get a response based on the user's message
     *
     * @param string $message The user's message
     * @param string $page_content Optional. The content of the current page.
     * @return string The chatbot's response
     */
    public function get_response($message, $page_content = '') {
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
                // Try Gemini first with prioritized page content
                $response = $this->get_gemini_response($message, $page_content);
                
                // Validate Gemini response quality
                if ($this->is_valid_gemini_response($response)) {
                    // Allow filtering of the Gemini response
                    $response = apply_filters('sa_helper_chatbot_filter_gemini_response', $response, $message, $page_content);
                    $this->store_bot_response($response);
                    error_log('SA Helper Bot: Successfully generated Gemini response');
                    
                    // Allow plugins to hook after successful Gemini response
                    do_action('sa_helper_chatbot_after_gemini_response', $response, $message);
                    return $response;
                }
                
                // If Gemini response is invalid, fall back to keyword matching
                error_log('SA Helper Bot: Gemini response was invalid or insufficient for message: ' . substr($message, 0, 50) . '...');
                
            } catch (Exception $e) {
                error_log('SA Helper Bot: Gemini API Exception: ' . $e->getMessage() . ' for message: ' . substr($message, 0, 50) . '...');
                do_action('sa_helper_chatbot_gemini_error', $e, $message);
            }
        } else {
            error_log('SA Helper Bot: Gemini API not configured, using keyword fallback');
        }
        
        // Use keyword-based fallback only when Gemini is unavailable or fails
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
            $this->api_settings['enable'] === true &&
            !empty($this->api_settings['api_key'])
        );
    }    /**
     * Get response from Gemini API
     *
     * @param string $message The user's message
     * @param string $page_content The content of the current page
     * @return string The AI-generated response
     */
    private function get_gemini_response($message, $page_content = '') {
        try {
            // Prepare context with page content prioritized
            $context = $this->prepare_context_for_gemini($page_content);
            
            // Build the prompt
            $prompt = $this->build_gemini_prompt($message, $context, !empty(trim($page_content)));
            
            // Make API request
            $response = $this->call_gemini_api($prompt);
            
            // Handle API response
            if (is_wp_error($response)) {
                error_log('Gemini API Error: ' . $response->get_error_message());
                throw new Exception('Gemini API Error: ' . $response->get_error_message());
            }
            
            // Process and return the AI response
            return $this->process_gemini_response($response, $message);
        } catch (Exception $e) {
            error_log('Gemini API Exception: ' . $e->getMessage());
            throw $e;
        }
    }    /**
     * Prepare context from knowledge base for Gemini
     *
     * @param string $page_content Optional. The content of the current page.
     * @return string Context from knowledge base and page content
     */
    private function prepare_context_for_gemini($page_content = '')
    {
        $context_parts = [];

        // PRIORITIZE: Current page content first
        if (!empty($page_content)) {
            // Sanitize and truncate page content to avoid overly long contexts
            $page_content_cleaned = wp_strip_all_tags($page_content);
            if (strlen($page_content_cleaned) > 3000) { // Limit page content length
                $page_content_cleaned = substr($page_content_cleaned, 0, 3000) . "...";
            }
            $context_parts[] = "CURRENT PAGE CONTENT (Primary source - prioritize this information):\n" . $page_content_cleaned;
        }

        // SECONDARY: Knowledge base content for additional context
        $knowledge_base_content = "";
        if (!empty($this->knowledge['company_info'])) {
            $knowledge_base_content .= "Company Information:\n" . wp_strip_all_tags($this->knowledge['company_info']) . "\n\n";
        }

        if (!empty($this->knowledge['website_navigation'])) {
            $knowledge_base_content .= "Website Navigation Tips:\n" . wp_strip_all_tags($this->knowledge['website_navigation']) . "\n\n";
        }

        if (!empty($this->knowledge['recent_news'])) {
            $knowledge_base_content .= "Recent News & Updates:\n" . wp_strip_all_tags($this->knowledge['recent_news']) . "\n\n";
        }
        
        if (!empty($this->knowledge['faq'])) {
            $knowledge_base_content .= "Frequently Asked Questions (FAQ):\n" . wp_strip_all_tags($this->knowledge['faq']) . "\n\n";
        }

        if (!empty($knowledge_base_content)) {
            $context_parts[] = "KNOWLEDGE BASE (Supplementary information - use if current page content is insufficient):\n" . $knowledge_base_content;
        }
        
        if (empty($context_parts)) {
            return "No specific context available. Please answer generally but helpfully.";
        }

        return implode("\n\n---\n\n", $context_parts);
    }
    
    /**
     * Process Gemini API response
     *
     * @param array $response The API response
     * @return string The formatted response
     */
    private function process_gemini_response($response, $original_message = '') {
        if (empty($response) || !isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return $this->get_fallback_message_for_gemini_failure($original_message);
        }
        
        $text = $response['candidates'][0]['content']['parts'][0]['text'];
        
        // Check if the response is empty, too short, or a generic refusal (might indicate API couldn't answer)
        if (strlen(trim($text)) < 10 || stripos($text, "I cannot provide information") !== false || stripos($text, "I'm sorry, but I cannot") !== false ) {
            return $this->get_fallback_message_for_gemini_failure($original_message);
        }
        
        // Format and limit length
        return $this->format_response($text);
    }    private function get_fallback_message_for_gemini_failure($original_message = '') {
        // This message signals to get_response that Gemini truly failed and keyword fallback should be used.
        // It's a unique string that process_gemini_response returns.
        // The actual user-facing message will come from get_keyword_response.
        return "GEMINI_API_FAILURE_FALLBACK_TRIGGER"; 
    }

    /**
     * Store user message in session for conversation history
     *
     * @param string $message The user's message
     */
    private function store_user_message($message) {
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
    private function store_bot_response($response) {
        if (!isset($this->session_data['conversation_history'])) {
            $this->session_data['conversation_history'] = array();
        }
        
        $this->session_data['conversation_history'][] = array(
            'type' => 'bot',
            'message' => sanitize_text_field($response),
            'timestamp' => current_time('timestamp')
        );
        
        // Limit conversation history to last 20 messages to prevent memory issues
        if (count($this->session_data['conversation_history']) > 20) {
            $this->session_data['conversation_history'] = array_slice($this->session_data['conversation_history'], -20);
        }
    }

    /**
     * Validate Gemini response quality
     *
     * @param string $response The response to validate
     * @return bool True if response is valid, false otherwise
     */
    private function is_valid_gemini_response($response) {
        // Check if response is empty or failure trigger
        if (empty($response) || $response === $this->get_fallback_message_for_gemini_failure()) {
            return false;
        }
        
        // Check if response is too short (likely incomplete)
        if (strlen(trim($response)) < 10) {
            return false;
        }
        
        // Check for generic refusals that indicate API couldn't answer
        $refusal_patterns = array(
            "I cannot provide information",
            "I'm sorry, but I cannot",
            "I don't have access to",
            "I'm unable to provide",
            "I can't help with that"
        );
        
        foreach ($refusal_patterns as $pattern) {
            if (stripos($response, $pattern) !== false) {
                return false;
            }
        }
          return true;
    }

    /**
     * Get conversation history for current session
     *
     * @return array Conversation history
     */
    public function get_conversation_history() {
        return isset($this->session_data['conversation_history']) ? $this->session_data['conversation_history'] : array();
    }

    /**
     * Clear conversation history
     */
    public function clear_conversation_history() {
        if (isset($this->session_data['conversation_history'])) {
            $this->session_data['conversation_history'] = array();
        }
        do_action('sa_helper_chatbot_conversation_cleared');
    }

    /**
     * Get session statistics
     *
     * @return array Session stats
     */
    public function get_session_stats() {
        $history = $this->get_conversation_history();
        return array(
            'session_id' => isset($this->session_data['session_id']) ? $this->session_data['session_id'] : '',
            'started_at' => isset($this->session_data['started_at']) ? $this->session_data['started_at'] : 0,
            'message_count' => count($history),
            'user_messages' => count(array_filter($history, function($item) { return $item['type'] === 'user'; })),
            'bot_messages' => count(array_filter($history, function($item) { return $item['type'] === 'bot'; }))
        );
    }
      /**
     * Build prompt for Gemini API
     *
     * @param string $message User's message
     * @param string $context Knowledge base context
     * @param bool $page_content_available Flag indicating if page content is available
     * @return array Complete prompt
     */
    private function build_gemini_prompt($message, $context, $page_content_available = false) {
        $priority_instruction = $page_content_available 
            ? "First and most importantly, check if the answer can be found in the 'CURRENT PAGE CONTENT' section. This should be your primary source. If the information on the current page is insufficient or doesn't answer the user's question, then supplement with information from the 'KNOWLEDGE BASE' section."
            : "Use the information from the 'KNOWLEDGE BASE' section to answer the question.";

        $system_instruction = "You are an intelligent and helpful virtual assistant for this website. Your goal is to provide accurate, helpful, and engaging responses to user questions. " .
                             $priority_instruction . " " .
                             "IMPORTANT INSTRUCTIONS:\n" .
                             "1. Do NOT simply copy text word-for-word from the provided context\n" .
                             "2. Synthesize the information and explain it in your own words in a natural, conversational way\n" .
                             "3. If you find relevant FAQ information, answer comprehensively but naturally\n" .
                             "4. If the user's question cannot be answered from the provided context, politely say so and suggest alternatives (like contacting support or checking specific pages)\n" .
                             "5. Keep responses concise but complete, ideally under 150 words unless more detail is truly needed\n" .
                             "6. Use a friendly, professional tone\n" .
                             "7. If multiple pieces of information are relevant, prioritize the most directly relevant to the user's question\n\n" .
                             "User's question: \"" . esc_html($message) . "\"\n\n" .
                             "Available context:\n" . $context;
        
        // The prompt structure for Gemini API
        $prompt = [
            'contents' => [
                [
                    'role' => 'user', 
                    'parts' => [
                        ['text' => $system_instruction]
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => isset($this->api_settings['temperature']) ? (float) $this->api_settings['temperature'] : 0.7,
                'maxOutputTokens' => isset($this->api_settings['max_tokens']) ? (int) $this->api_settings['max_tokens'] : 800,
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
            ],
        ];

        return $prompt;
    }

    /**
     * Call the Gemini API
     *
     * @param array $prompt The formatted prompt
     * @return mixed API response or WP_Error
     */
    private function call_gemini_api($prompt)
    {
        $api_key = $this->api_settings['api_key'];
        $model = $this->get_compatible_model_name($this->api_settings['model'] ?: 'gemini-1.5-pro');
        $endpoint = "https://generativelanguage.googleapis.com/v1/models/$model:generateContent?key=$api_key";

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
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            return new WP_Error('api_error', 'API returned error code: ' . $response_code . ' - ' . $response_body);
        }

        return json_decode($response_body, true);
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
     * Process Gemini API response
     *
     * @param array $response The API response
     * @return string The formatted response
     */    /**
     * Get response using keyword matching (fallback method)
     *
     * @param string $message The user's message
     * @param string $page_content Optional. The content of the current page.
     * @return string The chatbot's response
     */
    private function get_keyword_response($message, $page_content = '')
    {
        $message_lower = strtolower($message);

        // Enhanced: Add a generic "I couldn't find that with AI" type message if it reaches here after an AI attempt.
        // This is now the primary fallback if Gemini fails or is disabled.
        
        $options = get_option('sa_helper_chatbot_options', array());
        $fallback_prefix = isset($options['general']['gemini_fallback_prefix']) ? $options['general']['gemini_fallback_prefix'] : "I couldn't find a specific answer to that using my advanced understanding. However, based on common topics: ";
        $use_prefix = $this->is_gemini_api_configured(); // Only add prefix if Gemini was supposed to be used.

        if ($this->contains_keywords($message_lower, array('hello', 'hi', 'hey', 'greetings'))) {
            return "Hello! How can I assist you today?";
        }

        if ($this->contains_keywords($message_lower, array('thank', 'thanks'))) {
            return "You're welcome! Is there anything else I can help with?";
        }

        if ($this->contains_keywords($message_lower, array('bye', 'goodbye', 'later'))) {
            return "Goodbye! Feel free to chat again if you have more questions.";
        }
        
        // Check for company information questions
        if ($this->contains_keywords($message_lower, array(
            'company',
            'projects',
            'products',
            'services',
            'solutions',
            'about',
            'who are you',
            'who is',
            'what is',
            'business',
            'organization',
            'firm',
            'enterprise',
            'startup',
            'agency',
            'what do you guys do',
            'what do you do',
            'what does your company do',
            'Where are you located?',
            'where is your company',
            'services',
            'solutions',
            'mission',
            'vision',
            'values',
            'team',
            'Who founded this company?',
            'What are your working hours?',
            'founder',
            'CEO',
            'leadership',
            'project',
            'projects',
            'clients',
            'partners',
            'background',
            'overview',
            'profile',
            'identity',
            'history',
            'established',
            'foundation',
            'establishment',
            'founded',
            'origin',
            'introduction'
        ))) {
            if (!empty($this->knowledge['company_info'])) {
                return ($use_prefix ? $fallback_prefix : "") . $this->format_response($this->knowledge['company_info']);
            } else {
                return ($use_prefix ? $fallback_prefix : "") . "I don't have detailed company information available in my knowledge base right now.";
            }
        }

        // Check for navigation questions
        if ($this->contains_keywords($message_lower, array(
            'find',
            'where',
            'how do I',
            'page',
            'navigate',
            'go to',
            'location',
            'menu',
            'click',
            'site map',
            'link',
            'section',
            'open',
            'access',
            'visit',
            'how to access',
            'how can I find',
            'get to',
            'homepage',
            'contact page',
            'services page',
            'products page',
            'support page',
            'footer',
            'header',
            'scroll'
        ))) {
            if (!empty($this->knowledge['website_navigation'])) {
                return ($use_prefix ? $fallback_prefix : "") . $this->format_response($this->knowledge['website_navigation']);
            } else {
                return ($use_prefix ? $fallback_prefix : "") . "I don't have specific website navigation tips in my knowledge base at the moment.";
            }
        }

        // Check for news and updates
        if ($this->contains_keywords($message_lower, array(
            'news',
            'update',
            'recent',
            'latest',
            'announcement',
            'blog',
            'article',
            'press',
            'press release',
            'headline',
            'stories',
            'events',
            'happening',
            'what\'s new',
            'what is new',
            'what\'s happening',
            'updates',
            'insights',
            'newsletter',
            'trending',
            'launch',
            'release',
            'today',
            'yesterday',
            'timeline',
            'media',
            'coverage',
            'post',
            'published',
            'report'
        ))) {

            if (!empty($this->knowledge['recent_news'])) {
                return ($use_prefix ? $fallback_prefix : "") . $this->format_response($this->knowledge['recent_news']);
            } else {
                return ($use_prefix ? $fallback_prefix : "") . "I don't have any recent news updates in my knowledge base currently.";
            }
        }


        if ($this->contains_keywords($message_lower, array('contact', 'email', 'phone', 'call', 'reach'))) {
            // Example: Fetch contact info from WordPress options if available, otherwise use a placeholder.
            $contact_info = get_option('sa_helper_contact_info', "You can typically find contact details on our 'Contact Us' page.");
            if ($contact_info === "You can typically find contact details on our 'Contact Us' page." && !empty($this->knowledge['contact_info'])) { // Fallback to KB if WP option is generic
                 $contact_info = $this->knowledge['contact_info'];
            }
            return ($use_prefix ? $fallback_prefix : "") . $this->format_response($contact_info);
        }
        
        // Default fallback response if no keywords match
        $default_fallback = "I'm having a little trouble understanding that specific question right now. Could you try rephrasing it? I can generally help with information about our company, website navigation, or recent news.";
        return ($use_prefix ? $fallback_prefix : "") . $default_fallback;
    }

    /**
     * Check if a string contains any of the specified keywords
     *
     * @param string $string The string to check
     * @param array $keywords The keywords to look for
     * @return bool True if any keyword is found, false otherwise
     */
    private function contains_keywords($string, $keywords)
    {
        foreach ($keywords as $keyword) {
            if (stripos($string, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Format a response from the knowledge base
     *
     * @param string $text The raw text from the knowledge base
     * @return string The formatted response
     */
    private function format_response($text)
    {
        // Strip HTML tags and limit the length
        $text = strip_tags($text);

        // If the response is too long, truncate it
        if (strlen($text) > 1000) {
            $text = substr($text, 0, 997) . '...';
        }

        return $text;
    }
}
