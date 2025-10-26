<?php
/**
 * Creates Shortcodes Trait
 *
 * Provides helper methods for testing shortcodes including building
 * shortcode strings, rendering them, and making assertions about
 * the output.
 *
 * @package CTAHighlights\Tests\Traits
 */

namespace CTAHighlights\Tests\Traits;

trait CreatesShortcodes {

	/**
	 * Build a CTA shortcode string
	 *
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string Shortcode string.
	 */
	protected function buildShortcode( $atts = array(), $content = '' ) {
		$shortcode = '[cta_highlights';

		foreach ( $atts as $key => $value ) {
			$shortcode .= ' ' . $key . '="' . esc_attr( $value ) . '"';
		}

		$shortcode .= ']';

		if ( ! empty( $content ) ) {
			$shortcode .= $content;
		}

		$shortcode .= '[/cta_highlights]';

		return $shortcode;
	}

	/**
	 * Build and render a shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string Rendered HTML.
	 */
	protected function renderShortcode( $atts = array(), $content = '' ) {
		$shortcode = $this->buildShortcode( $atts, $content );
		return do_shortcode( $shortcode );
	}

	/**
	 * Build a self-closing shortcode (no content)
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode string.
	 */
	protected function buildSelfClosingShortcode( $atts = array() ) {
		$shortcode = '[cta_highlights';

		foreach ( $atts as $key => $value ) {
			$shortcode .= ' ' . $key . '="' . esc_attr( $value ) . '"';
		}

		$shortcode .= ' /]';

		return $shortcode;
	}

	/**
	 * Build a shortcode with highlight enabled
	 *
	 * @param array $atts Additional attributes.
	 * @param string $content Shortcode content.
	 * @return string Shortcode string.
	 */
	protected function buildHighlightShortcode( $atts = array(), $content = '' ) {
		$atts['highlight'] = 'true';
		return $this->buildShortcode( $atts, $content );
	}

	/**
	 * Build shortcode with custom template
	 *
	 * @param string $template Template name.
	 * @param array $atts Additional attributes.
	 * @param string $content Shortcode content.
	 * @return string Shortcode string.
	 */
	protected function buildShortcodeWithTemplate( $template, $atts = array(), $content = '' ) {
		$atts['template'] = $template;
		return $this->buildShortcode( $atts, $content );
	}

	/**
	 * Build a nested shortcode (shortcode within content)
	 *
	 * @param array $outer_atts Outer shortcode attributes.
	 * @param array $inner_atts Inner shortcode attributes.
	 * @param string $inner_content Inner shortcode content.
	 * @return string Shortcode string.
	 */
	protected function buildNestedShortcode( $outer_atts, $inner_atts, $inner_content = '' ) {
		$inner_shortcode = $this->buildShortcode( $inner_atts, $inner_content );
		return $this->buildShortcode( $outer_atts, $inner_shortcode );
	}

	/**
	 * Assert that shortcode renders successfully
	 *
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $message Optional failure message.
	 */
	protected function assertShortcodeRenders( $atts = array(), $content = '', $message = '' ) {
		$output = $this->renderShortcode( $atts, $content );

		if ( empty( $message ) ) {
			$message = 'Shortcode failed to render';
		}

		$this->assertNotEmpty( $output, $message );
	}

	/**
	 * Assert that shortcode output contains specific HTML
	 *
	 * @param string $expected Expected HTML snippet.
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $message Optional failure message.
	 */
	protected function assertShortcodeContains( $expected, $atts = array(), $content = '', $message = '' ) {
		$output = $this->renderShortcode( $atts, $content );

		if ( empty( $message ) ) {
			$message = 'Shortcode output does not contain expected HTML';
		}

		$this->assertStringContainsString( $expected, $output, $message );
	}

	/**
	 * Assert that shortcode output does not contain specific HTML
	 *
	 * @param string $unexpected Unexpected HTML snippet.
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $message Optional failure message.
	 */
	protected function assertShortcodeDoesNotContain( $unexpected, $atts = array(), $content = '', $message = '' ) {
		$output = $this->renderShortcode( $atts, $content );

		if ( empty( $message ) ) {
			$message = 'Shortcode output contains unexpected HTML';
		}

		$this->assertStringNotContainsString( $unexpected, $output, $message );
	}

	/**
	 * Assert that shortcode has wrapper with expected classes
	 *
	 * @param array $expected_classes Expected CSS classes.
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $message Optional failure message.
	 */
	protected function assertShortcodeHasWrapperClasses( $expected_classes, $atts = array(), $content = '', $message = '' ) {
		$output = $this->renderShortcode( $atts, $content );

		if ( empty( $message ) ) {
			$message = 'Shortcode wrapper does not have expected classes';
		}

		foreach ( $expected_classes as $class ) {
			$this->assertStringContainsString( 'class="' . $class, $output, $message );
		}
	}

