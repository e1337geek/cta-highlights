<?php
/**
 * Default CTA Highlights Template
 *
 * Available variables (all with defaults):
 * @var string $template        Template name
 * @var string $custom_class    Custom CSS class applied to wrapper
 * @var callable $get_att       Helper function: $get_att('key', 'default')
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Safe way to get button text (uses custom or default)
$cta_logo_img = $get_att( 'cta_logo_img', '#' );
$cta_logo_link = $get_att( 'cta_logo_link', '#' );
$cta_title = $get_att( 'cta_title', 'Default CTA Title' );
$cta_content = $get_att( 'cta_content', 'This is the default CTA content. Update it via the shortcode attributes.' );
$cta_button_text = $get_att( 'cta_button_text', 'Click Here' );
$cta_button_url = $get_att( 'cta_button_url', '#' );
?>

<div class="cta-container">
    <div class="cta-logo-container">
        <a href="<?php echo esc_url( $cta_logo_link ); ?>"><img class="cta-logo" src="<?php echo esc_url( $cta_logo_img ); ?>" /></a>
    </div>
    <div class="cta-content-container">
        <p class="cta-title"><?php echo esc_html( $cta_title ); ?></p>
        <p class="cta-content"><?php echo wp_kses_post( $content ); ?></p>
        <a class="cta-button button" href="<?php echo esc_url( $cta_button_url ); ?>"><?php echo esc_html( $cta_button_text ); ?></a>
    </div>
</div>
