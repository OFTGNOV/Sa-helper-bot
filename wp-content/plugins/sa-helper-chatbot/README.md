# SA Helper Chatbot WordPress Plugin

A sophisticated AI-powered chatbot plugin for WordPress that provides intelligent customer support using Google's Gemini AI. Built specifically for Software Architects Jamaica, this chatbot offers contextual responses based on conversation history, page content, and a customizable knowledge base.

## 🚀 Features

### Core Functionality
- **AI-Powered Responses**: Powered by Google Gemini 1.5 Pro for intelligent, contextual conversations
- **Conversation Memory**: Maintains conversation history for contextual, goal-oriented responses
- **Knowledge Base Integration**: Customizable knowledge base with multiple sections
- **Page Content Awareness**: Can analyze current page content to provide relevant responses
- **Smart Caching**: Response caching system to improve performance and reduce API costs

### User Experience
- **Mobile-Optimized**: Fully responsive design with touch-friendly controls
- **Dark Mode Support**: Complete dark/light theme compatibility
- **Sticky Interface**: Header and input remain accessible during scrolling
- **Markdown Support**: Rich text formatting in responses (bold, italic, lists, links)
- **Real-time Typing**: Smooth typing animations for bot responses
- **Easy Access**: Floating chat button for quick access

### Admin Features
- **Dashboard**: Comprehensive admin dashboard with usage statistics
- **API Testing**: Built-in Gemini API testing and validation tools
- **Settings Management**: Easy configuration of all plugin settings
- **Session Management**: Conversation history tracking and management
- **Error Handling**: Robust error handling with fallback responses

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Google Gemini API key
- SSL certificate (recommended for security)

## 🔧 Installation

### Manual Installation

1. **Download the Plugin**
   ```bash
   # Clone or download the plugin files
   git clone [repository-url] sa-helper-chatbot
   ```

2. **Upload to WordPress**
   - Upload the `sa-helper-chatbot` folder to `/wp-content/plugins/`
   - Or upload the zip file via WordPress admin → Plugins → Add New → Upload

3. **Activate the Plugin**
   - Go to WordPress admin → Plugins
   - Find "SA Helper Chatbot" and click "Activate"

### WordPress Repository Installation
*Coming soon - plugin will be submitted to WordPress.org*

## ⚙️ Configuration

### 1. Get Google Gemini API Key

