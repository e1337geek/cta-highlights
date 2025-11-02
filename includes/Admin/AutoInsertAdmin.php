<?php
/**
 * Auto-insertion admin interface
 *
 * @package CTAHighlights\Admin
 */

namespace CTAHighlights\Admin;

use CTAHighlights\AutoInsertion\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin interface for managing auto-insertion CTAs
 */
class AutoInsertAdmin {

	/**
	 * Database instance
	 *
	 * @var Database
	 */
	private $database;

	/**
	 * Constructor
	 *
	 * @param Database $database Database instance.
	 */
	public function __construct( $database ) {
		$this->database = $database;

		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
	}

	/**
	 * Add admin menu
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page(
			__( 'CTA Auto-Insert', 'cta-highlights' ),
			__( 'CTA Auto-Insert', 'cta-highlights' ),
			'manage_options',
			'cta-auto-insert',
			array( $this, 'render_page' ),
			'dashicons-megaphone',
			30
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'toplevel_page_cta-auto-insert' !== $hook ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style(
			'cta-highlights-admin',
			CTA_HIGHLIGHTS_URL . 'assets/css/admin.css',
			array(),
			CTA_HIGHLIGHTS_VERSION
		);
	}

	/**
	 * Handle admin actions (save, delete, duplicate)
	 *
	 * @return void
	 */
	public function handle_actions() {
		// Check if we're on the right page (check both GET and POST for page parameter).
		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
		$page = isset( $_GET['page'] ) ? $_GET['page'] : ( isset( $_POST['page'] ) ? $_POST['page'] : '' );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
		if ( 'cta-auto-insert' !== $page ) {
			return;
		}

		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

		// Handle delete.
		if ( 'delete' === $action && isset( $_GET['id'] ) ) {
			$id = absint( $_GET['id'] );
			if ( ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'delete_cta_' . $id ) ) {
				wp_die( esc_html__( 'Security check failed', 'cta-highlights' ) );
			}

			$this->database->delete( $id );
			wp_safe_redirect( admin_url( 'admin.php?page=cta-auto-insert&deleted=1' ) );
			exit;
		}

		// Handle duplicate.
		if ( 'duplicate' === $action && isset( $_GET['id'] ) ) {
			$id = absint( $_GET['id'] );
			if ( ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'duplicate_cta_' . $id ) ) {
				wp_die( esc_html__( 'Security check failed', 'cta-highlights' ) );
			}

