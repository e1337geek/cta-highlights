<?php
/**
 * Auto-Insertion Flow Integration Tests
 *
 * HIGH PRIORITY: Integration Tests for Complete Auto-Insertion Workflow
 *
 * This test class covers the complete auto-insertion flow including:
 * - Database (CTA storage and retrieval)
 * - Matcher (conditional logic)
 * - Manager (fallback chains, JSON output)
 * - Client-side JavaScript (simulated)
 * - Final DOM output
 *
 * Tests verify that all auto-insertion components work together correctly.
 *
 * @package CTAHighlights\Tests\Integration
 */

namespace CTAHighlights\Tests\Integration;

use CTAHighlights\AutoInsertion\Manager;
use CTAHighlights\Tests\Factories\CTAFactory;
use CTAHighlights\Tests\Factories\PostFactory;
use CTAHighlights\Tests\Traits\CreatesDatabase;
use WP_UnitTestCase;

class AutoInsertionFlowTest extends WP_UnitTestCase {
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
	// COMPLETE WORKFLOW TESTS
	// =============================================================

	/**
	 * @test
	 * Test complete auto-insertion workflow
	 *
	 * WHY: Verifies entire flow from DB to JSON output
	 * PRIORITY: HIGH (integration)
	 */
	public function it_completes_full_auto_insertion_workflow() {
		// Create CTA in database
		$cta_id = CTAFactory::create(
			array(
				'name'                => 'Test Auto CTA',
				'content'             => '<p>Subscribe to our newsletter!</p>',
				'status'              => 'active',
				'cta_type'            => 'primary',
				'post_types'          => array( 'post' ),
				'insertion_direction' => 'forward',
				'insertion_position'  => 2,
			)
		);

		// Create post
		$post = PostFactory::create( array( 'post_type' => 'post' ) );

		// Navigate to post
		$this->go_to( get_permalink( $post ) );

		// Capture output
		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		// Verify JSON script tag exists
		$this->assertStringContainsString( '<script type="application/json" id="cta-highlights-auto-insert-data">', $output );

		// Parse JSON
		preg_match( '/<script[^>]*>(.*?)<\/script>/s', $output, $matches );
		$json = json_decode( $matches[1], true );

		// Verify JSON structure
		$this->assertIsArray( $json );
		$this->assertArrayHasKey( 'postId', $json );
		$this->assertArrayHasKey( 'ctas', $json );
		$this->assertEquals( $post, $json['postId'] );

		// Verify CTA data
		$this->assertCount( 1, $json['ctas'] );
		$this->assertEquals( $cta_id, $json['ctas'][0]['id'] );
		$this->assertStringContainsString( 'Subscribe to our newsletter!', $json['ctas'][0]['content'] );
	}

