<?php
/**
 * Shortcode Handler Tests
 *
 * HIGH PRIORITY: Security Tests for Shortcode Processing
 *
 * This test class covers the Shortcode\Handler class which processes
 * user-provided shortcode attributes. This is HIGH PRIORITY because:
 * - It handles user input (XSS risk)
 * - It outputs HTML to the page (injection risk)
 * - It processes shortcode content (XSS risk)
 * - It builds CSS classes and data attributes (injection risk)
 *
 * @package CTAHighlights\Tests\Unit\Shortcode
 */

namespace CTAHighlights\Tests\Unit\Shortcode;

use CTAHighlights\Shortcode\Handler;
use CTAHighlights\Template\Loader;
use CTAHighlights\Tests\Factories\TemplateFactory;
use CTAHighlights\Tests\Traits\CreatesShortcodes;
use CTAHighlights\Tests\Traits\CreatesTemplates;
use CTAHighlights\Tests\Traits\AssertsHTML;
use WP_UnitTestCase;

class HandlerTest extends WP_UnitTestCase {
	use CreatesShortcodes;
	use CreatesTemplates;
	use AssertsHTML;

	/**
	 * Handler instance
	 *
	 * @var Handler
	 */
	private $handler;

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->setupTemplates();

		// Ensure default template exists
		if ( ! file_exists( CTA_HIGHLIGHTS_DIR . 'templates/default.php' ) ) {
			$this->createTemplate( 'default', '', 'plugin' );
		}

