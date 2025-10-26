<?php
/**
 * Registry Tests
 *
 * LOW PRIORITY: Simple Utility Tests for Template Registry
 *
 * This test class covers the Template\Registry class which tracks templates
 * used on a page. This is LOW PRIORITY because:
 * - Simple utility class with straightforward logic
 * - Used for template CSS enqueuing optimization
 * - No security risks or complex business logic
 * - Singleton pattern implementation
 *
 * @package CTAHighlights\Tests\Unit\Template
 */

namespace CTAHighlights\Tests\Unit\Template;

use CTAHighlights\Template\Registry;
use WP_UnitTestCase;

class RegistryTest extends WP_UnitTestCase {

	/**
	 * Registry instance
	 *
	 * @var Registry
	 */
	private $registry;

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->registry = Registry::instance();
		$this->registry->clear();
	}

	/**
	 * Teardown after each test
	 */
	public function tearDown(): void {
		$this->registry->clear();
		parent::tearDown();
	}

	// =============================================================
	// SINGLETON PATTERN TESTS
	// =============================================================

	/**
	 * @test
	 * Test that Registry is a singleton
	 *
	 * WHY: Registry must be a singleton for global template tracking
	 * PRIORITY: MEDIUM (architecture)
	 */
	public function it_is_a_singleton() {
		$instance1 = Registry::instance();
		$instance2 = Registry::instance();

		$this->assertSame( $instance1, $instance2, 'Registry should return same instance' );
	}

	/**
	 * @test
	 * Test that singleton cannot be cloned
	 *
	 * WHY: Singleton pattern should prevent cloning
	 * PRIORITY: LOW (architecture)
	 */
	public function it_prevents_cloning() {
		$this->expectException( \Error::class );

		$clone = clone $this->registry;
	}

	/**
	 * @test
	 * Test that singleton cannot be unserialized
	 *
	 * WHY: Singleton pattern should prevent unserialization
	 * PRIORITY: LOW (architecture)
	 */
	public function it_prevents_unserialization() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Cannot unserialize singleton' );

		$serialized = serialize( $this->registry );
		unserialize( $serialized );
	}

	// =============================================================
	// TEMPLATE REGISTRATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test registering a template
	 *
	 * WHY: Core functionality for tracking templates
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_registers_template() {
		$this->registry->register( 'default' );

		$this->assertTrue(
			$this->registry->is_registered( 'default' ),
			'Template should be registered'
		);
	}

	/**
	 * @test
	 * Test registering multiple templates
	 *
	 * WHY: Multiple templates can be used on one page
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_registers_multiple_templates() {
		$this->registry->register( 'default' );
		$this->registry->register( 'banner' );
		$this->registry->register( 'modal' );

		$this->assertEquals( 3, $this->registry->count() );

		$all = $this->registry->get_all();
		$this->assertContains( 'default', $all );
		$this->assertContains( 'banner', $all );
		$this->assertContains( 'modal', $all );
	}

	/**
	 * @test
	 * Test that duplicate registrations are ignored
	 *
	 * WHY: Same template shouldn't be registered twice
	 * PRIORITY: MEDIUM (data integrity)
	 */
	public function it_ignores_duplicate_registrations() {
		$this->registry->register( 'default' );
		$this->registry->register( 'default' );
		$this->registry->register( 'default' );

		$this->assertEquals( 1, $this->registry->count(), 'Should have only 1 template' );
	}

	/**
	 * @test
	 * Test template name sanitization
	 *
	 * WHY: Template names should be sanitized for file system use
	 * PRIORITY: MEDIUM (security)
	 */
	public function it_sanitizes_template_names() {
		$this->registry->register( '../../../evil' );

		$all = $this->registry->get_all();

		// Should be sanitized (no directory traversal)
		foreach ( $all as $template ) {
			$this->assertStringNotContainsString( '..', $template );
			$this->assertStringNotContainsString( '/', $template );
		}
	}

	// =============================================================
	// TEMPLATE CHECKING TESTS
	// =============================================================

	/**
	 * @test
	 * Test checking if template is registered
	 *
	 * WHY: Need to check registration status
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_checks_if_template_is_registered() {
		$this->registry->register( 'default' );

		$this->assertTrue( $this->registry->is_registered( 'default' ) );
		$this->assertFalse( $this->registry->is_registered( 'nonexistent' ) );
	}

	/**
	 * @test
	 * Test checking before any registration
	 *
	 * WHY: Should handle empty registry
	 * PRIORITY: LOW (edge case)
	 */
	public function it_returns_false_for_empty_registry() {
		$this->assertFalse( $this->registry->is_registered( 'default' ) );
	}

	// =============================================================
	// RETRIEVAL TESTS
	// =============================================================

	/**
	 * @test
	 * Test getting all registered templates
	 *
	 * WHY: Asset manager needs list of all templates
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_gets_all_registered_templates() {
		$this->registry->register( 'template1' );
		$this->registry->register( 'template2' );
		$this->registry->register( 'template3' );

		$all = $this->registry->get_all();

		$this->assertIsArray( $all );
		$this->assertCount( 3, $all );
	}

	/**
	 * @test
	 * Test getting all from empty registry
	 *
	 * WHY: Should return empty array
	 * PRIORITY: LOW (edge case)
	 */
	public function it_returns_empty_array_when_no_templates() {
		$all = $this->registry->get_all();

		$this->assertIsArray( $all );
		$this->assertEmpty( $all );
	}

	/**
	 * @test
	 * Test counting registered templates
	 *
	 * WHY: Useful for debugging and testing
	 * PRIORITY: LOW (utility)
	 */
	public function it_counts_registered_templates() {
		$this->assertEquals( 0, $this->registry->count() );

		$this->registry->register( 'template1' );
		$this->assertEquals( 1, $this->registry->count() );

		$this->registry->register( 'template2' );
		$this->assertEquals( 2, $this->registry->count() );
	}

	// =============================================================
	// CLEARING TESTS
	// =============================================================

	/**
	 * @test
	 * Test clearing all registered templates
	 *
	 * WHY: Need to reset registry between requests
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_clears_all_registered_templates() {
		$this->registry->register( 'template1' );
		$this->registry->register( 'template2' );

		$this->assertEquals( 2, $this->registry->count() );

		$this->registry->clear();

		$this->assertEquals( 0, $this->registry->count() );
		$this->assertEmpty( $this->registry->get_all() );
	}

	/**
	 * @test
	 * Test clearing empty registry
	 *
	 * WHY: Should handle clearing when already empty
	 * PRIORITY: LOW (edge case)
	 */
	public function it_handles_clearing_empty_registry() {
		$this->registry->clear();

		$this->assertEquals( 0, $this->registry->count() );
		$this->assertEmpty( $this->registry->get_all() );
	}

	// =============================================================
	// INTEGRATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test typical usage workflow
	 *
	 * WHY: End-to-end test of registry functionality
	 * PRIORITY: MEDIUM (integration)
	 */
	public function it_completes_typical_workflow() {
		// Start with empty registry
		$this->assertEquals( 0, $this->registry->count() );

		// Register templates as they're used
		$this->registry->register( 'default' );
		$this->registry->register( 'banner' );

		// Check registration
		$this->assertTrue( $this->registry->is_registered( 'default' ) );
		$this->assertTrue( $this->registry->is_registered( 'banner' ) );
		$this->assertFalse( $this->registry->is_registered( 'modal' ) );

		// Get all for CSS enqueuing
		$all = $this->registry->get_all();
		$this->assertCount( 2, $all );

		// Clear for next request
		$this->registry->clear();
		$this->assertEquals( 0, $this->registry->count() );
	}
}
