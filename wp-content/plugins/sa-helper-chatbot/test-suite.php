<?php
/**
 * SA Helper Chatbot - Comprehensive Test Suite
 * 
 * This script tests all major functionality of the plugin
 * Run this in a WordPress environment or via WP-CLI
 */

// Prevent direct access
if (!defined('ABSPATH') && !defined('WP_CLI')) {
    die('Direct access not permitted');
}

class SA_Helper_Chatbot_Test_Suite {
    
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    
    public function __construct() {
        echo "🤖 SA Helper Chatbot - Test Suite v2.0.0\n";
        echo "=========================================\n\n";
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        $this->test_plugin_structure();
        $this->test_settings_functionality();
        $this->test_ai_class_functionality();
        $this->test_knowledge_base();
        $this->test_session_management();
        $this->test_api_integration();
        $this->test_admin_functionality();
        $this->test_public_functionality();
        $this->test_security_features();
        $this->test_performance_features();
        
        $this->print_summary();
    }
    
    /**
     * Test basic plugin structure and file existence
     */
    private function test_plugin_structure() {
        echo "📁 Testing Plugin Structure...\n";
        
        $required_files = [
            'sa-helper-chatbot.php' => 'Main plugin file',
            'uninstall.php' => 'Uninstall script',
            'includes/class-sa-helper-chatbot.php' => 'Core plugin class',
            'includes/class-sa-helper-chatbot-ai.php' => 'AI functionality class',
            'includes/class-sa-helper-chatbot-public.php' => 'Public functionality',
            'includes/class-sa-helper-chatbot-loader.php' => 'Hook loader',
            'admin/class-sa-helper-chatbot-admin.php' => 'Admin interface',
            'admin/class-sa-helper-chatbot-dashboard.php' => 'Dashboard widgets',
            'admin/class-sa-helper-chatbot-api-test.php' => 'API testing',
            'assets/css/sa-helper-chatbot-public.css' => 'Public CSS',
            'assets/css/sa-helper-chatbot-admin.css' => 'Admin CSS',
            'assets/js/sa-helper-chatbot-public.js' => 'Public JavaScript',
            'assets/js/sa-helper-chatbot-admin.js' => 'Admin JavaScript',
            'templates/chatbot.php' => 'Chatbot template'
        ];
        
        $base_path = plugin_dir_path(__FILE__);
        foreach ($required_files as $file => $description) {
            $file_path = $base_path . $file;
            if (file_exists($file_path)) {
                $this->assert_test(true, "✓ {$description} exists");
            } else {
                $this->assert_test(false, "✗ {$description} missing: {$file}");
            }
        }
        echo "\n";
    }
    
    /**
     * Test settings and configuration
     */
    private function test_settings_functionality() {
        echo "⚙️ Testing Settings Functionality...\n";
        
        // Test default options
        $options = get_option('sa_helper_chatbot_options', []);
        $this->assert_test(
            is_array($options) && !empty($options),
            "Plugin options are properly initialized"
        );
        
        // Test knowledge base
        $knowledge = get_option('sa_helper_chatbot_knowledge', []);
        $this->assert_test(
            is_array($knowledge),
            "Knowledge base is properly initialized"
        );
        
        // Test required option keys
        $required_keys = ['general', 'gemini_api'];
        foreach ($required_keys as $key) {
            $this->assert_test(
                isset($options[$key]),
                "Required option key '{$key}' exists"
            );
        }
        
        echo "\n";
    }
    
    /**
     * Test AI class functionality
     */
    private function test_ai_class_functionality() {
        echo "🧠 Testing AI Class Functionality...\n";
        
        if (class_exists('SA_Helper_Chatbot_AI')) {
            $ai = new SA_Helper_Chatbot_AI();
            $this->assert_test(true, "AI class instantiated successfully");
            
            // Test basic response method
            try {
                $response = $ai->get_response("Hello, this is a test message", "This is test page content");
                $this->assert_test(
                    !empty($response) && is_string($response),
                    "AI generates valid response"
                );
            } catch (Exception $e) {
                $this->assert_test(false, "AI response failed: " . $e->getMessage());
            }
            
            // Test session management
            try {
                $stats = $ai->get_session_stats();
                $this->assert_test(
                    is_array($stats) && isset($stats['session_id']),
                    "Session management works correctly"
                );
            } catch (Exception $e) {
                $this->assert_test(false, "Session management failed: " . $e->getMessage());
            }
            
        } else {
            $this->assert_test(false, "AI class not found");
        }
        
        echo "\n";
    }
    
