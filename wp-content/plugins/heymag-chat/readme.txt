=== HeyMag - AI Chat Widget & WooCommerce Sync ===
Contributors: heymag
Tags: chat widget, ai chat, customer support, woocommerce, live chat
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add AI-powered customer chat to your WordPress site. Automatically sync WooCommerce products for smart product recommendations.

== Description ==

HeyMag brings AI-powered customer service to your WordPress site. Connect your HeyMag account and let our AI assistant help your customers 24/7.

= Key Features =

* **AI-Powered Chat Widget** - Embed an intelligent chat assistant on your site
* **WooCommerce Integration** - Automatically sync your products for accurate recommendations
* **Smart Product Suggestions** - AI understands your catalog and helps customers find what they need
* **24/7 Availability** - Never miss a customer inquiry, even outside business hours
* **Easy Setup** - Connect in minutes with just your widget token
* **Customizable** - Match the widget to your brand colors and style

= WooCommerce Sync Features =

* Automatic product sync on create/update/delete
* Inventory tracking and stock status updates
* Category and tag synchronization
* Product variations support
* Image and description sync
* Selective category sync options

= How It Works =

1. Sign up for a HeyMag account at [heymag.app](https://heymag.app)
2. Get your Widget Token from the HeyMag dashboard
3. Install this plugin and enter your token
4. The chat widget automatically appears on your site!

= Requirements =

* WordPress 5.8 or higher
* PHP 7.4 or higher
* A HeyMag account ([sign up free](https://heymag.app))
* WooCommerce 6.0+ (optional, for product sync)

== Installation ==

= Automatic Installation =

1. Go to Plugins > Add New in your WordPress admin
2. Search for "HeyMag"
3. Click "Install Now" and then "Activate"
4. Go to Settings > HeyMag Chat
5. Enter your Widget Token from your HeyMag dashboard
6. Click "Test Connection" to verify setup

= Manual Installation =

1. Download the plugin zip file
2. Go to Plugins > Add New > Upload Plugin
3. Choose the zip file and click "Install Now"
4. Activate the plugin
5. Configure under Settings > HeyMag Chat

= Getting Your Widget Token =

1. Log in to [heymag.app](https://heymag.app)
2. Navigate to Settings > Channels > Website Widget
3. Copy your Widget Token (starts with `wgt_`)
4. Paste it in the plugin settings

== Frequently Asked Questions ==

= Do I need a HeyMag account? =

Yes, you need a HeyMag account to use this plugin. You can sign up for free at [heymag.app](https://heymag.app).

= Is the chat widget customizable? =

Yes! You can customize:
- Position (bottom-right or bottom-left)
- Primary color to match your brand
- Button text
- Welcome message
- Mobile display settings
- Auto-open behavior

= Does it work with WooCommerce? =

Yes! The plugin integrates seamlessly with WooCommerce to sync your products. This enables the AI to:
- Answer product questions accurately
- Make relevant recommendations
- Provide up-to-date pricing and availability

= What pages can I exclude the widget from? =

You can exclude the widget from specific pages using URL patterns. For example:
- `/checkout` - Hide on checkout page
- `/cart/*` - Hide on cart and related pages
- `/my-account/*` - Hide on account pages

= Is my data secure? =

Yes. All communication is encrypted via HTTPS. We never store customer credit card information. See our [privacy policy](https://heymag.app/privacy) for details.

= Will the widget slow down my site? =

No. The widget script loads asynchronously and doesn't block your page rendering. We also use preconnect hints to minimize loading time.

= What languages are supported? =

The AI can communicate in multiple languages. The interface defaults to English but can be customized.

== Screenshots ==

1. Chat widget on your website
2. Plugin settings page
3. WooCommerce sync settings
4. Widget customization options
5. Connection status dashboard

== Changelog ==

= 1.1.0 =
* WooCommerce order hooks — real-time order status sync to HeyMag
* Customer identity matching — phone number lookup for personalized AI chat
* E-commerce product sync enhancements — category hierarchy, stock levels, variant pricing
* Setup wizard — guided onboarding flow for new installations
* Custom REST API endpoints — health check, store info, customer search
* HMAC-SHA256 webhook signature verification for secure event push
* Tested with WordPress 6.9 and WooCommerce 9.6

= 1.0.0 =
* Initial release
* AI chat widget injection
* WooCommerce product sync
* Customizable widget settings
* Real-time stock sync
* Category filtering for sync
* Mobile responsive widget

== Upgrade Notice ==

= 1.1.0 =
Adds WooCommerce order sync, customer identity matching, and setup wizard. Tested with WP 6.9 and WC 9.6.

= 1.0.0 =
Initial release of HeyMag Chat for WordPress.

== Privacy Policy ==

HeyMag Chat respects your privacy. The plugin:

* Sends product data to HeyMag servers for AI training (if WooCommerce sync is enabled)
* Does not collect personal visitor data without consent
* Uses secure HTTPS connections for all API calls
* Stores only your widget token locally

For full details, see our [Privacy Policy](https://heymag.app/privacy).

== Third-Party Services ==

This plugin connects to:

* **HeyMag API** (api.heymag.app) - For widget configuration and product sync
* **HeyMag Widget** (heymag.app/widget.js) - The chat widget script

By using this plugin, you agree to HeyMag's [Terms of Service](https://heymag.app/terms).
