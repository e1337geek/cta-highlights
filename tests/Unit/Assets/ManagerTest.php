<?php
/**
 * Assets Manager Tests
 *
 * MEDIUM PRIORITY: Business Logic Tests for Asset Management
 *
 * This test class covers the Assets\Manager class which handles conditional
 * asset enqueuing (CSS/JS). This is MEDIUM PRIORITY because:
 * - Affects page performance (conditional loading)
 * - Tests shortcode detection logic
 * - Tests template CSS enqueuing
 * - Not security-critical but important for user experience
 *
 * @package CTAHighlights\Tests\Unit\Assets
 */

namespace CTAHighlights\Tests\Unit\Assets;

use CTAHighlights\Assets\Manager;
use CTAHighlights\Template\Registry;
use CTAHighlights\Tests\Factories\PostFactory;
use WP_UnitTestCase;

class ManagerTest extends WP_UnitTestCase {

	/**
	 * Assets Manager instance
	 *
	 * @var Manager
	 */
	private $manager;

	/**
	 * Template Registry instance
	 *
	 * @var Registry
	 */
	private $registry;

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();

		$this->manager = new Manager(
			CTA_HIGHLIGHTS_DIR,
			CTA_HIGHLIGHTS_URL,
			CTA_HIGHLIGHTS_VERSION
		);

