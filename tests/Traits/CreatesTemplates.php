<?php
/**
 * Creates Templates Trait
 *
 * Provides helper methods for creating and managing template files
 * during tests. Use this trait to test template loading, overrides,
 * and rendering.
 *
 * @package CTAHighlights\Tests\Traits
 */

namespace CTAHighlights\Tests\Traits;

use CTAHighlights\Tests\Factories\TemplateFactory;

trait CreatesTemplates {

	/**
	 * Template files created during test
	 *
	 * @var array
	 */
	protected $created_template_files = array();

	/**
	 * Setup templates for testing
	 * Call this in your test's setUp() method
	 */
	protected function setupTemplates() {
		$this->created_template_files = array();

		// Ensure template directories exist
		$locations = array( 'theme', 'parent', 'plugin' );
		foreach ( $locations as $location ) {
			$dir = TemplateFactory::get_directory( $location );
			if ( $dir && ! file_exists( $dir ) ) {
				wp_mkdir_p( $dir );
			}
		}
	}

	/**
	 * Cleanup templates after testing
	 * Call this in your test's tearDown() method
	 */
	protected function teardownTemplates() {
		$this->cleanupCreatedTemplates();
	}

	/**
	 * Create a test template
	 *
	 * @param string $name Template name.
	 * @param string $content Template content.
	 * @param string $location Location: 'theme', 'parent', or 'plugin'.
	 * @return string|false Template file path or false.
	 */
	protected function createTemplate( $name, $content = '', $location = 'theme' ) {
		$path = TemplateFactory::create( $name, $content, $location );

		if ( $path !== false ) {
			$this->created_template_files[] = $path;
		}

		return $path;
	}

	/**
	 * Create a minimal test template
	 *
	 * @param string $name Template name.
	 * @param string $location Location.
	 * @return string|false Template file path or false.
	 */
	protected function createMinimalTemplate( $name, $location = 'theme' ) {
		return $this->createTemplate( $name, TemplateFactory::create_minimal( $name, $location ), $location );
	}

	/**
	 * Create template with CSS
	 *
	 * @param string $name Template name.
	 * @param string $template_content Template content.
	 * @param string $css_content CSS content.
	 * @param string $location Location.
	 * @return array|false Array with template and CSS paths or false.
	 */
	protected function createTemplateWithCSS( $name, $template_content = '', $css_content = '', $location = 'theme' ) {
		$paths = TemplateFactory::create_with_css( $name, $template_content, $css_content, $location );

		if ( $paths !== false ) {
			$this->created_template_files[] = $paths['template'];
			$this->created_template_files[] = $paths['css'];
		}

		return $paths;
	}

	/**
	 * Create template in multiple locations (for override testing)
	 *
	 * @param string $name Template name.
	 * @param array $locations Locations to create in.
	 * @return array Created file paths by location.
	 */
	protected function createTemplateInMultipleLocations( $name, $locations = array( 'theme', 'parent', 'plugin' ) ) {
		$paths = TemplateFactory::create_in_multiple_locations( $name, $locations );

		foreach ( $paths as $path ) {
			$this->created_template_files[] = $path;
		}

		return $paths;
	}

	/**
	 * Create an invalid template (for error testing)
	 *
	 * @param string $name Template name.
	 * @param string $type Type of invalid template.
	 * @param string $location Location.
	 * @return string|false Template file path or false.
	 */
	protected function createInvalidTemplate( $name, $type = 'syntax_error', $location = 'theme' ) {
		$path = TemplateFactory::create_invalid( $name, $type, $location );

		if ( $path !== false ) {
			$this->created_template_files[] = $path;
		}

		return $path;
	}

	/**
	 * Clean up all created templates
	 */
	protected function cleanupCreatedTemplates() {
		TemplateFactory::cleanup();
		$this->created_template_files = array();

		// Clear template cache
		if ( function_exists( 'cta_highlights_clear_cache' ) ) {
			cta_highlights_clear_cache();
		}
	}

