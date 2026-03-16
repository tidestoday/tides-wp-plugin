=== Tides Today Tides and Weather ===
Contributors: sjwright1986
Tags: tides, weather, shortcode, gutenberg, widget
Requires at least: 5.0
Tested up to: 6.3
Requires PHP: 5.5
Stable tag: trunk
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
* Proxied `widget.js` and `widget-init.js` output served from the site's own domain
* WordPress cache-backed API and proxy fetching to reduce repeated remote requests

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

= What versions are supported? =

The plugin is built for WordPress 5.0+ and PHP 5.5+.

== Changelog ==

= 2.0.0 =
Rebuilt the plugin around saved widgets, shortcode output, classic widget support, Gutenberg integration, proxied script delivery, and WordPress-backed API caching.
