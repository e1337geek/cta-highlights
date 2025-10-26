<?php
namespace CTAHighlights\Core;
use CTAHighlights\Template\Loader;
use CTAHighlights\Template\Registry;
use CTAHighlights\Assets\Manager;
use CTAHighlights\Shortcode\Handler;

final class Plugin {
	private static $instance = null;
	private $version = '1.0.0';
	private $plugin_dir;
	private $plugin_url;
	private $template_loader;
	private $asset_manager;
	private $shortcode_handler;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->define_constants();
		$this->init_hooks();
		$this->init_components();
	}

	private function define_constants() {
		$this->plugin_dir = plugin_dir_path( dirname( dirname( __FILE__ ) ) );
		$this->plugin_url = plugin_dir_url( dirname( dirname( __FILE__ ) ) );
	}

	private function init_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'switch_theme', array( $this, 'clear_template_cache' ) );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			add_action( 'wp_footer', array( $this, 'render_debug_info' ), 999 );
		}
	}

	private function init_components() {
		$this->template_loader = new Loader( $this->plugin_dir );
		$this->asset_manager = new Manager( $this->plugin_dir, $this->plugin_url, $this->version );
		$this->asset_manager->init();
		$this->shortcode_handler = new Handler( $this->template_loader );
		$this->shortcode_handler->init();
	}

	public function load_textdomain() {
		load_plugin_textdomain(
			'cta-highlights',
			false,
			dirname( plugin_basename( $this->plugin_dir . 'cta-highlights.php' ) ) . '/languages'
		);
	}

	public function clear_template_cache() {
		$this->template_loader->clear_cache();
	}

	public function get_template_loader() {
		return $this->template_loader;
	}

	public function get_asset_manager() {
		return $this->asset_manager;
	}

	public function get_shortcode_handler() {
		return $this->shortcode_handler;
	}

	public function get_version() {
		return $this->version;
	}

	public function get_plugin_dir() {
		return $this->plugin_dir;
	}

	public function get_plugin_url() {
		return $this->plugin_url;
	}

	public function render_debug_info() {
		$registry        = Registry::instance();
		$templates_used = $registry->get_all();

		if ( empty( $templates_used ) ) {
			return;
		}

		?>
		<div class="cta-highlights-debug" style="position:fixed;bottom:0;right:0;background:#000;color:#0f0;padding:1rem;font-size:12px;font-family:monospace;z-index:999999;max-width:300px;border:2px solid #0f0;">
			<strong style="color:#fff;">CTA Highlights Debug</strong><br>
			<strong>Version:</strong> <?php echo esc_html( $this->version ); ?><br>
			<strong>Templates Used:</strong><br>
			<?php foreach ( $templates_used as $template ) : ?>
				&nbsp;&nbsp;- <?php echo esc_html( $template ); ?><br>
			<?php endforeach; ?>
			<button onclick="this.parentElement.style.display='none'" style="margin-top:0.5rem;padding:0.25rem 0.5rem;cursor:pointer;background:#0f0;color:#000;border:none;font-family:monospace;">
				Close [X]
			</button>
		</div>
		<?php
	}

	private function __clone() {}

	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