    /**
     * Test knowledge base functionality
     */
    private function test_knowledge_base() {
        echo "📚 Testing Knowledge Base...\n";
        
        $knowledge = get_option('sa_helper_chatbot_knowledge', []);
        
        $expected_sections = ['company_info', 'website_navigation', 'recent_news', 'faq'];
        foreach ($expected_sections as $section) {
            $this->assert_test(
                array_key_exists($section, $knowledge),
                "Knowledge base has '{$section}' section"
            );
        }
        
        // Test knowledge base update
        $test_knowledge = $knowledge;
        $test_knowledge['test_section'] = 'Test content';
        update_option('sa_helper_chatbot_knowledge', $test_knowledge);
        
        $updated_knowledge = get_option('sa_helper_chatbot_knowledge', []);
        $this->assert_test(
            isset($updated_knowledge['test_section']),
            "Knowledge base can be updated"
        );
        
        // Clean up
        unset($updated_knowledge['test_section']);
        update_option('sa_helper_chatbot_knowledge', $updated_knowledge);
        
        echo "\n";
    }
    
    /**
     * Test session management
     */
    private function test_session_management() {
        echo "💾 Testing Session Management...\n";
        
        if (class_exists('SA_Helper_Chatbot_AI')) {
            $ai = new SA_Helper_Chatbot_AI();
            
            // Test conversation history
            try {
                $history = $ai->get_conversation_history();
                $this->assert_test(
                    is_array($history),
                    "Conversation history returns array"
                );
                
                // Test storing messages
                $ai->store_user_message("Test user message");
                $ai->store_bot_response("Test bot response");
                
                $updated_history = $ai->get_conversation_history();
                $this->assert_test(
                    count($updated_history) >= 2,
                    "Messages are stored in conversation history"
                );
                
            } catch (Exception $e) {
                $this->assert_test(false, "Session management error: " . $e->getMessage());
            }
        } else {
            $this->assert_test(false, "AI class not available for session testing");
        }
        
        echo "\n";
    }
    
    /**
     * Test API integration readiness
     */
    private function test_api_integration() {
        echo "🔌 Testing API Integration...\n";
        
        $options = get_option('sa_helper_chatbot_options', []);
        $api_settings = isset($options['gemini_api']) ? $options['gemini_api'] : [];
        
        // Test API settings structure
        $required_api_keys = ['enable', 'api_key', 'model', 'include_page_content', 'temperature', 'max_tokens'];
        foreach ($required_api_keys as $key) {
            $this->assert_test(
                array_key_exists($key, $api_settings),
                "API setting '{$key}' is configured"
            );
        }
        
        // Test API functionality (without actual API call)
        if (class_exists('SA_Helper_Chatbot_AI')) {
            $ai = new SA_Helper_Chatbot_AI();
            $reflection = new ReflectionClass($ai);
            
            $this->assert_test(
                $reflection->hasMethod('get_gemini_response'),
                "Gemini API method exists"
            );
            
            $this->assert_test(
                $reflection->hasMethod('prepare_context_for_gemini'),
                "Context preparation method exists"
            );
            
            $this->assert_test(
                $reflection->hasMethod('build_gemini_prompt'),
                "Prompt building method exists"
            );
        }
        
        echo "\n";
    }
    
    /**
     * Test admin functionality
     */
    private function test_admin_functionality() {
        echo "🔧 Testing Admin Functionality...\n";
        
        if (class_exists('SA_Helper_Chatbot_Admin')) {
            $admin = new SA_Helper_Chatbot_Admin();
            $this->assert_test(true, "Admin class instantiated successfully");
            
            // Test admin methods
            $reflection = new ReflectionClass($admin);
            $required_methods = [
                'add_menu_page',
                'register_settings',
                'display_options_page',
                'display_knowledge_base_editor'
            ];
            
            foreach ($required_methods as $method) {
                $this->assert_test(
                    $reflection->hasMethod($method),
                    "Admin method '{$method}' exists"
                );
            }
        } else {
            $this->assert_test(false, "Admin class not found");
        }
        
        // Test dashboard widget
        if (class_exists('SA_Helper_Chatbot_Dashboard')) {
            $this->assert_test(true, "Dashboard class exists");
        } else {
            $this->assert_test(false, "Dashboard class not found");
        }
        
        echo "\n";
    }
    
