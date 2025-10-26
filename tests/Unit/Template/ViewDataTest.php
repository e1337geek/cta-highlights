<?php
/**
 * ViewData Tests
 *
 * LOW PRIORITY: Utility Tests for Template View Data Container
 *
 * This test class covers the Template\ViewData class which provides safe access
 * to template variables without using extract(). This is LOW PRIORITY because:
 * - Simple data container class
 * - No complex business logic
 * - Implements ArrayAccess interface
 * - Read-only by design (security benefit)
 *
 * @package CTAHighlights\Tests\Unit\Template
 */

namespace CTAHighlights\Tests\Unit\Template;

use CTAHighlights\Template\ViewData;
use WP_UnitTestCase;

class ViewDataTest extends WP_UnitTestCase {

	// =============================================================
	// CONSTRUCTION TESTS
	// =============================================================

	/**
	 * @test
	 * Test creating ViewData with data
	 *
	 * WHY: Core functionality for data container
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_creates_with_data() {
		$data = array(
			'title'   => 'Test Title',
			'content' => 'Test Content',
		);

		$view_data = new ViewData( $data );

		$this->assertEquals( 'Test Title', $view_data->get( 'title' ) );
		$this->assertEquals( 'Test Content', $view_data->get( 'content' ) );
	}

	/**
	 * @test
	 * Test creating ViewData without data
	 *
	 * WHY: Should handle empty initialization
	 * PRIORITY: LOW (edge case)
	 */
	public function it_creates_empty() {
		$view_data = new ViewData();

		$this->assertEquals( '', $view_data->get( 'nonexistent' ) );
	}

	// =============================================================
	// GET METHOD TESTS
	// =============================================================

	/**
	 * @test
	 * Test getting existing value
	 *
	 * WHY: Core functionality for data retrieval
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_gets_existing_value() {
		$view_data = new ViewData(
			array(
				'name' => 'John Doe',
			)
		);

		$this->assertEquals( 'John Doe', $view_data->get( 'name' ) );
	}

	/**
	 * @test
	 * Test getting nonexistent value with default
	 *
	 * WHY: Should return default for missing keys
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_returns_default_for_missing_key() {
		$view_data = new ViewData( array() );

		$this->assertEquals( 'default', $view_data->get( 'missing', 'default' ) );
	}

	/**
	 * @test
	 * Test getting nonexistent value without default
	 *
	 * WHY: Should return empty string by default
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_returns_empty_string_for_missing_key() {
		$view_data = new ViewData( array() );

		$this->assertEquals( '', $view_data->get( 'missing' ) );
	}

	/**
	 * @test
	 * Test getting various data types
	 *
	 * WHY: Should handle different value types
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_handles_various_data_types() {
		$view_data = new ViewData(
			array(
				'string'  => 'text',
				'number'  => 42,
				'boolean' => true,
				'array'   => array( 1, 2, 3 ),
				'null'    => null,
			)
		);

		$this->assertEquals( 'text', $view_data->get( 'string' ) );
		$this->assertEquals( 42, $view_data->get( 'number' ) );
		$this->assertTrue( $view_data->get( 'boolean' ) );
		$this->assertEquals( array( 1, 2, 3 ), $view_data->get( 'array' ) );
		$this->assertNull( $view_data->get( 'null' ) );
	}

	// =============================================================
	// HAS METHOD TESTS
	// =============================================================

	/**
	 * @test
	 * Test checking if key exists
	 *
	 * WHY: Templates need to check variable existence
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_checks_if_key_exists() {
		$view_data = new ViewData(
			array(
				'title' => 'Test',
			)
		);

		$this->assertTrue( $view_data->has( 'title' ) );
		$this->assertFalse( $view_data->has( 'missing' ) );
	}

	/**
	 * @test
	 * Test has() with null value
	 *
	 * WHY: Null values should still count as existing
	 * PRIORITY: LOW (edge case)
	 */
	public function it_handles_null_values_in_has() {
		$view_data = new ViewData(
			array(
				'nullable' => null,
			)
		);

		$this->assertFalse( $view_data->has( 'nullable' ), 'isset() returns false for null' );
	}

	// =============================================================
	// ALL METHOD TESTS
	// =============================================================

	/**
	 * @test
	 * Test getting all data
	 *
	 * WHY: Sometimes need access to all data
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_gets_all_data() {
		$data = array(
			'key1' => 'value1',
			'key2' => 'value2',
			'key3' => 'value3',
		);

		$view_data = new ViewData( $data );

		$this->assertEquals( $data, $view_data->all() );
	}

	/**
	 * @test
	 * Test getting all from empty ViewData
	 *
	 * WHY: Should return empty array
	 * PRIORITY: LOW (edge case)
	 */
	public function it_returns_empty_array_for_all() {
		$view_data = new ViewData();

		$this->assertEquals( array(), $view_data->all() );
	}

	// =============================================================
	// MAGIC METHOD TESTS (PROPERTY ACCESS)
	// =============================================================

