<?php
/**
 * Template Loader class.
 *
 * @package CTAHighlights
 */

namespace CTAHighlights\Template;

/**
 * Class Loader
 *
 * Handles template loading with theme override support and caching.
 */
class Loader {
	/**
	 * Template cache array.
	 *
	 * @var array
	 */
	private $template_cache = array();

	/**
	 * Cache group name.
	 */
	private const CACHE_GROUP = 'cta_highlights_templates';

	/**
	 * Cache expiry time.
	 */
	private const CACHE_EXPIRY = HOUR_IN_SECONDS;

	/**
	 * Plugin directory path.
	 *
	 * @var string
	 */
	private $plugin_dir;

	/**
	 * Template subdirectory in theme.
	 */
	private const TEMPLATE_SUBDIR = 'cta-highlights-templates/';

	/**
	 * Constructor.
	 *
	 * @param string $plugin_dir Plugin directory path.
	 */
	public function __construct( $plugin_dir ) {
		$this->plugin_dir = trailingslashit( $plugin_dir );
	}

	/**
	 * Locate a template file with caching.
	 *
	 * @param string $template_name Template name without .php extension.
	 * @return string|null Template file path or null if not found.
	 */
	public function locate_template( $template_name ) {
		// Log security event if path traversal is attempted.
		if ( $this->contains_path_traversal( $template_name ) ) {
			$this->log_security_event( 'Path traversal attempt in template name: ' . $template_name );
		}

		$template_name = sanitize_file_name( $template_name );

		if ( isset( $this->template_cache[ $template_name ] ) ) {
			return $this->template_cache[ $template_name ];
		}

		$cache_key   = "template_path_{$template_name}";
		$cached_path = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached_path ) {
			if ( null === $cached_path || file_exists( $cached_path ) ) {
				$this->template_cache[ $template_name ] = $cached_path;
				return $cached_path;
			}
			wp_cache_delete( $cache_key, self::CACHE_GROUP );
		}

		$template_path = $this->find_template( $template_name );

		wp_cache_set( $cache_key, $template_path, self::CACHE_GROUP, self::CACHE_EXPIRY );
		$this->template_cache[ $template_name ] = $template_path;

