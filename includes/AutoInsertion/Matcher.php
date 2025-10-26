<?php
/**
 * Conditional logic matcher for auto-insertion CTAs
 *
 * @package CTAHighlights\AutoInsertion
 */

namespace CTAHighlights\AutoInsertion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Matcher class for evaluating CTA display conditions
 */
class Matcher {

	/**
	 * Check if CTA should be displayed on current post
	 *
	 * @param array   $cta CTA configuration.
	 * @param WP_Post $post Current post object.
	 * @return bool
	 */
	public function should_display( $cta, $post ) {
		// Check if auto-insertion is disabled for this post
		if ( get_post_meta( $post->ID, '_cta_highlights_disable_auto_insert', true ) ) {
			return false;
		}

		// Check post type match
		if ( ! $this->matches_post_type( $cta, $post ) ) {
			return false;
		}

		// Check category match
		if ( ! $this->matches_categories( $cta, $post ) ) {
			return false;
		}

		// Storage conditions are evaluated client-side in JavaScript
		// We'll add a data attribute for JS to evaluate

		return true;
	}

	/**
	 * Check if post type matches CTA configuration
	 *
	 * @param array   $cta CTA configuration.
	 * @param WP_Post $post Post object.
	 * @return bool
	 */
	private function matches_post_type( $cta, $post ) {
		if ( empty( $cta['post_types'] ) ) {
			return true; // No restriction
		}

		return in_array( $post->post_type, $cta['post_types'], true );
	}

	/**
	 * Check if categories match CTA configuration
	 *
	 * @param array   $cta CTA configuration.
	 * @param WP_Post $post Post object.
	 * @return bool
	 */
	private function matches_categories( $cta, $post ) {
		if ( empty( $cta['category_ids'] ) ) {
			return true; // No restriction
		}

		$post_categories = wp_get_post_categories( $post->ID );

		if ( empty( $post_categories ) ) {
			// Post has no categories
			return 'exclude' === $cta['category_mode']; // Only match if we're excluding
		}

		$has_match = (bool) array_intersect( $cta['category_ids'], $post_categories );

		if ( 'include' === $cta['category_mode'] ) {
			return $has_match; // Must be in one of the selected categories
		} else {
			return ! $has_match; // Must NOT be in any of the selected categories
		}
	}

	/**
	 * Evaluate localStorage/cookie conditions (called from JavaScript)
	 *
	 * This generates JavaScript code to evaluate conditions client-side
	 *
	 * @param array $conditions Storage conditions.
	 * @return string JavaScript evaluation code.
	 */
	public function generate_storage_condition_js( $conditions ) {
		if ( empty( $conditions ) ) {
			return 'true';
		}

		$js_parts = array();

		foreach ( $conditions as $condition ) {
			$key      = isset( $condition['key'] ) ? $condition['key'] : '';
			$operator = isset( $condition['operator'] ) ? $condition['operator'] : '=';
			$value    = isset( $condition['value'] ) ? $condition['value'] : '';
			$datatype = isset( $condition['datatype'] ) ? $condition['datatype'] : 'string';

			if ( empty( $key ) ) {
				continue;
			}

			$js_parts[] = $this->generate_single_condition_js( $key, $operator, $value, $datatype );
		}

		if ( empty( $js_parts ) ) {
			return 'true';
		}

		// Determine logic operator (all conditions must pass for MVP - AND logic)
		// In future versions, we can add support for mixed AND/OR logic
		return '(' . implode( ' && ', $js_parts ) . ')';
	}

	/**
	 * Generate JavaScript for a single storage condition
	 *
	 * @param string $key Storage key.
	 * @param string $operator Comparison operator.
	 * @param mixed  $value Comparison value.
	 * @param string $datatype Data type (string, number, boolean, regex).
	 * @return string JavaScript condition code.
	 */
	private function generate_single_condition_js( $key, $operator, $value, $datatype ) {
		$key_js   = json_encode( $key );
		$value_js = json_encode( $value );

		// Get value from storage (localStorage or cookie)
		$get_value_js = "this.storageManager.get({$key_js})";

		// Handle different data types and operators
		switch ( $datatype ) {
			case 'number':
				return $this->generate_numeric_condition( $get_value_js, $operator, $value_js );

			case 'boolean':
				return $this->generate_boolean_condition( $get_value_js, $operator, $value_js );

			case 'regex':
				return $this->generate_regex_condition( $get_value_js, $value_js );

			case 'date':
				return $this->generate_date_condition( $get_value_js, $operator, $value_js );

			default: // string
				return $this->generate_string_condition( $get_value_js, $operator, $value_js );
		}
	}

