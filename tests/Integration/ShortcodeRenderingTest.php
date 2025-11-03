<?php
/**
 * Shortcode Rendering Integration Tests
 *
 * HIGH PRIORITY: Integration Tests for Full Shortcode Rendering Flow
 *
 * This test class covers the complete shortcode rendering flow including:
 * - Shortcode Handler
 * - Template Loader
 * - Template Registry
 * - Asset Manager
 * - ViewData container
 *
 * Tests verify that all components work together correctly from shortcode
 * parsing through template rendering to final HTML output.
 *
 * @package CTAHighlights\Tests\Integration
 */

namespace CTAHighlights\Tests\Integration;

use CTAHighlights\Core\Plugin;
use CTAHighlights\Tests\Factories\TemplateFactory;
use CTAHighlights\Tests\Factories\PostFactory;
use WP_UnitTestCase;

class ShortcodeRenderingTest extends WP_UnitTestCase {

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
		TemplateFactory::cleanup();
	}

	/**
	 * Teardown after each test
	 */
	public function tearDown(): void {
		TemplateFactory::cleanup();
		parent::tearDown();
	}

	// =============================================================
	// COMPLETE RENDERING FLOW TESTS
	// =============================================================

	/**
	 * @test
	 * Test complete shortcode rendering with default template
	 *
	 * WHY: Verifies entire flow from shortcode to HTML
	 * PRIORITY: HIGH (integration)
	 */
	public function it_renders_complete_shortcode_flow() {
		$post = PostFactory::create_with_shortcode(
			array(
				'template' => 'default',
				'title'    => 'Subscribe Now',
			),
			'Join our newsletter for weekly updates!'
		);

		// Render the post content
		$content = apply_filters( 'the_content', get_post_field( 'post_content', $post ) );

		// Verify shortcode was processed
		$this->assertStringNotContainsString( '[cta_highlights', $content );

		// Verify CTA wrapper exists
		$this->assertStringContainsString( 'cta-highlights-wrapper', $content );

		// Verify title is rendered
		$this->assertStringContainsString( 'Subscribe Now', $content );

		// Verify content is rendered
		$this->assertStringContainsString( 'Join our newsletter', $content );
	}

	/**
	 * @test
	 * Test shortcode with custom attributes
	 *
	 * WHY: Verifies attribute parsing and template rendering
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_renders_shortcode_with_custom_attributes() {
		$shortcode = '[cta_highlights template="default" title="Special Offer" highlight="true" duration="10"]';
		$output = do_shortcode( $shortcode );

		// Verify wrapper
		$this->assertStringContainsString( 'cta-highlights-wrapper', $output );

		// Verify highlight attribute
		$this->assertStringContainsString( 'data-highlight="true"', $output );

		// Verify duration attribute
		$this->assertStringContainsString( 'data-duration="10"', $output );

		// Verify template attribute
		$this->assertStringContainsString( 'data-template="default"', $output );
	}

	/**
	 * @test
	 * Test shortcode with content
	 *
	 * WHY: Verifies content is passed through and rendered
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_renders_shortcode_with_content() {
		$shortcode = '[cta_highlights template="default" title="Download"]Get your free ebook today![/cta_highlights]';
		$output = do_shortcode( $shortcode );

		// Verify content is in output
		$this->assertStringContainsString( 'Get your free ebook today!', $output );

		// Verify title is in output
		$this->assertStringContainsString( 'Download', $output );
	}

	// =============================================================
	// TEMPLATE LOADING INTEGRATION
	// =============================================================

	/**
	 * @test
	 * Test template loading from plugin directory
	 *
	 * WHY: Verifies template loader finds plugin templates
	 * PRIORITY: HIGH (integration)
	 */
	public function it_loads_template_from_plugin_directory() {
		$shortcode = '[cta_highlights template="default" title="Test"]Content[/cta_highlights]';
		$output = do_shortcode( $shortcode );

		// Should successfully render (template found)
		$this->assertStringContainsString( 'cta-highlights-wrapper', $output );
		$this->assertStringContainsString( 'Test', $output );
	}

	/**
	 * @test
	 * Test template loading with theme override
	 *
	 * WHY: Verifies theme templates override plugin templates
	 * PRIORITY: HIGH (integration)
	 */
	public function it_loads_template_from_theme_override() {
		$this->markTestSkipped(
			'Theme template override testing requires WordPress locate_template() which does not work in PHPUnit environment'
		);

		// Create theme override template
		$theme_template = TemplateFactory::create(
			'custom',
			'<div class="custom-theme-template" data-template="<?php echo esc_attr( $data->template ); ?>"><?php echo esc_html( $data->cta_title ); ?></div>',
			'theme'
		);

		$shortcode = '[cta_highlights template="custom" title="Theme Override"]Content[/cta_highlights]';
		$output = do_shortcode( $shortcode );

		// Verify theme template was used
		$this->assertStringContainsString( 'custom-theme-template', $output );
		$this->assertStringContainsString( 'Theme Override', $output );
	}

	/**
	 * @test
	 * Test fallback to default template when custom not found
	 *
	 * WHY: Verifies graceful fallback to default
	 * PRIORITY: MEDIUM (error handling)
	 */
	public function it_falls_back_to_default_when_template_not_found() {
		$shortcode = '[cta_highlights template="nonexistent" title="Test"]Content[/cta_highlights]';
		$output = do_shortcode( $shortcode );

		// Should still render using default template
		$this->assertStringContainsString( 'cta-highlights-wrapper', $output );
	}

	// =============================================================
	// REGISTRY INTEGRATION
	// =============================================================

	/**
	 * @test
	 * Test that template is registered after rendering
	 *
	 * WHY: Verifies registry tracks used templates for CSS loading
	 * PRIORITY: MEDIUM (performance)
	 */
	public function it_registers_template_after_rendering() {
		$registry = \CTAHighlights\Template\Registry::instance();
		$registry->clear();

		$shortcode = '[cta_highlights template="default" title="Test"]Content[/cta_highlights]';
		do_shortcode( $shortcode );

		// Template should be registered
		$this->assertTrue( $registry->is_registered( 'default' ) );
	}

	/**
	 * @test
	 * Test that multiple templates are registered
	 *
	 * WHY: Verifies multiple templates on same page are tracked
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_registers_multiple_templates() {
		$registry = \CTAHighlights\Template\Registry::instance();
		$registry->clear();

		// Create second template
		TemplateFactory::create(
			'banner',
			'<div class="banner"><?php echo esc_html( $data->cta_title ); ?></div>',
			'plugin'
		);

		$shortcode1 = '[cta_highlights template="default" title="First"]Content[/cta_highlights]';
		$shortcode2 = '[cta_highlights template="banner" title="Second"]Content[/cta_highlights]';

		do_shortcode( $shortcode1 );
		do_shortcode( $shortcode2 );

		// Both templates should be registered
		$this->assertTrue( $registry->is_registered( 'default' ) );
		$this->assertTrue( $registry->is_registered( 'banner' ) );
		$this->assertEquals( 2, $registry->count() );
	}

	// =============================================================
	// VIEWDATA INTEGRATION
	// =============================================================

	/**
	 * @test
	 * Test that ViewData passes all attributes to template
	 *
	 * WHY: Verifies data container works with template rendering
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_passes_all_attributes_to_template() {
		// Create custom template that uses various attributes
		$template_content = <<<'PHP'
<div class="test-template"
     data-title="<?php echo esc_attr( $data->cta_title ); ?>"
     data-highlight="<?php echo esc_attr( $data->highlight ); ?>"
     data-duration="<?php echo esc_attr( $data->duration ); ?>"
     data-template="<?php echo esc_attr( $data->template ); ?>">
    <?php echo wp_kses_post( $data->cta_content ); ?>
</div>
PHP;

		TemplateFactory::create( 'test-attrs', $template_content, 'plugin' );

		$shortcode = '[cta_highlights template="test-attrs" title="My Title" highlight="true" duration="15"]Test Content[/cta_highlights]';
		$output = do_shortcode( $shortcode );

		// Verify all attributes made it through
		$this->assertStringContainsString( 'data-title="My Title"', $output );
		$this->assertStringContainsString( 'data-highlight="true"', $output );
		$this->assertStringContainsString( 'data-duration="15"', $output );
		$this->assertStringContainsString( 'data-template="test-attrs"', $output );
		$this->assertStringContainsString( 'Test Content', $output );
	}

	// =============================================================
	// SANITIZATION INTEGRATION
	// =============================================================

	/**
	 * @test
	 * Test that XSS is prevented in title attribute
	 *
	 * WHY: Verifies security through entire rendering flow
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_xss_in_title() {
		$shortcode = '[cta_highlights template="default" title="<script>alert(\'XSS\')</script>Test"]Content[/cta_highlights]';
		$output = do_shortcode( $shortcode );

		// Script tags should be escaped or removed
		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringNotContainsString( 'alert(', $output );
	}

	/**
	 * @test
	 * Test that XSS is prevented in content
	 *
	 * WHY: Verifies content sanitization works
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_xss_in_content() {
		$shortcode = '[cta_highlights template="default" title="Test"]<script>alert("XSS")</script>Safe Content[/cta_highlights]';
		$output = do_shortcode( $shortcode );

		// Script tags should be removed but safe content remains
		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringContainsString( 'Safe Content', $output );
	}

	/**
	 * @test
	 * Test that safe HTML is preserved in content
	 *
	 * WHY: Verifies wp_kses_post allows safe HTML
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_preserves_safe_html_in_content() {
		$shortcode = '[cta_highlights template="default" title="Test"]<strong>Bold</strong> and <em>italic</em> text[/cta_highlights]';
		$output = do_shortcode( $shortcode );

		// Safe HTML should be preserved
		$this->assertStringContainsString( '<strong>Bold</strong>', $output );
		$this->assertStringContainsString( '<em>italic</em>', $output );
	}

	// =============================================================
	// NESTED SHORTCODE TESTS
	// =============================================================

	/**
	 * @test
	 * Test nested shortcodes in content
	 *
	 * WHY: Verifies shortcodes are processed in CTA content
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_processes_nested_shortcodes() {
		// Register a test shortcode
		add_shortcode(
			'test_nested',
			function() {
				return 'NESTED_OUTPUT';
			}
		);

		$shortcode = '[cta_highlights template="default" title="Test"]Content with [test_nested] inside[/cta_highlights]';
		$output = do_shortcode( $shortcode );

		// Nested shortcode should be processed
		$this->assertStringContainsString( 'NESTED_OUTPUT', $output );
		$this->assertStringNotContainsString( '[test_nested]', $output );

		// Cleanup
		remove_shortcode( 'test_nested' );
	}

	// =============================================================
	// ERROR HANDLING INTEGRATION
	// =============================================================

	/**
	 * @test
	 * Test rendering with missing required attributes
	 *
	 * WHY: Verifies graceful handling of incomplete shortcodes
	 * PRIORITY: MEDIUM (error handling)
	 */
	public function it_handles_missing_required_attributes() {
		// Shortcode without template (should use default)
		$shortcode = '[cta_highlights]Content[/cta_highlights]';
		$output = do_shortcode( $shortcode );

		// Should still render
		$this->assertStringContainsString( 'cta-highlights-wrapper', $output );
		$this->assertStringContainsString( 'Content', $output );
	}

	/**
	 * @test
	 * Test rendering with empty content
	 *
	 * WHY: Verifies handling of empty shortcode
	 * PRIORITY: LOW (edge case)
	 */
	public function it_handles_empty_content() {
		$shortcode = '[cta_highlights template="default" title="Empty"][/cta_highlights]';
		$output = do_shortcode( $shortcode );

		// Should render wrapper even with empty content
		$this->assertStringContainsString( 'cta-highlights-wrapper', $output );
	}

	// =============================================================
	// MULTIPLE SHORTCODES TESTS
	// =============================================================

	/**
	 * @test
	 * Test multiple shortcodes in same content
	 *
	 * WHY: Verifies multiple CTAs can coexist
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_renders_multiple_shortcodes_in_same_content() {
		$content = <<<'CONTENT'
<p>Paragraph 1</p>
[cta_highlights template="default" title="First CTA"]First content[/cta_highlights]
<p>Paragraph 2</p>
[cta_highlights template="default" title="Second CTA"]Second content[/cta_highlights]
<p>Paragraph 3</p>
CONTENT;

		$output = do_shortcode( $content );

		// Both CTAs should be rendered
		$this->assertStringContainsString( 'First CTA', $output );
		$this->assertStringContainsString( 'Second CTA', $output );
		$this->assertStringContainsString( 'First content', $output );
		$this->assertStringContainsString( 'Second content', $output );

		// Both should have wrapper
		$this->assertEquals( 2, substr_count( $output, 'cta-highlights-wrapper' ) );
	}

	// =============================================================
	// ASSET ENQUEUING INTEGRATION
	// =============================================================

	/**
	 * @test
	 * Test that assets are enqueued when shortcode present
	 *
	 * WHY: Verifies asset manager detects shortcode
	 * PRIORITY: MEDIUM (performance)
	 */
	public function it_enqueues_assets_when_shortcode_present() {
		$post = PostFactory::create_with_shortcode(
			array( 'template' => 'default' ),
			'Test content'
		);

		$this->go_to( get_permalink( $post ) );

		// Trigger asset enqueuing
		do_action( 'wp_enqueue_scripts' );

		// Base assets should be enqueued
		$this->assertTrue( wp_style_is( 'cta-highlights-base', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'cta-highlights-base', 'enqueued' ) );
	}

	// =============================================================
	// PROGRAMMATIC RENDERING TESTS
	// =============================================================

	/**
	 * @test
	 * Test programmatic template rendering helper
	 *
	 * WHY: Verifies helper function works end-to-end
	 * PRIORITY: MEDIUM (API)
	 */
	public function it_renders_template_programmatically() {
		if ( ! function_exists( 'cta_highlights_render_template' ) ) {
			$this->markTestSkipped( 'Helper function not available' );
		}

		// Pass false as third parameter to return output instead of echoing
		$output = cta_highlights_render_template(
			'default',
			array(
				'title'   => 'Programmatic Test',
				'content' => 'Programmatic content',
			),
			false
		);

		$this->assertStringContainsString( 'cta-highlights-wrapper', $output );
		$this->assertStringContainsString( 'Programmatic Test', $output );
		$this->assertStringContainsString( 'Programmatic content', $output );
	}
}
