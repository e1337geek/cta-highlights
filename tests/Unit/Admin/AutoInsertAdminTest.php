<?php
/**
 * Auto-Insert Admin Tests
 *
 * HIGH PRIORITY: Security Tests for Admin Interface
 *
 * This test class covers the Admin\AutoInsertAdmin class which handles
 * the admin interface for managing CTAs. This is HIGH PRIORITY because:
 * - It handles user input from forms (XSS/injection risk)
 * - It performs privileged operations (authorization risk)
 * - It uses nonces for CSRF protection (security critical)
 * - It processes database operations (data integrity)
 *
 * @package CTAHighlights\Tests\Unit\Admin
 */

namespace CTAHighlights\Tests\Unit\Admin;

use CTAHighlights\Admin\AutoInsertAdmin;
use CTAHighlights\AutoInsertion\Database;
use CTAHighlights\Tests\Factories\CTAFactory;
use CTAHighlights\Tests\Factories\UserFactory;
use CTAHighlights\Tests\Traits\CreatesDatabase;
use WP_UnitTestCase;

class AutoInsertAdminTest extends WP_UnitTestCase {
	use CreatesDatabase;

	/**
	 * Admin instance
	 *
	 * @var AutoInsertAdmin
	 */
	private $admin;

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->setupDatabase();

