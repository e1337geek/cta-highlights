<?php
/**
 * Template View Data Container
 *
 * Provides safe access to template variables without using extract()
 *
 * @package CTAHighlights
 * @since 1.0.0
 */

namespace CTAHighlights\Template;

use ArrayAccess;

/**
 * ViewData class for template variable access
 *
 * @since 1.0.0
 */
class ViewData implements ArrayAccess {
	/**
	 * Template data
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * Constructor
	 *
	 * @param array $data Template data array.
	 */
	public function __construct( array $data = array() ) {
		$this->data = $data;
	}

	/**
	 * Get a value with optional default
	 *
	 * phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound
	 *
	 * @param string $key     Data key.
	 * @param mixed  $default Default value if key doesn't exist.
	 * @return mixed
	 */
	public function get( $key, $default = '' ) {
		return $this->data[ $key ] ?? $default;
	}

	/**
	 * Check if a key exists
	 *
	 * @param string $key Data key.
	 * @return bool
	 */
	public function has( $key ) {
		return isset( $this->data[ $key ] );
	}

	/**
	 * Get all data
	 *
	 * @return array
	 */
	public function all() {
		return $this->data;
	}

	/**
	 * Magic getter for property-style access
	 *
	 * @param string $key Data key.
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get( $key );
	}

	/**
	 * Magic isset for property-style access
	 *
	 * @param string $key Data key.
	 * @return bool
	 */
	public function __isset( $key ) {
		return $this->has( $key );
	}

	/**
	 * ArrayAccess: Check if offset exists
	 *
	 * @param mixed $offset Array offset.
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		return $this->has( $offset );
	}

	/**
	 * ArrayAccess: Get offset value
	 *
	 * @param mixed $offset Array offset.
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		return $this->get( $offset );
	}

	/**
	 * ArrayAccess: Set is not allowed (read-only)
	 *
	 * @param mixed $offset Array offset.
	 * @param mixed $value  Value to set.
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		// Read-only - do nothing.
	}

	/**
	 * ArrayAccess: Unset is not allowed (read-only)
	 *
	 * @param mixed $offset Array offset.
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		// Read-only - do nothing.
	}
}
