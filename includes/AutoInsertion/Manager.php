<?php
/**
 * Auto-insertion manager
 *
 * @package CTAHighlights\AutoInsertion
 */

namespace CTAHighlights\AutoInsertion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manager class orchestrating auto-insertion functionality
 */
class Manager {

	/**
	 * Singleton instance
	 *
	 * @var Manager
	 */
	private static $instance = null;

	/**
	 * Database instance
	 *
	 * @var Database
	 */
	private $database;

	/**
	 * Matcher instance
	 *
	 * @var Matcher
	 */
	private $matcher;

	/**
	 * Inserter instance
	 *
	 * @var Inserter
	 */
	private $inserter;

	/**
	 * Maximum fallback chain depth to prevent infinite loops
	 *
	 * @var int
	 */
	const MAX_FALLBACK_DEPTH = 10;

	/**
	 * Get singleton instance
	 *
	 * @return Manager
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->database = new Database();
		$this->matcher  = new Matcher();
		$this->inserter = new Inserter();

		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Output fallback chain data in footer.
		add_action( 'wp_footer', array( $this, 'output_fallback_data' ), 5 );

		// Enqueue scripts for storage condition evaluation.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Check if we need to force enqueue base assets for auto-inserted CTAs with shortcodes.
		// Priority 5 ensures this runs early during wp_enqueue_scripts.
		add_filter( 'cta_highlights_force_enqueue', array( $this, 'check_auto_insert_shortcodes' ), 5 );
	}

	/**
	 * Output fallback chain data in footer as inline JSON
	 * Client-side JavaScript will handle insertion
	 *
	 * @return void
	 */
	public function output_fallback_data() {
		// Only on singular posts.
		if ( ! is_singular() ) {
			return;
		}

		global $post;

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		// Find matching CTA.
		$cta = $this->find_matching_cta( $post );

		if ( ! $cta ) {
			return; // No CTA = no output (conditional loading for performance).
		}

		// Build fallback chain.
		$chain = $this->build_fallback_chain( $cta, $post );

		// Prepare data structure.
		$data = array(
			'postId'          => $post->ID,
			'contentSelector' => apply_filters( 'cta_highlights_content_selector', '.entry-content', $post->ID ),
			'ctas'            => $this->prepare_ctas_for_output( $chain ),
		);

		// Output as JSON script tag in footer.
		echo '<script type="application/json" id="cta-highlights-auto-insert-data">';
		echo wp_json_encode( $data );
		echo '</script>' . "\n";
	}

	/**
	 * Prepare CTAs for JSON output
	 * Processes content and generates storage condition JavaScript
	 *
	 * @param array $chain Array of CTAs in fallback order.
	 * @return array Prepared CTAs for output.
	 */
	private function prepare_ctas_for_output( $chain ) {
		$prepared = array();

		foreach ( $chain as $cta ) {
			// Process content (shortcodes, sanitization).
			$content = wp_kses_post( $cta['content'] );
			$content = do_shortcode( $content );

			// Generate storage condition JavaScript.
			$storage_condition_js = $this->matcher->generate_storage_condition_js( $cta['storage_conditions'] );

			$prepared[] = array(
				'id'                     => absint( $cta['id'] ),
				'content'                => $content,
				'storage_conditions'     => $cta['storage_conditions'],
				'storage_condition_js'   => $storage_condition_js,
				'has_storage_conditions' => ! empty( $cta['storage_conditions'] ),
				'insertion_direction'    => $cta['insertion_direction'],
				'insertion_position'     => absint( $cta['insertion_position'] ),
				'fallback_behavior'      => $cta['fallback_behavior'],
			);
		}

		return $prepared;
	}

	/**
	 * Build fallback chain for a CTA
	 * Includes the main CTA and all fallbacks that match post type/category
	 *
	 * @param array    $cta Primary CTA.
	 * @param \WP_Post $post Post object.
	 * @return array Array of CTAs in fallback order.
	 */
	private function build_fallback_chain( $cta, $post ) {
		$chain   = array( $cta );
		$visited = array( $cta['id'] );
		$current = $cta;
		$depth   = 0;

		// Follow the fallback chain.
		while ( ! empty( $current['fallback_cta_id'] ) && $depth < self::MAX_FALLBACK_DEPTH ) {
			// Prevent circular references.
			if ( in_array( $current['fallback_cta_id'], $visited, true ) ) {
				break;
			}

			$fallback = $this->database->get( $current['fallback_cta_id'] );

			if ( ! $fallback || 'active' !== $fallback['status'] ) {
				break;
			}

			// Check if fallback matches post type/category (not storage conditions).
			if ( $this->matcher->should_display( $fallback, $post ) ) {
				$chain[]   = $fallback;
				$visited[] = $fallback['id'];
				$current   = $fallback;
				++$depth;
			} else {
				// Fallback doesn't match post type/category, stop chain.
				break;
			}
		}

		return $chain;
	}