	/**
	 * Generate numeric comparison JavaScript
	 *
	 * @param string $get_value_js JavaScript to get value.
	 * @param string $operator Comparison operator.
	 * @param string $value_js JSON-encoded value.
	 * @return string
	 */
	private function generate_numeric_condition( $get_value_js, $operator, $value_js ) {
		$valid_operators = array( '=', '!=', '>', '<', '>=', '<=' );
		if ( ! in_array( $operator, $valid_operators, true ) ) {
			$operator = '=';
		}

		// Convert '=' to '===' for strict comparison
		if ( '=' === $operator ) {
			$operator = '===';
		} elseif ( '!=' === $operator ) {
			$operator = '!==';
		}

		return "(function() {
			const val = {$get_value_js};
			if (val === null || val === undefined) return false;
			const numVal = Number(val);
			if (isNaN(numVal)) return false;
			return numVal {$operator} {$value_js};
		})()";
	}

	/**
	 * Generate boolean comparison JavaScript
	 *
	 * @param string $get_value_js JavaScript to get value.
	 * @param string $operator Comparison operator.
	 * @param string $value_js JSON-encoded value.
	 * @return string
	 */
	private function generate_boolean_condition( $get_value_js, $operator, $value_js ) {
		$op = ( '=' === $operator || '==' === $operator ) ? '===' : '!==';

		return "(function() {
			const val = {$get_value_js};
			if (val === null || val === undefined) return false;
			let boolVal;
			if (typeof val === 'boolean') {
				boolVal = val;
			} else if (typeof val === 'string') {
				boolVal = val.toLowerCase() === 'true' || val === '1';
			} else {
				boolVal = Boolean(val);
			}
			return boolVal {$op} {$value_js};
		})()";
	}

	/**
	 * Generate regex comparison JavaScript
	 *
	 * @param string $get_value_js JavaScript to get value.
	 * @param string $value_js JSON-encoded regex pattern.
	 * @return string
	 */
	private function generate_regex_condition( $get_value_js, $value_js ) {
		return "(function() {
			const val = {$get_value_js};
			if (val === null || val === undefined) return false;
			try {
				const pattern = new RegExp({$value_js});
				return pattern.test(String(val));
			} catch (e) {
				return false;
			}
		})()";
	}

	/**
	 * Generate date/time comparison JavaScript
	 *
	 * @param string $get_value_js JavaScript to get value.
	 * @param string $operator Comparison operator.
	 * @param string $value_js JSON-encoded value (timestamp or date string).
	 * @return string
	 */
	private function generate_date_condition( $get_value_js, $operator, $value_js ) {
		$valid_operators = array( '=', '!=', '>', '<', '>=', '<=' );
		if ( ! in_array( $operator, $valid_operators, true ) ) {
			$operator = '=';
		}

		if ( '=' === $operator ) {
			$operator = '===';
		} elseif ( '!=' === $operator ) {
			$operator = '!==';
		}

		return "(function() {
			const val = {$get_value_js};
			if (val === null || val === undefined) return false;
			const timestamp = new Date(val).getTime();
			const compareTimestamp = new Date({$value_js}).getTime();
			if (isNaN(timestamp) || isNaN(compareTimestamp)) return false;
			return timestamp {$operator} compareTimestamp;
		})()";
	}

	/**
	 * Generate string comparison JavaScript
	 *
	 * @param string $get_value_js JavaScript to get value.
	 * @param string $operator Comparison operator.
	 * @param string $value_js JSON-encoded value.
	 * @return string
	 */
	private function generate_string_condition( $get_value_js, $operator, $value_js ) {
		$op = ( '=' === $operator || '==' === $operator ) ? '===' : '!==';

		return "(function() {
			const val = {$get_value_js};
			if (val === null || val === undefined) return false;
			return String(val) {$op} {$value_js};
		})()";
	}
}
