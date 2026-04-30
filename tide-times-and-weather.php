<?php
/*
Plugin Name: Tides Today Tides and Weather
Plugin URI:  https://tides.today/en/wordpress-plugin
Description: Build reusable Tides Today tide and weather widgets for shortcodes, sidebars, and the block editor.
Version:     2.1.0
Author:      Stephen Wright
Author URI:  https://tides.today/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: tides-today-tides-and-weather
Domain Path: /languages
Requires at least: 5.0
Requires PHP: 7.0
*/

if (! defined('ABSPATH')) {
	exit;
}

define('TTTW_PLUGIN_FILE', __FILE__);
define('TTTW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TTTW_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TTTW_PLUGIN_VERSION', '2.1.0');

require_once TTTW_PLUGIN_DIR . 'includes/class-tttw-widget.php';
require_once TTTW_PLUGIN_DIR . 'includes/class-tttw-plugin.php';

function tttw_plugin() {
	return TTTW_Plugin::instance();
}

register_activation_hook(__FILE__, array('TTTW_Plugin', 'activate'));

tttw_plugin();
