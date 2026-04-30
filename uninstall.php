<?php
if (! defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

function tttw_uninstall() {
	delete_option( 'tttw_widgets' );

	$cache_keys = get_option( 'tttw_cache_keys', array() );

	if ( ! is_array( $cache_keys ) ) {
		$cache_keys = array();
	}

	foreach ( $cache_keys as $cache_key ) {
		delete_transient( sanitize_key( $cache_key ) );
	}

	delete_option( 'tttw_cache_keys' );
}

tttw_uninstall();
