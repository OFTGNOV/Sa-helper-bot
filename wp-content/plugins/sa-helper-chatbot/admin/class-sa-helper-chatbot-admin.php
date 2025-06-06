<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    SA_Helper_Chatbot
 */
class SA_Helper_Chatbot_Admin {

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style('sa-helper-chatbot-admin', SA_HELPER_URL . 'assets/css/sa-helper-chatbot-admin.css', array(), SA_HELPER_VERSION, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        wp_enqueue_script('sa-helper-chatbot-admin', SA_HELPER_URL . 'assets/js/sa-helper-chatbot-admin.js', array('jquery'), SA_HELPER_VERSION, false);
    }

    /**
     * Add the options page to the admin menu
     */    public function add_menu_page() {
        add_menu_page(
            'SA Helper Chatbot Settings', 
            'SA Helper Bot', 
            'manage_options', 
            'sa-helper-chatbot', 
            array($this, 'display_options_page'), 
            'dashicons-format-chat',
            30
        );
        
        // Add submenu for API testing
        add_submenu_page(
            'sa-helper-chatbot',
            'Test Gemini API',
            'Test API Connection',
            'manage_options',
            'sa-helper-chatbot-api-test',
            'sa_helper_display_api_test_page'
        );
    }

    /**
     * Display the options page
     */
    public function display_options_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                // Only register one settings group at a time
                settings_fields('sa_helper_chatbot_options');
                do_settings_sections('sa_helper_chatbot_options');
                ?>
                
                <div class="sa-helper-chatbot-knowledge-base">
                    <h2>Knowledge Base</h2>
                    <p>Add information about your company, website navigation, and recent news for the chatbot to use.</p>
                    <?php 
                    // Don't call settings_fields again - it was already called above
                    $this->display_knowledge_base_editor(); 
                    ?>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register settings
     */   
    public function register_settings() {
        // Register each setting only once
        register_setting('sa_helper_chatbot_options', 'sa_helper_chatbot_options', array(
            'sanitize_callback' => array($this, 'sanitize_options')
        ));
        register_setting('sa_helper_chatbot_options', 'sa_helper_chatbot_knowledge');

        // General Settings Section
        add_settings_section(
            'sa_helper_chatbot_general_section',
            'General Settings',
            array($this, 'general_settings_section_callback'),
            'sa_helper_chatbot_options'
        );

        // Add chatbot enable field
        add_settings_field(
            'enable_chatbot',
            'Enable Chatbot',
            array($this, 'enable_chatbot_field_callback'),
            'sa_helper_chatbot_options',
            'sa_helper_chatbot_general_section'
        );

        // Add chatbot title field
        add_settings_field(
            'chatbot_title',
            'Chatbot Title',
            array($this, 'chatbot_title_field_callback'),
            'sa_helper_chatbot_options',
            'sa_helper_chatbot_general_section'
        );

        // Add welcome message field
        add_settings_field(
            'welcome_message',
            'Welcome Message',
            array($this, 'welcome_message_field_callback'),
            'sa_helper_chatbot_options',
            'sa_helper_chatbot_general_section'
        );

        // Gemini API Settings Section
        add_settings_section(
            'sa_helper_chatbot_api_section',
            'Gemini API Settings',
            array($this, 'api_settings_section_callback'),
            'sa_helper_chatbot_options'
        );

        // Add Gemini API enable field
        add_settings_field(
            'gemini_api_enable',
            'Enable Gemini AI',
            array($this, 'gemini_api_enable_callback'),
            'sa_helper_chatbot_options',
            'sa_helper_chatbot_api_section'
        );

        // Add Gemini API key field
        add_settings_field(
            'gemini_api_key',
            'Gemini API Key',
            array($this, 'gemini_api_key_callback'),
            'sa_helper_chatbot_options',
            'sa_helper_chatbot_api_section'
        );

        // Add Gemini model selection field
        add_settings_field(
            'gemini_model',
            'Gemini Model',
            array($this, 'gemini_model_callback'),
            'sa_helper_chatbot_options',
            'sa_helper_chatbot_api_section'
        );
    }

    /**
     * Sanitize the options before saving
     */
    public function sanitize_options($options) {
        // Make sure we preserve existing settings when updating
        $old_options = get_option('sa_helper_chatbot_options', array());
        $options = wp_parse_args($options, $old_options);
        
        // Ensure nested arrays exist
        if (!isset($options['general'])) {
            $options['general'] = array();
        }
        
        if (!isset($options['gemini_api'])) {
            $options['gemini_api'] = array();
        }
        
        return $options;
    }

    /**
     * General settings section callback
     */
    public function general_settings_section_callback() {
        echo '<p>Configure the appearance and behavior of your chatbot.</p>';
    }

    /**
     * Enable chatbot field callback
     */
    public function enable_chatbot_field_callback() {
        $options = get_option('sa_helper_chatbot_options', array());
        $enabled = isset($options['general']['enable']) ? $options['general']['enable'] : true;
        echo '<input type="checkbox" name="sa_helper_chatbot_options[general][enable]" value="1" ' . checked($enabled, true, false) . ' />';
        echo '<span class="description">Enable or disable the chatbot on your website</span>';
    }

    /**
     * Chatbot title field callback
     */
    public function chatbot_title_field_callback() {
        $options = get_option('sa_helper_chatbot_options', array());
        $title = isset($options['general']['title']) ? $options['general']['title'] : 'Helper Bot';
        echo '<input type="text" name="sa_helper_chatbot_options[general][title]" value="' . esc_attr($title) . '" />';
    }

