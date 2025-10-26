<?php
/**
 * Creates Database Trait
 *
 * Provides helper methods for database setup, teardown, and manipulation
 * in tests. Use this trait to manage plugin tables and test data.
 *
 * @package CTAHighlights\Tests\Traits
 */

namespace CTAHighlights\Tests\Traits;

use CTAHighlights\AutoInsertion\Database;

trait CreatesDatabase {

	/**
	 * Database instance
	 *
	 * @var Database
	 */
	protected $database;

	/**
	 * Setup database for testing
	 * Call this in your test's setUp() method
	 */
	protected function setupDatabase() {
		global $wpdb;

		$this->database = new Database();

		// Create table
		$this->database->create_table();

		// Clear any existing test data
		$this->clearDatabaseTable();
	}

	/**
	 * Cleanup database after testing
	 * Call this in your test's tearDown() method
	 */
	protected function teardownDatabase() {
		$this->clearDatabaseTable();
	}

	/**
	 * Clear all rows from the CTA table
	 */
	protected function clearDatabaseTable() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cta_auto_insertions';
		$wpdb->query( "TRUNCATE TABLE {$table_name}" );
	}

	/**
	 * Drop the CTA table
	 */
	protected function dropDatabaseTable() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cta_auto_insertions';
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
	}

	/**
	 * Recreate the CTA table from scratch
	 */
	protected function recreateDatabaseTable() {
		$this->dropDatabaseTable();
		$this->database->create_table();
	}

	/**
	 * Get row count from CTA table
	 *
	 * @return int
	 */
	protected function getDatabaseRowCount() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cta_auto_insertions';
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
	}

	/**
	 * Get all rows from CTA table
	 *
	 * @return array
	 */
	protected function getAllDatabaseRows() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cta_auto_insertions';
		return $wpdb->get_results( "SELECT * FROM {$table_name}", ARRAY_A );
	}

	/**
	 * Assert that CTA table has expected row count
	 *
	 * @param int $expected Expected row count.
	 * @param string $message Optional failure message.
	 */
	protected function assertDatabaseRowCount( $expected, $message = '' ) {
		$actual = $this->getDatabaseRowCount();

		if ( empty( $message ) ) {
			$message = "Failed asserting that CTA table has {$expected} rows. Actual: {$actual}";
		}

		$this->assertEquals( $expected, $actual, $message );
	}

	/**
	 * Assert that a CTA exists in database
	 *
	 * @param int $id CTA ID.
	 * @param string $message Optional failure message.
	 */
	protected function assertDatabaseHasCTA( $id, $message = '' ) {
		$cta = $this->database->get( $id );

		if ( empty( $message ) ) {
			$message = "Failed asserting that CTA with ID {$id} exists in database";
		}

		$this->assertNotNull( $cta, $message );
	}

	/**
	 * Assert that a CTA does not exist in database
	 *
	 * @param int $id CTA ID.
	 * @param string $message Optional failure message.
	 */
	protected function assertDatabaseMissingCTA( $id, $message = '' ) {
		$cta = $this->database->get( $id );

		if ( empty( $message ) ) {
			$message = "Failed asserting that CTA with ID {$id} does not exist in database";
		}

		$this->assertNull( $cta, $message );
	}

	/**
	 * Assert that database table exists
	 *
	 * @param string $message Optional failure message.
	 */
	protected function assertDatabaseTableExists( $message = '' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cta_auto_insertions';
		$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );

		if ( empty( $message ) ) {
			$message = "Failed asserting that table {$table_name} exists";
		}

		$this->assertEquals( $table_name, $exists, $message );
	}

	/**
	 * Get database table name
	 *
	 * @return string
	 */
	protected function getDatabaseTableName() {
		global $wpdb;
		return $wpdb->prefix . 'cta_auto_insertions';
	}

	/**
	 * Insert raw data into database (bypassing Database class for testing)
	 *
	 * @param array $data Raw data to insert.
	 * @param array $format Optional format array.
	 * @return int|false Insert ID or false on failure.
	 */
	protected function insertRawDatabaseRow( $data, $format = array() ) {
		global $wpdb;
		$table_name = $this->getDatabaseTableName();

		// Add timestamps if not provided
		if ( ! isset( $data['created_at'] ) ) {
			$data['created_at'] = current_time( 'mysql' );
		}
		if ( ! isset( $data['updated_at'] ) ) {
			$data['updated_at'] = current_time( 'mysql' );
		}

		$result = $wpdb->insert( $table_name, $data, $format );

		if ( $result === false ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Update raw data in database (bypassing Database class for testing)
	 *
	 * @param int $id Row ID.
	 * @param array $data Data to update.
	 * @param array $format Optional format array.
	 * @return bool True on success, false on failure.
	 */
	protected function updateRawDatabaseRow( $id, $data, $format = array() ) {
		global $wpdb;
		$table_name = $this->getDatabaseTableName();

		$result = $wpdb->update(
			$table_name,
			$data,
			array( 'id' => $id ),
			$format,
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get raw row from database (bypassing Database class for testing)
	 *
	 * @param int $id Row ID.
	 * @return array|null
	 */
	protected function getRawDatabaseRow( $id ) {
		global $wpdb;
		$table_name = $this->getDatabaseTableName();

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $id ),
			ARRAY_A
		);
	}

	/**
	 * Check if database table has specific column
	 *
	 * @param string $column_name Column name to check.
	 * @return bool
	 */
	protected function databaseTableHasColumn( $column_name ) {
		global $wpdb;
		$table_name = $this->getDatabaseTableName();
		$columns = $wpdb->get_col( "DESCRIBE {$table_name}" );

		return in_array( $column_name, $columns, true );
	}

	/**
	 * Get database error if any
	 *
	 * @return string
	 */
	protected function getDatabaseError() {
		global $wpdb;
		return $wpdb->last_error;
	}

	/**
	 * Assert no database errors occurred
	 *
	 * @param string $message Optional failure message.
	 */
	protected function assertNoDatabaseError( $message = '' ) {
		global $wpdb;

		if ( empty( $message ) ) {
			$message = 'Database error occurred: ' . $wpdb->last_error;
		}

		$this->assertEmpty( $wpdb->last_error, $message );
	}

	/**
	 * Enable or disable SQL error suppression
	 *
	 * @param bool $suppress Whether to suppress errors.
	 */
	protected function suppressDatabaseErrors( $suppress = true ) {
		global $wpdb;
		$wpdb->suppress_errors( $suppress );
	}
}
