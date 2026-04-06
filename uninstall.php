<?php
if (! defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Delete all saved widget configurations.
delete_option('tttw_widgets');

// Delete all cached transients created by the plugin.
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_tttw_cache_%' OR option_name LIKE '_transient_timeout_tttw_cache_%'");
