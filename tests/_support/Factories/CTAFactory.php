<?php
/**
 * CTA Factory for Testing
 *
 * Creates test CTA data for unit and integration tests.
 *
 * @package CTAHighlights\Tests
 */

namespace CTAHighlights\Tests\Factories;

use CTAHighlights\AutoInsertion\Database;

class CTAFactory {

	/**
	 * Default CTA attributes
	 *
	 * @var array
	 */
	private static $defaults = array(
		'name'                 => 'Test CTA',
		'content'              => '[cta_highlights template="default"]Test Content[/cta_highlights]',
		'status'               => 'active',
		'cta_type'             => 'primary',
		'post_types'           => array( 'post' ),
		'category_mode'        => 'include',
		'category_ids'         => array(),
		'storage_conditions'   => array(),
		'insertion_direction'  => 'forward',
		'insertion_position'   => 3,
		'fallback_behavior'    => 'end',
		'fallback_cta_id'      => null,
	);

	/**
	 * Create a test CTA in the database
	 *
	 * @param array $overrides Override default attributes.
	 * @return int CTA ID.
	 */
	public static function create( $overrides = array() ) {
		$database = new Database();
		$data = wp_parse_args( $overrides, self::$defaults );

		return $database->insert( $data );
	}

	/**
	 * Create multiple test CTAs
	 *
	 * @param int $count Number of CTAs to create.
	 * @param array $overrides Override default attributes.
	 * @return array Array of CTA IDs.
	 */
	public static function create_many( $count, $overrides = array() ) {
		$ids = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$data = $overrides;
			$data['name'] = ( $overrides['name'] ?? 'Test CTA' ) . ' #' . ( $i + 1 );
			$ids[] = self::create( $data );
		}

		return $ids;
	}

	/**
	 * Create a CTA with storage conditions
	 *
	 * @param array $conditions Storage conditions.
	 * @param array $overrides Override default attributes.
	 * @return int CTA ID.
	 */
	public static function create_with_conditions( $conditions, $overrides = array() ) {
		$overrides['storage_conditions'] = $conditions;
		return self::create( $overrides );
	}

	/**
	 * Create a fallback CTA chain
	 *
	 * @param int $depth Number of CTAs in chain.
	 * @return array Array of CTA IDs in order (primary first).
	 */
	public static function create_fallback_chain( $depth = 3 ) {
		$ids = array();

		for ( $i = 0; $i < $depth; $i++ ) {
			$data = array(
				'name' => 'CTA #' . ( $i + 1 ),
				'cta_type' => $i === 0 ? 'primary' : 'fallback',
			);

			// Link to previous CTA
			if ( $i > 0 && isset( $ids[ $i - 1 ] ) ) {
				$data['fallback_cta_id'] = null; // Will set after creating
			}

			$ids[] = self::create( $data );
		}

		// Update fallback references
		$database = new Database();
		for ( $i = 0; $i < count( $ids ) - 1; $i++ ) {
			$database->update( $ids[ $i ], array( 'fallback_cta_id' => $ids[ $i + 1 ] ) );
		}

		return $ids;
	}

	/**
	 * Create a circular fallback chain (for testing error handling)
	 *
	 * @return array Array of CTA IDs.
	 */
	public static function create_circular_chain() {
		$database = new Database();

		$id1 = self::create( array( 'name' => 'CTA 1' ) );
		$id2 = self::create( array( 'name' => 'CTA 2', 'fallback_cta_id' => $id1 ) );

		// Create circular reference
		$database->update( $id1, array( 'fallback_cta_id' => $id2 ) );

		return array( $id1, $id2 );
	}

	/**
	 * Create a CTA for specific post types
	 *
	 * @param array $post_types Post types.
	 * @param array $overrides Override default attributes.
	 * @return int CTA ID.
	 */
	public static function create_for_post_types( $post_types, $overrides = array() ) {
		$overrides['post_types'] = $post_types;
		return self::create( $overrides );
	}

	/**
	 * Create a CTA for specific categories
	 *
	 * @param array $category_ids Category IDs.
	 * @param string $mode 'include' or 'exclude'.
	 * @param array $overrides Override default attributes.
	 * @return int CTA ID.
	 */
	public static function create_for_categories( $category_ids, $mode = 'include', $overrides = array() ) {
		$overrides['category_ids'] = $category_ids;
		$overrides['category_mode'] = $mode;
		return self::create( $overrides );
	}

	/**
	 * Make a CTA data array without saving to database
	 *
	 * @param array $overrides Override default attributes.
	 * @return array CTA data.
	 */
	public static function make( $overrides = array() ) {
		return wp_parse_args( $overrides, self::$defaults );
	}

	/**
	 * Get default CTA attributes
	 *
	 * @return array
	 */
	public static function defaults() {
		return self::$defaults;
	}
}