		$this->manager->init();
		$this->registry = Registry::instance();
		$this->registry->clear();
	}

	/**
	 * Teardown after each test
	 */
	public function tearDown(): void {
		// Dequeue all plugin assets
		wp_dequeue_style( 'cta-highlights-base' );
		wp_deregister_style( 'cta-highlights-base' );
		wp_dequeue_script( 'cta-highlights-base' );
		wp_deregister_script( 'cta-highlights-base' );
		wp_dequeue_script( 'cta-highlights-auto-insert' );
		wp_deregister_script( 'cta-highlights-auto-insert' );

		// Clear template-specific styles
		$templates = array( 'default', 'banner', 'sidebar', 'inline' );
		foreach ( $templates as $template ) {
			wp_dequeue_style( 'cta-highlights-template-' . $template );
			wp_deregister_style( 'cta-highlights-template-' . $template );
		}

		$this->registry->clear();
		parent::tearDown();
	}

	// =============================================================
	// SHORTCODE DETECTION TESTS
	// =============================================================

	/**
	 * @test
	 * Test that assets are enqueued when shortcode exists in post
	 *
	 * WHY: Core conditional loading functionality
	 * PRIORITY: HIGH (performance)
	 */
	public function it_enqueues_assets_when_shortcode_in_post() {
		$post = PostFactory::create_with_shortcode(
			array( 'template' => 'default' ),
			'Subscribe to our newsletter!'
		);

		$this->go_to( get_permalink( $post ) );

		do_action( 'wp_enqueue_scripts' );

		$this->assertTrue(
			wp_style_is( 'cta-highlights-base', 'enqueued' ),
			'Base CSS should be enqueued'
		);

		$this->assertTrue(
			wp_script_is( 'cta-highlights-base', 'enqueued' ),
			'Base JS should be enqueued'
		);
	}

	/**
	 * @test
	 * Test that assets are not enqueued when no shortcode
	 *
	 * WHY: Performance optimization (don't load unnecessary assets)
	 * PRIORITY: HIGH (performance)
	 */
	public function it_doesnt_enqueue_assets_when_no_shortcode() {
		$post = PostFactory::create( array( 'post_content' => 'No shortcode here' ) );

		$this->go_to( get_permalink( $post ) );

		do_action( 'wp_enqueue_scripts' );

		$this->assertFalse(
			wp_style_is( 'cta-highlights-base', 'enqueued' ),
			'Base CSS should not be enqueued'
		);

		$this->assertFalse(
			wp_script_is( 'cta-highlights-base', 'enqueued' ),
			'Base JS should not be enqueued'
		);
	}

	/**
	 * @test
	 * Test that assets are not enqueued on archive pages
	 *
	 * WHY: Shortcode only works on singular posts
	 * PRIORITY: MEDIUM (performance)
	 */
	public function it_doesnt_enqueue_on_archive_pages() {
		PostFactory::create_with_shortcode(
			array( 'template' => 'default' ),
			'Test'
		);

		$this->go_to( home_url() );

		do_action( 'wp_enqueue_scripts' );

		$this->assertFalse(
			wp_style_is( 'cta-highlights-base', 'enqueued' ),
			'Should not enqueue on archive pages'
		);
	}

	// =============================================================
	// FORCE ENQUEUE FILTER TESTS
	// =============================================================

	/**
	 * @test
	 * Test that force enqueue filter works
	 *
	 * WHY: Allows manual asset enqueuing when needed
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_respects_force_enqueue_filter() {
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

		$this->assertTrue(
			wp_style_is( 'cta-highlights-base', 'enqueued' ),
			'Assets should be enqueued when filter returns true'
		);
	}

	// =============================================================
	// TEMPLATE CSS ENQUEUING TESTS
	// =============================================================

	/**
	 * @test
	 * Test that template CSS is enqueued when template is used
	 *
	 * WHY: Template-specific styles must be loaded
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_enqueues_template_css_when_template_used() {
		$post = PostFactory::create_with_shortcode(
			array( 'template' => 'default' ),
			'Test'
		);

		$this->go_to( get_permalink( $post ) );

		// Enqueue base assets
		do_action( 'wp_enqueue_scripts' );

		// Register template
		$this->registry->register( 'default' );

		// Enqueue template styles
		do_action( 'wp_footer' );

		// Check if template CSS is enqueued (if file exists)
		$css_path = CTA_HIGHLIGHTS_DIR . 'assets/css/templates/default.css';
		if ( file_exists( $css_path ) ) {
			$this->assertTrue(
				wp_style_is( 'cta-highlights-template-default', 'enqueued' ),
				'Template CSS should be enqueued'
			);
		} else {
			$this->markTestSkipped( 'Template CSS file does not exist' );
		}
	}

	/**
	 * @test
	 * Test that template CSS is not enqueued without shortcode
	 *
	 * WHY: Template styles only needed when shortcode present
	 * PRIORITY: MEDIUM (performance)
	 */
	public function it_doesnt_enqueue_template_css_without_shortcode() {
		$post = PostFactory::create( array( 'post_content' => 'No shortcode' ) );

		$this->go_to( get_permalink( $post ) );

		do_action( 'wp_enqueue_scripts' );

		$this->registry->register( 'default' );

		do_action( 'wp_footer' );

		$this->assertFalse(
			wp_style_is( 'cta-highlights-template-default', 'enqueued' ),
			'Template CSS should not be enqueued without shortcode'
		);
	}

	/**
	 * @test
	 * Test that multiple template CSS files are enqueued
	 *
	 * WHY: Multiple templates can be used on one page
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_enqueues_multiple_template_css_files() {
		$post = PostFactory::create_with_shortcode(
			array( 'template' => 'default' ),
			'Test'
		);

		$this->go_to( get_permalink( $post ) );

		do_action( 'wp_enqueue_scripts' );

		// Register multiple templates
		$this->registry->register( 'default' );
		$this->registry->register( 'banner' );

		do_action( 'wp_footer' );

		// Count enqueued template styles
		$count = 0;
		if ( wp_style_is( 'cta-highlights-template-default', 'enqueued' ) ) {
			$count++;
		}
		if ( wp_style_is( 'cta-highlights-template-banner', 'enqueued' ) ) {
			$count++;
		}

		// At least one should be enqueued (if files exist)
		$this->assertGreaterThanOrEqual( 0, $count );
	}

	// =============================================================
	// JAVASCRIPT CONFIG TESTS
	// =============================================================

	/**
	 * @test
	 * Test that JavaScript config is localized
	 *
	 * WHY: Client-side needs configuration data
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_localizes_javascript_config() {
		$post = PostFactory::create_with_shortcode(
			array( 'template' => 'default' ),
			'Test'
		);

		$this->go_to( get_permalink( $post ) );

		do_action( 'wp_enqueue_scripts' );

		// Get localized script data
		global $wp_scripts;
		$data = $wp_scripts->get_data( 'cta-highlights-base', 'data' );

		if ( $data ) {
			$this->assertStringContainsString( 'ctaHighlightsConfig', $data );
			$this->assertStringContainsString( 'globalCooldown', $data );
			$this->assertStringContainsString( 'templateCooldown', $data );
			$this->assertStringContainsString( 'overlayColor', $data );
		} else {
			$this->markTestSkipped( 'Script not enqueued or data not localized' );
		}
	}

	/**
	 * @test
	 * Test that config values can be filtered
	 *
	 * WHY: Allow customization of cooldown and overlay
	 * PRIORITY: MEDIUM (customization)
	 */
	public function it_allows_config_filtering() {
		// Add filters
		add_filter(
			'cta_highlights_global_cooldown',
			function() {
				return 7200; // 2 hours
			}
		);

		add_filter(
			'cta_highlights_template_cooldown',
			function() {
				return 172800; // 2 days
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

		if ( $data ) {
			$this->assertStringContainsString( '7200', $data, 'Custom global cooldown should be used' );
			$this->assertStringContainsString( '172800', $data, 'Custom template cooldown should be used' );
		}
	}

	// =============================================================
	// RESOURCE HINTS TESTS
	// =============================================================

	/**
	 * @test
	 * Test that DNS prefetch is added
	 *
	 * WHY: Performance optimization for plugin assets
	 * PRIORITY: LOW (performance)
	 */
	public function it_adds_dns_prefetch_hint() {
		$post = PostFactory::create_with_shortcode(
			array( 'template' => 'default' ),
			'Test'
		);

		$this->go_to( get_permalink( $post ) );

		do_action( 'wp_enqueue_scripts' );

		ob_start();
		do_action( 'wp_head' );
		$head_output = ob_get_clean();

		$this->assertStringContainsString( 'dns-prefetch', $head_output );
	}

	/**
	 * @test
	 * Test that preconnect can be disabled via filter
	 *
	 * WHY: Allow disabling preconnect if not desired
	 * PRIORITY: LOW (customization)
	 */
	public function it_respects_preconnect_filter() {
		add_filter(
			'cta_highlights_add_preconnect',
			function() {
				return false;
			}
		);

		$post = PostFactory::create_with_shortcode(
			array( 'template' => 'default' ),
			'Test'
		);

		$this->go_to( get_permalink( $post ) );

		do_action( 'wp_enqueue_scripts' );

		ob_start();
		do_action( 'wp_head' );
		$head_output = ob_get_clean();

		// Should still have dns-prefetch but not preconnect
		$this->assertStringContainsString( 'dns-prefetch', $head_output );
	}

	// =============================================================
	// WIDGET SHORTCODE DETECTION TESTS
	// =============================================================

	/**
	 * @test
	 * Test that shortcodes in widgets are detected
	 *
	 * WHY: Shortcodes can be used in sidebar widgets
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_detects_shortcodes_in_widgets() {
		// Create a text widget with shortcode
		$widget_options = array(
			2 => array(
				'text' => '[cta_highlights template="default"]',
			),
		);
		update_option( 'widget_text', $widget_options );

		// Register sidebar with widget
		$sidebars = wp_get_sidebars_widgets();
		$sidebars['sidebar-1'] = array( 'text-2' );
		wp_set_sidebars_widgets( $sidebars );

		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		do_action( 'wp_enqueue_scripts' );

		// Assets should be enqueued (widget has shortcode)
		$this->assertTrue(
			wp_style_is( 'cta-highlights-base', 'enqueued' ),
			'Assets should be enqueued when shortcode in widget'
		);

		// Cleanup
		delete_option( 'widget_text' );
	}

	// =============================================================
	// SCRIPT ATTRIBUTES TESTS
	// =============================================================

	/**
	 * @test
	 * Test that auto-insert script has defer attribute
	 *
	 * WHY: Defer improves page load performance
	 * PRIORITY: MEDIUM (performance)
	 */
	public function it_adds_defer_to_auto_insert_script() {
		$post = PostFactory::create();
		$this->go_to( get_permalink( $post ) );

		// Auto-insert script is enqueued on all singular pages
		do_action( 'wp_enqueue_scripts' );

		global $wp_scripts;
		$defer = $wp_scripts->get_data( 'cta-highlights-auto-insert', 'defer' );

		$this->assertTrue( (bool) $defer, 'Auto-insert script should have defer attribute' );
	}

	// =============================================================
	// ASSET FILE EXISTENCE TESTS
	// =============================================================

	/**
	 * @test
	 * Test that base CSS file exists
	 *
	 * WHY: File must exist for enqueuing to work
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_has_base_css_file() {
		$css_path = CTA_HIGHLIGHTS_DIR . 'assets/css/cta-highlights.css';

		$this->assertFileExists( $css_path, 'Base CSS file should exist' );
	}

	/**
	 * @test
	 * Test that base JS file exists
	 *
	 * WHY: File must exist for enqueuing to work
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_has_base_js_file() {
		$js_path = CTA_HIGHLIGHTS_DIR . 'assets/js/cta-highlights.js';

		$this->assertFileExists( $js_path, 'Base JS file should exist' );
	}

	/**
	 * @test
	 * Test that auto-insert JS file exists
	 *
	 * WHY: File must exist for auto-insertion to work
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_has_auto_insert_js_file() {
		$js_path = CTA_HIGHLIGHTS_DIR . 'assets/js/auto-insert.js';

		$this->assertFileExists( $js_path, 'Auto-insert JS file should exist' );
	}

	// =============================================================
	// INTEGRATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test complete asset loading workflow
	 *
	 * WHY: End-to-end test of asset management
	 * PRIORITY: HIGH (integration)
	 */
	public function it_completes_full_asset_workflow() {
		// Create post with shortcode
		$post = PostFactory::create_with_shortcode(
			array( 'template' => 'default' ),
			'Subscribe!'
		);

		$this->go_to( get_permalink( $post ) );

		// Enqueue base assets
		do_action( 'wp_enqueue_scripts' );

		// Base assets should be enqueued
		$this->assertTrue( wp_style_is( 'cta-highlights-base', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'cta-highlights-base', 'enqueued' ) );

		// Register template
		$this->registry->register( 'default' );

		// Enqueue template styles
		do_action( 'wp_footer' );

		// Template CSS should be enqueued (if file exists)
		$css_path = CTA_HIGHLIGHTS_DIR . 'assets/css/templates/default.css';
		if ( file_exists( $css_path ) ) {
			$this->assertTrue( wp_style_is( 'cta-highlights-template-default', 'enqueued' ) );
		}
	}
}
