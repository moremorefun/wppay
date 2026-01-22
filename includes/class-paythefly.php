<?php
/**
 * Main plugin class.
 *
 * @package PayTheFly
 */

namespace PayTheFly;

/**
 * Main PayTheFly plugin class.
 */
class PayTheFly {

	/**
	 * Admin instance.
	 *
	 * @var Admin\Admin|null
	 */
	private ?Admin\Admin $admin = null;

	/**
	 * Frontend instance.
	 *
	 * @var Frontend\Frontend|null
	 */
	private ?Frontend\Frontend $frontend = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->load_dependencies();
	}

	/**
	 * Load required dependencies.
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		// Admin classes.
		require_once PAYTHEFLY_DIR . 'includes/admin/class-admin.php';
		require_once PAYTHEFLY_DIR . 'includes/admin/class-settings.php';

		// Public/Frontend classes.
		require_once PAYTHEFLY_DIR . 'includes/public/class-frontend.php';
		require_once PAYTHEFLY_DIR . 'includes/public/class-shortcode.php';
		require_once PAYTHEFLY_DIR . 'includes/public/class-block.php';
		require_once PAYTHEFLY_DIR . 'includes/public/class-content-filter.php';

		// API classes.
		require_once PAYTHEFLY_DIR . 'includes/api/class-rest-api.php';

		// Database classes.
		require_once PAYTHEFLY_DIR . 'includes/database/class-database.php';
	}

	/**
	 * Run the plugin.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_api_hooks();
	}

	/**
	 * Set plugin locale for internationalization.
	 *
	 * Note: When hosted on WordPress.org, translations are loaded automatically.
	 * This method now only handles JS translation file mapping for Vite-built scripts.
	 *
	 * @return void
	 */
	private function set_locale(): void {
		// Handle JS translation files for Vite-built scripts with dynamic filenames.
		add_filter(
			'load_script_textdomain_relative_path',
			function ( $relative, $src ) {
				// Check if this is a PayTheFly script.
				if ( strpos( $src, '/paythefly/' ) === false ) {
					return $relative;
				}

				// Map Vite-built filenames to their source entry points.
				$basename = basename( $src );
				if ( preg_match( '/^(admin|shortcode|fab|block)-[a-zA-Z0-9]+\.js$/', $basename, $matches ) ) {
					return 'src/' . $matches[1] . '/index.tsx';
				}

				return $relative;
			},
			10,
			2
		);
	}

	/**
	 * Register all admin-related hooks.
	 *
	 * @return void
	 */
	private function define_admin_hooks(): void {
		$this->admin = new Admin\Admin();

		add_action( 'admin_menu', [ $this->admin, 'add_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this->admin, 'enqueue_scripts' ] );
	}

	/**
	 * Register all public-facing hooks.
	 *
	 * @return void
	 */
	private function define_public_hooks(): void {
		$this->frontend = new Frontend\Frontend();

		add_action( 'wp_enqueue_scripts', [ $this->frontend, 'enqueue_scripts' ] );
		add_action( 'init', [ $this->frontend, 'register_shortcodes' ] );
		add_action( 'init', [ $this->frontend, 'register_blocks' ] );
		add_action( 'init', [ $this->frontend, 'register_content_filter' ] );
	}

	/**
	 * Register REST API hooks.
	 *
	 * @return void
	 */
	private function define_api_hooks(): void {
		$rest_api = new Api\RestApi();

		add_action( 'rest_api_init', [ $rest_api, 'register_routes' ] );
	}
}
