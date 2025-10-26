<?php
namespace CTAHighlights\Template;

class Registry {
	private static $instance = null;
	private $templates_used = array();

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function register( $template_name ) {
		if ( ! in_array( $template_name, $this->templates_used, true ) ) {
			$this->templates_used[] = sanitize_file_name( $template_name );
		}
	}

	public function is_registered( $template_name ) {
		return in_array( $template_name, $this->templates_used, true );
	}

	public function get_all() {
		return $this->templates_used;
	}

	public function clear() {
		$this->templates_used = array();
	}

	public function count() {
		return count( $this->templates_used );
	}

	private function __clone() {}

	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
