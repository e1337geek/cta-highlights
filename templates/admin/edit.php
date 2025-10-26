<?php
/**
 * Admin edit/add form template
 *
 * @package CTAHighlights
 * @var array|null $cta CTA data (null for new CTA)
 * @var array $all_ctas All CTAs for fallback dropdown
 * @var array $post_types Available post types
 * @var array $categories Available categories
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_new = ! $cta;
$cta    = $cta ? $cta : array();

// Default values
$defaults = array(
	'id'                  => 0,
	'name'                => '',
	'content'             => '',
	'status'              => 'active',
	'post_types'          => array(),
	'category_mode'       => 'include',
	'category_ids'        => array(),
	'storage_conditions'  => array(),
	'insertion_direction' => 'forward',
	'insertion_position'  => 3,
	'fallback_behavior'   => 'end',
	'fallback_cta_id'     => null,
);

$cta = wp_parse_args( $cta, $defaults );
?>

<div class="wrap">
	<h1><?php echo $is_new ? esc_html__( 'Add New CTA', 'cta-highlights' ) : esc_html__( 'Edit CTA', 'cta-highlights' ); ?></h1>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'CTA updated successfully.', 'cta-highlights' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['created'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'CTA created successfully.', 'cta-highlights' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['duplicated'] ) ) : ?>
		<div class="notice notice-info is-dismissible">
			<p><?php esc_html_e( 'CTA duplicated successfully. You are now editing the copy.', 'cta-highlights' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'cta_auto_insert_save' ); ?>
		<input type="hidden" name="cta_id" value="<?php echo esc_attr( $cta['id'] ); ?>">
		<input type="hidden" name="cta_auto_insert_save" value="1">

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<!-- Name -->
					<div class="postbox">
						<div class="postbox-header">
							<h2><?php esc_html_e( 'CTA Name', 'cta-highlights' ); ?></h2>
						</div>
						<div class="inside">
							<input type="text" name="name" id="cta-name" class="large-text" value="<?php echo esc_attr( $cta['name'] ); ?>" placeholder="<?php esc_attr_e( 'Enter CTA name (for internal use)', 'cta-highlights' ); ?>" required>
						</div>
					</div>

					<!-- Content -->
					<div class="postbox">
						<div class="postbox-header">
							<h2><?php esc_html_e( 'CTA Content', 'cta-highlights' ); ?></h2>
						</div>
						<div class="inside">
							<?php
							wp_editor(
								$cta['content'],
								'cta_content',
								array(
									'textarea_name' => 'content',
									'textarea_rows' => 15,
									'media_buttons' => true,
									'teeny'         => false,
									'quicktags'     => true,
								)
							);
							?>
							<p class="description">
								<?php esc_html_e( 'You can use shortcodes, HTML, and embed media. Switch to "Text" mode to add custom CSS classes.', 'cta-highlights' ); ?>
							</p>
						</div>
					</div>

					<!-- Conditions -->
					<div class="postbox">
						<div class="postbox-header">
							<h2><?php esc_html_e( 'Display Conditions', 'cta-highlights' ); ?></h2>
						</div>
						<div class="inside">
							<!-- Post Types -->
							<h3><?php esc_html_e( 'Post Types', 'cta-highlights' ); ?></h3>
							<p class="description"><?php esc_html_e( 'Select which post types should display this CTA. Leave empty for all post types.', 'cta-highlights' ); ?></p>
							<fieldset>
								<?php foreach ( $post_types as $post_type ) : ?>
									<label style="display: block; margin: 5px 0;">
										<input type="checkbox" name="post_types[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $cta['post_types'], true ) ); ?>>
										<?php echo esc_html( $post_type->labels->name ); ?>
									</label>
								<?php endforeach; ?>
							</fieldset>

							<hr style="margin: 20px 0;">

							<!-- Categories -->
							<h3><?php esc_html_e( 'Categories', 'cta-highlights' ); ?></h3>
							<p>
								<label>
									<input type="radio" name="category_mode" value="include" <?php checked( $cta['category_mode'], 'include' ); ?>>
									<?php esc_html_e( 'Include: Show CTA only on posts in these categories', 'cta-highlights' ); ?>
								</label><br>
								<label>
									<input type="radio" name="category_mode" value="exclude" <?php checked( $cta['category_mode'], 'exclude' ); ?>>
									<?php esc_html_e( 'Exclude: Show CTA on all posts except those in these categories', 'cta-highlights' ); ?>
								</label>
							</p>
							<select name="category_ids[]" multiple size="10" style="width: 100%; max-width: 400px;">
								<?php foreach ( $categories as $category ) : ?>
									<option value="<?php echo esc_attr( $category->term_id ); ?>" <?php selected( in_array( $category->term_id, $cta['category_ids'], true ) ); ?>>
										<?php echo esc_html( $category->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Hold Ctrl (Cmd on Mac) to select multiple categories.', 'cta-highlights' ); ?></p>

							<hr style="margin: 20px 0;">

							<!-- Storage Conditions -->
							<h3><?php esc_html_e( 'LocalStorage / Cookie Conditions', 'cta-highlights' ); ?></h3>
							<p class="description"><?php esc_html_e( 'Add conditions based on localStorage or cookie values. All conditions must pass (AND logic).', 'cta-highlights' ); ?></p>
							<div id="storage-conditions-container">
								<?php if ( ! empty( $cta['storage_conditions'] ) ) : ?>
									<?php foreach ( $cta['storage_conditions'] as $condition ) : ?>
										<?php include CTA_HIGHLIGHTS_DIR . 'templates/admin/partials/storage-condition-row.php'; ?>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
							<button type="button" class="button" id="add-storage-condition"><?php esc_html_e( 'Add Condition', 'cta-highlights' ); ?></button>
						</div>
					</div>

					<!-- Insertion Settings -->
					<div class="postbox">
						<div class="postbox-header">
							<h2><?php esc_html_e( 'Insertion Settings', 'cta-highlights' ); ?></h2>
						</div>
						<div class="inside">
							<table class="form-table">
								<tr>
									<th scope="row"><?php esc_html_e( 'Direction', 'cta-highlights' ); ?></th>
									<td>
										<label>
											<input type="radio" name="insertion_direction" value="forward" <?php checked( $cta['insertion_direction'], 'forward' ); ?>>
											<?php esc_html_e( 'Forward (from start)', 'cta-highlights' ); ?>
										</label><br>
										<label>
											<input type="radio" name="insertion_direction" value="reverse" <?php checked( $cta['insertion_direction'], 'reverse' ); ?>>
											<?php esc_html_e( 'Reverse (from end)', 'cta-highlights' ); ?>
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="insertion-position"><?php esc_html_e( 'Position (element number)', 'cta-highlights' ); ?></label></th>
									<td>
										<input type="number" name="insertion_position" id="insertion-position" value="<?php echo esc_attr( $cta['insertion_position'] ); ?>" min="1" step="1" class="small-text">
										<p class="description">
											<?php esc_html_e( 'If "Forward": Insert after this element number (e.g., 3 = after 3rd element)', 'cta-highlights' ); ?><br>
											<?php esc_html_e( 'If "Reverse": Insert this many elements from the end (e.g., 2 = before last 2 elements)', 'cta-highlights' ); ?>
										</p>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'If content is shorter', 'cta-highlights' ); ?></th>
									<td>
										<label>
											<input type="radio" name="fallback_behavior" value="end" <?php checked( $cta['fallback_behavior'], 'end' ); ?>>
											<?php esc_html_e( 'Insert at the end', 'cta-highlights' ); ?>
										</label><br>
										<label>
											<input type="radio" name="fallback_behavior" value="skip" <?php checked( $cta['fallback_behavior'], 'skip' ); ?>>
											<?php esc_html_e( 'Don\'t insert (skip)', 'cta-highlights' ); ?>
										</label>
									</td>
								</tr>
							</table>
						</div>
					</div>
				</div>

				<!-- Sidebar -->
				<div id="postbox-container-1" class="postbox-container">
					<!-- Publish -->
					<div class="postbox">
						<div class="postbox-header">
							<h2><?php esc_html_e( 'Publish', 'cta-highlights' ); ?></h2>
						</div>
						<div class="inside">
							<div class="submitbox">
								<div id="minor-publishing">
									<label>
										<strong><?php esc_html_e( 'Status:', 'cta-highlights' ); ?></strong><br>
										<select name="status">
											<option value="active" <?php selected( $cta['status'], 'active' ); ?>><?php esc_html_e( 'Active', 'cta-highlights' ); ?></option>
											<option value="inactive" <?php selected( $cta['status'], 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'cta-highlights' ); ?></option>
										</select>
									</label>
								</div>
								<div id="major-publishing-actions">
									<div id="delete-action">
										<?php if ( ! $is_new ) : ?>
											<a class="submitdelete deletion" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=cta-auto-insert&action=delete&id=' . $cta['id'] ), 'delete_cta_' . $cta['id'] ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this CTA?', 'cta-highlights' ); ?>');">
												<?php esc_html_e( 'Delete', 'cta-highlights' ); ?>
											</a>
										<?php endif; ?>
									</div>
									<div id="publishing-action">
										<input type="submit" class="button button-primary button-large" value="<?php echo $is_new ? esc_attr__( 'Create CTA', 'cta-highlights' ) : esc_attr__( 'Update CTA', 'cta-highlights' ); ?>">
									</div>
									<div class="clear"></div>
								</div>
							</div>
						</div>
					</div>

					<!-- Fallback -->
					<div class="postbox">
						<div class="postbox-header">
							<h2><?php esc_html_e( 'Fallback CTA', 'cta-highlights' ); ?></h2>
						</div>
						<div class="inside">
							<p class="description"><?php esc_html_e( 'If this CTA\'s conditions are not met, try displaying this CTA instead:', 'cta-highlights' ); ?></p>
							<select name="fallback_cta_id" style="width: 100%;">
								<option value=""><?php esc_html_e( '— None —', 'cta-highlights' ); ?></option>
								<?php foreach ( $all_ctas as $fallback_cta ) : ?>
									<?php if ( $fallback_cta['id'] !== $cta['id'] ) : ?>
										<option value="<?php echo esc_attr( $fallback_cta['id'] ); ?>" <?php selected( $cta['fallback_cta_id'], $fallback_cta['id'] ); ?>>
											<?php echo esc_html( $fallback_cta['name'] ); ?>
										</option>
									<?php endif; ?>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>

<!-- Storage Condition Row Template -->
<script type="text/template" id="storage-condition-template">
	<?php
	$condition = array(
		'key'      => '',
		'operator' => '=',
		'value'    => '',
		'datatype' => 'string',
	);
	include CTA_HIGHLIGHTS_DIR . 'templates/admin/partials/storage-condition-row.php';
	?>
</script>

<script>
(function($) {
	$('#add-storage-condition').on('click', function() {
		var template = $('#storage-condition-template').html();
		$('#storage-conditions-container').append(template);
	});

	$(document).on('click', '.remove-storage-condition', function() {
		$(this).closest('.storage-condition-row').remove();
	});
})(jQuery);
</script>

<style>
.storage-condition-row {
	display: flex;
	gap: 10px;
	align-items: center;
	margin-bottom: 10px;
	padding: 10px;
	background: #f9f9f9;
	border: 1px solid #ddd;
	border-radius: 3px;
}
.storage-condition-row input[type="text"] {
	flex: 1;
	min-width: 0;
}
.storage-condition-row select {
	width: auto;
}
</style>
