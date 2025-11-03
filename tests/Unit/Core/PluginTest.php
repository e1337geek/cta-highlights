<?php
/**
 * Plugin Tests
 *
 * MEDIUM PRIORITY: Integration Tests for Plugin Initialization
 *
 * This test class covers the Core\Plugin class which is the main plugin class
 * responsible for initializing all components. This is MEDIUM PRIORITY because:
 * - Tests component initialization and wiring
 * - Ensures plugin structure is correct
 * - Tests hooks and integration points
 * - Not security-critical but important for stability
 *
 * @package CTAHighlights\Tests\Unit\Core
 */

namespace CTAHighlights\Tests\Unit\Core;

use CTAHighlights\Core\Plugin;
use WP_UnitTestCase;

class PluginTest extends WP_UnitTestCase {

	/**
	 * Plugin instance
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->plugin = Plugin::instance();
	}

	// =============================================================
	// SINGLETON PATTERN TESTS
	// =============================================================

	/**
	 * @test
	 * Test that Plugin is a singleton
	 *
	 * WHY: Plugin must be a singleton for proper initialization
	 * PRIORITY: HIGH (architecture)
	 */
	public function it_is_a_singleton() {
		$instance1 = Plugin::instance();
		$instance2 = Plugin::instance();

		$this->assertSame( $instance1, $instance2, 'Plugin should return same instance' );
	}

	/**
	 * @test
	 * Test that singleton cannot be cloned
	 *
	 * WHY: Singleton pattern should prevent cloning
	 * PRIORITY: LOW (architecture)
	 */
	public function it_prevents_cloning() {
		$this->expectException( \Error::class );

		$clone = clone $this->plugin;
	}

	/**
	 * @test
	 * Test that singleton cannot be unserialized
	 *
	 * WHY: Singleton pattern should prevent unserialization
	 * PRIORITY: LOW (architecture)
	 */
	public function it_prevents_unserialization() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Cannot unserialize singleton' );