		return $template_path;
	}

	/**
	 * Find a template file in theme or plugin directories.
	 *
	 * @param string $template_name Template name without .php extension.
	 * @return string|null Template file path or null if not found.
	 */
	private function find_template( $template_name ) {
		$template_file = $template_name . '.php';
		$theme_path    = self::TEMPLATE_SUBDIR . $template_file;

		$template_path = locate_template( array( $theme_path ) );

		if ( ! $template_path ) {
			$plugin_template_path = $this->plugin_dir . 'templates/' . $template_file;

			if ( file_exists( $plugin_template_path ) ) {
				$template_path = $plugin_template_path;
			}
		}

		if ( $template_path && $this->validate_template_file( $template_path ) ) {
			return $template_path;
		}

		return null;
	}

	/**
	 * Validate that a template file is safe to include.
	 *
	 * @param string $path Template file path to validate.
	 * @return bool True if valid and safe to include.
	 */
	private function validate_template_file( $path ) {
		if ( ! file_exists( $path ) ) {
			return false;
		}

		if ( ! is_readable( $path ) ) {
			$this->log_security_event( 'Template not readable: ' . $path );
			return false;
		}

		if ( 'php' !== pathinfo( $path, PATHINFO_EXTENSION ) ) {
			$this->log_security_event( 'Invalid template extension: ' . $path );
			return false;
		}

		$allowed_dirs = array(
			get_stylesheet_directory(),
			get_template_directory(),
			$this->plugin_dir,
		);

		$real_path  = realpath( $path );
		$is_allowed = false;

		foreach ( $allowed_dirs as $allowed_dir ) {
			$real_allowed_dir = realpath( $allowed_dir );
			if ( $real_allowed_dir && 0 === strpos( $real_path, $real_allowed_dir ) ) {
				$is_allowed = true;
				break;
			}
		}

		if ( ! $is_allowed ) {
			$this->log_security_event( 'Template outside allowed directories: ' . $path );
			return false;
		}

		return true;
	}

	/**
	 * Render a template with given arguments.
	 *
	 * @param string $template_path Path to template file or template name.
	 * @param array  $template_args Template arguments/variables.
	 * @return string Rendered template output.
	 */
	public function render( $template_path, array $template_args = array() ) {
		// If template_path doesn't exist as a file, try to locate it as a template name.
		if ( ! file_exists( $template_path ) ) {
			$located = $this->locate_template( $template_path );
			if ( $located ) {
				$template_path = $located;
			}
		}

		$view = new ViewData( $template_args );

		/**
		 * Get attribute helper for templates.
		 *
		 * This function allows template files to define their own default values
		 * for attributes. When a shortcode attribute is not provided (empty string),
		 * the template's default value will be used.
		 *
		 * @param string $key           Attribute key to retrieve.
		 * @param mixed  $default_value Default value if attribute is not set or empty.
		 * @return mixed The attribute value or default.
		 */
		$get_att = function ( $key, $default_value = '' ) use ( $view ) {
			$value = $view->get( $key );
			// Use the template's default if the value is empty or not set.
			if ( null === $value || '' === $value ) {
				return $default_value;
			}
			return $value;
		};

		// Make core template variables available.
		$template     = $view->get( 'template', 'default' );
		$content      = $view->get( 'content', '' );
		$custom_class = $view->get( 'custom_class', '' );

		// Also make view available as $data for backwards compatibility.
		$data = $view;

		ob_start();

		do_action( 'cta_highlights_before_template_include', $template_path, $view );

		// Verify file exists before including to prevent errors.
		if ( file_exists( $template_path ) && is_readable( $template_path ) ) {
			include $template_path;
		} else {
			$this->log_security_event( 'Template file not accessible: ' . $template_path );
		}

		do_action( 'cta_highlights_after_template_include', $template_path, $view );

		return ob_get_clean();
	}

	/**
	 * Clear the template cache.
	 *
	 * @return void
	 */
	public function clear_cache() {
		wp_cache_flush_group( self::CACHE_GROUP );
		$this->template_cache = array();

		do_action( 'cta_highlights_template_cache_cleared' );
	}

	/**
	 * Check if a string contains path traversal characters.
	 *
	 * @param string $string String to check.
	 * @return bool True if path traversal detected.
	 */
	private function contains_path_traversal( $string ) {
		// Check for common path traversal patterns.
		$patterns = array(
			'../',
			'..\\',
			'..%2f',
			'..%5c',
			'%2e%2e/',
			'%2e%2e\\',
		);

		$lower_string = strtolower( $string );

		foreach ( $patterns as $pattern ) {
			if ( false !== strpos( $lower_string, $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Log a security event for debugging.
	 *
	 * @param string $message Security event message.
	 * @return void
	 */
	private function log_security_event( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging for security events.
			error_log( "CTA Highlights Security: {$message}" );
		}

		do_action( 'cta_highlights_security_event', $message );
	}

	/**
	 * Get all available templates from plugin and theme.
	 *
	 * @return array List of templates with their metadata, keyed by template name.
	 */
	public function get_all_templates() {
		$templates = array();

		$plugin_templates_dir = $this->plugin_dir . 'templates/';
		if ( is_dir( $plugin_templates_dir ) ) {
			$plugin_files = glob( $plugin_templates_dir . '*.php' );
			if ( is_array( $plugin_files ) ) {
				foreach ( $plugin_files as $file ) {
					$template_name               = basename( $file, '.php' );
					$templates[ $template_name ] = array(
						'name'     => $template_name,
						'path'     => $file,
						'location' => 'plugin',
					);
				}
			}
		}

		$theme_templates_dir = get_stylesheet_directory() . '/' . self::TEMPLATE_SUBDIR;
		if ( is_dir( $theme_templates_dir ) ) {
			$theme_files = glob( $theme_templates_dir . '*.php' );
			if ( is_array( $theme_files ) ) {
				foreach ( $theme_files as $file ) {
					$template_name               = basename( $file, '.php' );
					$templates[ $template_name ] = array(
						'name'     => $template_name,
						'path'     => $file,
						'location' => 'theme',
					);
				}
			}
		}

		// Return associative array keyed by template name.
		return $templates;
	}
}