	/**
	 * Assert that shortcode renders with template
	 *
	 * @param string $template Expected template name.
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $message Optional failure message.
	 */
	protected function assertShortcodeUsesTemplate( $template, $atts = array(), $content = '', $message = '' ) {
		$output = $this->renderShortcode( $atts, $content );

		if ( empty( $message ) ) {
			$message = "Shortcode does not use template '{$template}'";
		}

		$this->assertStringContainsString( "cta-highlights-template-{$template}", $output, $message );
	}

	/**
	 * Assert that shortcode has highlight classes
	 *
	 * @param array $atts Shortcode attributes (should have highlight=true).
	 * @param string $content Shortcode content.
	 * @param string $message Optional failure message.
	 */
	protected function assertShortcodeHasHighlight( $atts = array(), $content = '', $message = '' ) {
		$output = $this->renderShortcode( $atts, $content );

		if ( empty( $message ) ) {
			$message = 'Shortcode does not have highlight classes';
		}

		$this->assertStringContainsString( 'cta-highlights-enabled', $output, $message );
	}

	/**
	 * Assert that shortcode does not have highlight classes
	 *
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $message Optional failure message.
	 */
	protected function assertShortcodeDoesNotHaveHighlight( $atts = array(), $content = '', $message = '' ) {
		$output = $this->renderShortcode( $atts, $content );

		if ( empty( $message ) ) {
			$message = 'Shortcode has highlight classes when it should not';
		}

		$this->assertStringNotContainsString( 'cta-highlights-enabled', $output, $message );
	}

	/**
	 * Assert that shortcode sanitizes XSS attempts
	 *
	 * @param string $xss_string XSS attempt string.
	 * @param string $attr_name Attribute name.
	 * @param string $message Optional failure message.
	 */
	protected function assertShortcodeSanitizesXSS( $xss_string, $attr_name = 'cta_title', $message = '' ) {
		$atts = array( $attr_name => $xss_string );
		$output = $this->renderShortcode( $atts );

		if ( empty( $message ) ) {
			$message = 'Shortcode does not sanitize XSS attempts';
		}

		$this->assertStringNotContainsString( '<script>', $output, $message );
		$this->assertStringNotContainsString( 'onerror=', $output, $message );
		$this->assertStringNotContainsString( 'javascript:', $output, $message );
	}

	/**
	 * Get shortcode handler instance
	 *
	 * @return \CTAHighlights\Shortcode\Handler
	 */
	protected function getShortcodeHandler() {
		return cta_highlights()->get_shortcode_handler();
	}

	/**
	 * Assert that post content has shortcode
	 *
	 * @param int $post_id Post ID.
	 * @param string $message Optional failure message.
	 */
	protected function assertPostHasShortcode( $post_id, $message = '' ) {
		$post = get_post( $post_id );

		if ( empty( $message ) ) {
			$message = "Post {$post_id} does not have CTA shortcode";
		}

		$this->assertTrue( has_shortcode( $post->post_content, 'cta_highlights' ), $message );
	}

	/**
	 * Get shortcode attributes from shortcode string
	 *
	 * @param string $shortcode_string Full shortcode string.
	 * @return array Parsed attributes.
	 */
	protected function parseShortcodeAttributes( $shortcode_string ) {
		preg_match( '/\[cta_highlights([^\]]*)\]/', $shortcode_string, $matches );

		if ( empty( $matches[1] ) ) {
			return array();
		}

		return shortcode_parse_atts( $matches[1] );
	}

	/**
	 * Create a post with shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param array $post_args Post arguments.
	 * @return int|\WP_Error Post ID or error.
	 */
	protected function createPostWithShortcode( $atts = array(), $content = '', $post_args = array() ) {
		$shortcode = $this->buildShortcode( $atts, $content );

		$defaults = array(
			'post_title'   => 'Test Post with Shortcode',
			'post_content' => "<p>Before shortcode.</p>\n\n{$shortcode}\n\n<p>After shortcode.</p>",
			'post_status'  => 'publish',
			'post_type'    => 'post',
		);

		$post_data = wp_parse_args( $post_args, $defaults );

		return wp_insert_post( $post_data );
	}

	/**
	 * Assert that rendered post contains shortcode output
	 *
	 * @param int $post_id Post ID.
	 * @param string $expected Expected content in output.
	 * @param string $message Optional failure message.
	 */
	protected function assertPostShortcodeRenders( $post_id, $expected, $message = '' ) {
		$post = get_post( $post_id );
		$output = do_shortcode( $post->post_content );

		if ( empty( $message ) ) {
			$message = 'Post shortcode output does not contain expected content';
		}

		$this->assertStringContainsString( $expected, $output, $message );
	}
}
