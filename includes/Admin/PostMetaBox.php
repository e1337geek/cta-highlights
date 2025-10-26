<?php
/**
 * Post meta box for disabling auto-insertion
 *
 * @package CTAHighlights\Admin
 */

namespace CTAHighlights\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post meta box class
 */
class PostMetaBox {

	/**
	 * Meta key for storing disable flag
	 *
	 * @var string
	 */
	const META_KEY = '_cta_highlights_disable_auto_insert';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box' ) );
	}

	/**
	 * Add meta box to post editor
	 *
	 * @return void
	 */
	public function add_meta_box() {
		$post_types = get_post_types( array( 'public' => true ), 'names' );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'cta_highlights_auto_insert',
				__( 'CTA Auto-Insertion', 'cta-highlights' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render meta box content
	 *
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'cta_highlights_meta_box', 'cta_highlights_meta_box_nonce' );

		$disabled = get_post_meta( $post->ID, self::META_KEY, true );
		?>
		<div class="cta-disable-metabox">
			<label>
				<input type="checkbox" name="cta_highlights_disable_auto_insert" value="1" <?php checked( $disabled, '1' ); ?>>
				<?php esc_html_e( 'Disable auto-inserted CTAs on this post', 'cta-highlights' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Check this box to prevent any CTAs from being automatically inserted into this post\'s content.', 'cta-highlights' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Save meta box data
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_meta_box( $post_id ) {
		// Check nonce
		if ( ! isset( $_POST['cta_highlights_meta_box_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cta_highlights_meta_box_nonce'] ) ), 'cta_highlights_meta_box' ) ) {
			return;
		}

		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save or delete meta
		if ( isset( $_POST['cta_highlights_disable_auto_insert'] ) && '1' === $_POST['cta_highlights_disable_auto_insert'] ) {
			update_post_meta( $post_id, self::META_KEY, '1' );
		} else {
			delete_post_meta( $post_id, self::META_KEY );
		}
	}
}
