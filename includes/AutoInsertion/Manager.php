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
		// Filter content to insert CTAs
		add_filter( 'the_content', array( $this, 'filter_content' ), 20 );

		// Enqueue scripts for storage condition evaluation
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Filter content to insert CTA
	 *
	 * @param string $content Post content.
	 * @return string Modified content.
	 */
	public function filter_content( $content ) {
		// Only process in main query and singular posts
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		global $post;

		if ( ! $post instanceof \WP_Post ) {
			return $content;
		}

		// Get matching CTA
		$cta = $this->find_matching_cta( $post );

		if ( ! $cta ) {
			return $content;
		}

		// Generate JavaScript for storage conditions
		$storage_condition_js = $this->matcher->generate_storage_condition_js( $cta['storage_conditions'] );

		// Insert CTA into content
		$modified_content = $this->inserter->insert( $content, $cta, $storage_condition_js );

		return apply_filters( 'cta_highlights_auto_insert_content', $modified_content, $content, $cta );
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
		// Prevent infinite loops
		if ( $depth >= self::MAX_FALLBACK_DEPTH ) {
			return null;
		}

		// Get all active CTAs
		$ctas = $this->database->get_all( array( 'status' => 'active' ) );

		foreach ( $ctas as $cta ) {
			// Skip if we've already visited this CTA (circular reference)
			if ( in_array( $cta['id'], $visited_ids, true ) ) {
				continue;
			}

			// Check if this CTA matches the current post
			if ( $this->matcher->should_display( $cta, $post ) ) {
				return $cta;
			}

			// If CTA doesn't match and has a fallback, try the fallback
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
		// Prevent infinite loops
		if ( $depth >= self::MAX_FALLBACK_DEPTH ) {
			return null;
		}

		// Check if this CTA matches
		if ( $this->matcher->should_display( $cta, $post ) ) {
			return $cta;
		}

		// Try this CTA's fallback
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
	 * Enqueue scripts for auto-insertion
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! is_singular() ) {
			return;
		}

		// Enqueue auto-insertion JavaScript (depends on base script)
		wp_enqueue_script(
			'cta-highlights-auto-insert',
			CTA_HIGHLIGHTS_URL . 'assets/js/auto-insert.js',
			array( 'cta-highlights-base' ),
			CTA_HIGHLIGHTS_VERSION,
			true
		);
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
