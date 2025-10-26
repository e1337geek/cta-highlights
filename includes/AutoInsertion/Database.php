<?php
/**
 * Database operations for auto-insertion CTAs
 *
 * @package CTAHighlights\AutoInsertion
 */

namespace CTAHighlights\AutoInsertion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database class for managing auto-insertion CTA storage
 */
class Database {

	/**
	 * Database version for migrations
	 *
	 * @var string
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Option name for storing database version
	 *
	 * @var string
	 */
	const VERSION_OPTION = 'cta_highlights_auto_insert_db_version';

	/**
	 * Get table name with WordPress prefix
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'cta_auto_insertions';
	}

	/**
	 * Create or update database table
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			content longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			post_types longtext,
			category_mode varchar(10) DEFAULT 'include',
			category_ids longtext,
			storage_conditions longtext,
			insertion_direction varchar(10) DEFAULT 'forward',
			insertion_position int(11) NOT NULL DEFAULT 3,
			fallback_behavior varchar(10) DEFAULT 'end',
			fallback_cta_id bigint(20) unsigned DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY fallback_cta_id (fallback_cta_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Drop the database table (uninstall cleanup)
	 *
	 * @return void
	 */
	public static function drop_table() {
		global $wpdb;
		$table_name = self::get_table_name();
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		delete_option( self::VERSION_OPTION );
	}

	/**
	 * Check if database needs migration
	 *
	 * @return bool
	 */
	public static function needs_migration() {
		$current_version = get_option( self::VERSION_OPTION, '0.0.0' );
		return version_compare( $current_version, self::DB_VERSION, '<' );
	}

	/**
	 * Get all CTAs
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function get_all( $args = array() ) {
		global $wpdb;
		$table_name = self::get_table_name();

		$defaults = array(
			'status'  => 'active',
			'orderby' => 'id',
			'order'   => 'ASC',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = '';
		if ( ! empty( $args['status'] ) ) {
			$where = $wpdb->prepare( 'WHERE status = %s', $args['status'] );
		}

		$orderby = sanitize_sql_orderby( "{$args['orderby']} {$args['order']}" );

		$query = "SELECT * FROM $table_name $where ORDER BY $orderby"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$results = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $this->unserialize_results( $results );
	}

	/**
	 * Get CTA by ID
	 *
	 * @param int $id CTA ID.
	 * @return array|null
	 */
	public function get( $id ) {
		global $wpdb;
		$table_name = self::get_table_name();

		$result = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		if ( ! $result ) {
			return null;
		}

		return $this->unserialize_row( $result );
	}

	/**
	 * Insert new CTA
	 *
	 * @param array $data CTA data.
	 * @return int|false Insert ID or false on failure.
	 */
	public function insert( $data ) {
		global $wpdb;
		$table_name = self::get_table_name();

		$data = $this->prepare_data( $data );

		$result = $wpdb->insert(
			$table_name,
			$data,
			array(
				'%s', // name
				'%s', // content
				'%s', // status
				'%s', // post_types
				'%s', // category_mode
				'%s', // category_ids
				'%s', // storage_conditions
				'%s', // insertion_direction
				'%d', // insertion_position
				'%s', // fallback_behavior
				'%d', // fallback_cta_id
			)
		);

		if ( false === $result ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Update CTA
	 *
	 * @param int   $id CTA ID.
	 * @param array $data CTA data.
	 * @return bool
	 */
	public function update( $id, $data ) {
		global $wpdb;
		$table_name = self::get_table_name();

		$data = $this->prepare_data( $data );

		$result = $wpdb->update(
			$table_name,
			$data,
			array( 'id' => $id ),
			array(
				'%s', // name
				'%s', // content
				'%s', // status
				'%s', // post_types
				'%s', // category_mode
				'%s', // category_ids
				'%s', // storage_conditions
				'%s', // insertion_direction
				'%d', // insertion_position
				'%s', // fallback_behavior
				'%d', // fallback_cta_id
			),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete CTA
	 *
	 * @param int $id CTA ID.
	 * @return bool
	 */
	public function delete( $id ) {
		global $wpdb;
		$table_name = self::get_table_name();

		$result = $wpdb->delete(
			$table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Duplicate CTA
	 *
	 * @param int $id CTA ID to duplicate.
	 * @return int|false New CTA ID or false on failure.
	 */
	public function duplicate( $id ) {
		$cta = $this->get( $id );

		if ( ! $cta ) {
			return false;
		}

		unset( $cta['id'], $cta['created_at'], $cta['updated_at'] );
		$cta['name'] = $cta['name'] . ' (Copy)';

		return $this->insert( $cta );
	}

	/**
	 * Prepare data for database insertion
	 *
	 * @param array $data Raw data.
	 * @return array Prepared data.
	 */
	private function prepare_data( $data ) {
		$prepared = array();

		// String fields
		if ( isset( $data['name'] ) ) {
			$prepared['name'] = sanitize_text_field( $data['name'] );
		}

		if ( isset( $data['content'] ) ) {
			$prepared['content'] = wp_kses_post( $data['content'] );
		}

		if ( isset( $data['status'] ) ) {
			$prepared['status'] = in_array( $data['status'], array( 'active', 'inactive' ), true )
				? $data['status']
				: 'active';
		}

		if ( isset( $data['category_mode'] ) ) {
			$prepared['category_mode'] = in_array( $data['category_mode'], array( 'include', 'exclude' ), true )
				? $data['category_mode']
				: 'include';
		}

		if ( isset( $data['insertion_direction'] ) ) {
			$prepared['insertion_direction'] = in_array( $data['insertion_direction'], array( 'forward', 'reverse' ), true )
				? $data['insertion_direction']
				: 'forward';
		}

		if ( isset( $data['fallback_behavior'] ) ) {
			$prepared['fallback_behavior'] = in_array( $data['fallback_behavior'], array( 'end', 'skip' ), true )
				? $data['fallback_behavior']
				: 'end';
		}

		// Integer fields
		if ( isset( $data['insertion_position'] ) ) {
			$prepared['insertion_position'] = absint( $data['insertion_position'] );
		}

		if ( isset( $data['fallback_cta_id'] ) ) {
			$prepared['fallback_cta_id'] = ! empty( $data['fallback_cta_id'] ) ? absint( $data['fallback_cta_id'] ) : null;
		}

		// JSON fields
		if ( isset( $data['post_types'] ) ) {
			$prepared['post_types'] = wp_json_encode( (array) $data['post_types'] );
		}

		if ( isset( $data['category_ids'] ) ) {
			$prepared['category_ids'] = wp_json_encode( array_map( 'absint', (array) $data['category_ids'] ) );
		}

		if ( isset( $data['storage_conditions'] ) ) {
			$prepared['storage_conditions'] = wp_json_encode( (array) $data['storage_conditions'] );
		}

		return $prepared;
	}

	/**
	 * Unserialize JSON fields in results
	 *
	 * @param array $results Database results.
	 * @return array
	 */
	private function unserialize_results( $results ) {
		return array_map( array( $this, 'unserialize_row' ), $results );
	}

	/**
	 * Unserialize JSON fields in a single row
	 *
	 * @param array $row Database row.
	 * @return array
	 */
	private function unserialize_row( $row ) {
		$json_fields = array( 'post_types', 'category_ids', 'storage_conditions' );

		foreach ( $json_fields as $field ) {
			if ( isset( $row[ $field ] ) ) {
				$decoded = json_decode( $row[ $field ], true );
				$row[ $field ] = is_array( $decoded ) ? $decoded : array();
			}
		}

		return $row;
	}
}
