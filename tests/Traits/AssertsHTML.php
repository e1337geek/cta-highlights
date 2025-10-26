<?php
/**
 * Asserts HTML Trait
 *
 * Provides custom HTML assertions for testing rendered output.
 * Use this trait to make detailed assertions about HTML structure,
 * attributes, accessibility, and security.
 *
 * @package CTAHighlights\Tests\Traits
 */

namespace CTAHighlights\Tests\Traits;

trait AssertsHTML {

	/**
	 * Assert that HTML contains element with tag
	 *
	 * @param string $html HTML to check.
	 * @param string $tag Tag name.
	 * @param string $message Optional failure message.
	 */
	protected function assertHTMLHasElement( $html, $tag, $message = '' ) {
		if ( empty( $message ) ) {
			$message = "HTML does not contain <{$tag}> element";
		}

		$this->assertMatchesRegularExpression( '/<' . preg_quote( $tag, '/' ) . '\b/i', $html, $message );
	}

	/**
	 * Assert that HTML contains element with specific attributes
	 *
	 * @param string $html HTML to check.
	 * @param string $tag Tag name.
	 * @param array $attributes Expected attributes (key => value).
	 * @param string $message Optional failure message.
	 */
	protected function assertHTMLHasElementWithAttributes( $html, $tag, $attributes, $message = '' ) {
		$dom = $this->loadHTML( $html );
		$elements = $dom->getElementsByTagName( $tag );

		if ( $elements->length === 0 ) {
			$this->fail( "No <{$tag}> elements found in HTML" );
		}

		$found = false;
		foreach ( $elements as $element ) {
			$matches_all = true;

			foreach ( $attributes as $attr_name => $attr_value ) {
				$actual_value = $element->getAttribute( $attr_name );

				if ( $actual_value !== $attr_value ) {
					$matches_all = false;
					break;
				}
			}

			if ( $matches_all ) {
				$found = true;
				break;
			}
		}

		if ( empty( $message ) ) {
			$message = "HTML does not contain <{$tag}> with expected attributes";
		}

		$this->assertTrue( $found, $message );
	}

	/**
	 * Assert that HTML element has CSS class
	 *
	 * @param string $html HTML to check.
	 * @param string $tag Tag name.
	 * @param string $class CSS class name.
	 * @param string $message Optional failure message.
	 */
	protected function assertHTMLElementHasClass( $html, $tag, $class, $message = '' ) {
		$dom = $this->loadHTML( $html );
		$elements = $dom->getElementsByTagName( $tag );

		if ( $elements->length === 0 ) {
			$this->fail( "No <{$tag}> elements found in HTML" );
		}

		$found = false;
		foreach ( $elements as $element ) {
			$element_classes = explode( ' ', $element->getAttribute( 'class' ) );

			if ( in_array( $class, $element_classes, true ) ) {
				$found = true;
				break;
			}
		}

		if ( empty( $message ) ) {
			$message = "HTML <{$tag}> element does not have class '{$class}'";
		}

		$this->assertTrue( $found, $message );
	}

	/**
	 * Assert that HTML contains link with URL
	 *
	 * @param string $html HTML to check.
	 * @param string $url Expected URL.
	 * @param string $message Optional failure message.
	 */
	protected function assertHTMLHasLink( $html, $url, $message = '' ) {
		$this->assertHTMLHasElementWithAttributes( $html, 'a', array( 'href' => $url ), $message );
	}

	/**
	 * Assert that HTML is properly escaped (no XSS)
	 *
	 * @param string $html HTML to check.
	 * @param string $message Optional failure message.
	 */
	protected function assertHTMLIsEscaped( $html, $message = '' ) {
		if ( empty( $message ) ) {
			$message = 'HTML contains unescaped dangerous content';
		}

		// Check for common XSS patterns
		$dangerous_patterns = array(
			'/<script\b/i',
			'/javascript:/i',
			'/onerror\s*=/i',
			'/onclick\s*=/i',
			'/onload\s*=/i',
			'/<iframe\b/i',
		);

		foreach ( $dangerous_patterns as $pattern ) {
			$this->assertDoesNotMatchRegularExpression( $pattern, $html, $message );
		}
	}

