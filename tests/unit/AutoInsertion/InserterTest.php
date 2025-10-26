<?php
/**
 * Inserter Tests
 *
 * MEDIUM PRIORITY: Business Logic Tests for Content Insertion
 *
 * This test class covers the AutoInsertion\Inserter class which handles
 * parsing content, calculating insertion positions, and injecting CTAs. This is
 * MEDIUM PRIORITY because:
 * - Core business logic for insertion positioning
 * - Not security-critical (client-side insertion is now primary)
 * - Tests legacy server-side insertion logic
 * - Position calculation is complex and error-prone
 *
 * Note: Auto-insertion now happens primarily client-side via JavaScript.
 * This class tests the legacy server-side logic kept for compatibility.
 *
 * @package CTAHighlights\Tests\Unit\AutoInsertion
 */

namespace CTAHighlights\Tests\Unit\AutoInsertion;

use CTAHighlights\AutoInsertion\Inserter;
use CTAHighlights\Tests\Factories\CTAFactory;
use WP_UnitTestCase;

class InserterTest extends WP_UnitTestCase {

	/**
	 * Inserter instance
	 *
	 * @var Inserter
	 */
	private $inserter;

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->inserter = new Inserter();
	}

	// =============================================================
	// POSITION CALCULATION TESTS (FORWARD DIRECTION)
	// =============================================================

	/**
	 * @test
	 * Test forward position calculation
	 *
	 * WHY: Core functionality for forward insertion
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_calculates_forward_position_correctly() {
		$content = $this->create_html_content( 5 );

		$cta = array(
			'id'                  => 1,
			'content'             => 'Test CTA',
			'insertion_direction' => 'forward',
			'insertion_position'  => 3, // After 3rd element
			'fallback_behavior'   => 'skip',
			'storage_conditions'  => array(),
		);

		$result = $this->inserter->insert( $content, $cta, 'true' );

		// CTA should be inserted after 3rd paragraph
		$this->assertStringContainsString( 'cta-highlights-wrapper', $result );
		$this->assertStringContainsString( 'data-cta-id="1"', $result );
	}

	/**
	 * @test
	 * Test forward insertion at position 1
	 *
	 * WHY: First position is a common use case
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_inserts_at_first_position_forward() {
		$content = $this->create_html_content( 5 );

		$cta = array(
			'id'                  => 1,
			'content'             => 'Test CTA',
			'insertion_direction' => 'forward',
			'insertion_position'  => 1, // After 1st element
			'fallback_behavior'   => 'skip',
			'storage_conditions'  => array(),
		);

		$result = $this->inserter->insert( $content, $cta, 'true' );

		$this->assertStringContainsString( 'cta-highlights-wrapper', $result );

		// Find position of CTA in result
		$cta_pos = strpos( $result, 'cta-highlights-wrapper' );
		$first_p_end = strpos( $result, '</p>' );

		// CTA should be after first paragraph
		$this->assertGreaterThan( $first_p_end, $cta_pos );
	}

	/**
	 * @test
	 * Test forward insertion beyond content length (skip behavior)
	 *
	 * WHY: Handle insufficient content gracefully
	 * PRIORITY: MEDIUM (edge case)
	 */
	public function it_skips_when_insufficient_content_forward() {
		$content = $this->create_html_content( 3 );

		$cta = array(
			'id'                  => 1,
			'content'             => 'Test CTA',
			'insertion_direction' => 'forward',
			'insertion_position'  => 10, // Beyond content
			'fallback_behavior'   => 'skip',
			'storage_conditions'  => array(),
		);

		$result = $this->inserter->insert( $content, $cta, 'true' );

		// CTA should NOT be inserted (skip behavior)
		$this->assertStringNotContainsString( 'cta-highlights-wrapper', $result );
		$this->assertEquals( $content, $result );
	}

	/**
	 * @test
	 * Test forward insertion beyond content length (end behavior)
	 *
	 * WHY: Fallback to end when insufficient content
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_inserts_at_end_when_insufficient_content_forward() {
		$content = $this->create_html_content( 3 );

		$cta = array(
			'id'                  => 1,
			'content'             => 'Test CTA',
			'insertion_direction' => 'forward',
			'insertion_position'  => 10, // Beyond content
			'fallback_behavior'   => 'end', // Insert at end
			'storage_conditions'  => array(),
		);

		$result = $this->inserter->insert( $content, $cta, 'true' );

		// CTA should be inserted
		$this->assertStringContainsString( 'cta-highlights-wrapper', $result );

		// Should be at the end
		$cta_pos = strpos( $result, 'cta-highlights-wrapper' );
		$last_p_end = strrpos( $result, '</p>' );

		$this->assertGreaterThan( $last_p_end, $cta_pos );
	}

	// =============================================================
	// POSITION CALCULATION TESTS (REVERSE DIRECTION)
	// =============================================================

	/**
	 * @test
	 * Test reverse position calculation
	 *
	 * WHY: Core functionality for reverse insertion
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_calculates_reverse_position_correctly() {
		$content = $this->create_html_content( 5 );

		$cta = array(
			'id'                  => 1,
			'content'             => 'Test CTA',
			'insertion_direction' => 'reverse',
			'insertion_position'  => 2, // 2 from end
			'fallback_behavior'   => 'skip',
			'storage_conditions'  => array(),
		);

		$result = $this->inserter->insert( $content, $cta, 'true' );

		$this->assertStringContainsString( 'cta-highlights-wrapper', $result );
	}

	/**
	 * @test
	 * Test reverse insertion at last position
	 *
	 * WHY: Last position is a common use case
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_inserts_at_last_position_reverse() {
		$content = $this->create_html_content( 5 );

		$cta = array(
			'id'                  => 1,
			'content'             => 'Test CTA',
			'insertion_direction' => 'reverse',
			'insertion_position'  => 1, // 1 from end (last position)
			'fallback_behavior'   => 'skip',
			'storage_conditions'  => array(),
		);

		$result = $this->inserter->insert( $content, $cta, 'true' );

		$this->assertStringContainsString( 'cta-highlights-wrapper', $result );

		// Should be near the end
		$cta_pos = strpos( $result, 'cta-highlights-wrapper' );
		$last_p_end = strrpos( $result, '</p>' );

		$this->assertGreaterThan( $last_p_end, $cta_pos );
	}

	/**
	 * @test
	 * Test reverse insertion beyond content length (skip behavior)
	 *
	 * WHY: Handle insufficient content gracefully
	 * PRIORITY: MEDIUM (edge case)
	 */
	public function it_skips_when_insufficient_content_reverse() {
		$content = $this->create_html_content( 3 );

		$cta = array(
			'id'                  => 1,
			'content'             => 'Test CTA',
			'insertion_direction' => 'reverse',
			'insertion_position'  => 10, // Beyond content
			'fallback_behavior'   => 'skip',
			'storage_conditions'  => array(),
		);

		$result = $this->inserter->insert( $content, $cta, 'true' );

		// CTA should NOT be inserted
		$this->assertStringNotContainsString( 'cta-highlights-wrapper', $result );
	}

	/**
	 * @test
	 * Test reverse insertion beyond content length (end behavior)
	 *
	 * WHY: Fallback to end when insufficient content
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_inserts_at_end_when_insufficient_content_reverse() {
		$content = $this->create_html_content( 3 );

		$cta = array(
			'id'                  => 1,
			'content'             => 'Test CTA',
			'insertion_direction' => 'reverse',
			'insertion_position'  => 10, // Beyond content
			'fallback_behavior'   => 'end', // Insert at end
			'storage_conditions'  => array(),
		);

		$result = $this->inserter->insert( $content, $cta, 'true' );

		// CTA should be inserted at end
		$this->assertStringContainsString( 'cta-highlights-wrapper', $result );
	}

	// =============================================================
	// HTML PARSING TESTS
	// =============================================================

	/**
	 * @test
	 * Test parsing of simple HTML content
	 *
	 * WHY: Must correctly parse content elements
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_parses_simple_html_content() {
		$content = '<p>Paragraph 1</p><p>Paragraph 2</p><p>Paragraph 3</p>';

		$cta = array(
			'id'                  => 1,
			'content'             => 'Test',
			'insertion_direction' => 'forward',
			'insertion_position'  => 2,
			'fallback_behavior'   => 'skip',
			'storage_conditions'  => array(),
		);

		$result = $this->inserter->insert( $content, $cta, 'true' );

		// Should successfully insert
		$this->assertStringContainsString( 'cta-highlights-wrapper', $result );
	}

	/**
	 * @test
	 * Test parsing of mixed HTML elements
	 *
	 * WHY: Content can have different element types
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_parses_mixed_html_elements() {
		$content = '<p>Paragraph</p><h2>Heading</h2><ul><li>List</li></ul><p>Another</p>';

		$cta = array(
			'id'                  => 1,
			'content'             => 'Test',
			'insertion_direction' => 'forward',
			'insertion_position'  => 3,
			'fallback_behavior'   => 'skip',
			'storage_conditions'  => array(),
		);

		$result = $this->inserter->insert( $content, $cta, 'true' );

		$this->assertStringContainsString( 'cta-highlights-wrapper', $result );
	}

	/**
	 * @test
	 * Test handling of empty content
	 *
	 * WHY: Edge case that should be handled gracefully
	 * PRIORITY: LOW (edge case)
	 */
	public function it_handles_empty_content() {
		$content = '';

		$cta = array(
			'id'                  => 1,
			'content'             => 'Test',
			'insertion_direction' => 'forward',
			'insertion_position'  => 1,
			'fallback_behavior'   => 'skip',
			'storage_conditions'  => array(),
		);

		$result = $this->inserter->insert( $content, $cta, 'true' );

		// Should return empty content (no crash)
		$this->assertEquals( $content, $result );
	}

	/**
	 * @test
	 * Test handling of malformed HTML
	 *
	 * WHY: Real-world content may have malformed HTML
	 * PRIORITY: LOW (robustness)
	 */
	public function it_handles_malformed_html_gracefully() {
		$content = '<p>Paragraph<p>Missing closing tag<p>Another';

		$cta = array(
			'id'                  => 1,
			'content'             => 'Test',
			'insertion_direction' => 'forward',
			'insertion_position'  => 1,
			'fallback_behavior'   => 'skip',
			'storage_conditions'  => array(),
		);

		// Should not crash
		$result = $this->inserter->insert( $content, $cta, 'true' );

		// Result should be a string
		$this->assertIsString( $result );
	}

	// =============================================================
	// CTA HTML GENERATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test CTA wrapper HTML generation
	 *
	 * WHY: Correct HTML structure is required
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_generates_correct_wrapper_html() {
		$content = $this->create_html_content( 3 );

		$cta = array(
			'id'                  => 123,
			'content'             => '<p>CTA Content</p>',
			'insertion_direction' => 'forward',
			'insertion_position'  => 1,
			'fallback_behavior'   => 'skip',
			'storage_conditions'  => array(),
		);

		$result = $this->inserter->insert( $content, $cta, 'true' );

		// Check wrapper classes
		$this->assertStringContainsString( 'cta-highlights-wrapper', $result );
		$this->assertStringContainsString( 'cta-highlights-auto-inserted', $result );

		// Check data attributes
		$this->assertStringContainsString( 'data-auto-insert="true"', $result );
		$this->assertStringContainsString( 'data-cta-id="123"', $result );
	}

	/**
	 * @test
	 * Test that storage conditions add hidden style
	 *
	 * WHY: CTAs with storage conditions should be hidden initially
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_hides_cta_when_storage_conditions_exist() {
		$content = $this->create_html_content( 3 );

		$cta = array(
			'id'                  => 1,
			'content'             => 'Test',
			'insertion_direction' => 'forward',
			'insertion_position'  => 1,
			'fallback_behavior'   => 'skip',
			'storage_conditions'  => array(
				array(
					'key'      => 'test',
					'operator' => '=',
					'value'    => 'value',
					'datatype' => 'string',
				),
			),
		);

		$storage_js = 'this.storageManager.get("test") === "value"';
		$result = $this->inserter->insert( $content, $cta, $storage_js );

		// Should have display:none style
		$this->assertStringContainsString( 'style="display:none;"', $result );
	}

	/**
	 * @test
	 * Test that CTAs without storage conditions are visible
	 *
	 * WHY: CTAs without conditions should show immediately
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_shows_cta_when_no_storage_conditions() {
		$content = $this->create_html_content( 3 );

		$cta = array(
			'id'                  => 1,
			'content'             => 'Test',
			'insertion_direction' => 'forward',
			'insertion_position'  => 1,
			'fallback_behavior'   => 'skip',
			'storage_conditions'  => array(), // Empty
		);

		$result = $this->inserter->insert( $content, $cta, 'true' );

		// Should NOT have display:none style
		$this->assertStringNotContainsString( 'style="display:none;"', $result );
	}

	/**
	 * @test
	 * Test that CTA content is sanitized
	 *
	 * WHY: Content should go through wp_kses_post
	 * PRIORITY: MEDIUM (security)
	 */
	public function it_sanitizes_cta_content() {
		$content = $this->create_html_content( 3 );

		$cta = array(
			'id'                  => 1,
			'content'             => '<p>Safe content</p><script>alert("XSS")</script>',
			'insertion_direction' => 'forward',
			'insertion_position'  => 1,
			'fallback_behavior'   => 'skip',
			'storage_conditions'  => array(),
		);

		$result = $this->inserter->insert( $content, $cta, 'true' );

		// Script tag should be removed
		$this->assertStringNotContainsString( '<script>', $result );
		$this->assertStringContainsString( 'Safe content', $result );
	}

	/**
	 * @test
	 * Test that shortcodes in CTA content are processed
	 *
	 * WHY: CTA content can contain shortcodes
	 * PRIORITY: LOW (functionality)
	 */
	public function it_processes_shortcodes_in_cta_content() {
		$content = $this->create_html_content( 3 );

		// Add a simple shortcode for testing
		add_shortcode(
			'test_shortcode',
			function() {
				return 'Shortcode Output';
			}
		);

		$cta = array(
			'id'                  => 1,
			'content'             => 'Before [test_shortcode] After',
			'insertion_direction' => 'forward',
			'insertion_position'  => 1,
			'fallback_behavior'   => 'skip',
			'storage_conditions'  => array(),
		);

		$result = $this->inserter->insert( $content, $cta, 'true' );

		// Shortcode should be expanded
		$this->assertStringContainsString( 'Shortcode Output', $result );
		$this->assertStringNotContainsString( '[test_shortcode]', $result );

		// Clean up
		remove_shortcode( 'test_shortcode' );
	}

	// =============================================================
	// HELPER METHODS
	// =============================================================

	/**
	 * Create HTML content with N paragraphs
	 *
	 * @param int $count Number of paragraphs.
	 * @return string HTML content.
	 */
	private function create_html_content( $count ) {
		$html = '';
		for ( $i = 1; $i <= $count; $i++ ) {
			$html .= "<p>Paragraph {$i}</p>\n";
		}
		return $html;
	}
}
