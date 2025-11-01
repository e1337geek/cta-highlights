<?php
/**
 * Example Template: Custom Defaults
 *
 * This template demonstrates how theme developers can now override
 * default attribute values on a per-template basis.
 *
 * Available variables:
 * @var CTAHighlights\Template\ViewData $view
 * @var callable $get_att - Helper function: $get_att('key', 'default')
 * @var string $template        Template name
 * @var string $content          Shortcode content
 * @var string $custom_class    Custom CSS class applied to wrapper
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Each template can define its own default values!
// These defaults will be used ONLY when the shortcode doesn't provide a value
$cta_logo_img    = $get_att( 'cta_logo_img', 'https://example.com/custom-logo.png' );
$cta_logo_link   = $get_att( 'cta_logo_link', 'https://example.com' );
$cta_title       = $get_att( 'cta_title', 'Custom Template Default Title' );
$cta_content     = $get_att( 'cta_content', 'This template has its own default content!' );
$cta_button_text = $get_att( 'cta_button_text', 'Custom Action' );
$cta_button_url  = $get_att( 'cta_button_url', 'https://example.com/action' );

// You can also add template-specific custom attributes with defaults
$badge_text      = $get_att( 'badge_text', 'New!' );
$show_badge      = $get_att( 'show_badge', 'true' );
?>

<div class="cta-container cta-custom-defaults">
	<?php if ( 'true' === $show_badge ) : ?>
		<div class="cta-badge"><?php echo esc_html( $badge_text ); ?></div>
	<?php endif; ?>

	<div class="cta-logo-container">
		<a href="<?php echo esc_url( $cta_logo_link ); ?>">
			<img class="cta-logo" src="<?php echo esc_url( $cta_logo_img ); ?>" alt="CTA Logo" />
		</a>
	</div>

	<div class="cta-content-container">
		<h3 class="cta-title"><?php echo esc_html( $cta_title ); ?></h3>
		<div class="cta-content">
			<?php
			// Use $content if provided via shortcode body, otherwise use cta_content attribute
			echo wp_kses_post( ! empty( $content ) ? $content : $cta_content );
			?>
		</div>
		<a class="cta-button button" href="<?php echo esc_url( $cta_button_url ); ?>">
			<?php echo esc_html( $cta_button_text ); ?>
		</a>
	</div>
</div>

<!--
USAGE EXAMPLES:

1. Using template defaults (no attributes provided):
   [cta_highlights template="example-custom-defaults"][/cta_highlights]
   Result: Shows all the default values defined in this template

2. Overriding some defaults:
   [cta_highlights template="example-custom-defaults" cta_title="My Custom Title"][/cta_highlights]
   Result: Uses "My Custom Title" but keeps all other template defaults

3. Overriding all values:
   [cta_highlights template="example-custom-defaults"
       cta_title="Special Offer"
       cta_button_text="Get Started"
       cta_button_url="/signup"
       show_badge="false"]
   Result: All custom values, badge hidden

4. Using custom attributes specific to this template:
   [cta_highlights template="example-custom-defaults" badge_text="Limited Time!"][/cta_highlights]
   Result: Shows custom badge text with other template defaults
-->