	/**
	 * Assert that HTML has ARIA attributes for accessibility
	 *
	 * @param string $html HTML to check.
	 * @param string $tag Tag name.
	 * @param array $aria_attributes Expected ARIA attributes (name => value).
	 * @param string $message Optional failure message.
	 */
	protected function assertHTMLHasARIA( $html, $tag, $aria_attributes, $message = '' ) {
		$this->assertHTMLHasElementWithAttributes( $html, $tag, $aria_attributes, $message );
	}

	/**
	 * Assert that HTML has proper heading hierarchy
	 *
	 * @param string $html HTML to check.
	 * @param string $message Optional failure message.
	 */
	protected function assertHTMLHasProperHeadingHierarchy( $html, $message = '' ) {
		$dom = $this->loadHTML( $html );

		$headings = array();
		for ( $i = 1; $i <= 6; $i++ ) {
			$elements = $dom->getElementsByTagName( "h{$i}" );
			foreach ( $elements as $element ) {
				$headings[] = $i;
			}
		}

		if ( empty( $headings ) ) {
			return; // No headings to check
		}

		// Check that headings don't skip levels
		for ( $i = 1; $i < count( $headings ); $i++ ) {
			$diff = $headings[ $i ] - $headings[ $i - 1 ];

			if ( $diff > 1 ) {
				if ( empty( $message ) ) {
					$message = "Heading hierarchy skips from h{$headings[$i-1]} to h{$headings[$i]}";
				}

				$this->fail( $message );
			}
		}

		$this->assertTrue( true );
	}

	/**
	 * Assert that HTML images have alt text
	 *
	 * @param string $html HTML to check.
	 * @param string $message Optional failure message.
	 */
	protected function assertHTMLImagesHaveAltText( $html, $message = '' ) {
		$dom = $this->loadHTML( $html );
		$images = $dom->getElementsByTagName( 'img' );

		if ( $images->length === 0 ) {
			return; // No images to check
		}

		$missing_alt = array();
		foreach ( $images as $image ) {
			if ( ! $image->hasAttribute( 'alt' ) ) {
				$src = $image->getAttribute( 'src' );
				$missing_alt[] = $src;
			}
		}

		if ( empty( $message ) ) {
			$message = 'Images missing alt attributes: ' . implode( ', ', $missing_alt );
		}

		$this->assertEmpty( $missing_alt, $message );
	}

	/**
	 * Assert that HTML has valid structure
	 *
	 * @param string $html HTML to check.
	 * @param string $message Optional failure message.
	 */
	protected function assertHTMLIsValid( $html, $message = '' ) {
		libxml_use_internal_errors( true );

		$dom = new \DOMDocument();
		$dom->loadHTML( $html );

		$errors = libxml_get_errors();
		libxml_clear_errors();

		if ( empty( $message ) ) {
			$error_messages = array();
			foreach ( $errors as $error ) {
				$error_messages[] = trim( $error->message );
			}
			$message = 'HTML has validation errors: ' . implode( ', ', $error_messages );
		}

		$this->assertEmpty( $errors, $message );
	}

	/**
	 * Assert that HTML contains semantic elements
	 *
	 * @param string $html HTML to check.
	 * @param array $required_elements Required semantic element tags.
	 * @param string $message Optional failure message.
	 */
	protected function assertHTMLHasSemanticElements( $html, $required_elements, $message = '' ) {
		$dom = $this->loadHTML( $html );

		$missing = array();
		foreach ( $required_elements as $tag ) {
			$elements = $dom->getElementsByTagName( $tag );
			if ( $elements->length === 0 ) {
				$missing[] = $tag;
			}
		}

		if ( empty( $message ) ) {
			$message = 'HTML missing semantic elements: ' . implode( ', ', $missing );
		}

		$this->assertEmpty( $missing, $message );
	}

