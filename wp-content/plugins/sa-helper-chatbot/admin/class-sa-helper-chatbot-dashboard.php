<?php
/**
 * Dashboard functionality for admin
 *
 * @package SA_Helper_Chatbot
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class SA_Helper_Chatbot_Dashboard {

    /**
     * Initialize dashboard hooks
     */
    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
    }

    /**
     * Add chatbot analytics widget to dashboard
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'sa_helper_chatbot_analytics',
            'SA Helper Chatbot Analytics',
            array($this, 'display_analytics_widget')
        );
    }

    /**
     * Display analytics widget content
     */
    public function display_analytics_widget() {
        // Get feedback data
        $feedbacks = get_option('sa_helper_chatbot_feedback', array());
        $total_feedback = count($feedbacks);
        $positive_feedback = count(array_filter($feedbacks, function($f) { return $f['feedback'] === 'positive'; }));
        $negative_feedback = count(array_filter($feedbacks, function($f) { return $f['feedback'] === 'negative'; }));
        
        // Calculate satisfaction rate
        $satisfaction_rate = $total_feedback > 0 ? round(($positive_feedback / $total_feedback) * 100, 1) : 0;
        
        // Get recent feedback
        $recent_feedback = array_slice(array_reverse($feedbacks), 0, 5);
        
        // Get API usage stats
        $options = get_option('sa_helper_chatbot_options', array());
        $api_enabled = isset($options['gemini_api']['enable']) && $options['gemini_api']['enable'];
        $api_configured = $api_enabled && !empty($options['gemini_api']['api_key']);
        
        ?>
        <div class="sa-helper-dashboard-widget">
            <div class="sa-helper-stats-grid">
                <div class="sa-helper-stat-box">
                    <div class="sa-helper-stat-number"><?php echo esc_html($satisfaction_rate); ?>%</div>
                    <div class="sa-helper-stat-label">Satisfaction Rate</div>
                </div>
                <div class="sa-helper-stat-box">
                    <div class="sa-helper-stat-number"><?php echo esc_html($total_feedback); ?></div>
                    <div class="sa-helper-stat-label">Total Feedback</div>
                </div>
                <div class="sa-helper-stat-box">
                    <div class="sa-helper-stat-number"><?php echo $api_configured ? '✓' : '✗'; ?></div>
                    <div class="sa-helper-stat-label">AI Status</div>
                </div>
            </div>
            
            <?php if ($total_feedback > 0): ?>
                <div class="sa-helper-feedback-breakdown">
                    <h4>Feedback Breakdown</h4>
                    <div class="sa-helper-feedback-bar">
                        <div class="sa-helper-positive" style="width: <?php echo ($positive_feedback / $total_feedback) * 100; ?>%"></div>
                        <div class="sa-helper-negative" style="width: <?php echo ($negative_feedback / $total_feedback) * 100; ?>%"></div>
                    </div>
                    <div class="sa-helper-feedback-labels">
                        <span class="positive">👍 <?php echo esc_html($positive_feedback); ?></span>
                        <span class="negative">👎 <?php echo esc_html($negative_feedback); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!$api_configured): ?>
                <div class="sa-helper-notice">
                    <p>
                        <strong>Tip:</strong> 
                        <a href="<?php echo esc_url(admin_url('admin.php?page=sa-helper-chatbot')); ?>">
                            Configure Gemini AI
                        </a> 
                        for more intelligent responses.
                    </p>
                </div>
            <?php endif; ?>
            
            <div class="sa-helper-quick-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=sa-helper-chatbot')); ?>" class="button">
                    Manage Settings
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=sa-helper-chatbot-api-test')); ?>" class="button">
                    Test API
                </a>
            </div>
        </div>
        
        <style>
            .sa-helper-dashboard-widget {
                padding: 12px;
            }
            
            .sa-helper-stats-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
                margin-bottom: 20px;
            }
            
            .sa-helper-stat-box {
                text-align: center;
                padding: 15px;
                background: #f9f9f9;
                border-radius: 6px;
                border: 1px solid #e1e1e1;
            }
            
            .sa-helper-stat-number {
                font-size: 24px;
                font-weight: bold;
                color: #0073aa;
                margin-bottom: 5px;
            }
            
            .sa-helper-stat-label {
                font-size: 12px;
                color: #666;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .sa-helper-feedback-breakdown {
                margin-bottom: 20px;
            }
            
            .sa-helper-feedback-breakdown h4 {
                margin: 0 0 10px 0;
                font-size: 14px;
            }
            
            .sa-helper-feedback-bar {
                height: 20px;
                background: #f0f0f0;
                border-radius: 10px;
                overflow: hidden;
                display: flex;
                margin-bottom: 8px;
            }
            
            .sa-helper-positive {
                background: linear-gradient(45deg, #46b450, #5cbf60);
            }
            
            .sa-helper-negative {
                background: linear-gradient(45deg, #dc3232, #e74c3c);
            }
            
            .sa-helper-feedback-labels {
                display: flex;
                justify-content: space-between;
                font-size: 12px;
            }
            
            .sa-helper-notice {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 4px;
                padding: 12px;
                margin-bottom: 15px;
            }
            
            .sa-helper-notice p {
                margin: 0;
                font-size: 13px;
            }
            
            .sa-helper-quick-actions {
                text-align: center;
            }
            
            .sa-helper-quick-actions .button {
                margin: 0 5px;
            }
        </style>
        <?php
    }
}

// Initialize dashboard if in admin
if (is_admin()) {
    new SA_Helper_Chatbot_Dashboard();
}
