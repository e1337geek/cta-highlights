<?php
/**
 * Post Meta Box Tests
 *
 * HIGH PRIORITY: Security Tests for Post Meta Box
 *
 * This test class covers the Admin\PostMetaBox class which handles
 * the meta box for disabling auto-insertion. This is HIGH PRIORITY because:
 * - It handles meta data operations (data integrity)
 * - It uses nonce verification (CSRF protection)
 * - It checks permissions (authorization)
 * - It prevents autosave operations (security)
 *
 * @package CTAHighlights\Tests\Unit\Admin
 */

namespace CTAHighlights\Tests\Unit\Admin;

use CTAHighlights\Admin\PostMetaBox;
use CTAHighlights\Tests\Factories\PostFactory;
use CTAHighlights\Tests\Factories\UserFactory;
use WP_UnitTestCase;

class PostMetaBoxTest extends WP_UnitTestCase {

	/**
	 * PostMetaBox instance
	 *
	 * @var PostMetaBox
	 */
	private $meta_box;

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->meta_box = new PostMetaBox();
	}

	/**
	 * Cleanup after each test
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clean up $_POST
		$_POST = array();
	}

	// =============================================================
	// NONCE VERIFICATION TESTS (CRITICAL SECURITY)
	// =============================================================

	/**
	 * @test
	 * Test that save requires valid nonce
	 *
	 * WHY: Prevents CSRF attacks on meta save operations
	 * PRIORITY: HIGH (security)
	 */
	public function it_requires_valid_nonce_to_save() {
		$admin_id = UserFactory::create_and_login_admin();
		$post_id = PostFactory::create();

		$_POST['cta_highlights_disable_auto_insert'] = '1';
		$_POST['cta_highlights_meta_box_nonce'] = 'invalid_nonce';

		$this->meta_box->save_meta_box( $post_id );

		// Meta should NOT be saved
		$value = get_post_meta( $post_id, PostMetaBox::META_KEY, true );
		$this->assertEmpty( $value );
	}

	/**
	 * @test
	 * Test that save works with valid nonce
	 *
	 * WHY: Core functionality must work correctly
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_saves_with_valid_nonce() {
		$admin_id = UserFactory::create_and_login_admin();
		$post_id = PostFactory::create();

		$_POST['cta_highlights_disable_auto_insert'] = '1';
		$_POST['cta_highlights_meta_box_nonce'] = wp_create_nonce( 'cta_highlights_meta_box' );

		$this->meta_box->save_meta_box( $post_id );

		// Meta should be saved
		$value = get_post_meta( $post_id, PostMetaBox::META_KEY, true );
		$this->assertEquals( '1', $value );
	}

	/**
	 * @test
	 * Test that save is skipped with no nonce
	 *
	 * WHY: Missing nonce should be treated as invalid
	 * PRIORITY: HIGH (security)
	 */
	public function it_skips_save_with_missing_nonce() {
		$admin_id = UserFactory::create_and_login_admin();
		$post_id = PostFactory::create();

		$_POST['cta_highlights_disable_auto_insert'] = '1';
		// No nonce provided

		$this->meta_box->save_meta_box( $post_id );

		// Meta should NOT be saved
		$value = get_post_meta( $post_id, PostMetaBox::META_KEY, true );
		$this->assertEmpty( $value );
	}

	// =============================================================
	// PERMISSION/CAPABILITY TESTS (CRITICAL SECURITY)
	// =============================================================

	/**
	 * @test
	 * Test that users must have edit_post capability
	 *
	 * WHY: Prevents unauthorized users from modifying posts
	 * PRIORITY: HIGH (security)
	 */
	public function it_requires_edit_post_capability() {
		$subscriber_id = UserFactory::create_subscriber();
		wp_set_current_user( $subscriber_id );

		$post_id = PostFactory::create();

		$_POST['cta_highlights_disable_auto_insert'] = '1';
		$_POST['cta_highlights_meta_box_nonce'] = wp_create_nonce( 'cta_highlights_meta_box' );

		$this->meta_box->save_meta_box( $post_id );

		// Meta should NOT be saved (subscriber can't edit posts)
		$value = get_post_meta( $post_id, PostMetaBox::META_KEY, true );
		$this->assertEmpty( $value );
	}

	/**
	 * @test
	 * Test that editors can save meta
	 *
	 * WHY: Editors should be able to disable CTAs on posts
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_allows_editor_to_save() {
		$editor_id = UserFactory::create_editor();
		wp_set_current_user( $editor_id );

		$post_id = PostFactory::create( array( 'post_author' => $editor_id ) );

		$_POST['cta_highlights_disable_auto_insert'] = '1';
		$_POST['cta_highlights_meta_box_nonce'] = wp_create_nonce( 'cta_highlights_meta_box' );

		$this->meta_box->save_meta_box( $post_id );

		// Meta should be saved
		$value = get_post_meta( $post_id, PostMetaBox::META_KEY, true );
		$this->assertEquals( '1', $value );
	}

	/**
	 * @test
	 * Test that authors can save meta on their own posts
	 *
	 * WHY: Authors should manage their own posts
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_allows_author_to_save_own_post() {
		$author_id = UserFactory::create_author();
		wp_set_current_user( $author_id );

		$post_id = PostFactory::create( array( 'post_author' => $author_id ) );

		$_POST['cta_highlights_disable_auto_insert'] = '1';
		$_POST['cta_highlights_meta_box_nonce'] = wp_create_nonce( 'cta_highlights_meta_box' );

		$this->meta_box->save_meta_box( $post_id );

		// Meta should be saved
		$value = get_post_meta( $post_id, PostMetaBox::META_KEY, true );
		$this->assertEquals( '1', $value );
	}

	/**
	 * @test
	 * Test that authors cannot save meta on others' posts
	 *
	 * WHY: Authors should not modify others' posts
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_author_from_editing_others_post() {
		$author_id = UserFactory::create_author();
		$other_author_id = UserFactory::create_author();
		wp_set_current_user( $author_id );

		// Create post by another author
		$post_id = PostFactory::create( array( 'post_author' => $other_author_id ) );

		$_POST['cta_highlights_disable_auto_insert'] = '1';
		$_POST['cta_highlights_meta_box_nonce'] = wp_create_nonce( 'cta_highlights_meta_box' );

		$this->meta_box->save_meta_box( $post_id );

		// Meta should NOT be saved
		$value = get_post_meta( $post_id, PostMetaBox::META_KEY, true );
		$this->assertEmpty( $value );
	}

	// =============================================================
	// AUTOSAVE PREVENTION TESTS (SECURITY)
	// =============================================================

	/**
	 * @test
	 * Test that autosave is blocked
	 *
	 * WHY: Prevents unintended saves during autosave
	 * PRIORITY: MEDIUM (data integrity)
	 */
	public function it_blocks_save_during_autosave() {
		$admin_id = UserFactory::create_and_login_admin();
		$post_id = PostFactory::create();

		$_POST['cta_highlights_disable_auto_insert'] = '1';
		$_POST['cta_highlights_meta_box_nonce'] = wp_create_nonce( 'cta_highlights_meta_box' );

		// Simulate autosave
		if ( ! defined( 'DOING_AUTOSAVE' ) ) {
			define( 'DOING_AUTOSAVE', true );
		}

		$this->meta_box->save_meta_box( $post_id );

		// Meta should NOT be saved during autosave
		$value = get_post_meta( $post_id, PostMetaBox::META_KEY, true );
		$this->assertEmpty( $value );
	}

	// =============================================================
	// META DATA OPERATIONS TESTS
	// =============================================================

	/**
	 * @test
	 * Test that checking the box saves meta
	 *
	 * WHY: Core functionality must work
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_saves_meta_when_checkbox_checked() {
		$admin_id = UserFactory::create_and_login_admin();
		$post_id = PostFactory::create();

		$_POST['cta_highlights_disable_auto_insert'] = '1';
		$_POST['cta_highlights_meta_box_nonce'] = wp_create_nonce( 'cta_highlights_meta_box' );

		$this->meta_box->save_meta_box( $post_id );

		$value = get_post_meta( $post_id, PostMetaBox::META_KEY, true );
		$this->assertEquals( '1', $value );
	}

	/**
	 * @test
	 * Test that unchecking the box deletes meta
	 *
	 * WHY: Core functionality must work
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_deletes_meta_when_checkbox_unchecked() {
		$admin_id = UserFactory::create_and_login_admin();
		$post_id = PostFactory::create();

		// First, set the meta
		update_post_meta( $post_id, PostMetaBox::META_KEY, '1' );

		// Now uncheck (by not including in $_POST)
		// $_POST['cta_highlights_disable_auto_insert'] NOT set
		$_POST['cta_highlights_meta_box_nonce'] = wp_create_nonce( 'cta_highlights_meta_box' );

		$this->meta_box->save_meta_box( $post_id );

		// Meta should be deleted
		$value = get_post_meta( $post_id, PostMetaBox::META_KEY, true );
		$this->assertEmpty( $value );
	}

	/**
	 * @test
	 * Test that only '1' value is saved
	 *
	 * WHY: Prevents unexpected values in database
	 * PRIORITY: MEDIUM (data integrity)
	 */
	public function it_only_saves_value_one() {
		$admin_id = UserFactory::create_and_login_admin();
		$post_id = PostFactory::create();

		// Try to save different value
		$_POST['cta_highlights_disable_auto_insert'] = 'malicious_value';
		$_POST['cta_highlights_meta_box_nonce'] = wp_create_nonce( 'cta_highlights_meta_box' );

		$this->meta_box->save_meta_box( $post_id );

		// Meta should NOT be saved (value is not '1')
		$value = get_post_meta( $post_id, PostMetaBox::META_KEY, true );
		$this->assertEmpty( $value );
	}

	/**
	 * @test
	 * Test that SQL injection attempts are harmless
	 *
	 * WHY: Defense in depth against injection
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_sql_injection() {
		$admin_id = UserFactory::create_and_login_admin();
		$post_id = PostFactory::create();

		// Attempt SQL injection
		$_POST['cta_highlights_disable_auto_insert'] = "1'; DROP TABLE wp_posts; --";
		$_POST['cta_highlights_meta_box_nonce'] = wp_create_nonce( 'cta_highlights_meta_box' );

		$this->meta_box->save_meta_box( $post_id );

		// Should not save malicious value
		$value = get_post_meta( $post_id, PostMetaBox::META_KEY, true );
		$this->assertNotEquals( "1'; DROP TABLE wp_posts; --", $value );

		// Tables should still exist
		global $wpdb;
		$tables = $wpdb->get_results( "SHOW TABLES LIKE '{$wpdb->posts}'" );
		$this->assertNotEmpty( $tables, 'Posts table should still exist' );
	}

	// =============================================================
	// META BOX REGISTRATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test that meta box is registered for public post types
	 *
	 * WHY: Meta box should be available where needed
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_registers_meta_box_for_public_post_types() {
		global $wp_meta_boxes;

		// Clear existing meta boxes
		$wp_meta_boxes = array();

		// Trigger meta box registration
		$this->meta_box->add_meta_box();

		// Check that meta box is registered for 'post'
		$this->assertArrayHasKey( 'post', $wp_meta_boxes );
		$this->assertArrayHasKey( 'side', $wp_meta_boxes['post'] );
		$this->assertArrayHasKey( 'default', $wp_meta_boxes['post']['side'] );
		$this->assertArrayHasKey( 'cta_highlights_auto_insert', $wp_meta_boxes['post']['side']['default'] );
	}

	// =============================================================
	// META BOX RENDERING TESTS
	// =============================================================

	/**
	 * @test
	 * Test that meta box renders with nonce field
	 *
	 * WHY: Nonce is required for security
	 * PRIORITY: HIGH (security)
	 */
	public function it_renders_with_nonce_field() {
		$admin_id = UserFactory::create_and_login_admin();
		$post_id = PostFactory::create();
		$post = get_post( $post_id );

		ob_start();
		$this->meta_box->render_meta_box( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'cta_highlights_meta_box_nonce', $output );
		$this->assertStringContainsString( 'nonce', $output );
	}

	/**
	 * @test
	 * Test that meta box shows checked state correctly
	 *
	 * WHY: UI should reflect saved state
	 * PRIORITY: MEDIUM (usability)
	 */
	public function it_shows_checked_state_when_meta_exists() {
		$admin_id = UserFactory::create_and_login_admin();
		$post_id = PostFactory::create();

		// Set meta
		update_post_meta( $post_id, PostMetaBox::META_KEY, '1' );

		$post = get_post( $post_id );

		ob_start();
		$this->meta_box->render_meta_box( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'checked', $output );
	}

	/**
	 * @test
	 * Test that meta box shows unchecked state correctly
	 *
	 * WHY: UI should reflect saved state
	 * PRIORITY: MEDIUM (usability)
	 */
	public function it_shows_unchecked_state_when_no_meta() {
		$admin_id = UserFactory::create_and_login_admin();
		$post_id = PostFactory::create();

		// No meta set
		$post = get_post( $post_id );

		ob_start();
		$this->meta_box->render_meta_box( $post );
		$output = ob_get_clean();

		// Should have checkbox but not checked
		$this->assertStringContainsString( 'type="checkbox"', $output );
		$this->assertStringNotContainsString( "checked='checked'", $output );
		$this->assertStringNotContainsString( 'checked="checked"', $output );
	}

	// =============================================================
	// INTEGRATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test complete workflow: render, check, save
	 *
	 * WHY: End-to-end functionality test
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_completes_full_workflow() {
		$admin_id = UserFactory::create_and_login_admin();
		$post_id = PostFactory::create();
		$post = get_post( $post_id );

		// 1. Render meta box
		ob_start();
		$this->meta_box->render_meta_box( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'cta_highlights_disable_auto_insert', $output );

		// 2. Check the box and save
		$_POST['cta_highlights_disable_auto_insert'] = '1';
		$_POST['cta_highlights_meta_box_nonce'] = wp_create_nonce( 'cta_highlights_meta_box' );

		$this->meta_box->save_meta_box( $post_id );

		// 3. Verify saved
		$value = get_post_meta( $post_id, PostMetaBox::META_KEY, true );
		$this->assertEquals( '1', $value );

		// 4. Render again - should be checked
		ob_start();
		$this->meta_box->render_meta_box( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'checked', $output );
	}
}