	/**
	 * @test
	 * Test magic __get for property access
	 *
	 * WHY: Templates use property syntax ($data->title)
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_supports_property_access() {
		$view_data = new ViewData(
			array(
				'title' => 'Test Title',
			)
		);

		$this->assertEquals( 'Test Title', $view_data->title );
	}

	/**
	 * @test
	 * Test magic __isset for property checking
	 *
	 * WHY: Templates use isset($data->title)
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_supports_isset_checking() {
		$view_data = new ViewData(
			array(
				'title' => 'Test',
			)
		);

		$this->assertTrue( isset( $view_data->title ) );
		$this->assertFalse( isset( $view_data->missing ) );
	}

	// =============================================================
	// ARRAYACCESS INTERFACE TESTS
	// =============================================================

	/**
	 * @test
	 * Test ArrayAccess offsetExists
	 *
	 * WHY: Templates can use isset($data['title'])
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_supports_array_isset() {
		$view_data = new ViewData(
			array(
				'title' => 'Test',
			)
		);

		$this->assertTrue( isset( $view_data['title'] ) );
		$this->assertFalse( isset( $view_data['missing'] ) );
	}

	/**
	 * @test
	 * Test ArrayAccess offsetGet
	 *
	 * WHY: Templates can use $data['title']
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_supports_array_access() {
		$view_data = new ViewData(
			array(
				'title' => 'Test Title',
			)
		);

		$this->assertEquals( 'Test Title', $view_data['title'] );
	}

	/**
	 * @test
	 * Test that ArrayAccess offsetSet does nothing (read-only)
	 *
	 * WHY: ViewData should be read-only for security
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_array_assignment() {
		$view_data = new ViewData(
			array(
				'title' => 'Original',
			)
		);

		// Try to set (should do nothing)
		$view_data['title'] = 'Modified';

		// Should still be original
		$this->assertEquals( 'Original', $view_data['title'], 'ViewData should be read-only' );
	}

	/**
	 * @test
	 * Test that ArrayAccess offsetUnset does nothing (read-only)
	 *
	 * WHY: ViewData should be read-only for security
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_array_unset() {
		$view_data = new ViewData(
			array(
				'title' => 'Test',
			)
		);

		// Try to unset (should do nothing)
		unset( $view_data['title'] );

		// Should still exist
		$this->assertTrue( isset( $view_data['title'] ), 'ViewData should be read-only' );
	}

	// =============================================================
	// SECURITY TESTS (READ-ONLY BEHAVIOR)
	// =============================================================

	/**
	 * @test
	 * Test that data cannot be modified after construction
	 *
	 * WHY: Read-only design prevents template mutations
	 * PRIORITY: HIGH (security)
	 */
	public function it_is_immutable_after_construction() {
		$original_data = array(
			'title' => 'Original',
		);

		$view_data = new ViewData( $original_data );

		// Try to modify via array access (should fail silently)
		$view_data['title'] = 'Modified';
		$view_data['new_key'] = 'New Value';

		// Original should be unchanged
		$this->assertEquals( 'Original', $view_data['title'] );
		$this->assertFalse( isset( $view_data['new_key'] ) );
	}

	// =============================================================
	// TEMPLATE USAGE TESTS (INTEGRATION)
	// =============================================================

	/**
	 * @test
	 * Test typical template usage patterns
	 *
	 * WHY: End-to-end test of common template scenarios
	 * PRIORITY: MEDIUM (integration)
	 */
	public function it_supports_typical_template_usage() {
		$view_data = new ViewData(
			array(
				'cta_title'       => 'Subscribe Now',
				'cta_content'     => 'Get weekly updates',
				'cta_button_text' => 'Subscribe',
				'cta_button_url'  => 'https://example.com',
			)
		);

		// Property access
		$this->assertEquals( 'Subscribe Now', $view_data->cta_title );

		// Array access
		$this->assertEquals( 'Get weekly updates', $view_data['cta_content'] );

		// get() method
		$this->assertEquals( 'Subscribe', $view_data->get( 'cta_button_text' ) );

		// Existence checking
		$this->assertTrue( isset( $view_data->cta_button_url ) );

		// Default values
		$this->assertEquals( '#', $view_data->get( 'missing_url', '#' ) );
	}

	/**
	 * @test
	 * Test safe access without warnings
	 *
	 * WHY: Should not generate PHP warnings/notices
	 * PRIORITY: MEDIUM (robustness)
	 */
	public function it_provides_safe_access_without_warnings() {
		$view_data = new ViewData( array() );

		// None of these should generate warnings
		$value1 = $view_data->nonexistent;
		$value2 = $view_data['nonexistent'];
		$value3 = $view_data->get( 'nonexistent' );
		$value4 = isset( $view_data->nonexistent );

		$this->assertEquals( '', $value1 );
		$this->assertEquals( '', $value2 );
		$this->assertEquals( '', $value3 );
		$this->assertFalse( $value4 );
	}
}
