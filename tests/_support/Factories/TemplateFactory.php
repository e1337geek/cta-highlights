<?php
/**
 * Template Factory for Testing
 *
 * Creates temporary template files for testing template loading,
 * override hierarchy, and template rendering.
 *
 * @package CTAHighlights\Tests
 */

namespace CTAHighlights\Tests\Factories;

class TemplateFactory {

	/**
	 * Temporary template files created during tests
	 *
	 * @var array
	 */
	private static $created_files = array();

	/**
	 * Template locations
	 *
	 * @var array
	 */
	private static $locations = array();

	/**
	 * Initialize template locations
	 */
	private static function init() {
		if ( empty( self::$locations ) ) {
			self::$locations = array(
				'theme'      => get_stylesheet_directory() . '/cta-highlights-templates',
				'parent'     => get_template_directory() . '/cta-highlights-templates',
				'plugin'     => CTA_HIGHLIGHTS_DIR . 'templates',
			);
		}
	}

	/**
	 * Create a temporary template file
	 *
	 * @param string $name Template name (without .php extension).
	 * @param string $content Template content.
	 * @param string $location Where to create: 'theme', 'parent', or 'plugin'.
	 * @return string|false Template file path or false on failure.
	 */
	public static function create( $name, $content = '', $location = 'theme' ) {
		self::init();

		if ( ! isset( self::$locations[ $location ] ) ) {
			return false;
		}

		$dir = self::$locations[ $location ];

		// Create directory if it doesn't exist
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$file_path = $dir . '/' . $name . '.php';

		// Use default content if none provided
		if ( empty( $content ) ) {
			$content = self::get_default_template_content( $name );
		}

		// Write file
		$result = file_put_contents( $file_path, $content );

		if ( $result !== false ) {
			self::$created_files[] = $file_path;
			return $file_path;
		}

		return false;
	}

	/**
	 * Create a template with CSS file
	 *
	 * @param string $name Template name (without .php extension).
	 * @param string $template_content Template PHP content.
	 * @param string $css_content CSS content.
	 * @param string $location Where to create.
	 * @return array|false Array with 'template' and 'css' paths or false.
	 */
	public static function create_with_css( $name, $template_content = '', $css_content = '', $location = 'theme' ) {
		self::init();

		$template_path = self::create( $name, $template_content, $location );

		if ( $template_path === false ) {
			return false;
		}

		$dir = self::$locations[ $location ];
		$css_path = $dir . '/' . $name . '.css';

		if ( empty( $css_content ) ) {
			$css_content = self::get_default_css_content( $name );
		}

		$result = file_put_contents( $css_path, $css_content );

		if ( $result !== false ) {
			self::$created_files[] = $css_path;

			return array(
				'template' => $template_path,
				'css'      => $css_path,
			);
		}

		return false;
	}

	/**
	 * Create a minimal valid template
	 *
	 * @param string $name Template name.
	 * @param string $location Where to create.
	 * @return string|false Template file path or false.
	 */
	public static function create_minimal( $name, $location = 'theme' ) {
		$content = <<<'PHP'
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="test-template">
	<h3><?php echo esc_html( $cta_title ); ?></h3>
	<div><?php echo wp_kses_post( $cta_content ); ?></div>
</div>
PHP;

		return self::create( $name, $content, $location );
	}

	/**
	 * Create an invalid template (for security testing)
	 *
	 * @param string $name Template name.
	 * @param string $type Type of invalid: 'no_closing_tag', 'syntax_error', 'xss'.
	 * @param string $location Where to create.
	 * @return string|false Template file path or false.
	 */
	public static function create_invalid( $name, $type = 'syntax_error', $location = 'theme' ) {
		$content = '';

		switch ( $type ) {
			case 'no_closing_tag':
				$content = '<?php echo "Missing closing tag";';
				break;

			case 'syntax_error':
				$content = '<?php $broken = ; ?>';
				break;

			case 'xss':
				$content = '<script>alert("XSS")</script><?php echo $_GET["xss"]; ?>';
				break;

			default:
				$content = '<?php syntax error here';
		}

		return self::create( $name, $content, $location );
	}

