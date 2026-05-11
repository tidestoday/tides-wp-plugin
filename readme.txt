=== Tides Today Tides and Weather ===
Contributors: tidestoday, sjwright1986
Tags: tides, weather, shortcode, gutenberg, widget
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.0
Stable tag: 2.1.2
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build reusable Tides Today tide and weather widgets and place them with a shortcode, a classic sidebar widget, or a Gutenberg block.

== Description ==

Tides Today Tides and Weather allows you to add tide times and weather information to your site for over 8,000 locations worldwide.

Features include:

* Add tide times and weather to your site for over 8,000 locations world-wide
* Preview your widget from the WordPress admin
* Language-aware country, region, and location selection from the Tides Today widget API
* Customise your Tide Times and Weather widget in English or French
* Shortcode output for every saved widget
* Classic sidebar widget support
* Gutenberg block support with a dropdown of saved widgets
* WordPress cache-backed API fetching to reduce repeated remote requests
* Styling support via WordPress' built-in Additional CSS tools and theme styles

== Installation ==

1. Upload the plugin folder to `wp-content/plugins/`.
2. Activate the plugin in WordPress.
3. Open `Tides Today` in the WordPress admin sidebar.
4. Create and save a widget configuration.
5. Use the generated shortcode, select the widget in a sidebar, or choose it from the Gutenberg block dropdown.

== Screenshots ==

1. The widget builder in the WordPress admin with live preview and location selection.
2. A saved Tides Today widget embedded on the front end of a WordPress post.
3. Add widgets in French to your French language website.

== Frequently Asked Questions ==

= How do I add a widget to a page? =

Save a widget in `Tides Today`, then either:

* paste its shortcode into the page
* add the `Tides Today Tides and Weather` block in Gutenberg
* choose it from the classic Widgets screen

= Are API calls cached? =

Yes. Countries, regions, locations, and widget data requests are cached with WordPress cache APIs.

= How do I style a widget? =

Use WordPress' built-in Additional CSS feature or your theme's stylesheet tools.

Each rendered widget includes the base class `.tttw-widget-host` and a widget-specific class like `.tttw-widget-host--tttw_abc123def456`, so you can target one widget or all widgets without saving custom CSS inside the plugin.

= What versions are supported? =

The plugin is built for WordPress 5.0+ and PHP 7.0+.

== External services ==

This plugin connects to the Tides Today widget API at `api.tidestoday.io` to load the list of available countries, regions, and locations, and to fetch the tide and weather data needed by saved widgets.

On first use, the plugin registers this WordPress installation with the Tides Today widget API. The API returns an installation identifier and secret, which are stored in the WordPress options table and used to sign future catalog and widget data requests. This helps protect the public widget API from abuse while allowing legitimate high-traffic sites to use cached server-side requests.

When an administrator uses the widget builder, the plugin sends the selected widget language and selected location identifiers or slugs to the Tides Today service to retrieve catalog data and preview data.

When a saved widget is displayed on the public site, the plugin requests `data.json` from the Tides Today API for the saved language, country, region, and location so the plugin-bundled JavaScript can render current tide and weather information.

Those API requests are made from your WordPress site server to the Tides Today service. Site visitors do not receive remotely hosted executable JavaScript from Tides Today; widget rendering JavaScript and base CSS are included in this plugin. The plugin caches service responses through WordPress.

Map and weather image assets referenced by the widget data are loaded from the Tides Today CDN at `https://cdn.tidestoday.media` when the widget renders. These are image assets only, not executable code. This is required because it is not practical to bundle and maintain map and weather image assets for more than 8,000 supported locations inside the plugin package.

Terms of Service: https://tides.today/en/terms-of-service

Privacy Policy: https://tides.today/en/privacy-policy

== Changelog ==

= 2.1.2 =
Added WordPress.org release assets and SVN publishing automation.

= 2.1.1 =
Moved WordPress data requests to the signed `wp-v1` widget API and added per-installation API registration credentials.

= 2.1.0 =
Replaced remotely generated widget JavaScript with plugin-bundled rendering code that uses server-fetched Tides Today `data.json` responses.

= 2.0.4 =
Fixes to uninstall script.

= 2.0.3 =
Fixes and added uninstall script.

= 2.0.2 =
Tightened remote request safety and removed the site URL from the service user agent.

= 2.0.1 =
Ensured the plugin fully complies with WordPress' plugin guidelines.

= 2.0.0 =
Rebuilt the plugin around saved widgets, shortcode output, classic widget support, Gutenberg integration, proxied script delivery, and WordPress-backed API caching.
