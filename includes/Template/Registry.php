<?php
/**
 * Template registry for tracking used templates
 *
 * @package CTAHighlights\Template
 */

namespace CTAHighlights\Template;

/**
 * Singleton registry for tracking CTA templates used during a page load
 */
class Registry {
	/**
	 * Singleton instance
	 *
	 * @var Registry|null
	 */
	private static $instance = null;

	/**
	 * Array of template names that have been used
	 *
	 * @var array
	 */
	private $templates_used = array();

	/**
	 * Get singleton instance
	 *
	 * @return Registry
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {}

	/**
	 * Register a template as used
	 *
	 * @param string $template_name Template name to register.
	 * @return void
	 */
	public function register( $template_name ) {
		if ( ! in_array( $template_name, $this->templates_used, true ) ) {
			$this->templates_used[] = sanitize_file_name( $template_name );
		}
	}

	/**
	 * Check if a template has been registered
	 *
	 * @param string $template_name Template name to check.
	 * @return bool
	 */
	public function is_registered( $template_name ) {
		return in_array( $template_name, $this->templates_used, true );
	}

	/**
	 * Get all registered templates
	 *
	 * @return array
	 */
	public function get_all() {
		return $this->templates_used;
	}

	/**
	 * Clear all registered templates
	 *
	 * @return void
	 */
	public function clear() {
		$this->templates_used = array();
	}

	/**
	 * Get count of registered templates
	 *
	 * @return int
	 */
	public function count() {
		return count( $this->templates_used );
	}

	/**
	 * Prevent cloning of singleton
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization of singleton
	 *
	 * @throws \Exception When attempting to unserialize.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
