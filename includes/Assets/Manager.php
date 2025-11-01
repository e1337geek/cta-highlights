<?php
namespace CTAHighlights\Assets;

use CTAHighlights\Template\Registry;

class Manager {
	private $plugin_dir;
	private $plugin_url;
	private $version;
	private $has_shortcode = false;

	public function __construct( $plugin_dir, $plugin_url, $version ) {
		$this->plugin_dir = trailingslashit( $plugin_dir );
		$this->plugin_url = trailingslashit( $plugin_url );
		$this->version    = $version;
	}

	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'enqueue_template_styles' ) );
		add_action( 'wp_head', array( $this, 'add_resource_hints' ), 1 );
	}

	public function maybe_enqueue_assets() {
		if ( $this->detect_shortcode() ) {
			$this->has_shortcode = true;
			$this->enqueue_base_assets();
		}
	}

	private function detect_shortcode() {
		global $post;

		if ( is_singular() && $post instanceof \WP_Post && has_shortcode( $post->post_content, 'cta_highlights' ) ) {
			return true;
		}

		if ( $this->check_widgets_for_shortcode() ) {
			return true;
		}

		$force_enqueue = apply_filters( 'cta_highlights_force_enqueue', false );

		if ( $force_enqueue ) {
			return true;
		}

		return false;
	}

	private function check_widgets_for_shortcode() {
		$sidebars = wp_get_sidebars_widgets();

		if ( ! is_array( $sidebars ) ) {
			return false;
		}

		foreach ( $sidebars as $sidebar_widgets ) {
			if ( ! is_array( $sidebar_widgets ) ) {
				continue;
			}

			foreach ( $sidebar_widgets as $widget_id ) {
				$widget_base = preg_replace( '/-\d+$/', '', $widget_id );
				$widget_data = get_option( "widget_{$widget_base}" );

				if ( is_array( $widget_data ) ) {
					$content = wp_json_encode( $widget_data );
					if ( false !== strpos( $content, '[cta_highlights' ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	public function enqueue_base_assets() {
		$css_file = $this->plugin_dir . 'assets/css/cta-highlights.css';
		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'cta-highlights-base',
				$this->plugin_url . 'assets/css/cta-highlights.css',
				array(),
				filemtime( $css_file ),
				'all'
			);
		}

		$js_file = $this->plugin_dir . 'assets/js/cta-highlights.js';
		if ( file_exists( $js_file ) ) {
			wp_enqueue_script(
				'cta-highlights-base',
				$this->plugin_url . 'assets/js/cta-highlights.js',
				array(),
				filemtime( $js_file ),
				true
			);

			wp_localize_script(
				'cta-highlights-base',
				'ctaHighlightsConfig',
				$this->get_js_config()
			);
		}
	}

	private function get_js_config() {
		return array(
			'globalCooldown'   => absint( apply_filters( 'cta_highlights_global_cooldown', 3600 ) ),
			'templateCooldown' => absint( apply_filters( 'cta_highlights_template_cooldown', 86400 ) ),
			'overlayColor'     => sanitize_text_field( apply_filters( 'cta_highlights_overlay_color', 'rgba(0, 0, 0, 0.7)' ) ),
			'debug'            => (bool) apply_filters( 'cta_highlights_debug', defined( 'WP_DEBUG' ) && WP_DEBUG ),
		);
	}

	public function enqueue_template_styles() {
		if ( ! $this->has_shortcode ) {
			return;
		}

		$registry       = Registry::instance();
		$templates_used = $registry->get_all();

		if ( empty( $templates_used ) ) {
			return;
		}

		foreach ( $templates_used as $template_name ) {
			$this->enqueue_template_css( $template_name );
		}
	}

	private function enqueue_template_css( $template_name ) {
		$theme_css_path = get_stylesheet_directory() . '/cta-highlights-templates/' . $template_name . '.css';
		$theme_css_url  = get_stylesheet_directory_uri() . '/cta-highlights-templates/' . $template_name . '.css';

		$plugin_css_path = $this->plugin_dir . 'assets/css/templates/' . $template_name . '.css';
		$plugin_css_url  = $this->plugin_url . 'assets/css/templates/' . $template_name . '.css';

		if ( file_exists( $theme_css_path ) ) {
			wp_enqueue_style(
				'cta-highlights-template-' . $template_name,
				$theme_css_url,
				array( 'cta-highlights-base' ),
				filemtime( $theme_css_path ),
				'all'
			);
		} elseif ( file_exists( $plugin_css_path ) ) {
			wp_enqueue_style(
				'cta-highlights-template-' . $template_name,
				$plugin_css_url,
				array( 'cta-highlights-base' ),
				filemtime( $plugin_css_path ),
				'all'
			);
		}
	}

	public function add_resource_hints() {
		if ( ! $this->has_shortcode ) {
			return;
		}

		echo '<link rel="dns-prefetch" href="' . esc_url( $this->plugin_url ) . '">' . "\n";

		if ( apply_filters( 'cta_highlights_add_preconnect', true ) ) {
			echo '<link rel="preconnect" href="' . esc_url( $this->plugin_url ) . '">' . "\n";
		}
	}
}
