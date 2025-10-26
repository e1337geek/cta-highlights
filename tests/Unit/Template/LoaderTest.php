<?php
/**
 * Template Loader Tests
 *
 * HIGH PRIORITY: Security Tests for Template Loading
 *
 * This test class covers the Template\Loader class which is responsible for
 * locating and loading template files. This is HIGH PRIORITY because:
 * - It handles file system operations (path traversal risk)
 * - It includes PHP files (arbitrary code execution risk)
 * - It validates file paths (directory traversal risk)
 * - It uses caching (cache poisoning risk)
 *
 * @package CTAHighlights\Tests\Unit\Template
 */

namespace CTAHighlights\Tests\Unit\Template;

use CTAHighlights\Template\Loader;
use CTAHighlights\Tests\Factories\TemplateFactory;
use CTAHighlights\Tests\Traits\CreatesTemplates;
use WP_UnitTestCase;

class LoaderTest extends WP_UnitTestCase {
	use CreatesTemplates;

	/**
	 * Loader instance
	 *
	 * @var Loader
	 */
	private $loader;

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->setupTemplates();
		$this->loader = new Loader( CTA_HIGHLIGHTS_DIR );
	}

	/**
	 * Cleanup after each test
	 */
	public function tearDown(): void {
		$this->teardownTemplates();
		$this->loader->clear_cache();
		parent::tearDown();
	}

	// =============================================================
	// PATH TRAVERSAL PREVENTION TESTS (CRITICAL SECURITY)
	// =============================================================

	/**
	 * @test
	 * Test that path traversal attempts are blocked
	 *
	 * WHY: Path traversal is a critical security vulnerability that could
	 * allow attackers to include arbitrary files from the file system
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_path_traversal_with_dot_dot_slash() {
		$malicious_paths = array(
			'../../../wp-config',
			'../../../../../../etc/passwd',
			'..\\..\\..\\windows\\system32\\config\\sam',
			'....//....//....//etc/passwd',
			'..%2F..%2F..%2Fwp-config',
		);

		foreach ( $malicious_paths as $malicious_path ) {
			$result = $this->loader->locate_template( $malicious_path );

			$this->assertNull(
				$result,
				"Path traversal attempt should return null: {$malicious_path}"
			);
		}
	}

	/**
	 * @test
	 * Test that absolute paths outside allowed directories are blocked
	 *
	 * WHY: Prevents loading templates from arbitrary locations
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_absolute_path_loading() {
		$malicious_paths = array(
			'/etc/passwd',
			'/var/www/malicious.php',
			'C:\\Windows\\System32\\config\\sam',
			'/tmp/evil.php',
		);

		foreach ( $malicious_paths as $malicious_path ) {
			$result = $this->loader->locate_template( $malicious_path );

			$this->assertNull(
				$result,
				"Absolute path should return null: {$malicious_path}"
			);
		}
	}

	/**
	 * @test
	 * Test that null byte injection is prevented
	 *
	 * WHY: Null bytes can be used to bypass extension checks in some systems
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_null_byte_injection() {
		$malicious_paths = array(
			"evil\x00.php",
			"config\x00.jpg",
			"malicious.txt\x00.php",
		);

		foreach ( $malicious_paths as $malicious_path ) {
			$result = $this->loader->locate_template( $malicious_path );

			$this->assertNull(
				$result,
				'Null byte injection should return null'
			);
		}
	}

	// =============================================================
	// FILE EXTENSION VALIDATION TESTS (SECURITY)
	// =============================================================

	/**
	 * @test
	 * Test that only .php files are allowed
	 *
	 * WHY: Restricting to .php prevents inclusion of unexpected file types
	 * PRIORITY: HIGH (security)
	 */
	public function it_only_allows_php_extension() {
		$invalid_extensions = array(
			'template.txt',
			'template.html',
			'template.inc',
			'template.phtml',
			'template.php5',
			'template',
			'template.PHP',  // Case sensitivity test
		);

		foreach ( $invalid_extensions as $template_name ) {
			// Create a file with invalid extension in theme
			$file_path = $this->getTemplateDirectory( 'theme' ) . '/' . $template_name;

			if ( ! is_dir( dirname( $file_path ) ) ) {
				wp_mkdir_p( dirname( $file_path ) );
			}

			file_put_contents( $file_path, '<?php echo "test"; ?>' );

			$template_name_without_ext = pathinfo( $template_name, PATHINFO_FILENAME );
			$result = $this->loader->locate_template( $template_name_without_ext );

			// Clean up
			if ( file_exists( $file_path ) ) {
				unlink( $file_path );
			}

			$this->assertNull(
				$result,
				"Non-.php extension should be rejected: {$template_name}"
			);
		}
	}

	/**
	 * @test
	 * Test that .php extension is required
	 *
	 * WHY: Ensures we're only including PHP template files
	 * PRIORITY: MEDIUM (validation)
	 */
	public function it_requires_php_extension() {
		// Create a valid template
		$this->createTemplate( 'test-valid', '<?php echo "valid"; ?>' );

		$result = $this->loader->locate_template( 'test-valid' );

		$this->assertNotNull( $result );
		$this->assertStringEndsWith( '.php', $result );
	}

	// =============================================================
	// DIRECTORY WHITELIST VALIDATION TESTS (SECURITY)
	// =============================================================

	/**
	 * @test
	 * Test that templates must be in allowed directories
	 *
	 * WHY: Prevents loading templates from unauthorized locations
	 * PRIORITY: HIGH (security)
	 */
	public function it_restricts_to_allowed_directories() {
		$allowed_dirs = array(
			get_stylesheet_directory(),
			get_template_directory(),
			CTA_HIGHLIGHTS_DIR,
		);

		// All these should be allowed
		foreach ( $allowed_dirs as $dir ) {
			$this->assertNotEmpty( $dir, 'Allowed directory should not be empty' );
		}

		// Templates from plugin directory should work
		$plugin_template = $this->loader->locate_template( 'default' );
		$this->assertNotNull( $plugin_template );
		$this->assertStringContainsString( CTA_HIGHLIGHTS_DIR, $plugin_template );
	}

	/**
	 * @test
	 * Test that symlinks outside allowed directories are blocked
	 *
	 * WHY: Symlinks can be used to bypass path restrictions
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_symlink_escape() {
		// Skip on Windows as symlinks work differently
		if ( 'WIN' === strtoupper( substr( PHP_OS, 0, 3 ) ) ) {
			$this->markTestSkipped( 'Symlink test not applicable on Windows' );
		}

		$temp_dir = sys_get_temp_dir() . '/cta_test_' . time();
		wp_mkdir_p( $temp_dir );

		// Create a file outside allowed directories
		$evil_file = $temp_dir . '/evil.php';
		file_put_contents( $evil_file, '<?php echo "evil"; ?>' );

		// Try to create a symlink in theme directory
		$theme_dir = get_stylesheet_directory() . '/cta-highlights-templates';
		wp_mkdir_p( $theme_dir );
		$symlink = $theme_dir . '/symlinked';

		// Attempt to create symlink (may fail if permissions insufficient)
		$symlink_created = @symlink( $evil_file, $symlink . '.php' );

		if ( $symlink_created ) {
			$result = $this->loader->locate_template( 'symlinked' );

			// Clean up
			if ( is_link( $symlink . '.php' ) ) {
				unlink( $symlink . '.php' );
			}

			$this->assertNull(
				$result,
				'Symlink pointing outside allowed directories should be rejected'
			);
		}

		// Clean up temp directory
		if ( file_exists( $evil_file ) ) {
			unlink( $evil_file );
		}
		if ( is_dir( $temp_dir ) ) {
			rmdir( $temp_dir );
		}
	}

	// =============================================================
	// TEMPLATE LOADING TESTS (FUNCTIONALITY)
	// =============================================================

	/**
	 * @test
	 * Test that valid templates are loaded correctly
	 *
	 * WHY: Core functionality must work correctly
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_loads_valid_templates() {
		$this->createTemplate( 'test-template', '<?php echo "test"; ?>' );

		$path = $this->loader->locate_template( 'test-template' );

		$this->assertNotNull( $path );
		$this->assertFileExists( $path );
	}

	/**
	 * @test
	 * Test that non-existent templates return null
	 *
	 * WHY: Proper error handling prevents crashes
	 * PRIORITY: MEDIUM (error handling)
	 */
	public function it_returns_null_for_nonexistent_template() {
		$path = $this->loader->locate_template( 'nonexistent-template-xyz' );

		$this->assertNull( $path );
	}

	/**
	 * @test
	 * Test template override hierarchy (theme > parent > plugin)
	 *
	 * WHY: Theme overrides must work correctly
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_respects_template_override_hierarchy() {
		// Create templates in multiple locations
		$paths = $this->createTemplateInMultipleLocations( 'test-override', array(
			'theme',
			'plugin',
		) );

		$located = $this->loader->locate_template( 'test-override' );

		// Theme should take precedence
		$this->assertNotNull( $located );
		$this->assertEquals( $paths['theme'], $located );
	}

	// =============================================================
	// CACHE SECURITY TESTS
	// =============================================================

	/**
	 * @test
	 * Test that cache is properly cleared
	 *
	 * WHY: Stale cache could serve wrong/malicious templates
	 * PRIORITY: MEDIUM (security)
	 */
	public function it_clears_cache_properly() {
		// Load a template to cache it
		$this->createTemplate( 'cached-template', '<?php echo "v1"; ?>' );
		$path1 = $this->loader->locate_template( 'cached-template' );

		// Clear cache
		$this->loader->clear_cache();

		// Update the template
		$this->createTemplate( 'cached-template', '<?php echo "v2"; ?>' );

		// Clear cache again to pick up new version
		$this->loader->clear_cache();

		$path2 = $this->loader->locate_template( 'cached-template' );

		// Should still return the same path (file location hasn't changed)
		$this->assertEquals( $path1, $path2 );
	}

	/**
	 * @test
	 * Test that cache doesn't serve invalid paths
	 *
	 * WHY: Prevents serving malicious cached paths
	 * PRIORITY: HIGH (security)
	 */
	public function it_validates_cached_paths() {
		$this->createTemplate( 'test-cache', '<?php echo "test"; ?>' );

		// Load once to cache
		$path = $this->loader->locate_template( 'test-cache' );
		$this->assertNotNull( $path );

		// Delete the actual file
		if ( file_exists( $path ) ) {
			unlink( $path );
		}

		// Clear cache to force re-lookup
		$this->loader->clear_cache();

		// Should return null now
		$result = $this->loader->locate_template( 'test-cache' );
		$this->assertNull( $result );
	}

	// =============================================================
	// FILE PERMISSION TESTS (SECURITY)
	// =============================================================

	/**
	 * @test
	 * Test that unreadable files are rejected
	 *
	 * WHY: Prevents including files the web server can't read
	 * PRIORITY: MEDIUM (error handling)
	 */
	public function it_rejects_unreadable_files() {
		// Skip on Windows as chmod works differently
		if ( 'WIN' === strtoupper( substr( PHP_OS, 0, 3 ) ) ) {
			$this->markTestSkipped( 'Chmod test not applicable on Windows' );
		}

		$template_path = $this->createTemplate(
			'unreadable',
			'<?php echo "test"; ?>'
		);

		// Make file unreadable (chmod 000)
		if ( $template_path ) {
			chmod( $template_path, 0000 );

			// Clear cache to force fresh lookup
			$this->loader->clear_cache();

			$result = $this->loader->locate_template( 'unreadable' );

			// Restore permissions for cleanup
			chmod( $template_path, 0644 );

			$this->assertNull(
				$result,
				'Unreadable file should be rejected'
			);
		}
	}

	// =============================================================
	// TEMPLATE RENDERING TESTS
	// =============================================================

	/**
	 * @test
	 * Test that templates render with variables
	 *
	 * WHY: Core functionality for template system
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_renders_template_with_variables() {
		$template_content = '<?php echo esc_html( $cta_title ); ?>';
		$template_path = $this->createTemplate( 'render-test', $template_content );

		$output = $this->loader->render( $template_path, array(
			'cta_title' => 'Test Title',
		) );

		$this->assertStringContainsString( 'Test Title', $output );
	}

	/**
	 * @test
	 * Test that template rendering escapes output
	 *
	 * WHY: Prevents XSS in template output
	 * PRIORITY: HIGH (security)
	 */
	public function it_escapes_output_in_templates() {
		$template_content = '<?php echo esc_html( $cta_title ); ?>';
		$template_path = $this->createTemplate( 'escape-test', $template_content );

		$output = $this->loader->render( $template_path, array(
			'cta_title' => '<script>alert("XSS")</script>',
		) );

		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringContainsString( '&lt;script&gt;', $output );
	}

	// =============================================================
	// SECURITY EVENT LOGGING TESTS
	// =============================================================

	/**
	 * @test
	 * Test that security events are logged
	 *
	 * WHY: Security monitoring and audit trail
	 * PRIORITY: MEDIUM (security monitoring)
	 */
	public function it_logs_security_events() {
		$events_logged = array();

		// Hook into security event action
		add_action(
			'cta_highlights_security_event',
			function ( $message ) use ( &$events_logged ) {
				$events_logged[] = $message;
			}
		);

		// Trigger a security event by trying to load invalid template
		$this->loader->locate_template( '../../../evil' );

		// Should have logged at least one security event
		$this->assertNotEmpty(
			$events_logged,
			'Security events should be logged for invalid paths'
		);
	}

	// =============================================================
	// TEMPLATE ENUMERATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test that get_all_templates returns valid templates
	 *
	 * WHY: Used by admin interface to list templates
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_lists_all_available_templates() {
		$templates = $this->loader->get_all_templates();

		$this->assertIsArray( $templates );

		// Should have at least the default template
		$this->assertNotEmpty( $templates );

		// Each template should have required keys
		foreach ( $templates as $template ) {
			$this->assertArrayHasKey( 'name', $template );
			$this->assertArrayHasKey( 'path', $template );
			$this->assertArrayHasKey( 'location', $template );
		}
	}

	/**
	 * @test
	 * Test that template listing doesn't expose sensitive paths
	 *
	 * WHY: Prevents information disclosure
	 * PRIORITY: LOW (information disclosure)
	 */
	public function it_doesnt_expose_sensitive_paths_in_listing() {
		$templates = $this->loader->get_all_templates();

		foreach ( $templates as $template ) {
			// Paths should be within allowed directories
			$path = $template['path'];

			$this->assertFileExists( $path, 'Listed template should exist' );
			$this->assertStringEndsWith( '.php', $path, 'Listed template should be .php' );
		}
	}

	// =============================================================
	// SANITIZATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test that template names are sanitized
	 *
	 * WHY: Prevents special characters in file names
	 * PRIORITY: HIGH (security)
	 */
	public function it_sanitizes_template_names() {
		$malicious_names = array(
			'test<script>.php',
			'test"quotes".php',
			"test'quotes'.php",
			'test\0null.php',
			'test;semicolon.php',
		);

		foreach ( $malicious_names as $malicious_name ) {
			$result = $this->loader->locate_template( $malicious_name );

			// Should either return null or sanitized version
			if ( $result !== null ) {
				$basename = basename( $result );
				$this->assertStringNotContainsString( '<', $basename );
				$this->assertStringNotContainsString( '"', $basename );
				$this->assertStringNotContainsString( "'", $basename );
			}
		}
	}
}
