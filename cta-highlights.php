<?php
/**
 * Plugin Name: CTA Highlights
 * Description: A flexible shortcode plugin for displaying call-to-action highlights with customizable templates that can be overridden in themes.
 * Version: 0.1.0
 * Author: Standby CXO
 * Author URI: https://standbycxo.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cta-highlights
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CTA_HIGHLIGHTS_VERSION', '0.1.0' );
define( 'CTA_HIGHLIGHTS_FILE', __FILE__ );
define( 'CTA_HIGHLIGHTS_DIR', plugin_dir_path( __FILE__ ) );
define( 'CTA_HIGHLIGHTS_URL', plugin_dir_url( __FILE__ ) );

/**
 * PSR-4 Autoloader
 */
spl_autoload_register(
	function ( $class ) {
		$prefix = 'CTAHighlights\\';
		$base_dir = __DIR__ . '/includes/';

		$len = strlen( $prefix );
		if ( 0 !== strncmp( $prefix, $class, $len ) ) {
			return;
		}

		$relative_class = substr( $class, $len );
		$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

/**
 * Initialize the plugin
 */
function cta_highlights_init() {
	return CTAHighlights\Core\Plugin::instance();
}

cta_highlights_init();

/**
 * BACKWARD COMPATIBILITY FUNCTIONS
 */

function cta_highlights_template_shortcode( $atts, $content = null ) {
	if ( function_exists( '_deprecated_function' ) ) {
		_deprecated_function( __FUNCTION__, '2.0.0', 'CTAHighlights\Shortcode\Handler::render_shortcode()' );
	}

	$plugin = CTAHighlights\Core\Plugin::instance();
	return $plugin->get_shortcode_handler()->render_shortcode( $atts, $content );
}

$cta_highlights_templates_used = array();

add_action(
	'cta_highlights_after_template_include',
	function() {
		global $cta_highlights_templates_used;
		$registry                       = CTAHighlights\Template\Registry::instance();
		$cta_highlights_templates_used = $registry->get_all();
	}
);

/**
 * Activation Hook
 */
function cta_highlights_activate() {
	add_option( 'cta_highlights_version', CTA_HIGHLIGHTS_VERSION );
	flush_rewrite_rules();

	do_action( 'cta_highlights_activated' );
}
register_activation_hook( __FILE__, 'cta_highlights_activate' );

/**
 * Deactivation Hook
 */
function cta_highlights_deactivate() {
	$plugin = CTAHighlights\Core\Plugin::instance();
	$plugin->get_template_loader()->clear_cache();
	flush_rewrite_rules();

	do_action( 'cta_highlights_deactivated' );
}
register_deactivation_hook( __FILE__, 'cta_highlights_deactivate' );

/**
 * HELPER FUNCTIONS
 */

function cta_highlights() {
	return CTAHighlights\Core\Plugin::instance();
}

function cta_highlights_get_template_loader() {
	return cta_highlights()->get_template_loader();
}

function cta_highlights_get_asset_manager() {
	return cta_highlights()->get_asset_manager();
}

function cta_highlights_render_template( $template_name, $args = array(), $echo = true ) {
	$args['template'] = $template_name;

	$shortcode = '[cta_highlights';
	foreach ( $args as $key => $value ) {
		if ( 'content' === $key ) {
			continue;
		}
		$shortcode .= ' ' . $key . '="' . esc_attr( $value ) . '"';
	}
	$shortcode .= ']';

	if ( isset( $args['content'] ) ) {
		$shortcode .= $args['content'];
	}

	$shortcode .= '[/cta_highlights]';

	$output = do_shortcode( $shortcode );

	if ( $echo ) {
		echo $output;
	} else {
		return $output;
	}
}

function cta_highlights_has_shortcode( $content = null ) {
	if ( null === $content ) {
		global $post;
		if ( ! $post instanceof WP_Post ) {
			return false;
		}
		$content = $post->post_content;
	}

	return has_shortcode( $content, 'cta_highlights' );
}

function cta_highlights_get_templates() {
	return cta_highlights_get_template_loader()->get_all_templates();
}

function cta_highlights_clear_cache() {
	cta_highlights_get_template_loader()->clear_cache();
}
