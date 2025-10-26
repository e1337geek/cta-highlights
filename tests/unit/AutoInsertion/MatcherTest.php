<?php
/**
 * Matcher Tests
 *
 * MEDIUM PRIORITY: Business Logic Tests for CTA Conditional Matching
 *
 * This test class covers the AutoInsertion\Matcher class which handles
 * conditional logic for determining when/where CTAs should display. This is
 * MEDIUM PRIORITY because:
 * - Core business logic functionality
 * - Determines CTA targeting accuracy
 * - Generates client-side JavaScript for storage conditions
 * - Not security-critical but impacts user experience
 *
 * @package CTAHighlights\Tests\Unit\AutoInsertion
 */

namespace CTAHighlights\Tests\Unit\AutoInsertion;

use CTAHighlights\AutoInsertion\Matcher;
use CTAHighlights\Tests\Factories\PostFactory;
use CTAHighlights\Tests\Factories\CTAFactory;
use WP_UnitTestCase;

class MatcherTest extends WP_UnitTestCase {

	/**
	 * Matcher instance
	 *
	 * @var Matcher
	 */
	private $matcher;

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->matcher = new Matcher();
	}

	// =============================================================
	// POST TYPE MATCHING TESTS
	// =============================================================

	/**
	 * @test
	 * Test that CTA displays on matching post type
	 *
	 * WHY: Core functionality for post type targeting
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_matches_cta_to_correct_post_type() {
		$post = PostFactory::create( array( 'post_type' => 'post' ) );
		$cta  = CTAFactory::create(
			array(
				'post_types' => array( 'post' ),
			)
		);

		$cta_data = CTAFactory::get_cta_data( $cta );
		$should_display = $this->matcher->should_display( $cta_data, get_post( $post ) );

		$this->assertTrue( $should_display, 'CTA should display on matching post type' );
	}

	/**
	 * @test
	 * Test that CTA doesn't display on non-matching post type
	 *
	 * WHY: Ensures targeting accuracy
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_doesnt_match_wrong_post_type() {
		$post = PostFactory::create( array( 'post_type' => 'page' ) );
		$cta  = CTAFactory::create(
			array(
				'post_types' => array( 'post' ),
			)
		);

		$cta_data = CTAFactory::get_cta_data( $cta );
		$should_display = $this->matcher->should_display( $cta_data, get_post( $post ) );

		$this->assertFalse( $should_display, 'CTA should not display on wrong post type' );
	}

	/**
	 * @test
	 * Test that CTA displays on all post types when none specified
	 *
	 * WHY: Empty post_types array should mean "all post types"
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_matches_all_post_types_when_empty() {
		$post = PostFactory::create( array( 'post_type' => 'page' ) );
		$cta  = CTAFactory::create(
			array(
				'post_types' => array(),
			)
		);

		$cta_data = CTAFactory::get_cta_data( $cta );
		$should_display = $this->matcher->should_display( $cta_data, get_post( $post ) );

		$this->assertTrue( $should_display, 'CTA should display on all post types when none specified' );
	}

	/**
	 * @test
	 * Test that CTA matches multiple post types
	 *
	 * WHY: CTAs should be able to target multiple post types
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_matches_multiple_post_types() {
		$post1 = PostFactory::create( array( 'post_type' => 'post' ) );
		$post2 = PostFactory::create( array( 'post_type' => 'page' ) );
		$post3 = PostFactory::create( array( 'post_type' => 'custom' ) );

		$cta = CTAFactory::create(
			array(
				'post_types' => array( 'post', 'page' ),
			)
		);

		$cta_data = CTAFactory::get_cta_data( $cta );

		$this->assertTrue( $this->matcher->should_display( $cta_data, get_post( $post1 ) ) );
		$this->assertTrue( $this->matcher->should_display( $cta_data, get_post( $post2 ) ) );
		$this->assertFalse( $this->matcher->should_display( $cta_data, get_post( $post3 ) ) );
	}

	// =============================================================
	// CATEGORY MATCHING TESTS (INCLUDE MODE)
	// =============================================================

	/**
	 * @test
	 * Test that CTA displays on posts in included categories
	 *
	 * WHY: Core functionality for category targeting (include mode)
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_matches_included_categories() {
		$cat_id = $this->factory->category->create( array( 'name' => 'News' ) );
		$post   = PostFactory::create_in_category( $cat_id );

		$cta = CTAFactory::create(
			array(
				'category_ids'   => array( $cat_id ),
				'category_mode'  => 'include',
			)
		);

		$cta_data = CTAFactory::get_cta_data( $cta );
		$should_display = $this->matcher->should_display( $cta_data, get_post( $post ) );

		$this->assertTrue( $should_display, 'CTA should display on post in included category' );
	}

	/**
	 * @test
	 * Test that CTA doesn't display on posts not in included categories
	 *
	 * WHY: Ensures category targeting works (include mode)
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_doesnt_match_posts_not_in_included_categories() {
		$cat1_id = $this->factory->category->create( array( 'name' => 'News' ) );
		$cat2_id = $this->factory->category->create( array( 'name' => 'Blog' ) );
		$post    = PostFactory::create_in_category( $cat2_id );

		$cta = CTAFactory::create(
			array(
				'category_ids'   => array( $cat1_id ),
				'category_mode'  => 'include',
			)
		);

		$cta_data = CTAFactory::get_cta_data( $cta );
		$should_display = $this->matcher->should_display( $cta_data, get_post( $post ) );

		$this->assertFalse( $should_display, 'CTA should not display on post not in included category' );
	}

	/**
	 * @test
	 * Test that CTA doesn't display on uncategorized posts (include mode)
	 *
	 * WHY: Posts with no categories shouldn't match include mode
	 * PRIORITY: MEDIUM (edge case)
	 */
	public function it_doesnt_match_uncategorized_posts_in_include_mode() {
		$cat_id = $this->factory->category->create( array( 'name' => 'News' ) );
		$post   = PostFactory::create(); // No category

		$cta = CTAFactory::create(
			array(
				'category_ids'   => array( $cat_id ),
				'category_mode'  => 'include',
			)
		);

		$cta_data = CTAFactory::get_cta_data( $cta );
		$should_display = $this->matcher->should_display( $cta_data, get_post( $post ) );

		$this->assertFalse( $should_display, 'CTA should not display on uncategorized post in include mode' );
	}

	// =============================================================
	// CATEGORY MATCHING TESTS (EXCLUDE MODE)
	// =============================================================

	/**
	 * @test
	 * Test that CTA doesn't display on posts in excluded categories
	 *
	 * WHY: Core functionality for category targeting (exclude mode)
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_doesnt_match_excluded_categories() {
		$cat_id = $this->factory->category->create( array( 'name' => 'Excluded' ) );
		$post   = PostFactory::create_in_category( $cat_id );

		$cta = CTAFactory::create(
			array(
				'category_ids'   => array( $cat_id ),
				'category_mode'  => 'exclude',
			)
		);

		$cta_data = CTAFactory::get_cta_data( $cta );
		$should_display = $this->matcher->should_display( $cta_data, get_post( $post ) );

		$this->assertFalse( $should_display, 'CTA should not display on post in excluded category' );
	}

	/**
	 * @test
	 * Test that CTA displays on posts not in excluded categories
	 *
	 * WHY: Ensures category targeting works (exclude mode)
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_matches_posts_not_in_excluded_categories() {
		$cat1_id = $this->factory->category->create( array( 'name' => 'Excluded' ) );
		$cat2_id = $this->factory->category->create( array( 'name' => 'News' ) );
		$post    = PostFactory::create_in_category( $cat2_id );

		$cta = CTAFactory::create(
			array(
				'category_ids'   => array( $cat1_id ),
				'category_mode'  => 'exclude',
			)
		);

		$cta_data = CTAFactory::get_cta_data( $cta );
		$should_display = $this->matcher->should_display( $cta_data, get_post( $post ) );

		$this->assertTrue( $should_display, 'CTA should display on post not in excluded category' );
	}

	/**
	 * @test
	 * Test that CTA displays on uncategorized posts (exclude mode)
	 *
	 * WHY: Posts with no categories should match exclude mode
	 * PRIORITY: MEDIUM (edge case)
	 */
	public function it_matches_uncategorized_posts_in_exclude_mode() {
		$cat_id = $this->factory->category->create( array( 'name' => 'Excluded' ) );
		$post   = PostFactory::create(); // No category

		$cta = CTAFactory::create(
			array(
				'category_ids'   => array( $cat_id ),
				'category_mode'  => 'exclude',
			)
		);

		$cta_data = CTAFactory::get_cta_data( $cta );
		$should_display = $this->matcher->should_display( $cta_data, get_post( $post ) );

		$this->assertTrue( $should_display, 'CTA should display on uncategorized post in exclude mode' );
	}

	/**
	 * @test
	 * Test that CTA matches all categories when none specified
	 *
	 * WHY: Empty category array should mean "all categories"
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_matches_all_categories_when_empty() {
		$cat_id = $this->factory->category->create( array( 'name' => 'News' ) );
		$post   = PostFactory::create_in_category( $cat_id );

		$cta = CTAFactory::create(
			array(
				'category_ids'   => array(),
				'category_mode'  => 'include',
			)
		);

		$cta_data = CTAFactory::get_cta_data( $cta );
		$should_display = $this->matcher->should_display( $cta_data, get_post( $post ) );

		$this->assertTrue( $should_display, 'CTA should display on all categories when none specified' );
	}

	// =============================================================
	// META BOX DISABLE TESTS
	// =============================================================

	/**
	 * @test
	 * Test that CTA doesn't display when disabled via meta box
	 *
	 * WHY: Post meta box should be able to disable auto-insertion
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_respects_meta_box_disable_flag() {
		$post = PostFactory::create();
		update_post_meta( $post, '_cta_highlights_disable_auto_insert', '1' );

		$cta = CTAFactory::create(
			array(
				'post_types' => array( 'post' ),
			)
		);

		$cta_data = CTAFactory::get_cta_data( $cta );
		$should_display = $this->matcher->should_display( $cta_data, get_post( $post ) );

		$this->assertFalse( $should_display, 'CTA should not display when disabled via meta box' );
	}

	/**
	 * @test
	 * Test that CTA displays when meta box is not set
	 *
	 * WHY: Default behavior is to allow auto-insertion
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_displays_when_meta_box_not_set() {
		$post = PostFactory::create();
		// Don't set meta - default is enabled

		$cta = CTAFactory::create(
			array(
				'post_types' => array( 'post' ),
			)
		);

		$cta_data = CTAFactory::get_cta_data( $cta );
		$should_display = $this->matcher->should_display( $cta_data, get_post( $post ) );

		$this->assertTrue( $should_display, 'CTA should display when meta box not set' );
	}

	// =============================================================
	// STORAGE CONDITION JAVASCRIPT GENERATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test that empty conditions generate 'true'
	 *
	 * WHY: No conditions should always pass
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_generates_true_for_empty_conditions() {
		$js = $this->matcher->generate_storage_condition_js( array() );

		$this->assertEquals( 'true', $js );
	}

	/**
	 * @test
	 * Test numeric condition JavaScript generation
	 *
	 * WHY: Must generate valid JavaScript for numeric comparisons
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_generates_numeric_condition_js() {
		$conditions = array(
			array(
				'key'      => 'page_views',
				'operator' => '>',
				'value'    => 5,
				'datatype' => 'number',
			),
		);

		$js = $this->matcher->generate_storage_condition_js( $conditions );

		$this->assertStringContainsString( 'page_views', $js );
		$this->assertStringContainsString( '>', $js );
		$this->assertStringContainsString( '5', $js );
		$this->assertStringContainsString( 'Number(val)', $js );
	}

	/**
	 * @test
	 * Test boolean condition JavaScript generation
	 *
	 * WHY: Must generate valid JavaScript for boolean comparisons
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_generates_boolean_condition_js() {
		$conditions = array(
			array(
				'key'      => 'subscribed',
				'operator' => '=',
				'value'    => true,
				'datatype' => 'boolean',
			),
		);

		$js = $this->matcher->generate_storage_condition_js( $conditions );

		$this->assertStringContainsString( 'subscribed', $js );
		$this->assertStringContainsString( 'true', $js );
		$this->assertStringContainsString( 'Boolean', $js );
	}

	/**
	 * @test
	 * Test string condition JavaScript generation
	 *
	 * WHY: Must generate valid JavaScript for string comparisons
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_generates_string_condition_js() {
		$conditions = array(
			array(
				'key'      => 'user_role',
				'operator' => '=',
				'value'    => 'subscriber',
				'datatype' => 'string',
			),
		);

		$js = $this->matcher->generate_storage_condition_js( $conditions );

		$this->assertStringContainsString( 'user_role', $js );
		$this->assertStringContainsString( 'subscriber', $js );
		$this->assertStringContainsString( 'String(val)', $js );
	}

	/**
	 * @test
	 * Test regex condition JavaScript generation
	 *
	 * WHY: Must generate valid JavaScript for regex matching
	 * PRIORITY: LOW (advanced feature)
	 */
	public function it_generates_regex_condition_js() {
		$conditions = array(
			array(
				'key'      => 'email',
				'operator' => '=',
				'value'    => '.*@example\\.com$',
				'datatype' => 'regex',
			),
		);

		$js = $this->matcher->generate_storage_condition_js( $conditions );

		$this->assertStringContainsString( 'email', $js );
		$this->assertStringContainsString( 'RegExp', $js );
		$this->assertStringContainsString( 'test', $js );
	}

	/**
	 * @test
	 * Test date condition JavaScript generation
	 *
	 * WHY: Must generate valid JavaScript for date comparisons
	 * PRIORITY: LOW (advanced feature)
	 */
	public function it_generates_date_condition_js() {
		$conditions = array(
			array(
				'key'      => 'last_visit',
				'operator' => '>',
				'value'    => '2024-01-01',
				'datatype' => 'date',
			),
		);

		$js = $this->matcher->generate_storage_condition_js( $conditions );

		$this->assertStringContainsString( 'last_visit', $js );
		$this->assertStringContainsString( 'Date', $js );
		$this->assertStringContainsString( 'getTime', $js );
	}

	/**
	 * @test
	 * Test multiple conditions with AND logic
	 *
	 * WHY: Multiple conditions should be combined with AND
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_combines_multiple_conditions_with_and() {
		$conditions = array(
			array(
				'key'      => 'page_views',
				'operator' => '>',
				'value'    => 3,
				'datatype' => 'number',
			),
			array(
				'key'      => 'subscribed',
				'operator' => '=',
				'value'    => false,
				'datatype' => 'boolean',
			),
		);

		$js = $this->matcher->generate_storage_condition_js( $conditions );

		$this->assertStringContainsString( '&&', $js, 'Conditions should be combined with AND' );
		$this->assertStringContainsString( 'page_views', $js );
		$this->assertStringContainsString( 'subscribed', $js );
	}

	/**
	 * @test
	 * Test that invalid conditions are skipped
	 *
	 * WHY: Missing keys should be gracefully handled
	 * PRIORITY: LOW (error handling)
	 */
	public function it_skips_conditions_with_missing_key() {
		$conditions = array(
			array(
				// Missing 'key'
				'operator' => '=',
				'value'    => 'test',
				'datatype' => 'string',
			),
		);

		$js = $this->matcher->generate_storage_condition_js( $conditions );

		$this->assertEquals( 'true', $js, 'Should return true when all conditions are invalid' );
	}

	/**
	 * @test
	 * Test operator sanitization for numeric conditions
	 *
	 * WHY: Invalid operators should default to =
	 * PRIORITY: LOW (security/validation)
	 */
	public function it_sanitizes_invalid_numeric_operators() {
		$conditions = array(
			array(
				'key'      => 'count',
				'operator' => 'invalid',
				'value'    => 5,
				'datatype' => 'number',
			),
		);

		$js = $this->matcher->generate_storage_condition_js( $conditions );

		// Should default to ===
		$this->assertStringContainsString( '===', $js );
	}

	// =============================================================
	// INTEGRATION TESTS (COMBINED CONDITIONS)
	// =============================================================

	/**
	 * @test
	 * Test complete matching flow with all conditions
	 *
	 * WHY: End-to-end test of matcher functionality
	 * PRIORITY: HIGH (integration)
	 */
	public function it_matches_with_all_conditions_met() {
		$cat_id = $this->factory->category->create( array( 'name' => 'News' ) );
		$post   = PostFactory::create_in_category( $cat_id, array( 'post_type' => 'post' ) );

		$cta = CTAFactory::create(
			array(
				'post_types'     => array( 'post' ),
				'category_ids'   => array( $cat_id ),
				'category_mode'  => 'include',
			)
		);

		$cta_data = CTAFactory::get_cta_data( $cta );
		$should_display = $this->matcher->should_display( $cta_data, get_post( $post ) );

		$this->assertTrue( $should_display, 'CTA should display when all conditions are met' );
	}

	/**
	 * @test
	 * Test that any failing condition prevents display
	 *
	 * WHY: ALL conditions must pass (AND logic)
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_doesnt_match_when_any_condition_fails() {
		$cat_id = $this->factory->category->create( array( 'name' => 'News' ) );
		$post   = PostFactory::create_in_category( $cat_id, array( 'post_type' => 'page' ) );

		$cta = CTAFactory::create(
			array(
				'post_types'     => array( 'post' ), // Wrong!
				'category_ids'   => array( $cat_id ), // Correct
				'category_mode'  => 'include',
			)
		);

		$cta_data = CTAFactory::get_cta_data( $cta );
		$should_display = $this->matcher->should_display( $cta_data, get_post( $post ) );

		$this->assertFalse( $should_display, 'CTA should not display when any condition fails' );
	}
}