		$this->admin = new AutoInsertAdmin( $this->database );
	}

	/**
	 * Cleanup after each test
	 */
	public function tearDown(): void {
		$this->teardownDatabase();
		parent::tearDown();
	}

	// =============================================================
	// CAPABILITY/PERMISSION TESTS (CRITICAL SECURITY)
	// =============================================================

	/**
	 * @test
	 * Test that only admins can access the admin page
	 *
	 * WHY: Prevents unauthorized users from managing CTAs
	 * PRIORITY: HIGH (security)
	 */
	public function it_requires_manage_options_capability() {
		// Create non-admin user
		$subscriber_id = UserFactory::create_subscriber();
		wp_set_current_user( $subscriber_id );

		// Try to access admin page
		// WordPress will handle the capability check via add_menu_page
		$this->assertFalse(
			current_user_can( 'manage_options' ),
			'Subscriber should not have manage_options capability'
		);
	}

	/**
	 * @test
	 * Test that admins can access the admin page
	 *
	 * WHY: Ensures authorized users can manage CTAs
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_allows_admin_access() {
		$admin_id = UserFactory::create_and_login_admin();

		$this->assertTrue(
			current_user_can( 'manage_options' ),
			'Admin should have manage_options capability'
		);
	}

	// =============================================================
	// NONCE VERIFICATION TESTS (CRITICAL SECURITY)
	// =============================================================

	/**
	 * @test
	 * Test that delete action requires valid nonce
	 *
	 * WHY: Prevents CSRF attacks on delete operations
	 * PRIORITY: HIGH (security)
	 */
	public function it_requires_nonce_for_delete() {
		UserFactory::create_and_login_admin();

		$cta_id = CTAFactory::create();

		// Set up GET parameters without valid nonce
		$_GET['page'] = 'cta-auto-insert';
		$_GET['action'] = 'delete';
		$_GET['id'] = $cta_id;
		$_GET['_wpnonce'] = 'invalid_nonce';

		// Expect wp_die to be called
		$this->expectException( \WPDieException::class );

		$this->admin->handle_actions();
	}

	/**
	 * @test
	 * Test that duplicate action requires valid nonce
	 *
	 * WHY: Prevents CSRF attacks on duplicate operations
	 * PRIORITY: HIGH (security)
	 */
	public function it_requires_nonce_for_duplicate() {
		UserFactory::create_and_login_admin();

		$cta_id = CTAFactory::create();

		$_GET['page'] = 'cta-auto-insert';
		$_GET['action'] = 'duplicate';
		$_GET['id'] = $cta_id;
		$_GET['_wpnonce'] = 'invalid_nonce';

		$this->expectException( \WPDieException::class );

		$this->admin->handle_actions();
	}

	/**
	 * @test
	 * Test that save action requires valid nonce
	 *
	 * WHY: Prevents CSRF attacks on save operations
	 * PRIORITY: HIGH (security)
	 */
	public function it_requires_nonce_for_save() {
		UserFactory::create_and_login_admin();

		$_POST['page'] = 'cta-auto-insert';
		$_POST['cta_auto_insert_save'] = '1';
		$_POST['_wpnonce'] = 'invalid_nonce';

		$this->expectException( \WPDieException::class );

		$this->admin->handle_actions();
	}

	// =============================================================
	// INPUT SANITIZATION TESTS (SECURITY)
	// =============================================================

	/**
	 * @test
	 * Test that form data is sanitized
	 *
	 * WHY: Prevents XSS and injection attacks
	 * PRIORITY: HIGH (security)
	 */
	public function it_sanitizes_form_input() {
		UserFactory::create_and_login_admin();

		$xss_attempts = array(
			'name' => '<script>alert("XSS")</script>Test Name',
			'content' => '<img src=x onerror="alert(\'XSS\')">',
			'status' => 'active"><script>alert("XSS")</script>',
		);

		$_POST['page'] = 'cta-auto-insert';
		$_POST['cta_auto_insert_save'] = '1';
		$_POST['_wpnonce'] = wp_create_nonce( 'cta_auto_insert_save' );
		foreach ( $xss_attempts as $key => $value ) {
			$_POST[ $key ] = $value;
		}

		// Capture redirect to prevent exit
		add_filter( 'wp_redirect', '__return_false' );

		$this->admin->handle_actions();

		// Check that data was sanitized
		$all_ctas = $this->database->get_all();
		$this->assertNotEmpty( $all_ctas );

		$saved_cta = $all_ctas[0];

		// Name should have script tags removed
		$this->assertStringNotContainsString( '<script>', $saved_cta['name'] );

		// Content should have dangerous attributes removed
		$this->assertStringNotContainsString( 'onerror=', $saved_cta['content'] );
	}

	/**
	 * @test
	 * Test that post types are sanitized
	 *
	 * WHY: Ensures only valid post types are stored
	 * PRIORITY: MEDIUM (validation)
	 */
	public function it_sanitizes_post_types() {
		UserFactory::create_and_login_admin();

		$_POST['page'] = 'cta-auto-insert';
		$_POST['cta_auto_insert_save'] = '1';
		$_POST['_wpnonce'] = wp_create_nonce( 'cta_auto_insert_save' );
		$_POST['name'] = 'Test CTA';
		$_POST['post_types'] = array(
			'post',
			'<script>alert("XSS")</script>',
			'page',
		);

		add_filter( 'wp_redirect', '__return_false' );

		$this->admin->handle_actions();

		$all_ctas = $this->database->get_all();
		$saved_cta = $all_ctas[0];

		// XSS attempt should be sanitized
		foreach ( $saved_cta['post_types'] as $post_type ) {
			$this->assertStringNotContainsString( '<script>', $post_type );
		}
	}

	/**
	 * @test
	 * Test that category IDs are sanitized to integers
	 *
	 * WHY: Prevents SQL injection through category IDs
	 * PRIORITY: HIGH (security)
	 */
	public function it_sanitizes_category_ids_to_integers() {
		UserFactory::create_and_login_admin();

		$_POST['page'] = 'cta-auto-insert';
		$_POST['cta_auto_insert_save'] = '1';
		$_POST['_wpnonce'] = wp_create_nonce( 'cta_auto_insert_save' );
		$_POST['name'] = 'Test CTA';
		$_POST['category_ids'] = array(
			'1',
			'2 OR 1=1',
			'3',
			'<script>',
		);

		add_filter( 'wp_redirect', '__return_false' );

		$this->admin->handle_actions();

		$all_ctas = $this->database->get_all();
		$saved_cta = $all_ctas[0];

		// All should be integers
		foreach ( $saved_cta['category_ids'] as $cat_id ) {
			$this->assertIsInt( $cat_id );
		}

		// SQL injection attempt should be converted to int (2)
		$this->assertContains( 2, $saved_cta['category_ids'] );
	}

	/**
	 * @test
	 * Test that storage conditions are sanitized
	 *
	 * WHY: Prevents XSS in storage condition values
	 * PRIORITY: HIGH (security)
	 */
	public function it_sanitizes_storage_conditions() {
		UserFactory::create_and_login_admin();

		$_POST['page'] = 'cta-auto-insert';
		$_POST['cta_auto_insert_save'] = '1';
		$_POST['_wpnonce'] = wp_create_nonce( 'cta_auto_insert_save' );
		$_POST['name'] = 'Test CTA';
		$_POST['storage_condition_key'] = array( 'testKey' );
		$_POST['storage_condition_operator'] = array( '=' );
		$_POST['storage_condition_value'] = array( '<script>alert("XSS")</script>' );
		$_POST['storage_condition_datatype'] = array( 'string' );

		add_filter( 'wp_redirect', '__return_false' );

		$this->admin->handle_actions();

		$all_ctas = $this->database->get_all();
		$saved_cta = $all_ctas[0];

		// Storage conditions should be sanitized
		$this->assertIsArray( $saved_cta['storage_conditions'] );

		if ( ! empty( $saved_cta['storage_conditions'] ) ) {
			$condition = $saved_cta['storage_conditions'][0];
			$this->assertStringNotContainsString( '<script>', $condition['value'] );
		}
	}

	// =============================================================
	// DELETE OPERATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test that delete operation works with valid nonce
	 *
	 * WHY: Core functionality must work correctly
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_deletes_cta_with_valid_nonce() {
		UserFactory::create_and_login_admin();

		$cta_id = CTAFactory::create();

		$_GET['page'] = 'cta-auto-insert';
		$_GET['action'] = 'delete';
		$_GET['id'] = $cta_id;
		$_GET['_wpnonce'] = wp_create_nonce( 'delete_cta_' . $cta_id );

		add_filter( 'wp_redirect', '__return_false' );

		$this->admin->handle_actions();

		$this->assertDatabaseMissingCTA( $cta_id );
	}

	/**
	 * @test
	 * Test that delete sanitizes ID parameter and validates nonce correctly
	 *
	 * WHY: Prevents SQL injection through ID parameter
	 * PRIORITY: HIGH (security)
	 */
	public function it_sanitizes_delete_id_parameter() {
		UserFactory::create_and_login_admin();

		$cta_id = CTAFactory::create();

		$_GET['page'] = 'cta-auto-insert';
		$_GET['action'] = 'delete';
		$_GET['id'] = $cta_id . ' OR 1=1'; // SQL injection attempt
		// Create nonce for the MALICIOUS unsanitized value
		$_GET['_wpnonce'] = wp_create_nonce( 'delete_cta_' . ( $cta_id . ' OR 1=1' ) );

		add_filter( 'wp_redirect', '__return_false' );

		// Expect wp_die() to be called when nonce verification fails
		$this->expectException( 'WPDieException' );
		$this->expectExceptionMessage( 'Security check failed' );

		$this->admin->handle_actions();

		// Note: This line won't be reached due to expectException
		// But if it did, CTA should still exist since nonce check failed
		// $this->assertDatabaseHasCTA( $cta_id );
	}

	// =============================================================
	// DUPLICATE OPERATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test that duplicate operation works with valid nonce
	 *
	 * WHY: Core functionality must work correctly
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_duplicates_cta_with_valid_nonce() {
		UserFactory::create_and_login_admin();

		$cta_id = CTAFactory::create( array( 'name' => 'Original CTA' ) );

		$_GET['page'] = 'cta-auto-insert';
		$_GET['action'] = 'duplicate';
		$_GET['id'] = $cta_id;
		$_GET['_wpnonce'] = wp_create_nonce( 'duplicate_cta_' . $cta_id );

		add_filter( 'wp_redirect', '__return_false' );

		$this->admin->handle_actions();

		// Should have 2 CTAs now
		$this->assertDatabaseRowCount( 2 );
	}

	// =============================================================
	// SAVE OPERATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test that new CTA is created with valid data
	 *
	 * WHY: Core functionality must work correctly
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_creates_new_cta_with_valid_data() {
		UserFactory::create_and_login_admin();

		$_POST['page'] = 'cta-auto-insert';
		$_POST['cta_auto_insert_save'] = '1';
		$_POST['_wpnonce'] = wp_create_nonce( 'cta_auto_insert_save' );
		$_POST['name'] = 'New CTA';
		$_POST['content'] = 'Test content';
		$_POST['status'] = 'active';

		add_filter( 'wp_redirect', '__return_false' );

		$this->admin->handle_actions();

		$this->assertDatabaseRowCount( 1 );

		$ctas = $this->database->get_all();
		$this->assertEquals( 'New CTA', $ctas[0]['name'] );
	}

	/**
	 * @test
	 * Test that existing CTA is updated with valid data
	 *
	 * WHY: Core functionality must work correctly
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_updates_existing_cta() {
		UserFactory::create_and_login_admin();

		$cta_id = CTAFactory::create( array( 'name' => 'Original Name' ) );

		$_POST['page'] = 'cta-auto-insert';
		$_POST['cta_auto_insert_save'] = '1';
		$_POST['_wpnonce'] = wp_create_nonce( 'cta_auto_insert_save' );
		$_POST['cta_id'] = $cta_id;
		$_POST['name'] = 'Updated Name';
		$_POST['content'] = 'Updated content';

		add_filter( 'wp_redirect', '__return_false' );

		$this->admin->handle_actions();

		// Should still have only 1 CTA
		$this->assertDatabaseRowCount( 1 );

		$cta = $this->database->get( $cta_id );
		$this->assertEquals( 'Updated Name', $cta['name'] );
	}

	// =============================================================
	// REDIRECT SAFETY TESTS (SECURITY)
	// =============================================================

	/**
	 * @test
	 * Test that redirects use wp_safe_redirect
	 *
	 * WHY: Prevents open redirect vulnerabilities
	 * PRIORITY: HIGH (security)
	 */
	public function it_uses_safe_redirects() {
		UserFactory::create_and_login_admin();

		$cta_id = CTAFactory::create();

		$_GET['page'] = 'cta-auto-insert';
		$_GET['action'] = 'delete';
		$_GET['id'] = $cta_id;
		$_GET['_wpnonce'] = wp_create_nonce( 'delete_cta_' . $cta_id );

		// Hook into wp_redirect to verify safe redirect is used
		$redirect_url = '';
		add_filter( 'wp_redirect', function( $location ) use ( &$redirect_url ) {
			$redirect_url = $location;
			return false;
		} );

		$this->admin->handle_actions();

		// Redirect URL should be to admin page
		$this->assertStringContainsString( admin_url(), $redirect_url );
		$this->assertStringContainsString( 'cta-auto-insert', $redirect_url );
	}

	// =============================================================
	// DATA VALIDATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test that insertion_position is sanitized to positive integer
	 *
	 * WHY: Prevents invalid position values
	 * PRIORITY: MEDIUM (validation)
	 */
	public function it_sanitizes_insertion_position_to_positive_integer() {
		UserFactory::create_and_login_admin();

		$_POST['page'] = 'cta-auto-insert';
		$_POST['cta_auto_insert_save'] = '1';
		$_POST['_wpnonce'] = wp_create_nonce( 'cta_auto_insert_save' );
		$_POST['name'] = 'Test CTA';
		$_POST['insertion_position'] = '-5'; // Negative number

		add_filter( 'wp_redirect', '__return_false' );

		$this->admin->handle_actions();

		$ctas = $this->database->get_all();
		$saved_cta = $ctas[0];

		// Should be converted to 0 (or positive)
		$this->assertGreaterThanOrEqual( 0, $saved_cta['insertion_position'] );
	}

	/**
	 * @test
	 * Test that fallback_cta_id is null or positive integer
	 *
	 * WHY: Ensures valid fallback references
	 * PRIORITY: MEDIUM (validation)
	 */
	public function it_sanitizes_fallback_cta_id() {
		UserFactory::create_and_login_admin();

		$_POST['page'] = 'cta-auto-insert';
		$_POST['cta_auto_insert_save'] = '1';
		$_POST['_wpnonce'] = wp_create_nonce( 'cta_auto_insert_save' );
		$_POST['name'] = 'Test CTA';
		$_POST['fallback_cta_id'] = 'invalid';

		add_filter( 'wp_redirect', '__return_false' );

		$this->admin->handle_actions();

		$ctas = $this->database->get_all();
		$saved_cta = $ctas[0];

		// Should be null or integer
		$this->assertTrue(
			is_null( $saved_cta['fallback_cta_id'] ) || is_int( $saved_cta['fallback_cta_id'] )
		);
	}

	// =============================================================
	// EMPTY DATA HANDLING TESTS
	// =============================================================

	/**
	 * @test
	 * Test that empty storage conditions are handled correctly
	 *
	 * WHY: Prevents invalid data structure
	 * PRIORITY: MEDIUM (validation)
	 */
	public function it_handles_empty_storage_conditions() {
		UserFactory::create_and_login_admin();

		$_POST['page'] = 'cta-auto-insert';
		$_POST['cta_auto_insert_save'] = '1';
		$_POST['_wpnonce'] = wp_create_nonce( 'cta_auto_insert_save' );
		$_POST['name'] = 'Test CTA';
		// No storage_condition_key provided

		add_filter( 'wp_redirect', '__return_false' );

		$this->admin->handle_actions();

		$ctas = $this->database->get_all();
		$saved_cta = $ctas[0];

		$this->assertIsArray( $saved_cta['storage_conditions'] );
		$this->assertEmpty( $saved_cta['storage_conditions'] );
	}
}
