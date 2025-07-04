<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    SA_Helper_Chatbot
 */
class SA_Helper_Chatbot_Public {

    /**
     * The AI handler instance
     *
     * @var SA_Helper_Chatbot_AI
     */
    private $ai_handler;    
    
    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        // Make sure the AI class is loaded
        if (class_exists('SA_Helper_Chatbot_AI')) {
            $this->ai_handler = new SA_Helper_Chatbot_AI();
        }
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'sa-helper-chatbot',
            SA_HELPER_URL . 'assets/css/sa-helper-chatbot-public.css',
            array(),
            SA_HELPER_VERSION
        );
    }    
    
    /**
     * Register the JavaScript for the public-facing side of the site.
     */    public function enqueue_scripts() {
        // Load DOMPurify for HTML sanitization
        wp_enqueue_script(
            'dompurify',
            SA_HELPER_URL . 'assets/js/dompurify.min.js',
            array(),
            '2.3.3', // Assuming a version for DOMPurify, adjust if known
            true
        );

        // Load Marked.js for Markdown parsing
        wp_enqueue_script(
            'marked',
            SA_HELPER_URL . 'assets/js/marked.min.js',
            array(),
            '15.0.12', // Updated to use version from package.json
            true
        );

        // Load main chatbot script
        wp_enqueue_script(
            'sa-helper-chatbot-public',
            SA_HELPER_URL . 'assets/js/sa-helper-chatbot-public.js',
            array('jquery', 'marked', 'dompurify'), // Ensure 'marked' and 'dompurify' are dependencies
            SA_HELPER_VERSION,
            true
        );

        // Add the AJAX object for the JavaScript
        wp_localize_script(
            'sa-helper-chatbot-public',
            'saHelperChatbot',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('sa-helper-chatbot-nonce'),
                'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            )
        );
    }

    /**
     * Display the chatbot interface on the frontend
     */
    public function display_chatbot() {
        $options = get_option('sa_helper_chatbot_options', array());
        
        // Check if chatbot is enabled - default to true if setting doesn't exist
        if (isset($options['general']['enable']) && $options['general']['enable'] === false) {
            return;
        }
          // Get chatbot settings with defaults
        $title = isset($options['general']['title']) ? $options['general']['title'] : 'Helper Bot';
        $welcome_message = isset($options['general']['welcome_message']) ? $options['general']['welcome_message'] : 'Hello! How can I help you today?';
        
        // Include the chatbot template
        include SA_HELPER_PATH . 'templates/chatbot.php';
    }   
    
    /**
     * Process the chatbot message via AJAX
     */
    public function process_message() {
        // Security check
        check_ajax_referer('sa-helper-chatbot-nonce', 'nonce');
        
        // Get the message and additional data
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        $page_content = isset($_POST['page_content']) ? sanitize_textarea_field($_POST['page_content']) : '';
        $page_url = isset($_POST['page_url']) ? esc_url_raw($_POST['page_url']) : '';
        $page_title = isset($_POST['page_title']) ? sanitize_text_field($_POST['page_title']) : '';

        if (empty($message)) {
            wp_send_json_error('Empty message');
            return;
        }
        
        // Rate limiting check (simple implementation)
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $rate_limit_key = 'sa_helper_rate_limit_' . md5($user_ip);
        $rate_limit = get_transient($rate_limit_key);
        
        if ($rate_limit && $rate_limit > 10) { // Max 10 requests per minute
            wp_send_json_error(array('message' => 'Too many requests. Please wait a moment before sending another message.'));
            return;
        }
        
        // Update rate limiting
        set_transient($rate_limit_key, ($rate_limit ?: 0) + 1, 60); // 60 seconds
        
        // Check if AI handler is available
        if (!isset($this->ai_handler)) {
            wp_send_json_error(array('message' => 'AI service is not available right now. Please try again later.'));
            return;
        }
        
        // Log the request context (for debugging)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SA Helper Bot Request - Page: ' . $page_title . ' (' . $page_url . '), Message length: ' . strlen($message));
        }
          try {
            // Get response with predictive suggestions from AI handler
            $result = $this->ai_handler->get_response_with_suggestions($message, $page_content);
            
            // Allow filtering of the final response
            $response = apply_filters('sa_helper_chatbot_final_response', $result['response'], $message, $page_content, $page_url);
            $suggestions = apply_filters('sa_helper_chatbot_filter_suggestions', $result['suggestions'], $message, $response);
            
            // Send the response with additional metadata and suggestions
            wp_send_json_success(array(
                'response' => $response,
                'suggestions' => $suggestions,
                'session_stats' => $this->ai_handler->get_session_stats(),
                'timestamp' => current_time('timestamp')
            ));
        } catch (Exception $e) {
            // Log the error
            error_log('SA Helper Bot Error: ' . $e->getMessage());
            
            // Send fallback response
            wp_send_json_success(array(
                'response' => "I'm having trouble processing your request right now. Let me try again with a simpler approach.",
                'error' => true,
                'timestamp' => current_time('timestamp')
            ));
        }
    }

    /**
     * Process user feedback on chatbot responses
     */
    public function process_feedback() {
        // Security check
        check_ajax_referer('sa-helper-chatbot-nonce', 'nonce');
        
        // Get the feedback data
        $feedback = isset($_POST['feedback']) ? sanitize_text_field($_POST['feedback']) : '';
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        $response = isset($_POST['response']) ? sanitize_text_field($_POST['response']) : '';
        
        if (empty($feedback)) {
            wp_send_json_error('Invalid feedback');
            return;
        }
        
        // Store the feedback
        $feedbacks = get_option('sa_helper_chatbot_feedback', array());
        $feedbacks[] = array(
            'feedback' => $feedback,
            'message' => $message,
            'response' => $response,
            'timestamp' => current_time('mysql')
        );
        
        // Limit stored feedback to last 100 entries
        if (count($feedbacks) > 100) {
            $feedbacks = array_slice($feedbacks, -100);
        }
          update_option('sa_helper_chatbot_feedback', $feedbacks);
        
        wp_send_json_success('Feedback recorded');
    }

    /**
     * Get initial suggestions for when chat first loads
     */
    public function get_initial_suggestions() {
        // Security check
        check_ajax_referer('sa-helper-chatbot-nonce', 'nonce');
        
        // Check if AI handler is available
        if (!isset($this->ai_handler)) {
            wp_send_json_error('AI service not available');
            return;
        }
        
        // Get initial suggestions
        $suggestions = $this->ai_handler->get_predictive_suggestions();
        
        wp_send_json_success(array(
            'suggestions' => $suggestions
        ));
    }

    /**
     * Get contextual suggestions based on conversation state
     */
    public function get_contextual_suggestions() {
        // Security check
        check_ajax_referer('sa-helper-chatbot-nonce', 'nonce');
        
        if (!$this->ai_handler) {
            wp_send_json_error('AI handler not available');
            return;
        }
        
        $conversation_length = isset($_POST['conversation_length']) ? intval($_POST['conversation_length']) : 0;
        
        // Get contextual suggestions based on conversation state
        if ($conversation_length === 0) {
            $suggestions = $this->ai_handler->get_initial_suggestions();
        } else {
            // Get suggestions based on conversation context
            $suggestions = $this->ai_handler->get_predictive_suggestions();
        }
        
        wp_send_json_success(array(
            'suggestions' => $suggestions
        ));
    }
}