1. Visit [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Create a new API key
3. Copy the API key for use in the plugin

### 2. Configure the Plugin

1. **Access Settings**
   - Go to WordPress admin → SA Helper Bot → Settings

2. **API Configuration**
   - Enter your Gemini API key
   - Select your preferred model (gemini-1.5-pro recommended)
   - Set temperature (0.1-1.0, default: 0.7)
   - Test the API connection

3. **Display Settings**
   - Choose chat button position
   - Set button colors
   - Configure mobile responsiveness

4. **Content Settings**
   - Enable/disable page content analysis
   - Configure knowledge base sections
   - Set conversation history length

### 3. Customize Knowledge Base

1. **Navigate to Knowledge Base**
   - Go to SA Helper Bot → Knowledge Base

2. **Add Content Sections**
   ```
   Company Information: Details about your company
   Services: Description of your services
   FAQ: Frequently asked questions
   Contact: Contact information and methods
   Policies: Terms, privacy, refund policies
   ```

3. **Format Content**
   - Use clear, concise language
   - Structure information logically
   - Include relevant keywords

## 🎨 Customization

### CSS Customization

The plugin provides extensive CSS classes for customization:

```css
/* Main chat container */
.sa-helper-chatbot-container { }

/* Chat messages */
.sa-helper-chatbot-messages .user-message { }
.sa-helper-chatbot-messages .bot-message { }

/* Dark mode */
.dark-mode .sa-helper-chatbot-container { }

/* Mobile responsive */
@media (max-width: 768px) {
    .sa-helper-chatbot-container { }
}
```

### JavaScript Hooks

```javascript
// Custom event listeners
document.addEventListener('sa-chatbot-message-sent', function(event) {
    // Handle message sent
});

document.addEventListener('sa-chatbot-response-received', function(event) {
    // Handle response received
});
```

### WordPress Hooks

```php
// Filter bot responses
add_filter('sa_helper_chatbot_filter_response', function($response, $message) {
    // Modify response before display
    return $response;
}, 10, 2);

// Add custom knowledge sections
add_filter('sa_helper_chatbot_knowledge_sections', function($sections) {
    $sections['custom_section'] = 'Custom content here';
    return $sections;
});
```

## 📱 Mobile Optimization

The plugin is fully optimized for mobile devices:

- **Touch-Friendly**: Large touch targets and swipe gestures
- **Responsive Layout**: Adapts to all screen sizes
- **Sticky Elements**: Header and input remain accessible
- **Smooth Scrolling**: Native scroll behavior with momentum
- **Performance**: Optimized for mobile performance

## 🔒 Security

### Data Protection
- Conversation history stored in secure PHP sessions
- API keys encrypted in database
- Input sanitization and validation
- XSS protection

### Privacy Compliance
- No personal data stored permanently
- Session-based conversation history
- GDPR/CCPA compliant
- User consent mechanisms

## 🚨 Troubleshooting

### Common Issues

**Chatbot not appearing**
- Check if plugin is activated
- Verify JavaScript is enabled
- Check for theme conflicts

**API errors**
- Verify API key is correct
- Check API quota and billing
- Test API connection in admin

**Mobile issues**
- Clear browser cache
- Check CSS conflicts
- Verify responsive settings

**Performance issues**
- Enable response caching
- Optimize knowledge base content
- Check server resources

### Debug Mode

Enable debug logging:
```php
// Add to wp-config.php
define('SA_HELPER_CHATBOT_DEBUG', true);
```

## 📊 Analytics & Monitoring

### Built-in Analytics
- Message volume tracking
- Response time monitoring
- Error rate tracking
- User engagement metrics

### Admin Dashboard
- Real-time statistics
- Usage trends
- Performance metrics
- Error logs

## 🔄 Updates & Maintenance

### Automatic Updates
- WordPress auto-update compatible
- Backward compatibility maintained
- Database migration handling

### Manual Updates
1. Backup your site
2. Download latest version
3. Replace plugin files
4. Check for database updates

## 🤝 Support

### Documentation
- [Wiki](link-to-wiki) - Detailed documentation
- [FAQ](link-to-faq) - Frequently asked questions
- [Video Tutorials](link-to-videos) - Step-by-step guides

### Community Support
- [Support Forum](link-to-forum)
- [GitHub Issues](link-to-github)
- [Discord Community](link-to-discord)

### Professional Support
- Email: support@softwarearchitects.com.jm
- Priority support available
- Custom development services

## 🛠️ Development

### File Structure
```
sa-helper-chatbot/
├── sa-helper-chatbot.php          # Main plugin file
├── README.md                      # This file
├── admin/                         # Admin interface
│   ├── class-sa-helper-chatbot-admin.php
│   ├── class-sa-helper-chatbot-dashboard.php
│   └── class-sa-helper-chatbot-api-test.php
├── includes/                      # Core functionality
│   ├── class-sa-helper-chatbot.php
│   ├── class-sa-helper-chatbot-loader.php
│   ├── class-sa-helper-chatbot-ai.php
│   └── class-sa-helper-chatbot-public.php
├── assets/                        # Frontend assets
│   ├── css/
│   │   ├── sa-helper-chatbot-admin.css
│   │   └── sa-helper-chatbot-public.css
│   └── js/
│       ├── sa-helper-chatbot-admin.js
│       ├── sa-helper-chatbot-public.js
│       ├── marked.min.js
│       └── dompurify.min.js
└── templates/                     # Template files
    └── chatbot.php
```

### Contributing
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

### Coding Standards
- WordPress Coding Standards
- PSR-4 autoloading
- Comprehensive documentation
- Unit testing required

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 🏆 Credits

### Development Team
- **Software Architects Jamaica** - Core development
- **Contributors** - Community contributions

### Dependencies
- **Google Gemini AI** - AI processing
- **Marked.js** - Markdown parsing
- **DOMPurify** - XSS protection

### Special Thanks
- WordPress community
- Beta testers
- Feature contributors

## 📈 Changelog

### Version 1.0.0 (Current)
- Initial release
- Google Gemini AI integration
- Mobile-optimized interface
- Dark mode support
- Conversation history
- Knowledge base system
- Admin dashboard
- API testing tools

### Planned Features
- Multi-language support
- Voice input/output
- Advanced analytics
- Integration with popular forms
- Webhook support
- REST API endpoints

---

**Made with ❤️ by Software Architects Jamaica**

For more information, visit [softwarearchitects.com.jm](https://softwarearchitects.com.jm)
