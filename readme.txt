=== Tides Today Tide Times and Weather ===
Contributors: sjwright1986
Tags: sea, tide, tide tables, tide times, weather
Requires at least: 4.3
Tested up to: 6.3
Stable tag: trunk
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Tides Today allows you to add up to 3 days tide times to your WordPress blog for over 700 UK and Ireland locations using a sidebar widget or shortcode

== Description ==

Tides Today allows you to add tide data and a map to your WordPress blog for over 700 UK (and UK territories) and Irish locations. This plugin can be used either as a shortcode or as a sidebar widget.

Features include:

* Choose to display either 1, 2 or 3-day tide forecast
* Over 700 locations from the UK, Ireland, Isle of Man, Chanel Islands, Falkland Islands and Gibraltar
* Add tide times using a shortcode or sidebar widget
* Add multiple tide time shortcodes to your page for different locations
* Easily override the look and feel using CSS
* **New for 1.1.0** Include a map of a location
* **New for 1.1.0** Localised in Welsh
* **New for 1.3.0** Test mode to diagnose issues

[youtube https://www.youtube.com/watch?v=JtjFDRzxNGE]

**Widget usage**

Simply drag and drop the widget into the sidebar through the WordPress dashboard. Set the properties using the fields and save.

**Shortcode usage**

The shortcode accepts the following parameters:
* days - the number of days to show tide data for (max 3, default 3)
* title - the title to show above the tide times (default 'Tide times')
* map - whether or not to show a map. (true or false, default true)
* location - the location slug to show times for (default 'llandudno', see the list of locations further down)

You can add content to the shortcode to display a description:

`[tide_times days=1 map=true title="Tide times for Conwy" location="conwy"]These are today's tides for Conwy[/tide_times]`

or without content:

`[tide_times days=1 map=true title="Tide times for Conwy" location="conwy"]`

Full list of locations can be found on our [plugin page](https://tides.today/three-day-forecast-plugin/)

** Test mode**
Not getting data? Add this into your wp-config.php file:

`define('TIDES_TODAY_TEST_MODE', true);`

**Legal stuff**

The data is provided under license and cannot be reproduced or sold. Users of this data are responsible for their own
safety, the author of this plugin cannot be help responsible for anything that happens as a result of using data
provided by this plugin. By using this plugin, you and your visitors are agreeing to the Tides Today
[Terms and conditions](https://tides.today/terms-and-conditions/).

You may not use the data provided by the API for anything other than displaying tide information on your website by the
means provided by this plugin.

You must at all times display the disclaimer AND 'Powered by Tides Today' link. You may use your own CSS styles to
change the plugin to suit your site but all data MUST be visible. This plugin cannot be sold under any circumstance.

Failure to comply with any of the conditions outlined here or as outlined in the Tides Today
[Terms and conditions](https://tides.today/terms-and-conditions/) will result in legal action being taken.

The author has the right at any point to block or remove access to the API that provides the data at any point.
There is absolutely no warranty offered or implied. Likewise there is no guarantee offered or implied. It is up to the
webmaster and/or legal owners of a website to decide whether the plugin is suitable and safe to use on their site.
The author is not responsible or any damage, downtime or loss of data as a result of using or installing this plugin.

We respect the privacy of our users. Tracking and anonymous usage data is collected by this plugin. By using this
plugin you are agreeing to your site and/or users being tracked.
Full [Privacy Policy](https://tides.today/privacy-policy/) can be found at the Tides Today website. By using this
plugin, you are agreeing to all conditions outlined at the [Privacy Policy](https://tides.today/privacy-policy/) or as
otherwise outlined in this file.

**License**

License: [GPL2](https://www.gnu.org/licenses/gpl-2.0.html)

== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.

== Frequently Asked Questions ==

= Can I have more than one widget or shortcode on the page? =

Yes

= How do I change how any forecasts are returned using the shortcode? =

Set an attribute of days=2 (change 2 to suit, up to 3)

= How do I find a list of locations to use with the shortcode? =

On our website, see our [plugin page](https://tides.today/three-day-forecast-plugin/)

= How to I enable/disable the location map? =

If using a shortcode, add the parameter 'map=true'. If you're using a sidebar widget, simply choose whether to show it.

== Screenshots ==
1. The plugin shortcode being used to display tide times and a map.

2. Configuration options in the Widget interface. Easy to add and customise.

3. A sidebar widget showing 3 days forecasts and no map.

4. Bellach ar gael yn y Gymraeg! Now available in Welsh!

== Changelog ==

= 1.3.2 =
Fixed Guzzle issue, added docker-compose

= 1.2.0 =
Fixed issue where older PHP versions would error. Thanks Joe Johnson for pointing this out

= 1.1.1 =
Fixed issue where class wasn't found when installed via composer

= 1.1.0 =
Added ability to add location map to widget and shortcode
Welsh translations added

= 1.0.3 =
Added screenshots and improved the readme file.
