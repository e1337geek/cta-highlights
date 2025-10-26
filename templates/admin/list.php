<?php
/**
 * Admin list page template
 *
 * @package CTAHighlights
 * @var AutoInsertListTable $list_table
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Auto-Insertion CTAs', 'cta-highlights' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=cta-auto-insert&action=add' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New', 'cta-highlights' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'CTA deleted successfully.', 'cta-highlights' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['error'] ) && 'duplicate_failed' === $_GET['error'] ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Failed to duplicate CTA.', 'cta-highlights' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post">
		<?php $list_table->display(); ?>
	</form>
</div>

<style>
.status-active {
	color: #46b450;
	font-weight: 600;
}
.status-inactive {
	color: #dc3232;
	font-weight: 600;
}
</style>
