<?php
/**
 * Content parser and CTA injector
 *
 * @package CTAHighlights\AutoInsertion
 */

namespace CTAHighlights\AutoInsertion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inserter class for parsing content and injecting CTAs
 */
class Inserter {

	/**
	 * Insert CTA into content
	 *
	 * @param string $content Post content.
	 * @param array  $cta CTA configuration.
	 * @param string $storage_condition_js JavaScript storage condition code.
	 * @return string Modified content.
	 */
	public function insert( $content, $cta, $storage_condition_js ) {
		// Parse content into DOM elements
		$elements = $this->parse_content_elements( $content );

		if ( empty( $elements ) ) {
			return $content; // No elements to insert into
		}

		// Calculate insertion position
		$position = $this->calculate_position( $elements, $cta );

		if ( false === $position ) {
			return $content; // Position not found (e.g., skip on insufficient content)
		}

		// Build CTA HTML
		$cta_html = $this->build_cta_html( $cta, $storage_condition_js );

		// Insert CTA at position
		return $this->inject_at_position( $content, $elements, $position, $cta_html );
	}

	/**
	 * Parse content into array of HTML elements
	 *
	 * @param string $content HTML content.
	 * @return array Array of elements with their positions.
	 */
	private function parse_content_elements( $content ) {
		if ( empty( $content ) ) {
			return array();
		}

		// Use DOMDocument to parse HTML
		$dom = new \DOMDocument( '1.0', 'UTF-8' );

		// Suppress warnings for malformed HTML
		libxml_use_internal_errors( true );

		// Load HTML with UTF-8 encoding
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		libxml_clear_errors();

		$elements = array();

		// Get direct children of body (or root if no body)
		$body = $dom->getElementsByTagName( 'body' )->item( 0 );
		$root = $body ? $body : $dom;

		if ( ! $root->hasChildNodes() ) {
			return array();
		}

		foreach ( $root->childNodes as $index => $node ) {
			// Only count element nodes (ignore text nodes, comments, etc.)
			if ( XML_ELEMENT_NODE !== $node->nodeType ) {
				continue;
			}

			// Get the HTML of this element
			$element_html = $dom->saveHTML( $node );

			$elements[] = array(
				'index'    => $index,
				'html'     => $element_html,
				'tag_name' => $node->nodeName,
			);
		}

		return $elements;
	}

	/**
	 * Calculate insertion position based on CTA configuration
	 *
	 * @param array $elements Parsed content elements.
	 * @param array $cta CTA configuration.
	 * @return int|false Element index to insert after, or false if not found.
	 */
	private function calculate_position( $elements, $cta ) {
		$total_elements = count( $elements );
		$position       = absint( $cta['insertion_position'] );
		$direction      = $cta['insertion_direction'];
		$fallback       = $cta['fallback_behavior'];

		if ( 'forward' === $direction ) {
			// Insert after the Nth element (1-indexed in UI, 0-indexed in array)
			$target_index = $position - 1;

			if ( $target_index >= $total_elements ) {
				// Insufficient elements
				if ( 'end' === $fallback ) {
					return $total_elements - 1; // Insert after last element
				} else {
					return false; // Skip insertion
				}
			}

			return $target_index;
		} else {
			// Reverse: Insert N elements from the end
			$target_index = $total_elements - $position;

			if ( $target_index < 0 ) {
				// Insufficient elements
				if ( 'end' === $fallback ) {
					return $total_elements - 1; // Insert after last element
				} else {
					return false; // Skip insertion
				}
			}

			return $target_index;
		}
	}

	/**
	 * Build CTA HTML wrapper
	 *
	 * @param array  $cta CTA configuration.
	 * @param string $storage_condition_js JavaScript condition code.
	 * @return string CTA HTML.
	 */
	private function build_cta_html( $cta, $storage_condition_js ) {
		$classes = array(
			'cta-highlights-wrapper',
			'cta-highlights-auto-inserted',
		);

		$data_attrs = array(
			'data-auto-insert'         => 'true',
			'data-cta-id'              => absint( $cta['id'] ),
			'data-storage-condition'   => esc_attr( $storage_condition_js ),
			'data-has-storage-condition' => ! empty( $cta['storage_conditions'] ) ? 'true' : 'false',
		);

		$classes_str   = implode( ' ', array_map( 'esc_attr', $classes ) );
		$data_attrs_str = '';
		foreach ( $data_attrs as $key => $value ) {
			$data_attrs_str .= sprintf( ' %s="%s"', esc_attr( $key ), $value );
		}

		$content = wp_kses_post( $cta['content'] );

		// Process shortcodes in content
		$content = do_shortcode( $content );

		return sprintf(
			'<div class="%s"%s style="display:none;">%s</div>',
			$classes_str,
			$data_attrs_str,
			$content
		);
	}

	/**
	 * Inject CTA HTML at calculated position
	 *
	 * @param string $content Original content.
	 * @param array  $elements Parsed elements.
	 * @param int    $position Element index to insert after.
	 * @param string $cta_html CTA HTML to inject.
	 * @return string Modified content.
	 */
	private function inject_at_position( $content, $elements, $position, $cta_html ) {
		// Find the HTML string to insert after
		$target_element = $elements[ $position ];

		// Find position of this element in original content
		$element_pos = strpos( $content, $target_element['html'] );

		if ( false === $element_pos ) {
			return $content; // Element not found (shouldn't happen)
		}

		// Calculate insertion point (after the element)
		$insertion_point = $element_pos + strlen( $target_element['html'] );

		// Insert CTA HTML
		return substr_replace( $content, "\n\n" . $cta_html . "\n\n", $insertion_point, 0 );
	}
}