			$new_id = $this->database->duplicate( $id );
			if ( $new_id ) {
				wp_safe_redirect( admin_url( 'admin.php?page=cta-auto-insert&action=edit&id=' . $new_id . '&duplicated=1' ) );
			} else {
				wp_safe_redirect( admin_url( 'admin.php?page=cta-auto-insert&error=duplicate_failed' ) );
			}
			exit;
		}

		// Handle save.
		if ( isset( $_POST['cta_auto_insert_save'] ) ) {
			if ( ! check_admin_referer( 'cta_auto_insert_save' ) ) {
				wp_die( esc_html__( 'Security check failed', 'cta-highlights' ) );
			}

			$data = $this->sanitize_form_data( $_POST );
			$id   = isset( $_POST['cta_id'] ) ? absint( $_POST['cta_id'] ) : 0;

			if ( $id ) {
				$this->database->update( $id, $data );
				wp_safe_redirect( admin_url( 'admin.php?page=cta-auto-insert&action=edit&id=' . $id . '&updated=1' ) );
			} else {
				$new_id = $this->database->insert( $data );
				wp_safe_redirect( admin_url( 'admin.php?page=cta-auto-insert&action=edit&id=' . $new_id . '&created=1' ) );
			}
			exit;
		}
	}

	/**
	 * Sanitize form data
	 *
	 * @param array $post_data POST data.
	 * @return array Sanitized data.
	 */
	private function sanitize_form_data( $post_data ) {
		$data = array();

		$data['name']     = isset( $post_data['name'] ) ? sanitize_text_field( wp_unslash( $post_data['name'] ) ) : '';
		$data['content']  = isset( $post_data['content'] ) ? wp_kses_post( wp_unslash( $post_data['content'] ) ) : '';
		$data['status']   = isset( $post_data['status'] ) ? sanitize_text_field( wp_unslash( $post_data['status'] ) ) : 'active';
		$data['cta_type'] = isset( $post_data['cta_type'] ) ? sanitize_text_field( wp_unslash( $post_data['cta_type'] ) ) : 'primary';

		// Post types.
		$data['post_types'] = isset( $post_data['post_types'] ) && is_array( $post_data['post_types'] )
			? array_map( 'sanitize_text_field', wp_unslash( $post_data['post_types'] ) )
			: array();

		// Categories.
		$data['category_mode'] = isset( $post_data['category_mode'] ) ? sanitize_text_field( wp_unslash( $post_data['category_mode'] ) ) : 'include';
		$data['category_ids']  = isset( $post_data['category_ids'] ) && is_array( $post_data['category_ids'] )
			? array_map( 'absint', wp_unslash( $post_data['category_ids'] ) )
			: array();

		// Storage conditions.
		$data['storage_conditions'] = $this->sanitize_storage_conditions( $post_data );

		// Insertion settings.
		$data['insertion_direction'] = isset( $post_data['insertion_direction'] ) ? sanitize_text_field( wp_unslash( $post_data['insertion_direction'] ) ) : 'forward';
		$data['insertion_position']  = isset( $post_data['insertion_position'] ) ? absint( $post_data['insertion_position'] ) : 3;
		$data['fallback_behavior']   = isset( $post_data['fallback_behavior'] ) ? sanitize_text_field( wp_unslash( $post_data['fallback_behavior'] ) ) : 'end';

		// Fallback CTA.
		$data['fallback_cta_id'] = isset( $post_data['fallback_cta_id'] ) && ! empty( $post_data['fallback_cta_id'] )
			? absint( $post_data['fallback_cta_id'] )
			: null;

		return $data;
	}

	/**
	 * Sanitize storage conditions from form data
	 *
	 * @param array $post_data POST data.
	 * @return array Sanitized storage conditions.
	 */
	private function sanitize_storage_conditions( $post_data ) {
		if ( ! isset( $post_data['storage_condition_key'] ) || ! is_array( $post_data['storage_condition_key'] ) ) {
			return array();
		}

		$conditions = array();
		$keys       = array_map( 'sanitize_text_field', wp_unslash( $post_data['storage_condition_key'] ) );
		$operators  = isset( $post_data['storage_condition_operator'] ) ? array_map( 'sanitize_text_field', wp_unslash( $post_data['storage_condition_operator'] ) ) : array();
		$values     = isset( $post_data['storage_condition_value'] ) ? array_map( 'sanitize_text_field', wp_unslash( $post_data['storage_condition_value'] ) ) : array();
		$datatypes  = isset( $post_data['storage_condition_datatype'] ) ? array_map( 'sanitize_text_field', wp_unslash( $post_data['storage_condition_datatype'] ) ) : array();

		foreach ( $keys as $index => $key ) {
			if ( empty( $key ) ) {
				continue;
			}

			$conditions[] = array(
				'key'      => $key,
				'operator' => isset( $operators[ $index ] ) ? $operators[ $index ] : '=',
				'value'    => isset( $values[ $index ] ) ? $values[ $index ] : '',
				'datatype' => isset( $datatypes[ $index ] ) ? $datatypes[ $index ] : 'string',
			);
		}

		return $conditions;
	}

	/**
	 * Render admin page
	 *
	 * @return void
	 */
	public function render_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Non-sensitive action parameter read.
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';

		if ( in_array( $action, array( 'edit', 'add' ), true ) ) {
			$this->render_edit_form();
		} else {
			$this->render_list();
		}
	}

	/**
	 * Render list page
	 *
	 * @return void
	 */
	private function render_list() {
		$list_table = new AutoInsertListTable( $this->database );
		$list_table->prepare_items();

		include CTA_HIGHLIGHTS_DIR . 'templates/admin/list.php';
	}

	/**
	 * Render edit form
	 *
	 * @return void
	 */
	private function render_edit_form() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Non-sensitive ID parameter read for display.
		$id  = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$cta = $id ? $this->database->get( $id ) : null;

		// Get all CTAs for fallback dropdown.
		$all_ctas = $this->database->get_all( array( 'status' => '' ) );

		// Get all post types.
		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		// Get all categories.
		$categories = get_categories( array( 'hide_empty' => false ) );

		include CTA_HIGHLIGHTS_DIR . 'templates/admin/edit.php';
	}
}