    /**
     * Test public functionality
     */
    private function test_public_functionality() {
        echo "🌐 Testing Public Functionality...\n";
        
        if (class_exists('SA_Helper_Chatbot_Public')) {
            $public = new SA_Helper_Chatbot_Public();
            $this->assert_test(true, "Public class instantiated successfully");
            
            // Test public methods
            $reflection = new ReflectionClass($public);
            $required_methods = [
                'enqueue_styles',
                'enqueue_scripts',
                'display_chatbot',
                'process_message',
                'process_feedback'
            ];
            
            foreach ($required_methods as $method) {
                $this->assert_test(
                    $reflection->hasMethod($method),
                    "Public method '{$method}' exists"
                );
            }
        } else {
            $this->assert_test(false, "Public class not found");
        }
        
        echo "\n";
    }
    
    /**
     * Test security features
     */
    private function test_security_features() {
        echo "🔒 Testing Security Features...\n";
        
        // Test nonce verification in AJAX handlers
        if (class_exists('SA_Helper_Chatbot_Public')) {
            $reflection = new ReflectionClass('SA_Helper_Chatbot_Public');
            $process_message = $reflection->getMethod('process_message');
            $method_content = file_get_contents($reflection->getFileName());
            
            $this->assert_test(
                strpos($method_content, 'check_ajax_referer') !== false,
                "AJAX nonce verification implemented"
            );
            
            $this->assert_test(
                strpos($method_content, 'sanitize_text_field') !== false ||
                strpos($method_content, 'sanitize_textarea_field') !== false,
                "Input sanitization implemented"
            );
        }
        
        // Test direct access protection
        $plugin_files = [
            'sa-helper-chatbot.php',
            'includes/class-sa-helper-chatbot.php',
            'admin/class-sa-helper-chatbot-admin.php'
        ];
        
        $base_path = plugin_dir_path(__FILE__);
        foreach ($plugin_files as $file) {
            $content = file_get_contents($base_path . $file);
            $this->assert_test(
                strpos($content, 'WPINC') !== false || strpos($content, 'ABSPATH') !== false,
                "Direct access protection in {$file}"
            );
        }
        
        echo "\n";
    }
    
    /**
     * Test performance features
     */
    private function test_performance_features() {
        echo "⚡ Testing Performance Features...\n";
        
        // Test caching/transients usage
        $base_path = plugin_dir_path(__FILE__);
        $ai_content = file_get_contents($base_path . 'includes/class-sa-helper-chatbot-ai.php');
        
        $this->assert_test(
            strpos($ai_content, 'get_transient') !== false || strpos($ai_content, 'set_transient') !== false,
            "Caching mechanisms implemented"
        );
        
        // Test rate limiting
        $public_content = file_get_contents($base_path . 'includes/class-sa-helper-chatbot-public.php');
        $this->assert_test(
            strpos($public_content, 'rate_limit') !== false,
            "Rate limiting implemented"
        );
        
        // Test content length limits
        $js_content = file_get_contents($base_path . 'assets/js/sa-helper-chatbot-public.js');
        $this->assert_test(
            strpos($js_content, '4000') !== false || strpos($js_content, 'length') !== false,
            "Content length limiting in JavaScript"
        );
        
        echo "\n";
    }
    
    /**
     * Assert test result
     */
    private function assert_test($condition, $description) {
        $this->total_tests++;
        if ($condition) {
            $this->passed_tests++;
            echo "  ✅ {$description}\n";
            $this->test_results[] = ['status' => 'PASS', 'description' => $description];
        } else {
            echo "  ❌ {$description}\n";
            $this->test_results[] = ['status' => 'FAIL', 'description' => $description];
        }
    }
    
    /**
     * Print test summary
     */
    private function print_summary() {
        echo "\n📊 Test Summary\n";
        echo "===============\n";
        echo "Total Tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        echo "Success Rate: " . round(($this->passed_tests / $this->total_tests) * 100, 2) . "%\n\n";
        
        // Show failed tests
        $failed_tests = array_filter($this->test_results, function($test) {
            return $test['status'] === 'FAIL';
        });
        
        if (!empty($failed_tests)) {
            echo "❌ Failed Tests:\n";
            foreach ($failed_tests as $test) {
                echo "  - {$test['description']}\n";
            }
            echo "\n";
        }
        
        if ($this->passed_tests === $this->total_tests) {
            echo "🎉 All tests passed! The plugin is ready for production.\n";
        } else {
            echo "⚠️ Some tests failed. Please review and fix the issues before deployment.\n";
        }
        
        echo "\n✨ SA Helper Chatbot v2.0.0 Test Complete ✨\n";
    }
}

// Run tests if this file is executed directly
if (defined('WP_CLI') && WP_CLI || (defined('ABSPATH') && current_user_can('manage_options'))) {
    $test_suite = new SA_Helper_Chatbot_Test_Suite();
    $test_suite->run_all_tests();
}
