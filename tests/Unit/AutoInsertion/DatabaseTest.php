<?php
/**
 * Database Class Tests
 *
 * HIGH PRIORITY: Security and Data Integrity Tests
 *
 * This test class covers the Database class which handles all CRUD operations
 * for auto-inserted CTAs. This is HIGH PRIORITY because:
 * - It directly interacts with the database (SQL injection risk)
 * - It handles user input (XSS/sanitization critical)
 * - It stores JSON data (serialization vulnerabilities)
 * - Data integrity is critical for plugin functionality
 *
 * @package CTAHighlights\Tests\Unit\AutoInsertion
 */

namespace CTAHighlights\Tests\Unit\AutoInsertion;

use CTAHighlights\AutoInsertion\Database;
use CTAHighlights\Tests\Factories\CTAFactory;
use WP_UnitTestCase;

class DatabaseTest extends WP_UnitTestCase {

	/**
	 * Database instance
	 *
	 * @var Database
	 */
	private $database;

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->database = new Database();

		// Ensure table exists
		$this->database->create_table();

		// Clear any existing test data
		global $wpdb;
		$table_name = $wpdb->prefix . 'cta_auto_insertions';
		$wpdb->query( "TRUNCATE TABLE {$table_name}" );
	}

	/**
	 * Cleanup after each test
	 */
	public function tearDown(): void {
		// Clean up test data
		global $wpdb;
		$table_name = $wpdb->prefix . 'cta_auto_insertions';
		$wpdb->query( "TRUNCATE TABLE {$table_name}" );

		parent::tearDown();
	}

	// =============================================================
	// TABLE CREATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test that database table is created with correct schema
	 *
	 * WHY: Ensures the plugin can store data and schema is correct
	 * PRIORITY: HIGH (data integrity)
	 */
	public function it_creates_table_with_correct_schema() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'cta_auto_insertions';

		// Table should exist
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
		$this->assertEquals( $table_name, $table_exists );

		// Check for required columns
		$columns = $wpdb->get_col( "DESCRIBE {$table_name}" );

		$required_columns = array(
			'id',
			'name',
			'content',
			'status',
			'cta_type',
			'post_types',
			'category_mode',
			'category_ids',
			'storage_conditions',
			'insertion_direction',
			'insertion_position',
			'fallback_behavior',
			'fallback_cta_id',
			'created_at',
			'updated_at',
		);

		foreach ( $required_columns as $column ) {
			$this->assertContains( $column, $columns, "Missing required column: {$column}" );
		}
	}

	// =============================================================
	// SQL INJECTION PREVENTION TESTS (CRITICAL SECURITY)
	// =============================================================

	/**
	 * @test
	 * Test that SQL injection is prevented in insert operations
	 *
	 * WHY: SQL injection is a critical security vulnerability
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_sql_injection_in_insert() {
		$malicious_data = array(
			'name'    => "'; DROP TABLE wp_users; --",
			'content' => "'; DELETE FROM wp_posts WHERE 1=1; --",
			'status'  => "active' OR '1'='1",
		);

		$id = $this->database->insert( $malicious_data );

		// Should succeed (not execute SQL)
		$this->assertIsInt( $id );
		$this->assertGreaterThan( 0, $id );

		// Retrieve and verify data was escaped
		$saved = $this->database->get( $id );
		$this->assertEquals( $malicious_data['name'], $saved['name'] );
		$this->assertEquals( $malicious_data['content'], $saved['content'] );

		// Verify tables still exist (SQL wasn't executed)
		global $wpdb;
		$users_table = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->users}'" );
		$this->assertEquals( $wpdb->users, $users_table, 'wp_users table should still exist' );
	}

	/**
	 * @test
	 * Test that SQL injection is prevented in update operations
	 *
	 * WHY: SQL injection is a critical security vulnerability
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_sql_injection_in_update() {
		$id = CTAFactory::create();

		$malicious_data = array(
			'name' => "'; UPDATE wp_users SET user_pass = 'hacked' WHERE 1=1; --",
		);

		$result = $this->database->update( $id, $malicious_data );

		$this->assertTrue( $result );

		// Verify data was escaped
		$saved = $this->database->get( $id );
		$this->assertEquals( $malicious_data['name'], $saved['name'] );
	}

	/**
	 * @test
	 * Test that SQL injection is prevented in get operations
	 *
	 * WHY: SQL injection is a critical security vulnerability
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_sql_injection_in_get() {
		$malicious_id = "1 OR 1=1";

		// Should return null (not execute SQL)
		$result = $this->database->get( $malicious_id );
		$this->assertNull( $result );
	}

	// =============================================================
	// DATA SANITIZATION TESTS (SECURITY)
	// =============================================================

	/**
	 * @test
	 * Test that XSS attempts are sanitized
	 *
	 * WHY: XSS is a critical security vulnerability
	 * PRIORITY: HIGH (security)
	 */
	public function it_sanitizes_xss_attempts() {
		$xss_data = array(
			'name'    => '<script>alert("XSS")</script>Test CTA',
			'content' => '<img src=x onerror="alert(\'XSS\')">',
		);

		$id = $this->database->insert( $xss_data );
		$saved = $this->database->get( $id );

		// Name should be sanitized (scripts removed)
		$this->assertStringNotContainsString( '<script>', $saved['name'] );

		// Content should allow safe HTML but strip dangerous attributes
		$this->assertStringNotContainsString( 'onerror=', $saved['content'] );
	}

	// =============================================================
	// CRUD OPERATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test inserting a CTA with valid data
	 *
	 * WHY: Core functionality - must reliably store data
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_inserts_cta_successfully() {
		$data = CTAFactory::make();

		$id = $this->database->insert( $data );

		$this->assertIsInt( $id );
		$this->assertGreaterThan( 0, $id );
	}

	/**
	 * @test
	 * Test retrieving a CTA by ID
	 *
	 * WHY: Core functionality - must reliably retrieve data
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_retrieves_cta_by_id() {
		$id = CTAFactory::create();

		$cta = $this->database->get( $id );

		$this->assertIsArray( $cta );
		$this->assertEquals( $id, $cta['id'] );
		$this->assertEquals( 'Test CTA', $cta['name'] );
	}

	/**
	 * @test
	 * Test retrieving non-existent CTA returns null
	 *
	 * WHY: Proper error handling prevents bugs
	 * PRIORITY: MEDIUM (error handling)
	 */
	public function it_returns_null_for_nonexistent_cta() {
		$cta = $this->database->get( 99999 );

		$this->assertNull( $cta );
	}

	/**
	 * @test
	 * Test updating an existing CTA
	 *
	 * WHY: Core functionality - must reliably update data
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_updates_cta_successfully() {
		$id = CTAFactory::create();

		$update_data = array(
			'name'   => 'Updated CTA Name',
			'status' => 'inactive',
		);

		$result = $this->database->update( $id, $update_data );
		$this->assertTrue( $result );

		// Verify update
		$cta = $this->database->get( $id );
		$this->assertEquals( 'Updated CTA Name', $cta['name'] );
		$this->assertEquals( 'inactive', $cta['status'] );
	}

	/**
	 * @test
	 * Test deleting a CTA
	 *
	 * WHY: Core functionality - must reliably delete data
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_deletes_cta_successfully() {
		$id = CTAFactory::create();

		$result = $this->database->delete( $id );
		$this->assertTrue( $result );

		// Verify deletion
		$cta = $this->database->get( $id );
		$this->assertNull( $cta );
	}

	// =============================================================
	// JSON SERIALIZATION TESTS (SECURITY & DATA INTEGRITY)
	// =============================================================

	/**
	 * @test
	 * Test that JSON fields are properly serialized
	 *
	 * WHY: JSON serialization errors can corrupt data
	 * PRIORITY: HIGH (data integrity)
	 */
	public function it_serializes_json_fields_correctly() {
		$data = CTAFactory::make( array(
			'post_types'          => array( 'post', 'page', 'custom_type' ),
			'category_ids'        => array( 1, 2, 3 ),
			'storage_conditions'  => array(
				array(
					'key'   => 'hasSubscribed',
					'operator' => '=',
					'value' => 'true',
					'type'  => 'boolean',
				),
			),
		) );

		$id = $this->database->insert( $data );
		$saved = $this->database->get( $id );

		// Verify arrays are preserved
		$this->assertIsArray( $saved['post_types'] );
		$this->assertEquals( array( 'post', 'page', 'custom_type' ), $saved['post_types'] );

		$this->assertIsArray( $saved['category_ids'] );
		$this->assertEquals( array( 1, 2, 3 ), $saved['category_ids'] );

		$this->assertIsArray( $saved['storage_conditions'] );
		$this->assertCount( 1, $saved['storage_conditions'] );
		$this->assertEquals( 'hasSubscribed', $saved['storage_conditions'][0]['key'] );
	}

	/**
	 * @test
	 * Test that malformed JSON is handled gracefully
	 *
	 * WHY: Prevents data corruption and errors
	 * PRIORITY: MEDIUM (error handling)
	 */
	public function it_handles_malformed_json_gracefully() {
		// Manually insert malformed JSON
		global $wpdb;
		$table_name = $wpdb->prefix . 'cta_auto_insertions';

		$wpdb->insert(
			$table_name,
			array(
				'name'       => 'Test CTA',
				'content'    => 'Test content',
				'post_types' => '{malformed json}',
			),
			array( '%s', '%s', '%s' )
		);

		$id = $wpdb->insert_id;
		$cta = $this->database->get( $id );

		// Should return empty array for malformed JSON
		$this->assertIsArray( $cta['post_types'] );
		$this->assertEmpty( $cta['post_types'] );
	}

	// =============================================================
	// QUERY TESTS (FILTERING & SORTING)
	// =============================================================

	/**
	 * @test
	 * Test filtering CTAs by status
	 *
	 * WHY: Core functionality for displaying correct CTAs
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_filters_ctas_by_status() {
		CTAFactory::create( array( 'name' => 'Active CTA 1', 'status' => 'active' ) );
		CTAFactory::create( array( 'name' => 'Active CTA 2', 'status' => 'active' ) );
		CTAFactory::create( array( 'name' => 'Inactive CTA', 'status' => 'inactive' ) );

		$active_ctas = $this->database->get_all( array( 'status' => 'active' ) );

		$this->assertCount( 2, $active_ctas );
		$this->assertEquals( 'active', $active_ctas[0]['status'] );
		$this->assertEquals( 'active', $active_ctas[1]['status'] );
	}

	/**
	 * @test
	 * Test retrieving all CTAs
	 *
	 * WHY: Core functionality for admin list table
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_retrieves_all_ctas() {
		$count = 5;
		CTAFactory::create_many( $count );

		$ctas = $this->database->get_all();

		$this->assertCount( $count, $ctas );
	}

	// =============================================================
	// EDGE CASES & ERROR HANDLING
	// =============================================================

	/**
	 * @test
	 * Test handling of missing required fields
	 *
	 * WHY: Prevents invalid data from being stored
	 * PRIORITY: MEDIUM (data integrity)
	 */
	public function it_handles_missing_required_fields() {
		$incomplete_data = array(
			'name' => 'Test CTA',
			// Missing other required fields
		);

		$id = $this->database->insert( $incomplete_data );

		// Should still insert with defaults
		$this->assertIsInt( $id );

		$saved = $this->database->get( $id );
		$this->assertEquals( 'active', $saved['status'] ); // Default value
	}

	/**
	 * @test
	 * Test handling of very large content
	 *
	 * WHY: Ensures plugin works with large CTAs
	 * PRIORITY: LOW (edge case)
	 */
	public function it_handles_large_content() {
		$large_content = str_repeat( 'Test content. ', 10000 ); // ~130KB

		$id = CTAFactory::create( array( 'content' => $large_content ) );
		$saved = $this->database->get( $id );

		$this->assertEquals( $large_content, $saved['content'] );
	}

	/**
	 * @test
	 * Test handling of special characters
	 *
	 * WHY: Ensures internationalization works correctly
	 * PRIORITY: MEDIUM (i18n)
	 */
	public function it_handles_special_characters() {
		$special_chars = array(
			'name'    => 'Test CTA with émojis 🚀 and spëcial çhars',
			'content' => 'Content with 中文字符 and العربية',
		);

		$id = $this->database->insert( $special_chars );
		$saved = $this->database->get( $id );

		$this->assertEquals( $special_chars['name'], $saved['name'] );
		$this->assertEquals( $special_chars['content'], $saved['content'] );
	}
}
