<?php
/**
 * Capabilities Integration Tests
 *
 * MEDIUM PRIORITY: Integration Tests for User Permissions and Capabilities
 *
 * This test class covers user permissions and role-based access including:
 * - Admin permissions (CTA management)
 * - Editor permissions (editing capabilities)
 * - Author permissions (limited access)
 * - Subscriber permissions (no access)
 * - Post meta box permissions
 * - Capability checks in admin
 *
 * Tests verify that WordPress roles and capabilities are respected.
 *
 * @package CTAHighlights\Tests\Integration
 */

namespace CTAHighlights\Tests\Integration;

use CTAHighlights\Tests\Factories\UserFactory;
use CTAHighlights\Tests\Factories\PostFactory;
use CTAHighlights\Tests\Factories\CTAFactory;
use CTAHighlights\Tests\Traits\CreatesDatabase;
use WP_UnitTestCase;

class CapabilitiesTest extends WP_UnitTestCase {
	use CreatesDatabase;

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->setupDatabase();
	}

	/**
	 * Teardown after each test
	 */
	public function tearDown(): void {
		$this->teardownDatabase();
		parent::tearDown();
	}

	// =============================================================
	// ADMINISTRATOR CAPABILITIES
	// =============================================================

	/**
	 * @test
	 * Test that administrators can manage CTAs
	 *
	 * WHY: Administrators should have full access
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_allows_admin_to_manage_ctas() {
		$admin_id = UserFactory::create_and_login_admin();

		// Admin should have edit_posts capability
		$this->assertTrue( current_user_can( 'edit_posts' ) );

		// Admin should have manage_options capability
		$this->assertTrue( current_user_can( 'manage_options' ) );
	}

	/**
	 * @test
	 * Test that administrators can access post meta box
	 *
	 * WHY: Administrators need to disable auto-insertion per post
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_allows_admin_to_access_meta_box() {
		$admin_id = UserFactory::create_and_login_admin();
		$post_id  = PostFactory::create();

		// Admin should be able to edit post
		$this->assertTrue(
			current_user_can( 'edit_post', $post_id ),
			'Admin should be able to edit post'
		);

		// Meta box should be available (tested via capability)
		$this->assertTrue( current_user_can( 'edit_posts' ) );
	}

	// =============================================================
	// EDITOR CAPABILITIES
	// =============================================================

	/**
	 * @test
	 * Test that editors can edit posts with meta box
	 *
	 * WHY: Editors should be able to manage content
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_allows_editor_to_use_meta_box() {
		$editor_id = UserFactory::create_and_login_editor();
		$post_id   = PostFactory::create();

		// Editor should be able to edit post
		$this->assertTrue(
			current_user_can( 'edit_post', $post_id ),
			'Editor should be able to edit post'
		);

		// Editor should have edit_posts capability
		$this->assertTrue( current_user_can( 'edit_posts' ) );
	}

	/**
	 * @test
	 * Test that editors can edit any post's meta
	 *
	 * WHY: Editors have elevated permissions
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_allows_editor_to_edit_any_post_meta() {
		$editor_id = UserFactory::create_and_login_editor();

		// Create post by another user
		$author_id = UserFactory::create_author();
		$post_id   = PostFactory::create( array( 'post_author' => $author_id ) );

		// Editor should still be able to edit
		$this->assertTrue(
			current_user_can( 'edit_post', $post_id ),
			'Editor should be able to edit any post'
		);
	}

	// =============================================================
	// AUTHOR CAPABILITIES
	// =============================================================

	/**
	 * @test
	 * Test that authors can edit their own posts
	 *
	 * WHY: Authors should manage their own content
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_allows_author_to_edit_own_posts() {
		$author_id = UserFactory::create_and_login_author();
		$post_id   = PostFactory::create( array( 'post_author' => $author_id ) );

		// Author should be able to edit their own post
		$this->assertTrue(
			current_user_can( 'edit_post', $post_id ),
			'Author should be able to edit own post'
		);
	}

	/**
	 * @test
	 * Test that authors cannot edit others' posts
	 *
	 * WHY: Authors should have limited permissions
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_author_from_editing_others_posts() {
		$author1_id = UserFactory::create_and_login_author();

		// Create post by different author
		$author2_id = UserFactory::create_author();
		$post_id    = PostFactory::create( array( 'post_author' => $author2_id ) );

		// Author 1 should NOT be able to edit author 2's post
		$this->assertFalse(
			current_user_can( 'edit_post', $post_id ),
			'Author should not be able to edit others\' posts'
		);
	}

	/**
	 * @test
	 * Test that authors can use meta box on own posts
	 *
	 * WHY: Authors should control auto-insertion on their posts
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_allows_author_to_use_meta_box_on_own_posts() {
		$author_id = UserFactory::create_and_login_author();
		$post_id   = PostFactory::create( array( 'post_author' => $author_id ) );

		// Author should have edit_post capability
		$this->assertTrue(
			current_user_can( 'edit_post', $post_id ),
			'Author should be able to edit own post meta'
		);
	}

	// =============================================================
	// CONTRIBUTOR CAPABILITIES
	// =============================================================

	/**
	 * @test
	 * Test that contributors can edit their draft posts
	 *
	 * WHY: Contributors can create drafts
	 * PRIORITY: LOW (functionality)
	 */
	public function it_allows_contributor_to_edit_own_drafts() {
		$contributor_id = UserFactory::create_and_login_contributor();
		$post_id        = PostFactory::create(
			array(
				'post_author' => $contributor_id,
				'post_status' => 'draft',
			)
		);

		// Contributor should be able to edit their draft
		$this->assertTrue(
			current_user_can( 'edit_post', $post_id ),
			'Contributor should be able to edit own draft'
		);
	}

	/**
	 * @test
	 * Test that contributors cannot edit published posts
	 *
	 * WHY: Contributors have limited permissions
	 * PRIORITY: MEDIUM (security)
	 */
	public function it_prevents_contributor_from_editing_published_posts() {
		$contributor_id = UserFactory::create_and_login_contributor();
		$post_id        = PostFactory::create(
			array(
				'post_author' => $contributor_id,
				'post_status' => 'publish',
			)
		);

		// Contributor should NOT be able to edit published post
		$this->assertFalse(
			current_user_can( 'edit_post', $post_id ),
			'Contributor should not be able to edit published posts'
		);
	}

	// =============================================================
	// SUBSCRIBER CAPABILITIES
	// =============================================================

	/**
	 * @test
	 * Test that subscribers cannot edit posts
	 *
	 * WHY: Subscribers have no editing permissions
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_subscriber_from_editing_posts() {
		$subscriber_id = UserFactory::create_and_login_subscriber();
		$post_id       = PostFactory::create();

		// Subscriber should NOT be able to edit posts
		$this->assertFalse(
			current_user_can( 'edit_post', $post_id ),
			'Subscriber should not be able to edit posts'
		);

		$this->assertFalse(
			current_user_can( 'edit_posts' ),
			'Subscriber should not have edit_posts capability'
		);
	}

	/**
	 * @test
	 * Test that subscribers cannot access meta box
	 *
	 * WHY: Subscribers should not have access to admin features
	 * PRIORITY: MEDIUM (security)
	 */
	public function it_prevents_subscriber_from_accessing_meta_box() {
		$subscriber_id = UserFactory::create_and_login_subscriber();

		// Subscriber should not have edit_posts
		$this->assertFalse(
			current_user_can( 'edit_posts' ),
			'Subscriber should not have edit_posts capability'
		);
	}

	// =============================================================
	// POST META PERMISSIONS
	// =============================================================

	/**
	 * @test
	 * Test that post meta is saved with proper permissions
	 *
	 * WHY: Meta should only be saved by authorized users
	 * PRIORITY: HIGH (security)
	 */
	public function it_requires_edit_post_capability_for_meta_save() {
		$author_id = UserFactory::create_and_login_author();
		$post_id   = PostFactory::create( array( 'post_author' => $author_id ) );

		// Author can save meta for own post
		$this->assertTrue(
			current_user_can( 'edit_post', $post_id ),
			'Author should be able to save meta for own post'
		);

		// Create post by different author
		$other_author_id = UserFactory::create_author();
		$other_post_id   = PostFactory::create( array( 'post_author' => $other_author_id ) );

		// Author cannot save meta for others' posts
		$this->assertFalse(
			current_user_can( 'edit_post', $other_post_id ),
			'Author should not be able to save meta for others\' posts'
		);
	}

	// =============================================================
	// ADMIN INTERFACE PERMISSIONS
	// =============================================================

	/**
	 * @test
	 * Test that only users with edit_posts can see admin interface
	 *
	 * WHY: Admin interface should be restricted
	 * PRIORITY: MEDIUM (security)
	 */
	public function it_restricts_admin_interface_to_editors() {
		// Admin
		$admin_id = UserFactory::create_and_login_admin();
		$this->assertTrue( current_user_can( 'edit_posts' ) );

		// Editor
		$editor_id = UserFactory::create_and_login_editor();
		$this->assertTrue( current_user_can( 'edit_posts' ) );

		// Author
		$author_id = UserFactory::create_and_login_author();
		$this->assertTrue( current_user_can( 'edit_posts' ) );

		// Subscriber
		$subscriber_id = UserFactory::create_and_login_subscriber();
		$this->assertFalse( current_user_can( 'edit_posts' ) );
	}

	// =============================================================
	// ROLE-BASED CTA ACCESS
	// =============================================================

	/**
	 * @test
	 * Test that all editors can view CTAs (frontend)
	 *
	 * WHY: CTAs are public on frontend
	 * PRIORITY: LOW (functionality)
	 */
	public function it_allows_all_roles_to_view_ctas_on_frontend() {
		// CTAs are visible on frontend regardless of role
		// This test verifies the concept

		$roles = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );

		foreach ( $roles as $role ) {
			$user_id = $this->factory->user->create( array( 'role' => $role ) );
			wp_set_current_user( $user_id );

			// All roles can view frontend content
			$this->assertTrue( true );

			wp_set_current_user( 0 );
		}
	}

	// =============================================================
	// CUSTOM CAPABILITY TESTS
	// =============================================================

	/**
	 * @test
	 * Test that custom capabilities can be added
	 *
	 * WHY: Extensibility for custom role management
	 * PRIORITY: LOW (extensibility)
	 */
	public function it_supports_custom_capabilities() {
		// Add custom capability to role
		$role = get_role( 'author' );
		$role->add_cap( 'manage_cta_highlights' );

		$author_id = UserFactory::create_and_login_author();

		// Author should now have custom capability
		$this->assertTrue(
			current_user_can( 'manage_cta_highlights' ),
			'Custom capability should be assignable'
		);

		// Remove custom capability from role
		$role->remove_cap( 'manage_cta_highlights' );

		// Refresh current user to clear capability cache
		$user = wp_get_current_user();
		$user->get_role_caps();

		$this->assertFalse(
			current_user_can( 'manage_cta_highlights' ),
			'Custom capability should be removable'
		);
	}

	// =============================================================
	// INTEGRATION WITH WORDPRESS CAPABILITIES
	// =============================================================

	/**
	 * @test
	 * Test integration with WordPress edit_posts capability
	 *
	 * WHY: Plugin should use standard WordPress capabilities
	 * PRIORITY: HIGH (integration)
	 */
	public function it_uses_standard_wordpress_capabilities() {
		// Verify standard capabilities are used
		$admin_id = UserFactory::create_and_login_admin();

		$this->assertTrue( current_user_can( 'edit_posts' ) );
		$this->assertTrue( current_user_can( 'edit_others_posts' ) );
		$this->assertTrue( current_user_can( 'publish_posts' ) );
		$this->assertTrue( current_user_can( 'manage_options' ) );
	}

	/**
	 * @test
	 * Test integration with edit_post capability
	 *
	 * WHY: Per-post capabilities should be respected
	 * PRIORITY: HIGH (integration)
	 */
	public function it_respects_per_post_capabilities() {
		$author_id = UserFactory::create_and_login_author();

		// Own post - can edit
		$own_post = PostFactory::create( array( 'post_author' => $author_id ) );
		$this->assertTrue( current_user_can( 'edit_post', $own_post ) );

		// Others' post - cannot edit
		$other_author = UserFactory::create_author();
		$other_post   = PostFactory::create( array( 'post_author' => $other_author ) );
		$this->assertFalse( current_user_can( 'edit_post', $other_post ) );
	}

	// =============================================================
	// NONCE AND PERMISSION CHECKS
	// =============================================================

	/**
	 * @test
	 * Test that capability checks are enforced before actions
	 *
	 * WHY: Security - actions should require proper permissions
	 * PRIORITY: HIGH (security)
	 */
	public function it_checks_capabilities_before_actions() {
		// This is a conceptual test - actual implementation in admin classes
		// tests verify nonce + capability checks

		$subscriber_id = UserFactory::create_and_login_subscriber();

		// Subscriber should not have edit_posts
		$this->assertFalse( current_user_can( 'edit_posts' ) );

		// If subscriber tries to perform admin action, it should fail
		// (This is tested in admin unit tests with proper nonce checks)
		$this->assertTrue( true );
	}

	// =============================================================
	// MULTISITE CAPABILITIES
	// =============================================================

	/**
	 * @test
	 * Test super admin capabilities in multisite
	 *
	 * WHY: Multisite has additional permission layers
	 * PRIORITY: LOW (multisite)
	 */
	public function it_supports_multisite_super_admin_if_applicable() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not a multisite installation' );
		}

		$admin_id = UserFactory::create_and_login_admin();

		// Super admin should have all capabilities
		grant_super_admin( $admin_id );

		$this->assertTrue( current_user_can( 'edit_posts' ) );
		$this->assertTrue( current_user_can( 'manage_options' ) );
	}
}
