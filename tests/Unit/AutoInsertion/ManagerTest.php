<?php
/**
 * Manager Tests
 *
 * MEDIUM PRIORITY: Business Logic Tests for Auto-Insertion Manager
 *
 * This test class covers the AutoInsertion\Manager class which orchestrates
 * the auto-insertion functionality including fallback chains, circular reference
 * detection, and JSON output. This is MEDIUM PRIORITY because:
 * - Complex business logic for fallback chains
 * - Critical for user experience but not security-critical
 * - Handles circular reference detection (prevents infinite loops)
 * - Generates JSON data for client-side JavaScript
 *
 * @package CTAHighlights\Tests\Unit\AutoInsertion
 */

namespace CTAHighlights\Tests\Unit\AutoInsertion;

use CTAHighlights\AutoInsertion\Manager;
use CTAHighlights\Tests\Factories\PostFactory;
use CTAHighlights\Tests\Factories\CTAFactory;
use CTAHighlights\Tests\Traits\CreatesDatabase;
use WP_UnitTestCase;

class ManagerTest extends WP_UnitTestCase {
	use CreatesDatabase;

	/**
	 * Manager instance
	 *
	 * @var Manager
	 */
	private $manager;

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->setupDatabase();
		$this->manager = Manager::instance();
	}

	/**
	 * Teardown after each test
	 */
	public function tearDown(): void {
		$this->teardownDatabase();
		parent::tearDown();
	}

	// =============================================================
	// SINGLETON PATTERN TESTS
	// =============================================================

	/**
	 * @test
	 * Test that Manager is a singleton
	 *
	 * WHY: Manager must be a singleton for proper initialization
	 * PRIORITY: MEDIUM (architecture)
	 */
	public function it_is_a_singleton() {
		$instance1 = Manager::instance();
		$instance2 = Manager::instance();

		$this->assertSame( $instance1, $instance2, 'Manager should return same instance' );
	}

	// =============================================================
	// FALLBACK CHAIN BUILDING TESTS
	// =============================================================

	/**
	 * @test
	 * Test that simple fallback chain is built correctly
	 *
	 * WHY: Core functionality for fallback behavior
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_builds_simple_fallback_chain() {
		// Create 2-CTA fallback chain
		$chain = CTAFactory::create_fallback_chain( 2 );

		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		// Capture output
		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		// Parse JSON
		$this->assertStringContainsString( '<script type="application/json"', $output );
		preg_match( '/<script[^>]*>(.*?)<\/script>/s', $output, $matches );

		if ( ! empty( $matches[1] ) ) {
			$data = json_decode( $matches[1], true );

			$this->assertIsArray( $data );
			$this->assertArrayHasKey( 'ctas', $data );
			$this->assertCount( 2, $data['ctas'], 'Should have 2 CTAs in chain' );
		}
	}

	/**
	 * @test
	 * Test that long fallback chain is built
	 *
	 * WHY: Must support multiple fallback levels
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_builds_long_fallback_chain() {
		// Create 5-CTA fallback chain
		$chain = CTAFactory::create_fallback_chain( 5 );

		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		preg_match( '/<script[^>]*>(.*?)<\/script>/s', $output, $matches );

		if ( ! empty( $matches[1] ) ) {
			$data = json_decode( $matches[1], true );

			$this->assertCount( 5, $data['ctas'], 'Should have 5 CTAs in chain' );
		}
	}

	/**
	 * @test
	 * Test that max depth limit is enforced
	 *
	 * WHY: Prevents infinite loops and performance issues
	 * PRIORITY: HIGH (stability)
	 */
	public function it_enforces_max_fallback_depth() {
		// Try to create 15-CTA chain (exceeds MAX_FALLBACK_DEPTH of 10)
		$chain = CTAFactory::create_fallback_chain( 15 );

		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		preg_match( '/<script[^>]*>(.*?)<\/script>/s', $output, $matches );

		if ( ! empty( $matches[1] ) ) {
			$data = json_decode( $matches[1], true );

			$this->assertLessThanOrEqual(
				Manager::MAX_FALLBACK_DEPTH,
				count( $data['ctas'] ),
				'Chain length should not exceed MAX_FALLBACK_DEPTH'
			);
		}
	}

	// =============================================================
	// CIRCULAR REFERENCE DETECTION TESTS (CRITICAL)
	// =============================================================

	/**
	 * @test
	 * Test that circular references are detected and prevented
	 *
	 * WHY: Prevents infinite loops that could crash the site
	 * PRIORITY: HIGH (stability/security)
	 */
	public function it_prevents_circular_reference_loops() {
		// Create circular chain: A -> B -> C -> A
		$chain = CTAFactory::create_circular_chain( 3 );

		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		preg_match( '/<script[^>]*>(.*?)<\/script>/s', $output, $matches );

		if ( ! empty( $matches[1] ) ) {
			$data = json_decode( $matches[1], true );

			// Should stop at 3 CTAs (not loop infinitely)
			$this->assertCount( 3, $data['ctas'], 'Should stop at circular reference' );

			// Verify CTA IDs are unique (no duplicates)
			$cta_ids = array_column( $data['ctas'], 'id' );
			$unique_ids = array_unique( $cta_ids );

			$this->assertCount(
				count( $cta_ids ),
				$unique_ids,
				'CTA IDs should be unique (no circular duplicates)'
			);
		}
	}

	/**
	 * @test
	 * Test that self-referencing fallback is handled
	 *
	 * WHY: Edge case where CTA points to itself
	 * PRIORITY: MEDIUM (edge case)
	 */
	public function it_handles_self_referencing_cta() {
		$cta_id = CTAFactory::create(
			array(
				'name'            => 'Self-Reference',
				'post_types'      => array( 'post' ),
				'cta_type'        => 'primary',
			)
		);

		// Make it point to itself
		$database = $this->manager->get_database();
		$database->update(
			$cta_id,
			array( 'fallback_cta_id' => $cta_id )
		);

		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		preg_match( '/<script[^>]*>(.*?)<\/script>/s', $output, $matches );

		if ( ! empty( $matches[1] ) ) {
			$data = json_decode( $matches[1], true );

			// Should have only 1 CTA (not infinite loop)
			$this->assertCount( 1, $data['ctas'], 'Should stop at self-reference' );
		}
	}

	// =============================================================
	// FALLBACK MATCHING TESTS
	// =============================================================

	/**
	 * @test
	 * Test that inactive fallbacks are skipped
	 *
	 * WHY: Only active CTAs should be in fallback chain
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_skips_inactive_fallbacks() {
		$cta1_id = CTAFactory::create(
			array(
				'name'       => 'Primary',
				'status'     => 'active',
				'post_types' => array( 'post' ),
				'cta_type'   => 'primary',
			)
		);

		$cta2_id = CTAFactory::create(
			array(
				'name'       => 'Fallback',
				'status'     => 'inactive', // Inactive!
				'post_types' => array( 'post' ),
				'cta_type'   => 'fallback',
			)
		);

		// Link them
		$database = $this->manager->get_database();
		$database->update( $cta1_id, array( 'fallback_cta_id' => $cta2_id ) );

		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		preg_match( '/<script[^>]*>(.*?)<\/script>/s', $output, $matches );

		if ( ! empty( $matches[1] ) ) {
			$data = json_decode( $matches[1], true );

			// Should have only 1 CTA (inactive fallback skipped)
			$this->assertCount( 1, $data['ctas'], 'Should skip inactive fallback' );
		}
	}

	/**
	 * @test
	 * Test that fallbacks are filtered by post type
	 *
	 * WHY: Fallbacks must match the post type/category
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_filters_fallbacks_by_post_type() {
		$cta1_id = CTAFactory::create(
			array(
				'name'       => 'Primary',
				'status'     => 'active',
				'post_types' => array( 'post' ),
				'cta_type'   => 'primary',
			)
		);

		$cta2_id = CTAFactory::create(
			array(
				'name'       => 'Fallback',
				'status'     => 'active',
				'post_types' => array( 'page' ), // Different post type!
				'cta_type'   => 'fallback',
			)
		);

		// Link them
		$database = $this->manager->get_database();
		$database->update( $cta1_id, array( 'fallback_cta_id' => $cta2_id ) );

		$post = PostFactory::create( array( 'post_type' => 'post' ) );
		$this->go_to( get_permalink( $post ) );

		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		preg_match( '/<script[^>]*>(.*?)<\/script>/s', $output, $matches );

		if ( ! empty( $matches[1] ) ) {
			$data = json_decode( $matches[1], true );

			// Should have only 1 CTA (fallback doesn't match post type)
			$this->assertCount( 1, $data['ctas'], 'Should filter fallback by post type' );
		}
	}

	// =============================================================
	// JSON OUTPUT TESTS
	// =============================================================

	/**
	 * @test
	 * Test that JSON output is valid
	 *
	 * WHY: Client-side JavaScript depends on valid JSON
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_outputs_valid_json() {
		CTAFactory::create(
			array(
				'post_types' => array( 'post' ),
				'cta_type'   => 'primary',
			)
		);

		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		preg_match( '/<script[^>]*>(.*?)<\/script>/s', $output, $matches );

		if ( ! empty( $matches[1] ) ) {
			$data = json_decode( $matches[1], true );

			$this->assertIsArray( $data, 'JSON should decode to array' );
			$this->assertNull( json_last_error(), 'JSON should be valid' );
		}
	}

	/**
	 * @test
	 * Test that JSON includes required fields
	 *
	 * WHY: Client-side JavaScript expects specific data structure
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_includes_required_fields_in_json() {
		CTAFactory::create(
			array(
				'post_types' => array( 'post' ),
				'cta_type'   => 'primary',
			)
		);

		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		preg_match( '/<script[^>]*>(.*?)<\/script>/s', $output, $matches );

		if ( ! empty( $matches[1] ) ) {
			$data = json_decode( $matches[1], true );

			$this->assertArrayHasKey( 'postId', $data );
			$this->assertArrayHasKey( 'contentSelector', $data );
			$this->assertArrayHasKey( 'ctas', $data );

			if ( ! empty( $data['ctas'] ) ) {
				$cta = $data['ctas'][0];

				$this->assertArrayHasKey( 'id', $cta );
				$this->assertArrayHasKey( 'content', $cta );
				$this->assertArrayHasKey( 'storage_condition_js', $cta );
				$this->assertArrayHasKey( 'insertion_direction', $cta );
				$this->assertArrayHasKey( 'insertion_position', $cta );
			}
		}
	}

	/**
	 * @test
	 * Test that content is processed (shortcodes expanded)
	 *
	 * WHY: Shortcodes in CTA content should be expanded
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_processes_shortcodes_in_content() {
		// Create CTA with shortcode in content
		CTAFactory::create(
			array(
				'content'    => 'Test [cta_highlights template="default" title="Nested"]',
				'post_types' => array( 'post' ),
				'cta_type'   => 'primary',
			)
		);

		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		preg_match( '/<script[^>]*>(.*?)<\/script>/s', $output, $matches );

		if ( ! empty( $matches[1] ) ) {
			$data = json_decode( $matches[1], true );

			if ( ! empty( $data['ctas'] ) ) {
				$content = $data['ctas'][0]['content'];

				// Shortcode should be processed (not raw)
				$this->assertStringNotContainsString( '[cta_highlights', $content );
			}
		}
	}

	// =============================================================
	// CONDITIONAL OUTPUT TESTS
	// =============================================================

	/**
	 * @test
	 * Test that no output on non-singular pages
	 *
	 * WHY: Auto-insertion only works on singular posts
	 * PRIORITY: MEDIUM (performance)
	 */
	public function it_doesnt_output_on_archive_pages() {
		CTAFactory::create(
			array(
				'post_types' => array( 'post' ),
				'cta_type'   => 'primary',
			)
		);

		$this->go_to( home_url() );

		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Should not output on non-singular pages' );
	}

	/**
	 * @test
	 * Test that no output when no matching CTA
	 *
	 * WHY: Conditional loading for performance
	 * PRIORITY: MEDIUM (performance)
	 */
	public function it_doesnt_output_when_no_matching_cta() {
		// Create CTA for 'page' post type
		CTAFactory::create(
			array(
				'post_types' => array( 'page' ),
				'cta_type'   => 'primary',
			)
		);

		// Visit a 'post' (not 'page')
		$post = PostFactory::create( array( 'post_type' => 'post' ) );
		$this->go_to( get_permalink( $post ) );

		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Should not output when no matching CTA' );
	}

	// =============================================================
	// ASSET ENQUEUING TESTS
	// =============================================================

	/**
	 * @test
	 * Test that auto-insert script is enqueued on singular pages
	 *
	 * WHY: JavaScript is required for client-side insertion
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_enqueues_scripts_on_singular_pages() {
		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		// Trigger script enqueuing
		do_action( 'wp_enqueue_scripts' );

		$this->assertTrue(
			wp_script_is( 'cta-highlights-auto-insert', 'enqueued' ),
			'Auto-insert script should be enqueued on singular pages'
		);
	}

	/**
	 * @test
	 * Test that scripts are not enqueued on archive pages
	 *
	 * WHY: Performance optimization (don't load unnecessary scripts)
	 * PRIORITY: MEDIUM (performance)
	 */
	public function it_doesnt_enqueue_scripts_on_archive_pages() {
		$this->go_to( home_url() );

		do_action( 'wp_enqueue_scripts' );

		$this->assertFalse(
			wp_script_is( 'cta-highlights-auto-insert', 'enqueued' ),
			'Auto-insert script should not be enqueued on archive pages'
		);
	}

	// =============================================================
	// COMPONENT ACCESS TESTS
	// =============================================================

	/**
	 * @test
	 * Test that getter methods return correct instances
	 *
	 * WHY: Public API should provide access to components
	 * PRIORITY: LOW (API consistency)
	 */
	public function it_provides_access_to_components() {
		$this->assertInstanceOf(
			'CTAHighlights\AutoInsertion\Database',
			$this->manager->get_database()
		);

		$this->assertInstanceOf(
			'CTAHighlights\AutoInsertion\Matcher',
			$this->manager->get_matcher()
		);

		$this->assertInstanceOf(
			'CTAHighlights\AutoInsertion\Inserter',
			$this->manager->get_inserter()
		);
	}
}
