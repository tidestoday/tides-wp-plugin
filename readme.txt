=== Tides Today Tides and Weather ===
Contributors: sjwright1986
Tags: tides, weather, shortcode, gutenberg, widget
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.0
Stable tag: 2.0.0
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build reusable Tides Today tide and weather widgets and place them with a shortcode, a classic sidebar widget, or a Gutenberg block.

== Description ==

Tides Today Tides and Weather lets site owners create saved widget definitions in the WordPress admin area and reuse them anywhere on the site.

Features include:

* Saved widget builder in the WordPress admin sidebar
* Language-aware country, region, and location selection from the Tides Today widget API
* Priority country ordering for English and French pickers
* Shortcode output for every saved widget
* Classic sidebar widget support
* Gutenberg block support with a dropdown of saved widgets
* Proxied `widget.js` and `widget-init.js` assets enqueued from the site's own domain
* WordPress cache-backed API and proxy fetching to reduce repeated remote requests
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

== Frequently Asked Questions ==

= How do I add a widget to a page? =

Save a widget in `Tides Today`, then either:

* paste its shortcode into the page
* add the `Tides Today Tides and Weather` block in Gutenberg
* choose it from the classic Widgets screen

= Are API calls cached? =

Yes. Countries, regions, locations, and proxied widget script fetches are cached with WordPress cache APIs.

= How do I style a widget? =

Use WordPress' built-in Additional CSS feature or your theme's stylesheet tools.

Each rendered widget includes the base class `.tttw-widget-host` and a widget-specific class like `.tttw-widget-host--tttw_abc123def456`, so you can target one widget or all widgets without saving custom CSS inside the plugin.

= What versions are supported? =

The plugin is built for WordPress 5.0+ and PHP 7.0+.

== External services ==

This plugin connects to the Tides Today widget API at `api.tidestoday.io` to load the list of available countries, regions, and locations, and to fetch the widget JavaScript required to display tide and weather data.

When an administrator uses the widget builder, the plugin sends the selected widget language and selected location identifiers or slugs to the Tides Today service to retrieve catalog data and preview assets.

When a saved widget is displayed on the public site, the plugin requests the Tides Today widget JavaScript for the saved language, country, region, location, and display options so the widget can render current tide and weather information.

Those requests are made from your WordPress site server to the Tides Today service. The requests include your site URL in the user agent string for service identification and debugging. Site visitors do not send data directly from their browsers to `api.tidestoday.io`; the plugin proxies and caches the service responses through WordPress.

Terms of Service: https://tides.today/en/terms-of-service

Privacy Policy: https://tides.today/en/privacy-policy

== Changelog ==

= 2.0.0 =
Rebuilt the plugin around saved widgets, shortcode output, classic widget support, Gutenberg integration, proxied script delivery, and WordPress-backed API caching.