	/**
	 * Find matching CTA for current post with fallback chain
	 *
	 * @param \WP_Post $post Post object.
	 * @param int      $depth Current recursion depth.
	 * @param array    $visited_ids Already visited CTA IDs to prevent loops.
	 * @return array|null CTA configuration or null if none found.
	 */
	private function find_matching_cta( $post, $depth = 0, $visited_ids = array() ) {
		// Prevent infinite loops.
		if ( $depth >= self::MAX_FALLBACK_DEPTH ) {
			return null;
		}

		// Get all active primary CTAs (excluding fallback-only CTAs).
		$ctas = $this->database->get_all(
			array(
				'status'   => 'active',
				'cta_type' => 'primary',
			)
		);

		foreach ( $ctas as $cta ) {
			// Skip if we've already visited this CTA (circular reference).
			if ( in_array( $cta['id'], $visited_ids, true ) ) {
				continue;
			}

			// Check if this CTA matches the current post.
			if ( $this->matcher->should_display( $cta, $post ) ) {
				return $cta;
			}

			// If CTA doesn't match and has a fallback, try the fallback.
			if ( ! empty( $cta['fallback_cta_id'] ) ) {
				$visited_ids[] = $cta['id'];
				$fallback_cta  = $this->database->get( $cta['fallback_cta_id'] );

				if ( $fallback_cta && 'active' === $fallback_cta['status'] ) {
					$fallback_match = $this->find_matching_cta_recursive(
						$fallback_cta,
						$post,
						$depth + 1,
						$visited_ids
					);

					if ( $fallback_match ) {
						return $fallback_match;
					}
				}
			}
		}

		return null;
	}

	/**
	 * Recursively check if a fallback CTA matches
	 *
	 * @param array    $cta CTA configuration.
	 * @param \WP_Post $post Post object.
	 * @param int      $depth Current recursion depth.
	 * @param array    $visited_ids Already visited CTA IDs.
	 * @return array|null CTA configuration or null if none found.
	 */
	private function find_matching_cta_recursive( $cta, $post, $depth, $visited_ids ) {
		// Prevent infinite loops.
		if ( $depth >= self::MAX_FALLBACK_DEPTH ) {
			return null;
		}

		// Check if this CTA matches.
		if ( $this->matcher->should_display( $cta, $post ) ) {
			return $cta;
		}

		// Try this CTA's fallback.
		if ( ! empty( $cta['fallback_cta_id'] ) && ! in_array( $cta['fallback_cta_id'], $visited_ids, true ) ) {
			$visited_ids[] = $cta['id'];
			$fallback_cta  = $this->database->get( $cta['fallback_cta_id'] );

			if ( $fallback_cta && 'active' === $fallback_cta['status'] ) {
				return $this->find_matching_cta_recursive(
					$fallback_cta,
					$post,
					$depth + 1,
					$visited_ids
				);
			}
		}

		return null;
	}

	/**
	 * Check if auto-inserted CTAs contain the [cta_highlights] shortcode
	 * This ensures base assets are enqueued for the highlight feature
	 * Checks the entire fallback chain since client-side JavaScript may select any CTA
	 *
	 * @param bool $force Current force enqueue value.
	 * @return bool
	 */
	public function check_auto_insert_shortcodes( $force ) {
		// If already forced, return early.
		if ( $force ) {
			return $force;
		}

		// Only check on singular posts.
		if ( ! is_singular() ) {
			return $force;
		}

		global $post;

		if ( ! $post instanceof \WP_Post ) {
			return $force;
		}

		// Get matching CTA for this post.
		$cta = $this->find_matching_cta( $post );

		if ( ! $cta ) {
			return $force;
		}

		// Build the entire fallback chain.
		// This is necessary because client-side JavaScript may select any CTA from the chain.
		// based on storage conditions, so we need to check all of them.
		$chain = $this->build_fallback_chain( $cta, $post );

		// Check if ANY CTA in the chain contains the [cta_highlights] shortcode.
		foreach ( $chain as $chain_cta ) {
			if ( ! empty( $chain_cta['content'] ) && has_shortcode( $chain_cta['content'], 'cta_highlights' ) ) {
				return true; // Force enqueue.
			}
		}

		return $force;
	}

	/**
	 * Enqueue scripts for auto-insertion
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! is_singular() ) {
			return;
		}

		// Enqueue auto-insertion JavaScript.
		// Note: This script is independent and doesn't require cta-highlights-base.
		// It handles client-side insertion, position calculation, and storage evaluation.
		wp_enqueue_script(
			'cta-highlights-auto-insert',
			CTA_HIGHLIGHTS_URL . 'assets/js/auto-insert.js',
			array(), // No dependencies - standalone script.
			CTA_HIGHLIGHTS_VERSION,
			true // In footer.
		);

		// Add defer attribute for performance (non-blocking).
		wp_script_add_data( 'cta-highlights-auto-insert', 'defer', true );
	}

	/**
	 * Get database instance
	 *
	 * @return Database
	 */
	public function get_database() {
		return $this->database;
	}

	/**
	 * Get matcher instance
	 *
	 * @return Matcher
	 */
	public function get_matcher() {
		return $this->matcher;
	}

	/**
	 * Get inserter instance
	 *
	 * @return Inserter
	 */
	public function get_inserter() {
		return $this->inserter;
	}
}
