<?php
/**
 * User Factory for Testing
 *
 * Creates test WordPress users with various roles for permission testing.
 *
 * @package CTAHighlights\Tests
 */

namespace CTAHighlights\Tests\Factories;

class UserFactory {

	/**
	 * Default user attributes
	 *
	 * @var array
	 */
	private static $defaults = array(
		'user_login' => 'testuser',
		'user_pass'  => 'password',
		'user_email' => 'testuser@example.com',
		'role'       => 'subscriber',
	);

	/**
	 * Counter for unique usernames
	 *
	 * @var int
	 */
	private static $counter = 0;

	/**
	 * Create a test user
	 *
	 * @param array $overrides Override default attributes.
	 * @return int|\WP_Error User ID or error.
	 */
	public static function create( $overrides = array() ) {
		self::$counter++;

		$data = wp_parse_args( $overrides, self::$defaults );

		// Make username unique if not provided
		if ( ! isset( $overrides['user_login'] ) ) {
			$data['user_login'] = 'testuser' . self::$counter;
		}

		// Make email unique if not provided
		if ( ! isset( $overrides['user_email'] ) ) {
			$data['user_email'] = 'testuser' . self::$counter . '@example.com';
		}

		return wp_insert_user( $data );
	}

	/**
	 * Create multiple test users
	 *
	 * @param int $count Number of users to create.
	 * @param array $overrides Override default attributes.
	 * @return array Array of user IDs.
	 */
	public static function create_many( $count, $overrides = array() ) {
		$ids = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$id = self::create( $overrides );

			if ( ! is_wp_error( $id ) ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * Create an administrator user
	 *
	 * @param array $overrides Override default attributes.
	 * @return int|\WP_Error User ID or error.
	 */
	public static function create_admin( $overrides = array() ) {
		$overrides['role'] = 'administrator';
		return self::create( $overrides );
	}

	/**
	 * Create an editor user
	 *
	 * @param array $overrides Override default attributes.
	 * @return int|\WP_Error User ID or error.
	 */
	public static function create_editor( $overrides = array() ) {
		$overrides['role'] = 'editor';
		return self::create( $overrides );
	}

	/**
	 * Create an author user
	 *
	 * @param array $overrides Override default attributes.
	 * @return int|\WP_Error User ID or error.
	 */
	public static function create_author( $overrides = array() ) {
		$overrides['role'] = 'author';
		return self::create( $overrides );
	}

	/**
	 * Create a contributor user
	 *
	 * @param array $overrides Override default attributes.
	 * @return int|\WP_Error User ID or error.
	 */
	public static function create_contributor( $overrides = array() ) {
		$overrides['role'] = 'contributor';
		return self::create( $overrides );
	}

	/**
	 * Create a subscriber user
	 *
	 * @param array $overrides Override default attributes.
	 * @return int|\WP_Error User ID or error.
	 */
	public static function create_subscriber( $overrides = array() ) {
		$overrides['role'] = 'subscriber';
		return self::create( $overrides );
	}

	/**
	 * Create a user and set as current user
	 *
	 * @param array $overrides Override default attributes.
	 * @return int|\WP_Error User ID or error.
	 */
	public static function create_and_login( $overrides = array() ) {
		$user_id = self::create( $overrides );

		if ( ! is_wp_error( $user_id ) ) {
			wp_set_current_user( $user_id );
		}

		return $user_id;
	}

	/**
	 * Create an admin and set as current user
	 *
	 * @param array $overrides Override default attributes.
	 * @return int|\WP_Error User ID or error.
	 */
	public static function create_and_login_admin( $overrides = array() ) {
		$overrides['role'] = 'administrator';
		return self::create_and_login( $overrides );
	}

	/**
	 * Create a user with specific capabilities
	 *
	 * @param array $capabilities Capabilities to add.
	 * @param array $overrides Override default attributes.
	 * @return int|\WP_Error User ID or error.
	 */
	public static function create_with_capabilities( $capabilities, $overrides = array() ) {
		$user_id = self::create( $overrides );

		if ( ! is_wp_error( $user_id ) ) {
			$user = new \WP_User( $user_id );

			foreach ( $capabilities as $cap ) {
				$user->add_cap( $cap );
			}
		}

		return $user_id;
	}

	/**
	 * Create a user with meta data
	 *
	 * @param array $meta Meta key-value pairs.
	 * @param array $overrides Override default attributes.
	 * @return int|\WP_Error User ID or error.
	 */
	public static function create_with_meta( $meta, $overrides = array() ) {
		$user_id = self::create( $overrides );

		if ( ! is_wp_error( $user_id ) ) {
			foreach ( $meta as $key => $value ) {
				update_user_meta( $user_id, $key, $value );
			}
		}

		return $user_id;
	}

	/**
	 * Create users with different roles for permission testing
	 *
	 * @return array Associative array of role => user_id.
	 */
	public static function create_all_roles() {
		return array(
			'administrator' => self::create_admin(),
			'editor'        => self::create_editor(),
			'author'        => self::create_author(),
			'contributor'   => self::create_contributor(),
			'subscriber'    => self::create_subscriber(),
		);
	}

	/**
	 * Make a user data array without saving
	 *
	 * @param array $overrides Override default attributes.
	 * @return array User data.
	 */
	public static function make( $overrides = array() ) {
		self::$counter++;

		$data = wp_parse_args( $overrides, self::$defaults );

		if ( ! isset( $overrides['user_login'] ) ) {
			$data['user_login'] = 'testuser' . self::$counter;
		}

		if ( ! isset( $overrides['user_email'] ) ) {
			$data['user_email'] = 'testuser' . self::$counter . '@example.com';
		}

		return $data;
	}

	/**
	 * Get default user attributes
	 *
	 * @return array
	 */
	public static function defaults() {
		return self::$defaults;
	}

	/**
	 * Reset counter (useful for cleanup between tests)
	 */
	public static function reset_counter() {
		self::$counter = 0;
	}
}
