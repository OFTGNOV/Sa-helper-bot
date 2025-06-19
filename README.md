# SA Helper Chatbot

A powerful WordPress plugin that provides an intelligent AI-powered chatbot for your website. Features enhanced Gemini API integration with intelligent fallback methods, conversation persistence, and responsive design optimized for all devices.

## 🌟 Features

### 🤖 **AI-Powered Responses**
- **Google Gemini API Integration** - Advanced AI responses using Google's latest Gemini models
- **Intelligent Fallback System** - Keyword-based responses when AI is unavailable
- **Customizable Knowledge Base** - Manage your own content for consistent responses
- **Conversation Context** - Maintains context across multiple exchanges

### 🎨 **User Experience**
- **Modern Design** - Clean, professional interface that matches your site
- **Mobile Optimized** - Perfect scrolling and interaction on all devices
- **Dark Mode Support** - Automatically adapts to user's system preferences
- **Accessibility Features** - Screen reader friendly with proper ARIA labels
- **Conversation Persistence** - Chat history preserved across page navigation

### 📱 **Mobile Excellence**
- **Touch-Optimized Scrolling** - Smooth scrolling with momentum on iOS/Android
- **Sticky Header** - Chat header stays visible during long conversations
- **Responsive Layout** - Adapts perfectly to any screen size
- **Background Scroll Prevention** - No interference with page scrolling

### 🛠️ **Admin Features**
- **Dashboard Analytics** - Track user engagement and satisfaction
- **Knowledge Base Editor** - Easy-to-use content management
- **API Testing Tools** - Built-in tools to test your Gemini API connection
- **Flexible Configuration** - Extensive customization options

## 📋 Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Node.js**: 18+ (for development)
- **PHP Extensions**: cURL, JSON
- **Google Gemini API Key** (optional, for AI features)

## 🚀 Installation

### Method 1: Manual Installation

1. **Download** the plugin files
2. **Upload** the `sa-helper-chatbot` folder to `/wp-content/plugins/`
3. **Activate** the plugin through the WordPress admin panel
4. **Configure** your settings in the admin dashboard

### Method 2: WordPress Admin

1. Go to **Plugins** → **Add New**
2. **Upload** the plugin ZIP file
3. **Install** and **Activate**

## ⚙️ Configuration

### Basic Setup

1. Navigate to **SA Helper Chatbot** in your WordPress admin
2. Configure basic settings:
   - **Enable/Disable** the chatbot
   - **Customize** the chat title
   - **Set** welcome message

### Google Gemini API Setup (Optional but Recommended)

