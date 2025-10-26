<?php
/**
 * Test Helper Functions
 *
 * Global helper functions available in all PHPUnit tests.
 *
 * @package CTAHighlights\Tests
 */

namespace CTAHighlights\Tests;

/**
 * Create a test post with CTA shortcode
 *
 * @param array $args Post arguments.
 * @param array $cta_atts CTA shortcode attributes.
 * @param string $cta_content CTA shortcode content.
 * @return int Post ID.
 */
function create_test_post_with_cta( $args = array(), $cta_atts = array(), $cta_content = '' ) {
	$defaults = array(
		'post_title' => 'Test Post with CTA',
		'post_content' => '',
		'post_status' => 'publish',
		'post_type' => 'post',
	);

	$args = wp_parse_args( $args, $defaults );

	// Build shortcode
	$shortcode = '[cta_highlights';
	foreach ( $cta_atts as $key => $value ) {
		$shortcode .= ' ' . $key . '="' . esc_attr( $value ) . '"';
	}
	$shortcode .= ']' . $cta_content . '[/cta_highlights]';

	// Add shortcode to content
	$args['post_content'] .= "\n\n" . $shortcode;

	return wp_insert_post( $args );
}

/**
 * Create a test CTA in the database
 *
 * @param array $data CTA data.
 * @return int CTA ID.
 */
function create_test_cta( $data = array() ) {
	$database = new \CTAHighlights\AutoInsertion\Database();

	$defaults = array(
		'name' => 'Test CTA',
		'content' => '[cta_highlights]Test Content[/cta_highlights]',
		'status' => 'active',
		'cta_type' => 'primary',
		'post_types' => array( 'post' ),
		'category_mode' => 'include',
		'category_ids' => array(),
		'storage_conditions' => array(),
		'insertion_direction' => 'forward',
		'insertion_position' => 3,
		'fallback_behavior' => 'end',
		'fallback_cta_id' => null,
	);

	$data = wp_parse_args( $data, $defaults );

	return $database->insert( $data );
}

/**
 * Get rendered shortcode HTML
 *
 * @param array $atts Shortcode attributes.
 * @param string $content Shortcode content.
 * @return string Rendered HTML.
 */
function get_shortcode_html( $atts = array(), $content = '' ) {
	$shortcode = '[cta_highlights';
	foreach ( $atts as $key => $value ) {
		$shortcode .= ' ' . $key . '="' . esc_attr( $value ) . '"';
	}
	$shortcode .= ']' . $content . '[/cta_highlights]';

	return do_shortcode( $shortcode );
}

/**
 * Create a temporary template file
 *
 * @param string $name Template name.
 * @param string $content Template content.
 * @return string Template path.
 */
function create_temp_template( $name, $content ) {
	$template_dir = get_stylesheet_directory() . '/cta-highlights-templates';

	if ( ! file_exists( $template_dir ) ) {
		wp_mkdir_p( $template_dir );
	}

	$template_path = $template_dir . '/' . $name . '.php';
	file_put_contents( $template_path, $content );

	return $template_path;
}

/**
 * Clean up temporary template files
 */
function cleanup_temp_templates() {
	$template_dir = get_stylesheet_directory() . '/cta-highlights-templates';

	if ( file_exists( $template_dir ) ) {
		$files = glob( $template_dir . '/*' );
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}
		rmdir( $template_dir );
	}
}

/**
 * Assert HTML contains specific element with attributes
 *
 * @param string $html HTML to check.
 * @param string $tag Tag name.
 * @param array $attributes Expected attributes.
 * @return bool
 */
function html_contains_element( $html, $tag, $attributes = array() ) {
	$dom = new \DOMDocument();
	@$dom->loadHTML( $html );

	$elements = $dom->getElementsByTagName( $tag );

	if ( $elements->length === 0 ) {
		return false;
	}

	if ( empty( $attributes ) ) {
		return true;
	}

	foreach ( $elements as $element ) {
		$matches_all = true;
		foreach ( $attributes as $attr => $value ) {
			if ( $element->getAttribute( $attr ) !== $value ) {
				$matches_all = false;
				break;
			}
		}
		if ( $matches_all ) {
			return true;
		}
	}

	return false;
}

/**
 * Get current database version
 *
 * @return string|null
 */
function get_current_db_version() {
	global $wpdb;
	return get_option( 'cta_highlights_db_version' );
}

/**
 * Reset plugin database tables
 */
function reset_plugin_tables() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'cta_auto_insertions';

	$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
	delete_option( 'cta_highlights_db_version' );

	// Recreate
	$database = new \CTAHighlights\AutoInsertion\Database();
	$database->create_table();
}

/**
 * Clear all plugin caches
 */
function clear_all_caches() {
	wp_cache_flush();
	cta_highlights_clear_cache();
}

/**
 * Mock WordPress nonce
 *
 * @param string $action Nonce action.
 * @return string
 */
function mock_nonce( $action = -1 ) {
	return wp_create_nonce( $action );
}

/**
 * Get test file path
 *
 * @param string $relative_path Relative path from tests directory.
 * @return string
 */
function test_file_path( $relative_path ) {
	return dirname( __DIR__ ) . '/' . ltrim( $relative_path, '/' );
}

/**
 * Assert array has keys
 *
 * @param array $expected_keys Expected keys.
 * @param array $array Array to check.
 * @return bool
 */
function array_has_keys( $expected_keys, $array ) {
	foreach ( $expected_keys as $key ) {
		if ( ! array_key_exists( $key, $array ) ) {
			return false;
		}
	}
	return true;
}
