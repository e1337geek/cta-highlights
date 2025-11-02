<?php
/**
 * Main Plugin Class
 *
 * Core plugin singleton that initializes and manages all plugin components.
 *
 * @package CTAHighlights\Core
 * @since 1.0.0
 */

namespace CTAHighlights\Core;

use CTAHighlights\Template\Loader;
use CTAHighlights\Template\Registry;
use CTAHighlights\Assets\Manager;
use CTAHighlights\Shortcode\Handler;
use CTAHighlights\AutoInsertion\Manager as AutoInsertManager;
use CTAHighlights\AutoInsertion\Database as AutoInsertDatabase;
use CTAHighlights\Admin\AutoInsertAdmin;
use CTAHighlights\Admin\PostMetaBox;

/**
 * Plugin class - singleton main controller
 *
 * @since 1.0.0
 */
final class Plugin {
	/**
	 * Singleton instance
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version = '1.0.0';

	/**
	 * Plugin directory path
	 *
	 * @var string
	 */
	private $plugin_dir;

	/**
	 * Plugin URL
	 *
	 * @var string
	 */
	private $plugin_url;

	/**
	 * Template loader instance
	 *
	 * @var Loader
	 */
	private $template_loader;

	/**
	 * Asset manager instance
	 *
	 * @var Manager
	 */
	private $asset_manager;

	/**
	 * Shortcode handler instance
	 *
	 * @var Handler
	 */
	private $shortcode_handler;

	/**
	 * Auto-insertion manager instance
	 *
	 * @var AutoInsertManager
	 */
	private $auto_insert_manager;

	/**
	 * Auto-insertion admin interface instance
	 *
	 * @var AutoInsertAdmin|null
	 */
	private $auto_insert_admin;

	/**
	 * Post meta box instance
	 *
	 * @var PostMetaBox|null
	 */
	private $post_meta_box;

	/**
	 * Get singleton instance
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - private for singleton pattern
	 */
	private function __construct() {
		$this->define_constants();
		$this->init_hooks();
		$this->init_components();
	}

	/**
	 * Define plugin constants
	 *
	 * @return void
	 */
	private function define_constants() {
		$this->plugin_dir = plugin_dir_path( dirname( __DIR__ ) );
		$this->plugin_url = plugin_dir_url( dirname( __DIR__ ) );
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'switch_theme', array( $this, 'clear_template_cache' ) );
		add_action( 'plugins_loaded', array( $this, 'check_database_migration' ) );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			add_action( 'wp_footer', array( $this, 'render_debug_info' ), 999 );
		}
	}

	/**
	 * Initialize plugin components
	 *
	 * Creates and initializes all plugin component instances.
	 *
	 * @return void
	 */
	private function init_components() {
		$this->template_loader = new Loader( $this->plugin_dir );
		$this->asset_manager   = new Manager( $this->plugin_dir, $this->plugin_url, $this->version );
		$this->asset_manager->init();
		$this->shortcode_handler = new Handler( $this->template_loader );
		$this->shortcode_handler->init();

		// Initialize auto-insertion components.
		$this->auto_insert_manager = AutoInsertManager::instance();

		// Initialize admin components (only in admin).
		if ( is_admin() ) {
			$auto_insert_db          = $this->auto_insert_manager->get_database();
			$this->auto_insert_admin = new AutoInsertAdmin( $auto_insert_db );
			$this->post_meta_box     = new PostMetaBox();
		}
	}

	/**
	 * Load plugin text domain for translations
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'cta-highlights',
			false,
			dirname( plugin_basename( $this->plugin_dir . 'cta-highlights.php' ) ) . '/languages'
		);
	}

	/**
	 * Clear template cache
	 *
	 * @return void
	 */
	public function clear_template_cache() {
		$this->template_loader->clear_cache();
	}

	/**
	 * Check and run database migration if needed
	 *
	 * @return void
	 */
	public function check_database_migration() {
		if ( AutoInsertDatabase::needs_migration() ) {
			AutoInsertDatabase::create_table();
		}
	}

	/**
	 * Get template loader instance
	 *
	 * @return Loader
	 */
	public function get_template_loader() {
		return $this->template_loader;
	}

	/**
	 * Get asset manager instance
	 *
	 * @return Manager
	 */
	public function get_asset_manager() {
		return $this->asset_manager;
	}

	/**
	 * Get shortcode handler instance
	 *
	 * @return Handler
	 */
	public function get_shortcode_handler() {
		return $this->shortcode_handler;
	}

	/**
	 * Get auto-insertion manager instance
	 *
	 * @return AutoInsertManager
	 */
	public function get_auto_insert_manager() {
		return $this->auto_insert_manager;
	}

	/**
	 * Get plugin version
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get plugin directory path
	 *
	 * @return string
	 */
	public function get_plugin_dir() {
		return $this->plugin_dir;
	}

	/**
	 * Get plugin URL
	 *
	 * @return string
	 */
	public function get_plugin_url() {
		return $this->plugin_url;
	}

	/**
	 * Render debug information in footer
	 *
	 * Only shown when WP_DEBUG is enabled.
	 *
	 * @return void
	 */
	public function render_debug_info() {
		$registry       = Registry::instance();
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

	/**
	 * Prevent cloning of singleton
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization of singleton
	 *
	 * @throws \Exception When attempting to unserialize.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
