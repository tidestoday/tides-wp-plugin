<?php
if (! defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

function tttw_uninstall() {
	delete_option( 'tttw_widgets' );

	global $wpdb;
	$transient_names = wp_cache_get( 'tttw_uninstall_transients' );
	if ( false === $transient_names ) {
		$transient_names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_tttw_cache_' ) . '%'
			)
		);
		wp_cache_set( 'tttw_uninstall_transients', $transient_names );
	}
	foreach ( $transient_names as $option_name ) {
		delete_transient( substr( $option_name, strlen( '_transient_' ) ) );
	}
}

tttw_uninstall();