	/**
	 * Create a template that uses custom attributes
	 *
	 * @param string $name Template name.
	 * @param array $custom_atts Custom attribute names to use.
	 * @param string $location Where to create.
	 * @return string|false Template file path or false.
	 */
	public static function create_with_custom_attributes( $name, $custom_atts, $location = 'theme' ) {
		$attr_output = '';

		foreach ( $custom_atts as $att ) {
			$attr_output .= "\t<div class=\"custom-{$att}\"><?php echo esc_html( \$get_att( '{$att}', 'default' ) ); ?></div>\n";
		}

		$content = <<<PHP
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="custom-template">
{$attr_output}
</div>
PHP;

		return self::create( $name, $content, $location );
	}

	/**
	 * Create a template in multiple locations (for override testing)
	 *
	 * @param string $name Template name.
	 * @param array $locations Array of locations to create in.
	 * @return array Array of created file paths.
	 */
	public static function create_in_multiple_locations( $name, $locations = array( 'theme', 'parent', 'plugin' ) ) {
		$files = array();

		foreach ( $locations as $location ) {
			$content = self::get_location_specific_content( $name, $location );
			$file = self::create( $name, $content, $location );

			if ( $file !== false ) {
				$files[ $location ] = $file;
			}
		}

		return $files;
	}

	/**
	 * Get default template content
	 *
	 * @param string $name Template name.
	 * @return string Template content.
	 */
	private static function get_default_template_content( $name ) {
		return <<<'PHP'
<?php
/**
 * Test Template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cta-template">
	<?php if ( ! empty( $cta_title ) ) : ?>
		<h3 class="cta-title"><?php echo esc_html( $cta_title ); ?></h3>
	<?php endif; ?>

	<?php if ( ! empty( $cta_content ) ) : ?>
		<div class="cta-content"><?php echo wp_kses_post( $cta_content ); ?></div>
	<?php endif; ?>

	<?php if ( ! empty( $cta_button_text ) && ! empty( $cta_button_url ) ) : ?>
		<a href="<?php echo esc_url( $cta_button_url ); ?>" class="cta-button">
			<?php echo esc_html( $cta_button_text ); ?>
		</a>
	<?php endif; ?>
</div>
PHP;
	}

	/**
	 * Get default CSS content
	 *
	 * @param string $name Template name.
	 * @return string CSS content.
	 */
	private static function get_default_css_content( $name ) {
		return <<<CSS
.cta-template {
	padding: 2rem;
	background: #f5f5f5;
	border: 1px solid #ddd;
}

.cta-title {
	margin: 0 0 1rem;
	font-size: 1.5rem;
}

.cta-content {
	margin: 0 0 1rem;
}

.cta-button {
	display: inline-block;
	padding: 0.5rem 1rem;
	background: #0073aa;
	color: #fff;
	text-decoration: none;
}
CSS;
	}

	/**
	 * Get location-specific content (for override testing)
	 *
	 * @param string $name Template name.
	 * @param string $location Location identifier.
	 * @return string Template content with location marker.
	 */
	private static function get_location_specific_content( $name, $location ) {
		return <<<PHP
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="test-template from-{$location}">
	<p>Loaded from: {$location}</p>
	<h3><?php echo esc_html( \$cta_title ); ?></h3>
</div>
PHP;
	}

	/**
	 * Clean up all created template files
	 *
	 * @return int Number of files deleted.
	 */
	public static function cleanup() {
		$deleted = 0;

		foreach ( self::$created_files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
				$deleted++;
			}
		}

		// Remove empty directories
		foreach ( self::$locations as $dir ) {
			if ( file_exists( $dir ) && count( glob( $dir . '/*' ) ) === 0 ) {
				rmdir( $dir );
			}
		}

		self::$created_files = array();

		// Clear template cache
		if ( function_exists( 'cta_highlights_clear_cache' ) ) {
			cta_highlights_clear_cache();
		}

		return $deleted;
	}

	/**
	 * Get all created file paths
	 *
	 * @return array
	 */
	public static function get_created_files() {
		return self::$created_files;
	}

	/**
	 * Check if a template file exists in a location
	 *
	 * @param string $name Template name.
	 * @param string $location Location to check.
	 * @return bool
	 */
	public static function exists( $name, $location = 'theme' ) {
		self::init();

		if ( ! isset( self::$locations[ $location ] ) ) {
			return false;
		}

		$file_path = self::$locations[ $location ] . '/' . $name . '.php';
		return file_exists( $file_path );
	}

	/**
	 * Get template directory path for a location
	 *
	 * @param string $location Location identifier.
	 * @return string|false Directory path or false.
	 */
	public static function get_directory( $location = 'theme' ) {
		self::init();

		return self::$locations[ $location ] ?? false;
	}
}