1. Get your API key from [Google AI Studio](https://makersuite.google.com/app/apikey)
2. In plugin settings, go to **Gemini API Settings**
3. **Enable** Gemini API
4. **Enter** your API key
5. **Select** your preferred model:
   - `gemini-1.5-pro` (Recommended)
   - `gemini-1.0-pro-vision`
6. **Configure** advanced settings:
   - **Temperature** (0.0-2.0) - Controls response creativity
   - **Max Tokens** (100-2048) - Response length limit
   - **Include Page Content** - Provides context from current page

### Knowledge Base Setup

1. Go to **Knowledge Base** tab
2. Add content to sections:
   - **Company Information**
   - **Website Navigation**
   - **Recent News**
   - **FAQ**

## 📖 Usage

### For Visitors

1. **Click** the floating chat button (bottom-right corner)
2. **Type** your question in the input field
3. **Press Enter** or click **Send**
4. **Rate** responses with 👍/👎 for continuous improvement

### Chat Features

- **Markdown Support** - Rich text formatting in responses
- **Link Handling** - External links open in new tabs
- **Conversation History** - Preserved across page visits
- **Cross-Page Context** - Shows which page previous messages came from

### For Administrators

#### Dashboard Widget
- **User Satisfaction** metrics
- **Total Feedback** count
- **Quick Actions** to settings and API testing

#### API Testing
- **Test** your Gemini API connection
- **Send** custom messages to verify responses
- **Debug** API issues with detailed error messages

## 🎨 Customization

### CSS Customization

The plugin uses CSS custom properties for easy theming:

```css
.sa-helper-chatbot-popup {
    --primary-color: #0073aa;
    --secondary-color: #005177;
    --text-color: #333;
    --background-color: #fff;
}
```

### Hook System

#### Available Filters

```php
// Modify incoming messages
add_filter('sa_helper_chatbot_filter_message', 'your_function');

// Modify page content sent to AI
add_filter('sa_helper_chatbot_filter_page_content', 'your_function');

// Modify fallback responses
add_filter('sa_helper_chatbot_filter_fallback_response', 'your_function');
```

#### Available Actions

```php
// Before processing response
add_action('sa_helper_chatbot_before_response', 'your_function');

// After fallback response
add_action('sa_helper_chatbot_after_fallback_response', 'your_function');

// Plugin activation
add_action('sa_helper_chatbot_activated', 'your_function');
```

## 🔧 Development

### File Structure

```
sa-helper-chatbot/
├── admin/                          # Admin functionality
│   ├── class-sa-helper-chatbot-admin.php
│   ├── class-sa-helper-chatbot-api-test.php
│   └── class-sa-helper-chatbot-dashboard.php
├── assets/                         # Frontend assets
│   ├── css/
│   │   ├── sa-helper-chatbot-admin.css
│   │   └── sa-helper-chatbot-public.css
│   └── js/
│       ├── dompurify.min.js       # XSS protection
│       ├── marked.min.js          # Markdown rendering
│       ├── sa-helper-chatbot-admin.js
│       └── sa-helper-chatbot-public.js
├── includes/                       # Core functionality
│   ├── class-sa-helper-chatbot.php
│   ├── class-sa-helper-chatbot-ai.php
│   ├── class-sa-helper-chatbot-loader.php
│   └── class-sa-helper-chatbot-public.php
├── templates/                      # Template files
│   └── chatbot.php
├── package.json                    # Dependencies
├── package-lock.json
└── sa-helper-chatbot.php          # Main plugin file
```

### Building for Development

```bash
# Install dependencies
npm install

# The plugin uses marked.js for Markdown rendering
# No build process required - files are included directly
```

## 🛡️ Security Features

- **Nonce Verification** - All AJAX requests are verified
- **Input Sanitization** - User input is properly sanitized
- **XSS Protection** - DOMPurify prevents malicious content
- **Rate Limiting** - Prevents API abuse (10 requests per minute per IP)
- **Content Security** - Markdown rendering with restricted HTML tags

## 📊 Performance

### Optimizations

- **Caching System** - Knowledge base and API settings cached
- **Session Management** - Efficient conversation storage
- **DOM Optimization** - Minimal DOM manipulation
- **Lazy Loading** - Assets loaded only when needed
- **Response Caching** - API responses cached for 1 hour

### Mobile Performance

- **Hardware Acceleration** - GPU-accelerated scrolling
- **Touch Optimization** - Native-feeling touch interactions
- **Memory Management** - Conversation history limited to 20 items
- **Viewport Optimization** - Proper mobile viewport handling

## 🔍 Troubleshooting

### Common Issues

#### Chatbot Not Appearing
1. **Check** if plugin is activated
2. **Verify** chatbot is enabled in settings
3. **Clear** browser cache
4. **Check** for JavaScript errors in console

#### API Not Working
1. **Verify** API key is correct
2. **Check** PHP extensions (cURL, JSON)
3. **Test** API connection in admin panel
4. **Review** error logs for details

#### Mobile Scrolling Issues
1. **Clear** browser cache
2. **Disable** other chat plugins temporarily
3. **Check** for CSS conflicts
4. **Test** in different browsers

#### Conversation Not Persisting
1. **Check** browser's sessionStorage availability
2. **Verify** no conflicting storage clear scripts
3. **Test** in incognito/private mode

### Debug Mode

Enable WordPress debug mode for detailed error information:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs in `/wp-content/debug.log` for SA Helper Bot entries.

## 📈 Analytics & Feedback

### Built-in Analytics

- **User Satisfaction Rate** - Based on 👍/👎 feedback
- **Total Interactions** - Count of messages processed
- **Feedback Breakdown** - Positive vs negative ratings
- **Recent Feedback** - Latest user interactions

### Data Storage

- **Feedback Data** - Stored in WordPress options table
- **Conversation History** - Browser sessionStorage (temporary)
- **Knowledge Base** - WordPress options table
- **Settings** - WordPress options table with caching

## 🤝 Contributing

### Development Setup

1. **Clone** the repository
2. **Install** dependencies: `npm install`
3. **Set up** WordPress development environment
4. **Create** `.env` file with API keys for testing

### Code Standards

- **WordPress Coding Standards** for PHP
- **JSHint** for JavaScript
- **Proper** documentation and comments
- **Security** best practices

## 📄 License

This project is licensed under the MIT License. See the LICENSE file for details.

## 👨‍💻 Author

**Tamai Richards**
- Website: [https://oftgnov.github.io/Tamai.com/](https://oftgnov.github.io/Tamai.com/)
- Plugin: SA Helper Chatbot for Software Architects Jamaica

## 🆕 Changelog

### Version 1.0.0
- Initial release
- Google Gemini API integration
- Mobile-optimized responsive design
- Knowledge base management system
- Analytics and feedback system
- Dark mode support
- Conversation persistence
- Admin dashboard and testing tools

## 🔮 Roadmap

### Upcoming Features
- **Multi-language Support** - Internationalization
- **Custom Themes** - Additional design options
- **Advanced Analytics** - Detailed usage statistics
- **Integration APIs** - Connect with CRM systems
- **Voice Messages** - Audio input/output support
- **File Upload** - Document sharing capability

---

## 🆘 Support

For support, feature requests, or bug reports:

1. **Check** this README first
2. **Review** the troubleshooting section
3. **Test** with API testing tools in admin
4. **Contact** the developer with detailed information

**Made with ❤️ for WordPress communities**
