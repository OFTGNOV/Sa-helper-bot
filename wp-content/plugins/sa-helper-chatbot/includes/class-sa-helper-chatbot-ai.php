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
     * Initialize the class
     */
    public function __construct()
    {
        $this->knowledge = get_option('sa_helper_chatbot_knowledge', array(
            'company_info' => '',
            'website_navigation' => '',
            'recent_news' => ''
        ));

        $options = get_option('sa_helper_chatbot_options', array());
        $this->api_settings = isset($options['gemini_api']) ? $options['gemini_api'] : array(
            'api_key' => '',
            'model' => 'gemini-1.5-pro',
            'enable' => false,
        );

        // Ensure backward compatibility with older model names
        if (isset($this->api_settings['model'])) {
            $this->api_settings['model'] = $this->get_compatible_model_name($this->api_settings['model']);
        }
    }

    /**
     * Get a response based on the user's message
     *
     * @param string $message The user's message
     * @return string The chatbot's response
     */
    public function get_response($message) {
        // First try to use Gemini API if it's configured
        if ($this->is_gemini_api_configured()) {
            $gemini_response = $this->get_gemini_response($message);
            
            // If the response doesn't contain an error indicator, return it
            if (strpos($gemini_response, "I'm having trouble") === false && 
                strpos($gemini_response, "Let me fall back") === false) {
                return $gemini_response;
            }
            
            // If we got an error response, log it
            error_log('SA Helper Bot: Gemini API response contained an error. Falling back to keyword matching.');
        }
        
        // Fall back to keyword matching if API is not configured or returned an error
        return $this->get_keyword_response($message);
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
    }

    /**
     * Get response from Gemini API
     *
     * @param string $message The user's message
     * @return string The AI-generated response
     */
    private function get_gemini_response($message) {
        try {
            // Prepare the knowledge base context
            $context = $this->prepare_context_for_gemini();
            
            // Build the prompt
            $prompt = $this->build_gemini_prompt($message, $context);
            
            // Make API request
            $response = $this->call_gemini_api($prompt);
            
            // Handle API response
            if (is_wp_error($response)) {
                // Log error
                error_log('Gemini API Error: ' . $response->get_error_message());
                return "I'm having trouble connecting to my brain right now. Let me fall back to what I know: " . 
                       $this->get_keyword_response($message);
            }
            
            // Process and return the AI response
            return $this->process_gemini_response($response);
        } catch (Exception $e) {
            // Catch any unexpected errors
            error_log('Gemini API Exception: ' . $e->getMessage());
            return "I encountered an unexpected error. Let me answer with what I know directly: " . 
                   $this->get_keyword_response($message);
        }
    }

    /**
     * Prepare context from knowledge base for Gemini
     *
     * @return string Context from knowledge base
     */
    private function prepare_context_for_gemini()
    {
        $context = "";

        if (!empty($this->knowledge['company_info'])) {
            $context .= "COMPANY INFORMATION:\n" . strip_tags($this->knowledge['company_info']) . "\n\n";
        }

        if (!empty($this->knowledge['website_navigation'])) {
            $context .= "WEBSITE NAVIGATION:\n" . strip_tags($this->knowledge['website_navigation']) . "\n\n";
        }

        if (!empty($this->knowledge['recent_news'])) {
            $context .= "RECENT NEWS:\n" . strip_tags($this->knowledge['recent_news']) . "\n\n";
        }

        return $context;
    }
    
    /**
     * Process Gemini API response
     *
     * @param array $response The API response
     * @return string The formatted response
     */
    private function process_gemini_response($response) {
        if (empty($response) || !isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return "I'm having trouble understanding right now. Let me try a different approach: " . 
                   $this->get_keyword_response(strtolower($this->last_message));
        }
        
        $text = $response['candidates'][0]['content']['parts'][0]['text'];
        
        // Check if the response is empty or too short (likely an error)
        if (strlen(trim($text)) < 5) {
            return "I received an incomplete response. Let me answer directly: " . 
                   $this->get_keyword_response(strtolower($this->last_message));
        }
        
        // Format and limit length
        return $this->format_response($text);
    }
    
    /**
     * Build prompt for Gemini API
     *
     * @param string $message User's message
     * @param string $context Knowledge base context
     * @return array Complete prompt
     */
    private function build_gemini_prompt($message, $context) {
        // Save the message for potential fallback
        $this->last_message = $message;
        
        // Detect potential keywords in the user's message
        $detected_topics = [];
        
        if ($this->contains_keywords($message, array('company', 'about', 'who are you', 'business', 'organization'))) {
            $detected_topics[] = 'company information';
        }
        
        if ($this->contains_keywords($message, array('find', 'where', 'page', 'navigate', 'go to', 'location', 'menu'))) {
            $detected_topics[] = 'website navigation';
        }
        
        if ($this->contains_keywords($message, array('news', 'update', 'recent', 'latest', 'announcement', 'blog'))) {
            $detected_topics[] = 'recent news';
        }
        
        if ($this->contains_keywords($message, array('contact', 'email', 'phone', 'call', 'reach'))) {
            $detected_topics[] = 'contact information';
        }
        
        $detected_topics = !empty($detected_topics) 
            ? implode(', ', $detected_topics) 
            : 'general information';
        
        $system_instruction = "You are a helpful assistant for a company website. " .
                             "Answer questions based ONLY on the company information provided. " .
                             "Keep responses concise (under 150 words) and helpful. " .
                             "Use a friendly, professional tone. " .
                             "If you don't know the answer based on the provided information, say so politely and suggest contacting the company directly.";
        
        // Enhanced prompt with topic focus guidance
        $prompt = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => "Here is information about the company:\n\n$context\n\nUser question: $message"]
                    ],
                ],
                [
                    'role' => 'system',
                    'parts' => [
                        ['text' => $system_instruction]
                    ]

                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 800,
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
     */
    private function process_gemini_response($response) {
        if (empty($response) || !isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return "I'm having trouble understanding right now. Please try asking a different question.";
        }
        
        $text = $response['candidates'][0]['content']['parts'][0]['text'];
        
        // Format and limit length
        return $this->format_response($text);
    }

    /**
     * Get response using keyword matching (fallback method)
     *
     * @param string $message The user's message
     * @return string The chatbot's response
     */
    private function get_keyword_response($message)
    {
        $message = strtolower($message);

        // Check for company information questions
        if ($this->contains_keywords($message, array(
            'company',
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
            'what do you do',
            'what does your company do',
            'services',
            'solutions',
            'mission',
            'vision',
            'values',
            'team',
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
            'introduction'
        ))) {
            if (!empty($this->knowledge['company_info'])) {
                return $this->format_response($this->knowledge['company_info']);
            } else {
                return "I'd be happy to tell you about our company, but it looks like that information hasn't been set up yet.";
            }
        }

        // Check for navigation questions
        if ($this->contains_keywords($message, array(
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
                return $this->format_response($this->knowledge['website_navigation']);
            } else {
                return "I can help you navigate our website, but it seems that navigation information hasn't been set up yet.";
            }
        }

        // Check for news and updates
        if ($this->contains_keywords($message, array(
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
                return $this->format_response($this->knowledge['recent_news']);
            } else {
                return "I'd be happy to share our latest news, but it looks like that information hasn't been updated yet.";
            }
        }

        // Default responses for common queries
        if ($this->contains_keywords($message, array('hello', 'hi', 'hey', 'greetings'))) {
            return "Hello! How can I assist you today?";
        }

        if ($this->contains_keywords($message, array('thank', 'thanks'))) {
            return "You're welcome! Is there anything else I can help with?";
        }

        if ($this->contains_keywords($message, array('bye', 'goodbye', 'later'))) {
            return "Goodbye! Feel free to chat again if you have more questions.";
        }

        if ($this->contains_keywords($message, array('contact', 'email', 'phone', 'call', 'reach'))) {
            return "You can contact us through our contact form on the website, or by calling our customer service at [your phone number].";
        }

        // Default fallback response
        return "I'm not sure I understand. Could you rephrase your question? I can provide information about our company, help you navigate the website, or share recent news.";
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
