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
    private $ai_handler;    /**
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
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'sa-helper-chatbot',
            SA_HELPER_URL . 'assets/js/sa-helper-chatbot-public.js',
            array('jquery'),
            SA_HELPER_VERSION,
            true
        );
        
        // Add the AJAX object for the JavaScript
        wp_localize_script('sa-helper-chatbot', 'saHelperChatbot', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sa-helper-chatbot-nonce')
        ));
    }

    /**
     * Display the chatbot interface on the frontend
     */
    public function display_chatbot() {
        $options = get_option('sa_helper_chatbot_options', array());
        
        // Check if chatbot is enabled
        if (isset($options['general']['enable']) && !$options['general']['enable']) {
            return;
        }
        
        // Get chatbot settings
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
        
        // Get the message
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
          if (empty($message)) {
            wp_send_json_error('Empty message');
            return;
        }
        
        // Check if AI handler is available
        if (!isset($this->ai_handler)) {
            wp_send_json_error(array('message' => 'AI service is not available right now. Please try again later.'));
            return;
        }
        
        // Get response from AI handler
        $response = $this->ai_handler->get_response($message);
        
        // Send the response
        wp_send_json_success(array(
            'response' => $response
        ));
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
}
