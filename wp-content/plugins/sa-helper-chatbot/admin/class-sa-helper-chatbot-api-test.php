<?php
/**
 * API Test functionality for admin
 *
 * @package SA_Helper_Chatbot
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Display the API test page in admin
 */
function sa_helper_display_api_test_page() {
    // Get API settings
    $options = get_option('sa_helper_chatbot_options', array());
    $api_settings = isset($options['gemini_api']) ? $options['gemini_api'] : array(
        'enable' => false,
        'api_key' => '',
        'model' => 'gemini-pro'
    );
    
    $is_enabled = isset($api_settings['enable']) && $api_settings['enable'] === true;
    $has_api_key = !empty($api_settings['api_key']);
    $model = isset($api_settings['model']) ? $api_settings['model'] : 'gemini-pro';
    
    // Check if form was submitted
    $test_message = '';
    $api_response = '';
    $response_status = '';
    
    if (isset($_POST['sa_helper_test_api']) && check_admin_referer('sa_helper_test_api_nonce')) {
        $test_message = isset($_POST['test_message']) ? sanitize_textarea_field($_POST['test_message']) : 'Hello, is the API working?';
        
        // Only proceed if API is enabled and key is set
        if ($is_enabled && $has_api_key) {
            $api_response = sa_helper_test_api_connection($test_message, $api_settings);
            
            if (is_wp_error($api_response)) {
                $response_status = 'error';
                $api_response = $api_response->get_error_message();
            } else {
                $response_status = 'success';
            }
        } else {
            $response_status = 'error';
            $api_response = 'API is not enabled or API key is not set. Please configure the API in the main settings.';
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Test Gemini API Connection</h1>
        
        <?php if (!$is_enabled || !$has_api_key): ?>
            <div class="notice notice-warning">
                <p>
                    <strong>API is not fully configured.</strong> 
                    Please <a href="<?php echo admin_url('admin.php?page=sa-helper-chatbot'); ?>">enable the API and set your API key</a> before testing.
                </p>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('sa_helper_test_api_nonce'); ?>
            <input type="hidden" name="sa_helper_test_api" value="1">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="test_message">Test Message</label>
                    </th>
                    <td>
                        <textarea name="test_message" id="test_message" rows="3" class="large-text"><?php echo esc_textarea($test_message ?: 'Hello, can you tell me about your capabilities?'); ?></textarea>
                        <p class="description">Enter a message to test the API response.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">API Status</th>
                    <td>
                        <?php if ($is_enabled && $has_api_key): ?>
                            <span class="api-status-indicator api-status-enabled"></span> Enabled (<?php echo esc_html($model); ?>)
                        <?php else: ?>
                            <span class="api-status-indicator api-status-disabled"></span> Disabled
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Test API Connection', 'primary', 'submit', true, $is_enabled && $has_api_key ? array() : array('disabled' => 'disabled')); ?>
            
            <?php if ($response_status): ?>
                <div class="api-test-results">
                    <h2>API Response</h2>
                    <?php if ($response_status === 'error'): ?>
                        <div class="notice notice-error">
                            <p><strong>Error:</strong> <?php echo esc_html($api_response); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="notice notice-success">
                            <p><strong>Success!</strong> The API responded correctly.</p>
                        </div>
                        <div class="api-response">
                            <h3>Response:</h3>
                            <div class="api-response-content">
                                <?php echo nl2br(esc_html($api_response)); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
    <style>
        .api-test-results {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .api-response {
            margin-top: 15px;
        }
        .api-response-content {
            padding: 15px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 3px;
            min-height: 100px;
        }
    </style>
    <?php
}

/**
 * Test the API connection with a message
 * 
 * @param string $message The message to send to the API
 * @param array $api_settings API settings
 * @return string|WP_Error Response text or error
 */
function sa_helper_test_api_connection($message, $api_settings) {
    $api_key = $api_settings['api_key'];
    $model = isset($api_settings['model']) ? $api_settings['model'] : 'gemini-pro';
    $endpoint = "https://generativelanguage.googleapis.com/v1/models/$model:generateContent?key=$api_key";
    
    $prompt = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $message]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.4,
            'maxOutputTokens' => 800,
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
        return $response;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    if ($response_code !== 200) {
        return new WP_Error(
            'api_error', 
            'API returned error code: ' . $response_code . ' - ' . $response_body
        );
    }
    
    $response_data = json_decode($response_body, true);
    
    if (empty($response_data) || !isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
        return new WP_Error('api_error', 'Invalid or empty response received from the API');
    }
    
    return $response_data['candidates'][0]['content']['parts'][0]['text'];
}