	/**
	 * Assert that HTML data attributes are present
	 *
	 * @param string $html HTML to check.
	 * @param string $tag Tag name.
	 * @param array $data_attributes Expected data attributes (name => value).
	 * @param string $message Optional failure message.
	 */
	protected function assertHTMLHasDataAttributes( $html, $tag, $data_attributes, $message = '' ) {
		$dom = $this->loadHTML( $html );
		$elements = $dom->getElementsByTagName( $tag );

		if ( $elements->length === 0 ) {
			$this->fail( "No <{$tag}> elements found in HTML" );
		}

		$found = false;
		foreach ( $elements as $element ) {
			$matches_all = true;

			foreach ( $data_attributes as $attr_name => $attr_value ) {
				$full_attr_name = 'data-' . $attr_name;
				$actual_value = $element->getAttribute( $full_attr_name );

				if ( $actual_value !== $attr_value ) {
					$matches_all = false;
					break;
				}
			}

			if ( $matches_all ) {
				$found = true;
				break;
			}
		}

		if ( empty( $message ) ) {
			$message = "HTML <{$tag}> element does not have expected data attributes";
		}

		$this->assertTrue( $found, $message );
	}

	/**
	 * Assert that HTML is minified (no unnecessary whitespace)
	 *
	 * @param string $html HTML to check.
	 * @param string $message Optional failure message.
	 */
	protected function assertHTMLIsMinified( $html, $message = '' ) {
		if ( empty( $message ) ) {
			$message = 'HTML is not minified';
		}

		// Check for multiple consecutive spaces
		$this->assertDoesNotMatchRegularExpression( '/  +/', $html, $message );

		// Check for spaces around tags
		$this->assertDoesNotMatchRegularExpression( '/>\s+</', $html, $message );
	}

	/**
	 * Assert that HTML does not contain element
	 *
	 * @param string $html HTML to check.
	 * @param string $tag Tag name.
	 * @param string $message Optional failure message.
	 */
	protected function assertHTMLDoesNotHaveElement( $html, $tag, $message = '' ) {
		if ( empty( $message ) ) {
			$message = "HTML contains <{$tag}> element when it should not";
		}

		$this->assertDoesNotMatchRegularExpression( '/<' . preg_quote( $tag, '/' ) . '\b/i', $html, $message );
	}

	/**
	 * Get element count by tag name
	 *
	 * @param string $html HTML to check.
	 * @param string $tag Tag name.
	 * @return int Element count.
	 */
	protected function getHTMLElementCount( $html, $tag ) {
		$dom = $this->loadHTML( $html );
		$elements = $dom->getElementsByTagName( $tag );

		return $elements->length;
	}

	/**
	 * Assert HTML element count
	 *
	 * @param int $expected Expected count.
	 * @param string $html HTML to check.
	 * @param string $tag Tag name.
	 * @param string $message Optional failure message.
	 */
	protected function assertHTMLElementCount( $expected, $html, $tag, $message = '' ) {
		$actual = $this->getHTMLElementCount( $html, $tag );

		if ( empty( $message ) ) {
			$message = "Expected {$expected} <{$tag}> elements, found {$actual}";
		}

		$this->assertEquals( $expected, $actual, $message );
	}

	/**
	 * Load HTML into DOMDocument
	 *
	 * @param string $html HTML string.
	 * @return \DOMDocument
	 */
	private function loadHTML( $html ) {
		libxml_use_internal_errors( true );

		$dom = new \DOMDocument();
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		libxml_clear_errors();

		return $dom;
	}

	/**
	 * Extract text content from HTML
	 *
	 * @param string $html HTML string.
	 * @return string Text content.
	 */
	protected function extractTextFromHTML( $html ) {
		$dom = $this->loadHTML( $html );
		return $dom->textContent;
	}

	/**
	 * Assert that HTML text content contains string
	 *
	 * @param string $expected Expected text.
	 * @param string $html HTML to check.
	 * @param string $message Optional failure message.
	 */
	protected function assertHTMLTextContains( $expected, $html, $message = '' ) {
		$text = $this->extractTextFromHTML( $html );

		if ( empty( $message ) ) {
			$message = 'HTML text content does not contain expected string';
		}

		$this->assertStringContainsString( $expected, $text, $message );
	}
}