		$loader = new Loader( CTA_HIGHLIGHTS_DIR );
		$this->handler = new Handler( $loader );
		$this->handler->init();
	}

	/**
	 * Cleanup after each test
	 */
	public function tearDown(): void {
		$this->teardownTemplates();
		parent::tearDown();
	}

	// =============================================================
	// XSS PREVENTION TESTS (CRITICAL SECURITY)
	// =============================================================

	/**
	 * @test
	 * Test that XSS attempts in cta_title are escaped
	 *
	 * WHY: Title is output directly to HTML, must be escaped
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_xss_in_cta_title() {
		$this->assertShortcodeSanitizesXSS(
			'<script>alert("XSS")</script>',
			'cta_title'
		);
	}

	/**
	 * @test
	 * Test that XSS attempts in custom_class are escaped
	 *
	 * WHY: CSS classes are output to HTML class attribute
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_xss_in_custom_class() {
		$output = $this->renderShortcode(array(
			'template' => 'default',
			'custom_class' => '"><script>alert("XSS")</script><div class="',
		));

		$this->assertHTMLIsEscaped( $output );
		$this->assertStringNotContainsString( '<script>', $output );
	}

	/**
	 * @test
	 * Test that XSS attempts in cta_button_url are escaped
	 *
	 * WHY: URLs can contain javascript: protocol
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_xss_in_button_url() {
		$xss_urls = array(
			'javascript:alert("XSS")',
			'data:text/html,<script>alert("XSS")</script>',
			'vbscript:msgbox("XSS")',
			'onclick=alert("XSS")//https://example.com',
		);

		foreach ( $xss_urls as $xss_url ) {
			$output = $this->renderShortcode(array(
				'cta_button_url' => $xss_url,
			));

			$this->assertStringNotContainsString( 'javascript:', $output );
			$this->assertStringNotContainsString( 'vbscript:', $output );
			$this->assertStringNotContainsString( 'onclick=', $output );
		}
	}

	/**
	 * @test
	 * Test that XSS attempts in shortcode content are escaped
	 *
	 * WHY: Content is output to page and could contain malicious code
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_xss_in_content() {
		$xss_content = '<script>alert("XSS")</script><img src=x onerror="alert(\'XSS\')">';

		$output = $this->renderShortcode(
			array( 'template' => 'default' ),
			$xss_content
		);

		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringNotContainsString( 'onerror=', $output );
	}

	/**
	 * @test
	 * Test that XSS attempts in template name are sanitized
	 *
	 * WHY: Template name is used in file operations and CSS classes
	 * PRIORITY: HIGH (security)
	 */
	public function it_prevents_xss_in_template_name() {
		$xss_template = '<script>alert("XSS")</script>';

		$output = $this->renderShortcode(array(
			'template' => $xss_template,
		));

		// Should show error since sanitized template won't exist
		// Or return empty if not admin
		$this->assertStringNotContainsString( '<script>', $output );
	}

	/**
	 * @test
	 * Test that HTML event handlers are stripped or escaped
	 *
	 * WHY: Event handlers can execute JavaScript
	 * PRIORITY: HIGH (security)
	 */
	public function it_strips_html_event_handlers() {
		$malicious_atts = array(
			'cta_title' => 'Test" onload="alert(\'XSS\')" data-foo="',
			'custom_class' => 'test" onclick="alert(\'XSS\')" class="',
		);

		$output = $this->renderShortcode( $malicious_atts );

		// Event handlers should be escaped (quotes converted to &quot;) or removed entirely
		// Check that the dangerous patterns are neutralized
		$this->assertStringNotContainsString( 'onload="alert', $output );
		$this->assertStringNotContainsString( 'onclick="alert', $output );
		$this->assertStringNotContainsString( 'onerror="alert', $output );

		// Verify escaping is working - quotes should be HTML entities
		$this->assertStringContainsString( '&quot;', $output );
	}

	// =============================================================
	// INPUT SANITIZATION TESTS (SECURITY)
	// =============================================================

	/**
	 * @test
	 * Test that template names are sanitized
	 *
	 * WHY: Template names are used in file operations
	 * PRIORITY: HIGH (security)
	 */
	public function it_sanitizes_template_names() {
		$invalid_templates = array(
			'../../../etc/passwd',
			'..\\..\\..\\windows\\system32',
			'template/with/slashes',
			'template\\with\\backslashes',
			'template.php.txt',
		);

		foreach ( $invalid_templates as $invalid_template ) {
			$output = $this->renderShortcode(array(
				'template' => $invalid_template,
			));

			// Should either be empty or show error (for admins)
			// Should not contain path traversal
			$this->assertStringNotContainsString( '..', $output );
			$this->assertStringNotContainsString( '/etc/', $output );
		}
	}

	/**
	 * @test
	 * Test that CSS classes are sanitized
	 *
	 * WHY: Prevents CSS injection and XSS
	 * PRIORITY: HIGH (security)
	 */
	public function it_sanitizes_css_classes() {
		$malicious_classes = array(
			'<script>',
			'test"><script>alert("XSS")</script>',
			'test{background:url(javascript:alert(1))}',
			'test;color:red',
		);

		foreach ( $malicious_classes as $malicious_class ) {
			$output = $this->renderShortcode(array(
				'custom_class' => $malicious_class,
			));

			$this->assertHTMLIsEscaped( $output );
			$this->assertStringNotContainsString( '<script>', $output );
			$this->assertStringNotContainsString( 'javascript:', $output );
		}
	}

	/**
	 * @test
	 * Test that numeric values are sanitized
	 *
	 * WHY: Prevents injection through numeric fields
	 * PRIORITY: MEDIUM (validation)
	 */
	public function it_sanitizes_numeric_values() {
		$output = $this->renderShortcode(array(
			'highlight' => 'true',
			'highlight_duration' => '-5',  // Negative number
		));

		// Should convert to positive or use default
		$this->assertStringContainsString( 'data-duration="', $output );

		// Extract duration value
		preg_match( '/data-duration="(\d+)"/', $output, $matches );

		if ( ! empty( $matches[1] ) ) {
			$duration = (int) $matches[1];
			$this->assertGreaterThanOrEqual( 0, $duration, 'Duration should be non-negative' );
		}
	}

	/**
	 * @test
	 * Test that SQL injection attempts are sanitized
	 *
	 * WHY: Even though shortcodes don't directly interact with DB,
	 * ensuring they can't be used for SQL injection is important
	 * PRIORITY: MEDIUM (defense in depth)
	 */
	public function it_sanitizes_sql_injection_attempts() {
		$sql_injection = "'; DROP TABLE wp_posts; --";

		$output = $this->renderShortcode(array(
			'cta_title' => $sql_injection,
		));

		// The cta_title is sanitized with sanitize_text_field which strips tags and some chars
		// Then it's output with esc_html in the template which escapes HTML entities
		// The SQL string itself will appear in output (it's not executable in HTML context)
		// but the dangerous characters should be escaped
		$this->assertStringContainsString( 'DROP TABLE', $output ); // Content is preserved
		$this->assertStringContainsString( '&#039;', $output ); // Quote is escaped when rendered
	}

	// =============================================================
	// SHORTCODE RENDERING TESTS (FUNCTIONALITY)
	// =============================================================

	/**
	 * @test
	 * Test that shortcode renders with default attributes
	 *
	 * WHY: Core functionality must work
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_renders_with_default_attributes() {
		$output = $this->renderShortcode();

		$this->assertNotEmpty( $output );
		$this->assertHTMLHasElement( $output, 'section' );
		$this->assertHTMLElementHasClass( $output, 'section', 'cta-highlights-wrapper' );
	}

	/**
	 * @test
	 * Test that shortcode renders with custom attributes
	 *
	 * WHY: Users need to customize CTAs
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_renders_with_custom_attributes() {
		$output = $this->renderShortcode(array(
			'cta_title' => 'Custom Title',
			'cta_button_text' => 'Click Here',
			'custom_class' => 'my-custom-class',
		));

		$this->assertStringContainsString( 'Custom Title', $output );
		$this->assertStringContainsString( 'my-custom-class', $output );
	}

	/**
	 * @test
	 * Test that shortcode processes nested shortcodes
	 *
	 * WHY: WordPress shortcodes can be nested
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_processes_nested_shortcodes() {
		// Register a test shortcode
		add_shortcode( 'test_shortcode', function() {
			return 'NESTED_CONTENT';
		} );

		$output = $this->renderShortcode(
			array( 'template' => 'default' ),
			'[test_shortcode]'
		);

		$this->assertStringContainsString( 'NESTED_CONTENT', $output );

		// Clean up
		remove_shortcode( 'test_shortcode' );
	}

	/**
	 * @test
	 * Test that shortcode balances unbalanced tags
	 *
	 * WHY: Prevents breaking page layout
	 * PRIORITY: MEDIUM (functionality)
	 */
	public function it_balances_unbalanced_tags() {
		$unbalanced = '<div><p>Test content';

		$output = $this->renderShortcode(
			array( 'template' => 'default' ),
			$unbalanced
		);

		// Should have closing tags
		$this->assertStringContainsString( '</p>', $output );
		$this->assertStringContainsString( '</div>', $output );
	}

	// =============================================================
	// HIGHLIGHT FEATURE TESTS
	// =============================================================

	/**
	 * @test
	 * Test that highlight adds correct classes and attributes
	 *
	 * WHY: Highlight feature requires specific HTML structure
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_adds_highlight_classes_and_attributes() {
		$output = $this->renderShortcode(array(
			'highlight' => 'true',
			'highlight_duration' => '10',
		));

		$this->assertHTMLElementHasClass( $output, 'section', 'cta-highlights-enabled' );
		$this->assertStringContainsString( 'data-highlight="true"', $output );
		$this->assertStringContainsString( 'data-duration="10"', $output );
	}

	/**
	 * @test
	 * Test that highlight adds ARIA attributes
	 *
	 * WHY: Accessibility requirement for modal-like behavior
	 * PRIORITY: HIGH (accessibility)
	 */
	public function it_adds_aria_attributes_for_highlight() {
		$output = $this->renderShortcode(array(
			'highlight' => 'true',
		));

		$this->assertStringContainsString( 'role="dialog"', $output );
		$this->assertStringContainsString( 'aria-modal="false"', $output );
		$this->assertStringContainsString( 'aria-labelledby=', $output );
	}

	/**
	 * @test
	 * Test that non-highlighted CTAs have aria-label
	 *
	 * WHY: Accessibility for screen readers
	 * PRIORITY: MEDIUM (accessibility)
	 */
	public function it_adds_aria_label_for_non_highlighted() {
		$output = $this->renderShortcode(array(
			'highlight' => 'false',
		));

		$this->assertStringContainsString( 'aria-label="Call to Action"', $output );
	}

	// =============================================================
	// ERROR HANDLING TESTS
	// =============================================================

	/**
	 * @test
	 * Test that missing templates show error to admins
	 *
	 * WHY: Helps admins debug template issues
	 * PRIORITY: MEDIUM (usability)
	 */
	public function it_shows_error_to_admins_for_missing_template() {
		// Make current user an admin
		$admin_id = wp_insert_user(array(
			'user_login' => 'testadmin',
			'user_pass' => 'password',
			'role' => 'administrator',
		));

		wp_set_current_user( $admin_id );

		// Temporarily rename default template so fallback fails
		$default_template = CTA_HIGHLIGHTS_DIR . 'templates/default.php';
		$temp_name = CTA_HIGHLIGHTS_DIR . 'templates/default.php.bak';

		if ( file_exists( $default_template ) ) {
			rename( $default_template, $temp_name );
		}

		$output = $this->renderShortcode(array(
			'template' => 'nonexistent-template',
		));

		// When nonexistent-template is not found, Handler falls back to 'default'
		// Since default is also missing, it shows error for 'default'
		$this->assertStringContainsString( 'CTA Highlights Error', $output );
		$this->assertStringContainsString( 'default', $output );

		// Restore default template
		if ( file_exists( $temp_name ) ) {
			rename( $temp_name, $default_template );
		}
	}

	/**
	 * @test
	 * Test that missing templates return empty for non-admins
	 *
	 * WHY: Don't expose errors to regular visitors
	 * PRIORITY: HIGH (security)
	 */
	public function it_returns_empty_for_missing_template_non_admin() {
		// Set as subscriber (non-admin)
		$user_id = wp_insert_user(array(
			'user_login' => 'testuser',
			'user_pass' => 'password',
			'role' => 'subscriber',
		));

		wp_set_current_user( $user_id );

		// Temporarily rename default template so fallback fails
		$default_template = CTA_HIGHLIGHTS_DIR . 'templates/default.php';
		$temp_name = CTA_HIGHLIGHTS_DIR . 'templates/default.php.bak';

		if ( file_exists( $default_template ) ) {
			rename( $default_template, $temp_name );
		}

		$output = $this->renderShortcode(array(
			'template' => 'nonexistent-template',
		));

		$this->assertEmpty( $output );

		// Restore default template
		if ( file_exists( $temp_name ) ) {
			rename( $temp_name, $default_template );
		}
	}

	// =============================================================
	// TEMPLATE INTEGRATION TESTS
	// =============================================================

	/**
	 * @test
	 * Test that template receives all attributes
	 *
	 * WHY: Templates need access to all shortcode data
	 * PRIORITY: HIGH (functionality)
	 */
	public function it_passes_all_attributes_to_template() {
		// Create a template that outputs all attributes
		$template_content = '<?php
			echo "TITLE:" . esc_html( $view->get( "cta_title" ) );
			echo "BUTTON:" . esc_html( $view->get( "cta_button_text" ) );
		?>';

		$this->createTemplate( 'test-atts', $template_content, 'plugin' );

		$output = $this->renderShortcode(array(
			'template' => 'test-atts',
			'cta_title' => 'Test Title',
			'cta_button_text' => 'Test Button',
		));

		$this->assertStringContainsString( 'TITLE:Test Title', $output );
		$this->assertStringContainsString( 'BUTTON:Test Button', $output );
	}

	// =============================================================
	// FILTER HOOK TESTS
	// =============================================================

	/**
	 * @test
	 * Test that cta_highlights_shortcode_atts filter works
	 *
	 * WHY: Allows developers to modify attributes
	 * PRIORITY: MEDIUM (extensibility)
	 */
	public function it_applies_shortcode_atts_filter() {
		add_filter( 'cta_highlights_shortcode_atts', function( $atts ) {
			$atts['cta_title'] = 'FILTERED_TITLE';
			return $atts;
		} );

		$output = $this->renderShortcode(array(
			'cta_title' => 'Original Title',
		));

		$this->assertStringContainsString( 'FILTERED_TITLE', $output );
		$this->assertStringNotContainsString( 'Original Title', $output );

		// Clean up
		remove_all_filters( 'cta_highlights_shortcode_atts' );
	}

	/**
	 * @test
	 * Test that cta_highlights_template_output filter works
	 *
	 * WHY: Allows developers to modify final output
	 * PRIORITY: MEDIUM (extensibility)
	 */
	public function it_applies_template_output_filter() {
		add_filter( 'cta_highlights_template_output', function( $output ) {
			return $output . '<!-- FILTERED -->';
		} );

		$output = $this->renderShortcode();

		$this->assertStringContainsString( '<!-- FILTERED -->', $output );

		// Clean up
		remove_all_filters( 'cta_highlights_template_output' );
	}

	// =============================================================
	// ATTRIBUTE ALIAS TESTS
	// =============================================================

	/**
	 * @test
	 * Test that attribute aliases work correctly
	 *
	 * WHY: Provides convenience aliases for common attributes
	 * PRIORITY: LOW (usability)
	 */
	public function it_supports_attribute_aliases() {
		// cta_text is alias for cta_title
		// cta_button is alias for cta_button_text
		// cta_link is alias for cta_button_url

		$output = $this->renderShortcode(array(
			'cta_text' => 'Alias Title',
			'cta_button' => 'Alias Button',
			'cta_link' => 'https://example.com',
		));

		$this->assertStringContainsString( 'Alias Title', $output );
		$this->assertStringContainsString( 'Alias Button', $output );
	}

	// =============================================================
	// WRAPPER HTML STRUCTURE TESTS
	// =============================================================

	/**
	 * @test
	 * Test that wrapper uses section element
	 *
	 * WHY: Semantic HTML for better accessibility
	 * PRIORITY: MEDIUM (accessibility)
	 */
	public function it_uses_semantic_section_element() {
		$output = $this->renderShortcode();

		$this->assertHTMLHasElement( $output, 'section' );
		$this->assertStringStartsWith( '<section', $output );
		$this->assertStringEndsWith( '</section>', $output );
	}

	/**
	 * @test
	 * Test that template-specific class is added
	 *
	 * WHY: Allows CSS targeting of specific templates
	 * PRIORITY: LOW (styling)
	 */
	public function it_adds_template_specific_class() {
		$this->createTemplate( 'custom-template', '<?php echo "test"; ?>', 'plugin' );

		$output = $this->renderShortcode(array(
			'template' => 'custom-template',
		));

		$this->assertHTMLElementHasClass(
			$output,
			'section',
			'cta-highlights-template-custom-template'
		);
	}
}
