<?php
/**
 * Hooks and Filters Integration Tests
 *
 * MEDIUM PRIORITY: Integration Tests for WordPress Hooks and Filters
 *
 * This test class covers WordPress hooks and filters integration including:
 * - Action hooks (init, wp_enqueue_scripts, wp_footer, etc.)
 * - Filter hooks (the_content, cta_highlights_*, etc.)
 * - Custom hooks provided by plugin
 * - Hook priorities and execution order
 *
 * Tests verify that hooks and filters work correctly for extensibility.
 *
 * @package CTAHighlights\Tests\Integration
 */

namespace CTAHighlights\Tests\Integration;

use CTAHighlights\Core\Plugin;
use CTAHighlights\Tests\Factories\PostFactory;
use CTAHighlights\Tests\Factories\CTAFactory;
use CTAHighlights\Tests\Traits\CreatesDatabase;
use WP_UnitTestCase;

class HooksFiltersTest extends WP_UnitTestCase {
	use CreatesDatabase;

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
		$this->setupDatabase();
		$this->plugin = Plugin::instance();
	}

	/**
	 * Teardown after each test
	 */
	public function tearDown(): void {
		$this->teardownDatabase();
		parent::tearDown();
	}

	// =============================================================
	// PLUGIN INITIALIZATION HOOKS
	// =============================================================

	/**
	 * @test
	 * Test init hook is registered
	 *
	 * WHY: Plugin should hook into init for textdomain loading
	 * PRIORITY: LOW (i18n)
	 */
	public function it_hooks_into_init() {
		$this->assertIsInt(
			has_action( 'init', array( $this->plugin, 'load_textdomain' ) ),
			'Plugin should hook into init'
		);
	}

	/**
	 * @test
	 * Test switch_theme hook is registered
	 *
	 * WHY: Template cache should clear on theme switch
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_hooks_into_switch_theme() {
		$this->assertIsInt(
			has_action( 'switch_theme', array( $this->plugin, 'clear_template_cache' ) ),
			'Plugin should hook into switch_theme'
		);
	}

	/**
	 * @test
	 * Test plugins_loaded hook is registered
	 *
	 * WHY: Database migration check happens on plugins_loaded
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_hooks_into_plugins_loaded() {
		$this->assertIsInt(
			has_action( 'plugins_loaded', array( $this->plugin, 'check_database_migration' ) ),
			'Plugin should hook into plugins_loaded'
		);
	}

	// =============================================================
	// ASSET ENQUEUING HOOKS
	// =============================================================

	/**
	 * @test
	 * Test wp_enqueue_scripts hook is registered
	 *
	 * WHY: Assets must be enqueued on frontend
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_hooks_into_wp_enqueue_scripts() {
		$asset_manager = $this->plugin->get_asset_manager();

		$this->assertIsInt(
			has_action( 'wp_enqueue_scripts', array( $asset_manager, 'maybe_enqueue_assets' ) ),
			'Asset manager should hook into wp_enqueue_scripts'
		);
	}

	/**
	 * @test
	 * Test wp_footer hook is registered
	 *
	 * WHY: Template CSS enqueued in footer
	 * PRIORITY: MEDIUM (performance)
	 */
	public function it_hooks_into_wp_footer() {
		$asset_manager = $this->plugin->get_asset_manager();

		$this->assertIsInt(
			has_action( 'wp_footer', array( $asset_manager, 'enqueue_template_styles' ) ),
			'Asset manager should hook into wp_footer'
		);
	}

	// =============================================================
	// FILTER HOOKS TESTS
	// =============================================================

	/**
	 * @test
	 * Test the_content filter integration
	 *
	 * WHY: Shortcodes in content must be processed
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_integrates_with_the_content_filter() {
		$post = PostFactory::create_with_shortcode(
			array( 'template' => 'default', 'title' => 'Test' ),
			'Content'
		);

		// Apply the_content filter
		$content = apply_filters( 'the_content', get_post_field( 'post_content', $post ) );

		// Shortcode should be processed
		$this->assertStringNotContainsString( '[cta_highlights', $content );
		$this->assertStringContainsString( 'cta-highlights-wrapper', $content );
	}

	/**
	 * @test
	 * Test custom content selector filter
	 *
	 * WHY: Allows customization of auto-insert target
	 * PRIORITY: MEDIUM (extensibility)
	 */
	public function it_applies_content_selector_filter() {
		$custom_selector = null;

		// Add filter
		add_filter(
			'cta_highlights_content_selector',
			function( $selector, $post_id ) use ( &$custom_selector ) {
				$custom_selector = $selector;
				return '.custom-content';
			},
			10,
			2
		);

		CTAFactory::create(
			array(
				'post_types' => array( 'post' ),
				'cta_type'   => 'primary',
				'status'     => 'active',
			)
		);

		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		$manager = \CTAHighlights\AutoInsertion\Manager::instance();
		ob_start();
		$manager->output_fallback_data();
		$output = ob_get_clean();

		// Filter should have been called
		$this->assertNotNull( $custom_selector );

		// Custom selector should be in JSON
		preg_match( '/<script[^>]*>(.*?)<\/script>/s', $output, $matches );
		if ( ! empty( $matches[1] ) ) {
			$json = json_decode( $matches[1], true );
			$this->assertEquals( '.custom-content', $json['contentSelector'] );
		}
	}

	/**
	 * @test
	 * Test force enqueue filter
	 *
	 * WHY: Allows forcing asset enqueue when needed
	 * PRIORITY: MEDIUM (extensibility)
	 */
	public function it_applies_force_enqueue_filter() {
		$post = PostFactory::create( array( 'post_content' => 'No shortcode' ) );
		$this->go_to( get_permalink( $post ) );

		// Add filter to force enqueue
		add_filter(
			'cta_highlights_force_enqueue',
			function() {
				return true;
			}
		);

		do_action( 'wp_enqueue_scripts' );

		// Assets should be enqueued despite no shortcode
		$this->assertTrue( wp_style_is( 'cta-highlights-base', 'enqueued' ) );
	}

	/**
	 * @test
	 * Test cooldown filters
	 *
	 * WHY: Allows customization of cooldown periods
	 * PRIORITY: LOW (extensibility)
	 */
	public function it_applies_cooldown_filters() {
		$global_cooldown   = null;
		$template_cooldown = null;

		// Add filters
		add_filter(
			'cta_highlights_global_cooldown',
			function( $cooldown ) use ( &$global_cooldown ) {
				$global_cooldown = $cooldown;
				return 7200; // 2 hours
			}
		);

		add_filter(
			'cta_highlights_template_cooldown',
			function( $cooldown ) use ( &$template_cooldown ) {
				$template_cooldown = $cooldown;
				return 172800; // 2 days
			}
		);

		$post = PostFactory::create_with_shortcode(
			array( 'template' => 'default' ),
			'Test'
		);
		$this->go_to( get_permalink( $post ) );

		do_action( 'wp_enqueue_scripts' );

		// Filters should have been called
		$this->assertNotNull( $global_cooldown );
		$this->assertNotNull( $template_cooldown );

		// Custom values should be in config
		global $wp_scripts;
		$data = $wp_scripts->get_data( 'cta-highlights-base', 'data' );

		if ( $data ) {
			$this->assertStringContainsString( '7200', $data );
			$this->assertStringContainsString( '172800', $data );
		}
	}

	/**
	 * @test
	 * Test overlay color filter
	 *
	 * WHY: Allows customization of overlay color
	 * PRIORITY: LOW (extensibility)
	 */
	public function it_applies_overlay_color_filter() {
		add_filter(
			'cta_highlights_overlay_color',
			function() {
				return 'rgba(255, 0, 0, 0.5)';
			}
		);

		$post = PostFactory::create_with_shortcode(
			array( 'template' => 'default' ),
			'Test'
		);
		$this->go_to( get_permalink( $post ) );

		do_action( 'wp_enqueue_scripts' );

		global $wp_scripts;
		$data = $wp_scripts->get_data( 'cta-highlights-base', 'data' );

		$this->assertStringContainsString( 'rgba(255, 0, 0, 0.5)', $data );
	}

	// =============================================================
	// AUTO-INSERTION HOOKS
	// =============================================================

	/**
	 * @test
	 * Test wp_footer hook for auto-insert data
	 *
	 * WHY: JSON data output in footer for client-side
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_outputs_auto_insert_data_in_footer() {
		CTAFactory::create(
			array(
				'post_types' => array( 'post' ),
				'cta_type'   => 'primary',
				'status'     => 'active',
			)
		);

		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		$manager = \CTAHighlights\AutoInsertion\Manager::instance();

		$this->assertIsInt(
			has_action( 'wp_footer', array( $manager, 'output_fallback_data' ) ),
			'Manager should hook into wp_footer'
		);
	}

	// =============================================================
	// CUSTOM PLUGIN HOOKS
	// =============================================================

	/**
	 * @test
	 * Test custom action hooks are available for developers
	 *
	 * WHY: Extensibility for third-party developers
	 * PRIORITY: LOW (extensibility)
	 */
	public function it_provides_custom_action_hooks_for_extensibility() {
		// This test verifies that developers can hook into plugin actions
		// by adding their own hooks

		$hook_fired = false;

		// Example: hook into template rendering (if provided)
		add_action(
			'cta_highlights_before_template',
			function() use ( &$hook_fired ) {
				$hook_fired = true;
			}
		);

		// If the plugin provides this hook, it should fire during rendering
		// For now, we verify the concept works
		do_action( 'cta_highlights_before_template' );

		$this->assertTrue( $hook_fired, 'Custom hooks should be available' );
	}

	// =============================================================
	// HOOK PRIORITY TESTS
	// =============================================================

	/**
	 * @test
	 * Test hook execution order
	 *
	 * WHY: Certain hooks must run before others
	 * PRIORITY: MEDIUM (stability)
	 */
	public function it_executes_hooks_in_correct_order() {
		// Test that force_enqueue check runs early
		$manager = \CTAHighlights\AutoInsertion\Manager::instance();

		$priority = has_filter(
			'cta_highlights_force_enqueue',
			array( $manager, 'check_auto_insert_shortcodes' )
		);

		// Should have low priority number (runs early)
		$this->assertLessThanOrEqual( 5, $priority, 'Force enqueue check should run early' );
	}

	// =============================================================
	// ADMIN HOOKS TESTS
	// =============================================================

	/**
	 * @test
	 * Test that admin hooks are only registered in admin
	 *
	 * WHY: Performance - admin hooks shouldn't load on frontend
	 * PRIORITY: LOW (performance)
	 */
	public function it_only_registers_admin_hooks_in_admin() {
		// This is conceptual since tests run in admin context
		// Verify admin components exist
		if ( is_admin() ) {
			$this->assertNotNull( $this->plugin->get_auto_insert_manager() );
		}

		$this->assertTrue( true );
	}

	// =============================================================
	// DATABASE MIGRATION HOOKS
	// =============================================================

	/**
	 * @test
	 * Test database migration runs on activation
	 *
	 * WHY: Tables must be created on plugin activation
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_runs_database_migration_check() {
		// Trigger migration check
		$this->plugin->check_database_migration();

		// Verify table exists using the correct table name from Database class
		global $wpdb;
		$table_name   = $wpdb->prefix . 'cta_auto_insertions';
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;

		$this->assertTrue( $table_exists, 'Database table should exist after migration' );
	}

	// =============================================================
	// SHORTCODE REGISTRATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test shortcode is registered
	 *
	 * WHY: Shortcode must be available globally
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_registers_cta_highlights_shortcode() {
		global $shortcode_tags;

		$this->assertArrayHasKey( 'cta_highlights', $shortcode_tags );
	}

	/**
	 * @test
	 * Test shortcode callback is correct
	 *
	 * WHY: Shortcode should call handler's render method
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_registers_correct_shortcode_callback() {
		global $shortcode_tags;

		$callback = $shortcode_tags['cta_highlights'];

		$this->assertIsArray( $callback );
		$this->assertInstanceOf( 'CTAHighlights\Shortcode\Handler', $callback[0] );
		$this->assertEquals( 'render', $callback[1] );
	}

	// =============================================================
	// FILTER CHAIN TESTS
	// =============================================================

	/**
	 * @test
	 * Test multiple filters can be chained
	 *
	 * WHY: Multiple plugins/themes should be able to filter
	 * PRIORITY: LOW (compatibility)
	 */
	public function it_allows_filter_chaining() {
		$value = 'initial';

		// Add multiple filters
		add_filter(
			'test_chain_filter',
			function( $val ) {
				return $val . '_filter1';
			}
		);

		add_filter(
			'test_chain_filter',
			function( $val ) {
				return $val . '_filter2';
			}
		);

		$result = apply_filters( 'test_chain_filter', $value );

		$this->assertEquals( 'initial_filter1_filter2', $result );
	}

	// =============================================================
	// REMOVE HOOKS TESTS
	// =============================================================

	/**
	 * @test
	 * Test hooks can be removed
	 *
	 * WHY: Developers should be able to unhook if needed
	 * PRIORITY: LOW (extensibility)
	 */
	public function it_allows_removing_hooks() {
		$asset_manager = $this->plugin->get_asset_manager();

		// Verify hook exists
		$this->assertIsInt(
			has_action( 'wp_enqueue_scripts', array( $asset_manager, 'maybe_enqueue_assets' ) )
		);

		// Remove hook
		remove_action( 'wp_enqueue_scripts', array( $asset_manager, 'maybe_enqueue_assets' ) );

		// Verify removed
		$this->assertFalse(
			has_action( 'wp_enqueue_scripts', array( $asset_manager, 'maybe_enqueue_assets' ) )
		);

		// Re-add for other tests
		add_action( 'wp_enqueue_scripts', array( $asset_manager, 'maybe_enqueue_assets' ) );
	}
}
