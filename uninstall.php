<?php
/**
 * Uninstall Script
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load database class for cleanup
require_once plugin_dir_path( __FILE__ ) . 'includes/AutoInsertion/Database.php';

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

	// Clean up auto-insertion database table
	\CTAHighlights\AutoInsertion\Database::drop_table();

	// Clean up post meta for auto-insertion disable flag
	$wpdb->query(
		"DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_cta_highlights_disable_auto_insert'"
	);

	do_action( 'cta_highlights_uninstalled' );
}

cta_highlights_uninstall_cleanup();
