<?php
/**
 * Post Factory for Testing
 *
 * Creates test WordPress posts for testing.
 *
 * @package CTAHighlights\Tests
 */

namespace CTAHighlights\Tests\Factories;

class PostFactory {

	/**
	 * Default post attributes
	 *
	 * @var array
	 */
	private static $defaults = array(
		'post_title'   => 'Test Post',
		'post_content' => '<p>Test post content.</p><p>Second paragraph.</p><p>Third paragraph.</p>',
		'post_status'  => 'publish',
		'post_type'    => 'post',
		'post_author'  => 1,
	);

	/**
	 * Create a test post
	 *
	 * @param array $overrides Override default attributes.
	 * @return int|\WP_Error Post ID or error.
	 */
	public static function create( $overrides = array() ) {
		$data = wp_parse_args( $overrides, self::$defaults );
		return wp_insert_post( $data, true );
	}

	/**
	 * Create multiple test posts
	 *
	 * @param int $count Number of posts to create.
	 * @param array $overrides Override default attributes.
	 * @return array Array of post IDs.
	 */
	public static function create_many( $count, $overrides = array() ) {
		$ids = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$data = $overrides;
			$data['post_title'] = ( $overrides['post_title'] ?? 'Test Post' ) . ' #' . ( $i + 1 );
			$id = self::create( $data );

			if ( ! is_wp_error( $id ) ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * Create a post with CTA shortcode
	 *
	 * @param array $shortcode_atts Shortcode attributes.
	 * @param string $shortcode_content Shortcode content.
	 * @param array $post_overrides Post attributes.
	 * @return int|\WP_Error Post ID or error.
	 */
	public static function create_with_shortcode( $shortcode_atts = array(), $shortcode_content = '', $post_overrides = array() ) {
		$shortcode = self::build_shortcode( $shortcode_atts, $shortcode_content );

		$post_overrides['post_content'] = ( $post_overrides['post_content'] ?? self::$defaults['post_content'] ) . "\n\n" . $shortcode;

		return self::create( $post_overrides );
	}

	/**
	 * Create a post with multiple paragraphs
	 *
	 * @param int $paragraph_count Number of paragraphs.
	 * @param array $overrides Post attributes.
	 * @return int|\WP_Error Post ID or error.
	 */
	public static function create_with_paragraphs( $paragraph_count, $overrides = array() ) {
		$content = '';

		for ( $i = 1; $i <= $paragraph_count; $i++ ) {
			$content .= "<p>This is paragraph {$i} of the test content.</p>\n";
		}

		$overrides['post_content'] = $content;

		return self::create( $overrides );
	}

	/**
	 * Create a post in a specific category
	 *
	 * @param int|array $category_ids Category ID(s).
	 * @param array $overrides Post attributes.
	 * @return int|\WP_Error Post ID or error.
	 */
	public static function create_in_category( $category_ids, $overrides = array() ) {
		$post_id = self::create( $overrides );

		if ( ! is_wp_error( $post_id ) ) {
			wp_set_post_categories( $post_id, (array) $category_ids );
		}

		return $post_id;
	}

	/**
	 * Create a post with auto-insert disabled
	 *
	 * @param array $overrides Post attributes.
	 * @return int|\WP_Error Post ID or error.
	 */
	public static function create_with_auto_insert_disabled( $overrides = array() ) {
		$post_id = self::create( $overrides );

		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_cta_highlights_disable_auto_insert', '1' );
		}

		return $post_id;
	}

	/**
	 * Build CTA shortcode string
	 *
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string Shortcode string.
	 */
	private static function build_shortcode( $atts = array(), $content = '' ) {
		$shortcode = '[cta_highlights';

		foreach ( $atts as $key => $value ) {
			$shortcode .= ' ' . $key . '="' . esc_attr( $value ) . '"';
		}

		$shortcode .= ']';

		if ( ! empty( $content ) ) {
			$shortcode .= $content;
		}

		$shortcode .= '[/cta_highlights]';

		return $shortcode;
	}

	/**
	 * Make a post data array without saving
	 *
	 * @param array $overrides Override default attributes.
	 * @return array Post data.
	 */
	public static function make( $overrides = array() ) {
		return wp_parse_args( $overrides, self::$defaults );
	}

	/**
	 * Get default post attributes
	 *
	 * @return array
	 */
	public static function defaults() {
		return self::$defaults;
	}
}