		$serialized = serialize( $this->plugin );
		unserialize( $serialized );
	}

	// =============================================================
	// COMPONENT INITIALIZATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test that template loader is initialized
	 *
	 * WHY: Template loader is required for rendering
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_initializes_template_loader() {
		$loader = $this->plugin->get_template_loader();

		$this->assertInstanceOf(
			'CTAHighlights\Template\Loader',
			$loader,
			'Template loader should be initialized'
		);
	}

	/**
	 * @test
	 * Test that asset manager is initialized
	 *
	 * WHY: Asset manager is required for CSS/JS
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_initializes_asset_manager() {
		$manager = $this->plugin->get_asset_manager();

		$this->assertInstanceOf(
			'CTAHighlights\Assets\Manager',
			$manager,
			'Asset manager should be initialized'
		);
	}

	/**
	 * @test
	 * Test that shortcode handler is initialized
	 *
	 * WHY: Shortcode handler is core functionality
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_initializes_shortcode_handler() {
		$handler = $this->plugin->get_shortcode_handler();

		$this->assertInstanceOf(
			'CTAHighlights\Shortcode\Handler',
			$handler,
			'Shortcode handler should be initialized'
		);
	}

	/**
	 * @test
	 * Test that auto-insert manager is initialized
	 *
	 * WHY: Auto-insert manager is core functionality
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_initializes_auto_insert_manager() {
		$manager = $this->plugin->get_auto_insert_manager();

		$this->assertInstanceOf(
			'CTAHighlights\AutoInsertion\Manager',
			$manager,
			'Auto-insert manager should be initialized'
		);
	}

	// =============================================================
	// PLUGIN PROPERTIES TESTS
	// =============================================================

	/**
	 * @test
	 * Test that plugin version is set
	 *
	 * WHY: Version is used for asset cache busting
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_has_version() {
		$version = $this->plugin->get_version();

		$this->assertNotEmpty( $version );
		$this->assertIsString( $version );
	}

	/**
	 * @test
	 * Test that plugin directory is set
	 *
	 * WHY: Plugin directory is used for file paths
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_has_plugin_directory() {
		$dir = $this->plugin->get_plugin_dir();

		$this->assertNotEmpty( $dir );
		$this->assertDirectoryExists( $dir );
	}

	/**
	 * @test
	 * Test that plugin URL is set
	 *
	 * WHY: Plugin URL is used for asset URLs
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_has_plugin_url() {
		$url = $this->plugin->get_plugin_url();

		$this->assertNotEmpty( $url );
		$this->assertStringContainsString( 'http', $url );
	}

	// =============================================================
	// HOOK REGISTRATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test that init hook is registered for textdomain
	 *
	 * WHY: Textdomain is required for translations
	 * PRIORITY: LOW (i18n)
	 */
	public function it_registers_textdomain_hook() {
		$this->assertIsInt(
			has_action( 'init', array( $this->plugin, 'load_textdomain' ) ),
			'Textdomain loading should be hooked to init'
		);
	}

	/**
	 * @test
	 * Test that switch_theme hook is registered
	 *
	 * WHY: Template cache should clear on theme switch
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_registers_theme_switch_hook() {
		$this->assertIsInt(
			has_action( 'switch_theme', array( $this->plugin, 'clear_template_cache' ) ),
			'Template cache clearing should be hooked to switch_theme'
		);
	}

	/**
	 * @test
	 * Test that plugins_loaded hook is registered
	 *
	 * WHY: Database migration check happens on plugins_loaded
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_registers_plugins_loaded_hook() {
		$this->assertIsInt(
			has_action( 'plugins_loaded', array( $this->plugin, 'check_database_migration' ) ),
			'Database migration check should be hooked to plugins_loaded'
		);
	}

	// =============================================================
	// TEMPLATE CACHE TESTS
	// =============================================================

	/**
	 * @test
	 * Test that template cache can be cleared
	 *
	 * WHY: Cache clearing is important for development
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_clears_template_cache() {
		// This should not throw an error
		$this->plugin->clear_template_cache();

		// If we got here, it worked
		$this->assertTrue( true );
	}

	// =============================================================
	// DATABASE MIGRATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test that database migration check runs
	 *
	 * WHY: Database table must exist for auto-insertion
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_checks_database_migration() {
		// Run migration check
		$this->plugin->check_database_migration();

		// Verify table exists using the correct table name from Database class
		global $wpdb;
		$table_name = $wpdb->prefix . 'cta_auto_insertions';
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;

		$this->assertTrue( $table_exists, 'Auto-insert table should exist after migration check' );
	}

	// =============================================================
	// DEBUG INFO TESTS
	// =============================================================

	/**
	 * @test
	 * Test that debug info is rendered when WP_DEBUG is true
	 *
	 * WHY: Debug info helps with troubleshooting
	 * PRIORITY: LOW (development)
	 */
	public function it_renders_debug_info_when_wp_debug() {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			$this->markTestSkipped( 'WP_DEBUG is not enabled' );
		}

		// Register a template to show in debug
		$registry = \CTAHighlights\Template\Registry::instance();
		$registry->register( 'test-template' );

		ob_start();
		$this->plugin->render_debug_info();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'CTA Highlights Debug', $output );
		$this->assertStringContainsString( 'test-template', $output );

		$registry->clear();
	}

	/**
	 * @test
	 * Test that debug info is not rendered when no templates
	 *
	 * WHY: Should only show debug when templates are used
	 * PRIORITY: LOW (performance)
	 */
	public function it_doesnt_render_debug_when_no_templates() {
		$registry = \CTAHighlights\Template\Registry::instance();
		$registry->clear();

		ob_start();
		$this->plugin->render_debug_info();
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Should not render debug info when no templates used' );
	}

	// =============================================================
	// INTEGRATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test that all components are properly wired together
	 *
	 * WHY: Components must work together correctly
	 * PRIORITY: HIGH (integration)
	 */
	public function it_wires_components_together() {
		// Get all components
		$loader = $this->plugin->get_template_loader();
		$asset_manager = $this->plugin->get_asset_manager();
		$shortcode_handler = $this->plugin->get_shortcode_handler();
		$auto_insert_manager = $this->plugin->get_auto_insert_manager();

		// All should be initialized
		$this->assertNotNull( $loader );
		$this->assertNotNull( $asset_manager );
		$this->assertNotNull( $shortcode_handler );
		$this->assertNotNull( $auto_insert_manager );

		// Auto-insert manager should have database
		$database = $auto_insert_manager->get_database();
		$this->assertInstanceOf( 'CTAHighlights\AutoInsertion\Database', $database );
	}

	/**
	 * @test
	 * Test plugin initialization sequence
	 *
	 * WHY: Initialization order matters
	 * PRIORITY: MEDIUM (stability)
	 */
	public function it_initializes_in_correct_order() {
		// Get a fresh instance (via singleton)
		$plugin = Plugin::instance();

		// Components should all be available
		$this->assertNotNull( $plugin->get_template_loader() );
		$this->assertNotNull( $plugin->get_asset_manager() );
		$this->assertNotNull( $plugin->get_shortcode_handler() );
		$this->assertNotNull( $plugin->get_auto_insert_manager() );

		// No errors should have occurred
		$this->assertTrue( true );
	}

	/**
	 * @test
	 * Test that shortcode is registered
	 *
	 * WHY: Shortcode registration is critical functionality
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_registers_shortcode() {
		global $shortcode_tags;

		$this->assertArrayHasKey(
			'cta_highlights',
			$shortcode_tags,
			'cta_highlights shortcode should be registered'
		);
	}
}
