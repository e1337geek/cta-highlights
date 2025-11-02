<?php
/**
 * Shortcode Handler
 *
 * Handles registration and rendering of the [cta_highlights] shortcode.
 *
 * @package CTAHighlights\Shortcode
 * @since 1.0.0
 */

namespace CTAHighlights\Shortcode;

use CTAHighlights\Template\Loader;
use CTAHighlights\Template\Registry;

/**
 * Handler class for CTA Highlights shortcode
 *
 * @since 1.0.0
 */
class Handler {
	/**
	 * Template loader instance
	 *
	 * @var Loader
	 */
	private $template_loader;

	/**
	 * Template registry instance
	 *
	 * @var Registry
	 */
	private $template_registry;

	/**
	 * Default shortcode attributes
	 *
	 * @var array
	 */
	private $default_atts = array(
		'template'           => 'default',
		'cta_title'          => '',
		'cta_text'           => '',
		'cta_button'         => '',
		'cta_link'           => '#',
		'cta_button_text'    => 'Learn More',
		'cta_button_url'     => '#',
		'background'         => '',
		'text_color'         => '',
		'alignment'          => 'center',
		'custom_class'       => '',
		'highlight'          => 'false',
		'highlight_duration' => '5',
	);

	/**
	 * Constructor
	 *
	 * @param Loader $template_loader Template loader instance.
	 */
	public function __construct( Loader $template_loader ) {
		$this->template_loader   = $template_loader;
		$this->template_registry = Registry::instance();
	}

	/**
	 * Initialize the shortcode handler
	 *
	 * @return void
	 */
	public function init() {
		add_shortcode( 'cta_highlights', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render the CTA highlights shortcode
	 *
	 * @param array|string $atts    Shortcode attributes.
	 * @param string|null  $content Shortcode content.
	 * @return string Rendered shortcode output.
	 */
	public function render_shortcode( $atts = array(), $content = null ) {
		$atts = $this->normalize_attributes( $atts );
		$atts = wp_parse_args( $atts, $this->default_atts );
		$atts = apply_filters( 'cta_highlights_shortcode_atts', $atts );

		$template_name = sanitize_file_name( $atts['template'] );

		$this->template_registry->register( $template_name );

		$template_path = $this->template_loader->locate_template( $template_name );

		if ( ! $template_path ) {
			return $this->render_error( $template_name );
		}

		$processed_content = $this->process_content( $content );
		$atts['content']   = $processed_content;

		$atts = apply_filters( 'cta_highlights_template_args', $atts, $template_name, $template_path );

		$wrapper_html = $this->build_wrapper_html( $atts, $template_name );

		$template_output = $this->template_loader->render( $template_path, $atts );

		$output = $wrapper_html['opening'] . $template_output . $wrapper_html['closing'];

		return apply_filters( 'cta_highlights_template_output', $output, $template_name, $atts );
	}

	/**
	 * Normalize shortcode attributes to array format
	 *
	 * @param mixed $atts Shortcode attributes.
	 * @return array Normalized attributes.
	 */
	private function normalize_attributes( $atts ) {
		if ( empty( $atts ) ) {
			return array();
		}

		if ( is_string( $atts ) ) {
			return array( $atts );
		}

		return (array) $atts;
	}

	/**
	 * Process shortcode content
	 *
	 * @param string|null $content Shortcode content.
	 * @return string Processed content.
	 */
	private function process_content( $content ) {
		if ( null === $content || '' === $content ) {
			return '';
		}

		$content = do_shortcode( $content );
		$content = force_balance_tags( $content );

		return $content;
	}

	/**
	 * Build wrapper HTML for the CTA
	 *
	 * @param array  $atts          Shortcode attributes.
	 * @param string $template_name Template name.
	 * @return array Array with 'opening' and 'closing' wrapper HTML.
	 */
	private function build_wrapper_html( array $atts, $template_name ) {
		$wrapper_classes = array( 'cta-highlights-wrapper' );

		if ( ! empty( $atts['custom_class'] ) ) {
			$custom_classes = explode( ' ', $atts['custom_class'] );
			foreach ( $custom_classes as $class ) {
				$sanitized_class = sanitize_html_class( $class );
				if ( ! empty( $sanitized_class ) ) {
					$wrapper_classes[] = $sanitized_class;
				}
			}
		}

		$wrapper_classes[] = 'cta-highlights-template-' . sanitize_html_class( $template_name );

		if ( 'true' === $atts['highlight'] ) {
			$wrapper_classes[] = 'cta-highlights-enabled';
		}

		$wrapper_class_attr = implode( ' ', $wrapper_classes );

		$data_attrs = '';
		if ( 'true' === $atts['highlight'] ) {
			$unique_id = wp_unique_id( 'cta-highlights-' );

			$data_attrs .= ' data-highlight="true"';
			$data_attrs .= ' data-template="' . esc_attr( $template_name ) . '"';
			$data_attrs .= ' data-duration="' . esc_attr( absint( $atts['highlight_duration'] ) ) . '"';
			$data_attrs .= ' role="dialog"';
			$data_attrs .= ' aria-modal="false"';
			$data_attrs .= ' aria-labelledby="' . esc_attr( $unique_id ) . '"';
		} else {
			// Add aria-label for non-highlighted CTAs to identify promotional content.
			$data_attrs .= ' aria-label="Call to Action"';
		}

		$opening = '<section class="' . esc_attr( $wrapper_class_attr ) . '"' . $data_attrs . '>';
		$closing = '</section>';

		return array(
			'opening' => $opening,
			'closing' => $closing,
		);
	}

	/**
	 * Render error message for missing template
	 *
	 * @param string $template_name Template name that was not found.
	 * @return string Error message HTML or empty string for non-admin users.
	 */
	private function render_error( $template_name ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}

		return sprintf(
			'<div class="cta-highlights-error" style="border:2px solid #dc3232;padding:10px;background:#fff;color:#dc3232;margin:1rem 0;"><strong>%s:</strong> %s</div>',
			esc_html__( 'CTA Highlights Error', 'cta-highlights' ),
			sprintf(
				/* translators: %s: template name */
				esc_html__( 'Template "%s" not found in theme or plugin.', 'cta-highlights' ),
				esc_html( $template_name )
			)
		);
	}
}
