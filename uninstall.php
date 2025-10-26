<?php
/**
 * Uninstall Script
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

function cta_highlights_uninstall_cleanup() {
	delete_option( 'cta_highlights_version' );

	wp_cache_flush_group( 'cta_highlights_templates' );

	global $wpdb;
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			'_transient_cta_highlights_%'
		)
	);
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			'_transient_timeout_cta_highlights_%'
		)
	);

	do_action( 'cta_highlights_uninstalled' );
}

cta_highlights_uninstall_cleanup();
