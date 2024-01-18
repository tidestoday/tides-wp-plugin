<?php
defined('ABSPATH') or die('You shall not pass!');

use \TidesToday\TideTimes\Plugin;

/*
Plugin Name: Tides Today Tide Times and Weather
Plugin URI:  https://tides.today/en/wordpress-plugin
Description: The Tides Today plugin provides 7 day tide forecasts for over 8,000 locations worldwide.
Version:     1.0.0
Author:      Stephen Wright
Author URI:  https://www.stewright.me/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: tides-today
Domain Path: /languages
*/

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$tideTimesPlugin = new Plugin(dirname(plugin_basename(__FILE__)));