	/**
	 * @test
	 * Test conditional matching in workflow
	 *
	 * WHY: Verifies matcher integration with manager
	 * PRIORITY: HIGH (integration)
	 */
	public function it_matches_cta_based_on_conditions() {
		// Create CTA for specific post type
		$cta_id = CTAFactory::create(
			array(
				'post_types' => array( 'page' ),
				'cta_type'   => 'primary',
				'status'     => 'active',
			)
		);

		// Create post (not page)
		$post = PostFactory::create( array( 'post_type' => 'post' ) );
		$this->go_to( get_permalink( $post ) );

		// Should NOT output (wrong post type)
		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Should not output CTA for non-matching post type' );

		// Create page
		$page = PostFactory::create( array( 'post_type' => 'page' ) );
		$this->go_to( get_permalink( $page ) );

		// Should output
		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'cta-highlights-auto-insert-data', $output );
	}

	// =============================================================
	// FALLBACK CHAIN WORKFLOW
	// =============================================================

	/**
	 * @test
	 * Test fallback chain in complete workflow
	 *
	 * WHY: Verifies fallback logic works end-to-end
	 * PRIORITY: HIGH (integration)
	 */
	public function it_builds_fallback_chain_in_workflow() {
		// Create 3-CTA fallback chain
		$chain = CTAFactory::create_fallback_chain( 3 );

		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		// Parse JSON
		preg_match( '/<script[^>]*>(.*?)<\/script>/s', $output, $matches );
		$json = json_decode( $matches[1], true );

		// Should have 3 CTAs in chain
		$this->assertCount( 3, $json['ctas'] );

		// Verify chain order
		for ( $i = 0; $i < 3; $i++ ) {
			$this->assertEquals( $chain[ $i ], $json['ctas'][ $i ]['id'] );
		}
	}

	/**
	 * @test
	 * Test circular reference prevention in workflow
	 *
	 * WHY: Verifies circular detection works in full workflow
	 * PRIORITY: HIGH (stability)
	 */
	public function it_prevents_circular_references_in_workflow() {
		// Create circular chain
		$chain = CTAFactory::create_circular_chain( 3 );

		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		// Parse JSON
		preg_match( '/<script[^>]*>(.*?)<\/script>/s', $output, $matches );
		$json = json_decode( $matches[1], true );

		// Should have 3 CTAs (not infinite)
		$this->assertCount( 3, $json['ctas'] );

		// Verify no duplicate IDs
		$ids = array_column( $json['ctas'], 'id' );
		$unique_ids = array_unique( $ids );
		$this->assertCount( count( $ids ), $unique_ids, 'No duplicate CTAs in chain' );
	}

	// =============================================================
	// CATEGORY FILTERING WORKFLOW
	// =============================================================

	/**
	 * @test
	 * Test category filtering in workflow
	 *
	 * WHY: Verifies category matching works end-to-end
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_filters_by_category_in_workflow() {
		$cat_id = $this->factory->category->create( array( 'name' => 'News' ) );

		// Create CTA for specific category
		$cta_id = CTAFactory::create(
			array(
				'category_ids'  => array( $cat_id ),
				'category_mode' => 'include',
				'post_types'    => array( 'post' ),
				'cta_type'      => 'primary',
				'status'        => 'active',
			)
		);

		// Create post in different category
		$other_cat = $this->factory->category->create( array( 'name' => 'Blog' ) );
		$post      = PostFactory::create_in_category( $other_cat );
		$this->go_to( get_permalink( $post ) );

		// Should NOT output
		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		$this->assertEmpty( $output );

		// Create post in correct category
		$post2 = PostFactory::create_in_category( $cat_id );
		$this->go_to( get_permalink( $post2 ) );

		// Should output
		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'cta-highlights-auto-insert-data', $output );
	}

	// =============================================================
	// META BOX DISABLE WORKFLOW
	// =============================================================

	/**
	 * @test
	 * Test meta box disable flag in workflow
	 *
	 * WHY: Verifies per-post disable works end-to-end
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_respects_meta_box_disable_in_workflow() {
		// Create CTA
		$cta_id = CTAFactory::create(
			array(
				'post_types' => array( 'post' ),
				'cta_type'   => 'primary',
				'status'     => 'active',
			)
		);

		// Create post with disable flag
		$post = PostFactory::create();
		update_post_meta( $post, '_cta_highlights_disable_auto_insert', '1' );

		$this->go_to( get_permalink( $post ) );

		// Should NOT output
		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Should respect meta box disable flag' );
	}

	// =============================================================
	// JSON OUTPUT STRUCTURE TESTS
	// =============================================================

	/**
	 * @test
	 * Test JSON output structure is valid
	 *
	 * WHY: Client-side JavaScript depends on correct structure
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_outputs_valid_json_structure() {
		$cta_id = CTAFactory::create(
			array(
				'content'             => '<p>Test</p>',
				'post_types'          => array( 'post' ),
				'cta_type'            => 'primary',
				'status'              => 'active',
				'insertion_direction' => 'forward',
				'insertion_position'  => 3,
			)
		);

		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		preg_match( '/<script[^>]*>(.*?)<\/script>/s', $output, $matches );
		$json = json_decode( $matches[1], true );

		// Verify top-level structure
		$this->assertArrayHasKey( 'postId', $json );
		$this->assertArrayHasKey( 'contentSelector', $json );
		$this->assertArrayHasKey( 'ctas', $json );

		// Verify CTA structure
		$cta = $json['ctas'][0];
		$this->assertArrayHasKey( 'id', $cta );
		$this->assertArrayHasKey( 'content', $cta );
		$this->assertArrayHasKey( 'storage_condition_js', $cta );
		$this->assertArrayHasKey( 'insertion_direction', $cta );
		$this->assertArrayHasKey( 'insertion_position', $cta );
		$this->assertArrayHasKey( 'fallback_behavior', $cta );
	}

	/**
	 * @test
	 * Test shortcodes are processed in content
	 *
	 * WHY: Nested shortcodes should be expanded
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_processes_shortcodes_in_content_output() {
		// Register test shortcode
		add_shortcode(
			'test_sc',
			function() {
				return 'EXPANDED';
			}
		);

		$cta_id = CTAFactory::create(
			array(
				'content'    => 'Before [test_sc] After',
				'post_types' => array( 'post' ),
				'cta_type'   => 'primary',
				'status'     => 'active',
			)
		);

		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		preg_match( '/<script[^>]*>(.*?)<\/script>/s', $output, $matches );
		$json = json_decode( $matches[1], true );

		// Shortcode should be expanded
		$this->assertStringContainsString( 'EXPANDED', $json['ctas'][0]['content'] );
		$this->assertStringNotContainsString( '[test_sc]', $json['ctas'][0]['content'] );

		remove_shortcode( 'test_sc' );
	}

	// =============================================================
	// ASSET ENQUEUING INTEGRATION
	// =============================================================

	/**
	 * @test
	 * Test auto-insert script is enqueued
	 *
	 * WHY: JavaScript is required for client-side insertion
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_enqueues_auto_insert_script() {
		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		// Trigger script enqueuing
		do_action( 'wp_enqueue_scripts' );

		// Auto-insert script should be enqueued
		$this->assertTrue(
			wp_script_is( 'cta-highlights-auto-insert', 'enqueued' ),
			'Auto-insert script should be enqueued'
		);
	}

	/**
	 * @test
	 * Test force enqueue when auto-insert CTA has nested shortcode
	 *
	 * WHY: Base assets needed if CTA contains [cta_highlights] shortcode
	 * PRIORITY: MEDIUM (performance)
	 */
	public function it_force_enqueues_when_nested_shortcode() {
		// Create CTA with nested shortcode
		$cta_id = CTAFactory::create(
			array(
				'content'    => 'Content [cta_highlights template="default" title="Nested"]',
				'post_types' => array( 'post' ),
				'cta_type'   => 'primary',
				'status'     => 'active',
			)
		);

		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		// Trigger asset enqueuing
		do_action( 'wp_enqueue_scripts' );

		// Base assets should be enqueued
		$this->assertTrue(
			wp_style_is( 'cta-highlights-base', 'enqueued' ),
			'Base assets should be enqueued when nested shortcode'
		);
	}

	// =============================================================
	// INACTIVE CTA TESTS
	// =============================================================

	/**
	 * @test
	 * Test inactive CTAs are not included
	 *
	 * WHY: Only active CTAs should be output
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_excludes_inactive_ctas() {
		$cta_id = CTAFactory::create(
			array(
				'status'     => 'inactive',
				'post_types' => array( 'post' ),
				'cta_type'   => 'primary',
			)
		);

		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		// Should NOT output inactive CTA
		$this->assertEmpty( $output );
	}

	// =============================================================
	// MULTIPLE POSTS TESTS
	// =============================================================

	/**
	 * @test
	 * Test different CTAs for different post types
	 *
	 * WHY: Verifies conditional logic works across post types
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_shows_different_ctas_for_different_post_types() {
		// Create CTA for posts
		$post_cta = CTAFactory::create(
			array(
				'name'       => 'Post CTA',
				'content'    => '<p>Post Content</p>',
				'post_types' => array( 'post' ),
				'cta_type'   => 'primary',
				'status'     => 'active',
			)
		);

		// Create CTA for pages
		$page_cta = CTAFactory::create(
			array(
				'name'       => 'Page CTA',
				'content'    => '<p>Page Content</p>',
				'post_types' => array( 'page' ),
				'cta_type'   => 'primary',
				'status'     => 'active',
			)
		);

		// Test on post
		$post = PostFactory::create( array( 'post_type' => 'post' ) );
		$this->go_to( get_permalink( $post ) );

		ob_start();
		$this->manager->output_fallback_data();
		$post_output = ob_get_clean();

		preg_match( '/<script[^>]*>(.*?)<\/script>/s', $post_output, $matches );
		$post_json = json_decode( $matches[1], true );

		$this->assertStringContainsString( 'Post Content', $post_json['ctas'][0]['content'] );

		// Test on page
		$page = PostFactory::create( array( 'post_type' => 'page' ) );
		$this->go_to( get_permalink( $page ) );

		ob_start();
		$this->manager->output_fallback_data();
		$page_output = ob_get_clean();

		preg_match( '/<script[^>]*>(.*?)<\/script>/s', $page_output, $matches );
		$page_json = json_decode( $matches[1], true );

		$this->assertStringContainsString( 'Page Content', $page_json['ctas'][0]['content'] );
	}

	// =============================================================
	// NO CTA TESTS
	// =============================================================

	/**
	 * @test
	 * Test no output when no matching CTA
	 *
	 * WHY: Performance - don't output when nothing to insert
	 * PRIORITY: MEDIUM (performance)
	 */
	public function it_outputs_nothing_when_no_matching_cta() {
		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Should not output when no matching CTA' );
	}

	/**
	 * @test
	 * Test no output on archive pages
	 *
	 * WHY: Auto-insertion only works on singular posts
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_outputs_nothing_on_archive_pages() {
		CTAFactory::create(
			array(
				'post_types' => array( 'post' ),
				'cta_type'   => 'primary',
				'status'     => 'active',
			)
		);

		$this->go_to( home_url() );

		ob_start();
		$this->manager->output_fallback_data();
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Should not output on archive pages' );
	}
}
