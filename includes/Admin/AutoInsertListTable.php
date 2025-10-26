<?php
/**
 * Auto-insertion CTA list table
 *
 * @package CTAHighlights\Admin
 */

namespace CTAHighlights\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List table for auto-insertion CTAs
 */
class AutoInsertListTable extends \WP_List_Table {

	/**
	 * Database instance
	 *
	 * @var \CTAHighlights\AutoInsertion\Database
	 */
	private $database;

	/**
	 * Constructor
	 *
	 * @param \CTAHighlights\AutoInsertion\Database $database Database instance.
	 */
	public function __construct( $database ) {
		parent::__construct(
			array(
				'singular' => 'cta',
				'plural'   => 'ctas',
				'ajax'     => false,
			)
		);

		$this->database = $database;
	}

	/**
	 * Get columns
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'name'       => __( 'Name', 'cta-highlights' ),
			'status'     => __( 'Status', 'cta-highlights' ),
			'cta_type'   => __( 'Type', 'cta-highlights' ),
			'conditions' => __( 'Conditions', 'cta-highlights' ),
			'insertion'  => __( 'Insertion', 'cta-highlights' ),
			'fallback'   => __( 'Fallback', 'cta-highlights' ),
		);
	}

	/**
	 * Get sortable columns
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'name'   => array( 'name', true ),
			'status' => array( 'status', false ),
		);
	}

	/**
	 * Prepare items for display
	 *
	 * @return void
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'id';
		$order   = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'ASC';

		$this->items = $this->database->get_all(
			array(
				'status'  => '',
				'orderby' => $orderby,
				'order'   => $order,
			)
		);
	}

	/**
	 * Column: checkbox
	 *
	 * @param array $item Item data.
	 * @return string
	 */
	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="cta_ids[]" value="%d" />', $item['id'] );
	}

	/**
	 * Column: name
	 *
	 * @param array $item Item data.
	 * @return string
	 */
	protected function column_name( $item ) {
		$edit_url   = add_query_arg(
			array(
				'page'   => 'cta-auto-insert',
				'action' => 'edit',
				'id'     => $item['id'],
			),
			admin_url( 'admin.php' )
		);
		$delete_url = wp_nonce_url(
			add_query_arg(
				array(
					'page'   => 'cta-auto-insert',
					'action' => 'delete',
					'id'     => $item['id'],
				),
				admin_url( 'admin.php' )
			),
			'delete_cta_' . $item['id']
		);
		$duplicate_url = wp_nonce_url(
			add_query_arg(
				array(
					'page'   => 'cta-auto-insert',
					'action' => 'duplicate',
					'id'     => $item['id'],
				),
				admin_url( 'admin.php' )
			),
			'duplicate_cta_' . $item['id']
		);

		$actions = array(
			'edit'      => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), __( 'Edit', 'cta-highlights' ) ),
			'duplicate' => sprintf( '<a href="%s">%s</a>', esc_url( $duplicate_url ), __( 'Duplicate', 'cta-highlights' ) ),
			'delete'    => sprintf( '<a href="%s" onclick="return confirm(\'%s\');">%s</a>', esc_url( $delete_url ), esc_js( __( 'Are you sure you want to delete this CTA?', 'cta-highlights' ) ), __( 'Delete', 'cta-highlights' ) ),
		);

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $edit_url ),
			esc_html( $item['name'] ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Column: status
	 *
	 * @param array $item Item data.
	 * @return string
	 */
	protected function column_status( $item ) {
		$status_labels = array(
			'active'   => __( 'Active', 'cta-highlights' ),
			'inactive' => __( 'Inactive', 'cta-highlights' ),
		);

		$status = isset( $item['status'] ) ? $item['status'] : 'active';
		$label  = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : $status;

		$class = 'active' === $status ? 'status-active' : 'status-inactive';

		return sprintf( '<span class="%s">%s</span>', esc_attr( $class ), esc_html( $label ) );
	}

	/**
	 * Column: CTA type
	 *
	 * @param array $item Item data.
	 * @return string
	 */
	protected function column_cta_type( $item ) {
		$type_labels = array(
			'primary'  => __( 'Primary', 'cta-highlights' ),
			'fallback' => __( 'Fallback Only', 'cta-highlights' ),
		);

		$type  = isset( $item['cta_type'] ) ? $item['cta_type'] : 'primary';
		$label = isset( $type_labels[ $type ] ) ? $type_labels[ $type ] : $type;

		$class = 'primary' === $type ? 'cta-type-primary' : 'cta-type-fallback';

		return sprintf( '<span class="%s">%s</span>', esc_attr( $class ), esc_html( $label ) );
	}

	/**
	 * Column: conditions
	 *
	 * @param array $item Item data.
	 * @return string
	 */
	protected function column_conditions( $item ) {
		$conditions = array();

		// Post types
		if ( ! empty( $item['post_types'] ) ) {
			$post_type_names = array();
			foreach ( $item['post_types'] as $post_type ) {
				$post_type_obj = get_post_type_object( $post_type );
				$post_type_names[] = $post_type_obj ? $post_type_obj->labels->name : $post_type;
			}
			$conditions[] = sprintf( __( 'Post Types: %s', 'cta-highlights' ), implode( ', ', $post_type_names ) );
		}

		// Categories
		if ( ! empty( $item['category_ids'] ) ) {
			$category_names = array();
			foreach ( $item['category_ids'] as $cat_id ) {
				$category = get_category( $cat_id );
				if ( $category ) {
					$category_names[] = $category->name;
				}
			}
			if ( ! empty( $category_names ) ) {
				$mode = isset( $item['category_mode'] ) ? $item['category_mode'] : 'include';
				$mode_label = 'include' === $mode ? __( 'Include', 'cta-highlights' ) : __( 'Exclude', 'cta-highlights' );
				$conditions[] = sprintf( __( 'Categories (%s): %s', 'cta-highlights' ), $mode_label, implode( ', ', $category_names ) );
			}
		}

		// Storage conditions
		if ( ! empty( $item['storage_conditions'] ) ) {
			$count = count( $item['storage_conditions'] );
			$conditions[] = sprintf( _n( '%d localStorage condition', '%d localStorage conditions', $count, 'cta-highlights' ), $count );
		}

		return ! empty( $conditions ) ? implode( '<br>', $conditions ) : __( 'None', 'cta-highlights' );
	}

	/**
	 * Column: insertion
	 *
	 * @param array $item Item data.
	 * @return string
	 */
	protected function column_insertion( $item ) {
		$direction = isset( $item['insertion_direction'] ) ? $item['insertion_direction'] : 'forward';
		$position  = isset( $item['insertion_position'] ) ? absint( $item['insertion_position'] ) : 3;
		$fallback  = isset( $item['fallback_behavior'] ) ? $item['fallback_behavior'] : 'end';

		$direction_label = 'forward' === $direction
			? __( 'After element', 'cta-highlights' )
			: __( 'From end, element', 'cta-highlights' );

		$fallback_label = 'end' === $fallback
			? __( 'or last', 'cta-highlights' )
			: __( 'or skip', 'cta-highlights' );

		return sprintf(
			'%s #%d (%s)',
			$direction_label,
			$position,
			$fallback_label
		);
	}

	/**
	 * Column: fallback
	 *
	 * @param array $item Item data.
	 * @return string
	 */
	protected function column_fallback( $item ) {
		if ( empty( $item['fallback_cta_id'] ) ) {
			return '—';
		}

		$fallback = $this->database->get( $item['fallback_cta_id'] );

		if ( ! $fallback ) {
			return sprintf( '<em>%s</em>', __( 'CTA not found', 'cta-highlights' ) );
		}

		return esc_html( $fallback['name'] );
	}

	/**
	 * Default column display
	 *
	 * @param array  $item Item data.
	 * @param string $column_name Column name.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '—';
	}

	/**
	 * Get bulk actions
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'cta-highlights' ),
		);
	}

	/**
	 * Display when no items
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No auto-insertion CTAs found.', 'cta-highlights' );
	}
}