	/**
	 * Assert that template file exists
	 *
	 * @param string $name Template name.
	 * @param string $location Location.
	 * @param string $message Optional failure message.
	 */
	protected function assertTemplateExists( $name, $location = 'theme', $message = '' ) {
		$exists = TemplateFactory::exists( $name, $location );

		if ( empty( $message ) ) {
			$message = "Failed asserting that template '{$name}' exists in '{$location}' location";
		}

		$this->assertTrue( $exists, $message );
	}

	/**
	 * Assert that template file does not exist
	 *
	 * @param string $name Template name.
	 * @param string $location Location.
	 * @param string $message Optional failure message.
	 */
	protected function assertTemplateDoesNotExist( $name, $location = 'theme', $message = '' ) {
		$exists = TemplateFactory::exists( $name, $location );

		if ( empty( $message ) ) {
			$message = "Failed asserting that template '{$name}' does not exist in '{$location}' location";
		}

		$this->assertFalse( $exists, $message );
	}

	/**
	 * Get template directory path
	 *
	 * @param string $location Location.
	 * @return string|false Directory path or false.
	 */
	protected function getTemplateDirectory( $location = 'theme' ) {
		return TemplateFactory::get_directory( $location );
	}

	/**
	 * Assert that a template was loaded from expected location
	 *
	 * @param string $output Rendered output.
	 * @param string $location Expected location.
	 * @param string $message Optional failure message.
	 */
	protected function assertTemplateLoadedFrom( $output, $location, $message = '' ) {
		if ( empty( $message ) ) {
			$message = "Failed asserting that template was loaded from '{$location}'";
		}

		$this->assertStringContainsString( "from-{$location}", $output, $message );
	}

	/**
	 * Render a template and return output
	 *
	 * @param string $template Template name.
	 * @param array $args Template arguments.
	 * @return string Rendered output.
	 */
	protected function renderTemplate( $template, $args = array() ) {
		ob_start();
		cta_highlights_render_template( $template, $args, true );
		return ob_get_clean();
	}

	/**
	 * Assert that template renders without errors
	 *
	 * @param string $template Template name.
	 * @param array $args Template arguments.
	 * @param string $message Optional failure message.
	 */
	protected function assertTemplateRendersWithoutErrors( $template, $args = array(), $message = '' ) {
		$output = $this->renderTemplate( $template, $args );

		if ( empty( $message ) ) {
			$message = "Template '{$template}' failed to render without errors";
		}

		// Check for PHP errors or warnings in output
		$this->assertStringNotContainsString( 'Fatal error', $output, $message );
		$this->assertStringNotContainsString( 'Warning:', $output, $message );
		$this->assertStringNotContainsString( 'Notice:', $output, $message );
	}

	/**
	 * Assert that template output contains expected HTML
	 *
	 * @param string $expected_html Expected HTML snippet.
	 * @param string $template Template name.
	 * @param array $args Template arguments.
	 * @param string $message Optional failure message.
	 */
	protected function assertTemplateContains( $expected_html, $template, $args = array(), $message = '' ) {
		$output = $this->renderTemplate( $template, $args );

		if ( empty( $message ) ) {
			$message = "Failed asserting that template '{$template}' output contains expected HTML";
		}

		$this->assertStringContainsString( $expected_html, $output, $message );
	}

	/**
	 * Get template loader instance
	 *
	 * @return \CTAHighlights\Template\Loader
	 */
	protected function getTemplateLoader() {
		return cta_highlights_get_template_loader();
	}

	/**
	 * Locate a template file
	 *
	 * @param string $template_name Template name.
	 * @return string|null Template file path or null.
	 */
	protected function locateTemplate( $template_name ) {
		$loader = $this->getTemplateLoader();
		return $loader->locate_template( $template_name );
	}

	/**
	 * Clear template cache
	 */
	protected function clearTemplateCache() {
		if ( function_exists( 'cta_highlights_clear_cache' ) ) {
			cta_highlights_clear_cache();
		}
	}

	/**
	 * Get all available templates
	 *
	 * @return array
	 */
	protected function getAvailableTemplates() {
		if ( function_exists( 'cta_highlights_get_templates' ) ) {
			return cta_highlights_get_templates();
		}

		return array();
	}
}
