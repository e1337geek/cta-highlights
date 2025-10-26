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

	public function __construct( $plugin_dir ) {
		$this->plugin_dir = trailingslashit( $plugin_dir );
	}

	public function locate_template( $template_name ) {
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

	public function render( $template_path, array $template_args = array() ) {
		$view = new ViewData( $template_args );

		$get_att = function ( $key, $default = '' ) use ( $view ) {
			return $view->get( $key, $default );
		};

		$template        = $view->get( 'template', 'default' );
		$cta_title       = $view->get( 'cta_title', '' );
		$cta_content     = $view->get( 'cta_content', '' );
		$cta_button_text = $view->get( 'cta_button_text', 'Learn More' );
		$cta_button_url  = $view->get( 'cta_button_url', '#' );
		$content         = $view->get( 'content', '' );
		$custom_class    = $view->get( 'custom_class', '' );

		ob_start();

		do_action( 'cta_highlights_before_template_include', $template_path, $view );

		include $template_path;

		do_action( 'cta_highlights_after_template_include', $template_path, $view );

		return ob_get_clean();
	}

	public function clear_cache() {
		wp_cache_flush_group( self::CACHE_GROUP );
		$this->template_cache = array();

		do_action( 'cta_highlights_template_cache_cleared' );
	}

	private function log_security_event( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "CTA Highlights Security: {$message}" );
		}

		do_action( 'cta_highlights_security_event', $message );
	}

	public function get_all_templates() {
		$templates = array();

		$plugin_templates_dir = $this->plugin_dir . 'templates/';
		if ( is_dir( $plugin_templates_dir ) ) {
			$plugin_files = glob( $plugin_templates_dir . '*.php' );
			foreach ( $plugin_files as $file ) {
				$template_name              = basename( $file, '.php' );
				$templates[ $template_name ] = array(
					'name'     => $template_name,
					'path'     => $file,
					'location' => 'plugin',
				);
			}
		}

		$theme_templates_dir = get_stylesheet_directory() . '/' . self::TEMPLATE_SUBDIR;
		if ( is_dir( $theme_templates_dir ) ) {
			$theme_files = glob( $theme_templates_dir . '*.php' );
			foreach ( $theme_files as $file ) {
				$template_name              = basename( $file, '.php' );
				$templates[ $template_name ] = array(
					'name'     => $template_name,
					'path'     => $file,
					'location' => 'theme',
				);
			}
		}

		return array_values( $templates );
	}
}