    /**
     * Welcome message field callback
     */
    public function welcome_message_field_callback() {
        $options = get_option('sa_helper_chatbot_options', array());
        $message = isset($options['general']['welcome_message']) ? $options['general']['welcome_message'] : 'Hi there! How can I help you today?';
        echo '<textarea name="sa_helper_chatbot_options[general][welcome_message]" rows="3" cols="50">' . esc_textarea($message) . '</textarea>';
    }

    /**
     * API settings section callback
     */
    public function api_settings_section_callback() {
        echo '<p>Configure Google Gemini API settings to enable advanced AI capabilities for your chatbot.</p>';
        echo '<p>Don\'t have an API key? <a href="https://ai.google.dev/tutorials/setup" target="_blank">Get a Gemini API key from Google</a>.</p>';
    }

    /**
     * Enable Gemini API callback
     */
    public function gemini_api_enable_callback() {
        $options = get_option('sa_helper_chatbot_options', array());
        $enabled = isset($options['gemini_api']['enable']) ? $options['gemini_api']['enable'] : false;
        echo '<input type="checkbox" name="sa_helper_chatbot_options[gemini_api][enable]" value="1" ' . checked($enabled, true, false) . ' />';
        echo '<span class="description">Enable Gemini AI for more intelligent responses (requires API key)</span>';
    }

    /**
     * Gemini API key callback
     */
    public function gemini_api_key_callback() {
        $options = get_option('sa_helper_chatbot_options', array());
        $api_key = isset($options['gemini_api']['api_key']) ? $options['gemini_api']['api_key'] : '';
        
        echo '<input type="password" name="sa_helper_chatbot_options[gemini_api][api_key]" value="' . esc_attr($api_key) . '" class="regular-text" autocomplete="off" />';
        if (!empty($api_key)) {
            echo '<span class="description" style="margin-left:10px;">✓ API key is set</span>';
        }
    }

    /**
     * Gemini model selection callback
     */
    public function gemini_model_callback() {
        $options = get_option('sa_helper_chatbot_options', array());
        $selected_model = isset($options['gemini_api']['model']) ? $options['gemini_api']['model'] : 'gemini-1.5-pro';
        
        $models = array(
            'gemini-1.5-pro' => 'Gemini 1.5 Pro (Recommended)',
            'gemini-1.5-flash' => 'Gemini 1.5 Flash (Faster, more efficient)',
            'gemini-1.0-pro' => 'Gemini 1.0 Pro (Legacy)',
            'gemini-1.0-pro-vision' => 'Gemini 1.0 Pro Vision (Supports images, legacy)'
        );
        
        echo '<select name="sa_helper_chatbot_options[gemini_api][model]">';
        foreach ($models as $model_id => $model_name) {
            echo '<option value="' . esc_attr($model_id) . '" ' . selected($selected_model, $model_id, false) . '>' . esc_html($model_name) . '</option>';
        }
        echo '</select>';
    }

    /**
     * Display the knowledge base editor
     */
    public function display_knowledge_base_editor() {
        $knowledge = get_option('sa_helper_chatbot_knowledge', array(
            'company_info' => '',
            'website_navigation' => '',
            'recent_news' => ''
        ));
        ?>
        <div class="sa-helper-knowledge-tabs">
            <div class="nav-tab-wrapper">
                <a href="#company-info" class="nav-tab nav-tab-active">Company Information</a>
                <a href="#website-navigation" class="nav-tab">Website Navigation</a>
                <a href="#recent-news" class="nav-tab">Recent News</a>
            </div>

            <div id="company-info" class="tab-content active">
                <h3>Company Information</h3>
                <p>Add information about your company that the chatbot can use to answer visitor questions.</p>
                <?php 
                wp_editor(
                    $knowledge['company_info'], 
                    'sa_helper_chatbot_knowledge_company_info',
                    array(
                        'textarea_name' => 'sa_helper_chatbot_knowledge[company_info]',
                        'textarea_rows' => 10,
                        'media_buttons' => false
                    )
                );
                ?>
            </div>

            <div id="website-navigation" class="tab-content">
                <h3>Website Navigation</h3>
                <p>Add information about your website structure to help visitors navigate.</p>
                <?php 
                wp_editor(
                    $knowledge['website_navigation'], 
                    'sa_helper_chatbot_knowledge_website_navigation',
                    array(
                        'textarea_name' => 'sa_helper_chatbot_knowledge[website_navigation]',
                        'textarea_rows' => 10,
                        'media_buttons' => false
                    )
                );
                ?>
            </div>

            <div id="recent-news" class="tab-content">
                <h3>Recent News</h3>
                <p>Add recent news, updates, or announcements.</p>
                <?php 
                wp_editor(
                    $knowledge['recent_news'], 
                    'sa_helper_chatbot_knowledge_recent_news',
                    array(
                        'textarea_name' => 'sa_helper_chatbot_knowledge[recent_news]',
                        'textarea_rows' => 10,
                        'media_buttons' => false
                    )
                );
                ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab functionality
            $('.sa-helper-knowledge-tabs .nav-tab').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.sa-helper-knowledge-tabs .nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').removeClass('active');
                $(target).addClass('active');
            });
        });
        </script>
        <style>
            .tab-content {
                display: none;
                padding: 20px;
                border: 1px solid #ccc;
                border-top: none;
            }
            .tab-content.active {
                display: block;
            }
        </style>
        <?php
    }
}
