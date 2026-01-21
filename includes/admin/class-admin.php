<?php
/**
 * Admin class.
 *
 * @package PayTheFly
 */

namespace PayTheFly\Admin;

/**
 * Handles admin functionality.
 */
class Admin {

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settings = new Settings();
	}

	/**
	 * Add admin menu pages.
	 *
	 * @return void
	 */
	public function add_admin_menu(): void {
		add_menu_page(
			__( 'PayTheFly', 'paythefly' ),
			__( 'PayTheFly', 'paythefly' ),
			'manage_options',
			'paythefly',
			[ $this, 'render_admin_page' ],
			'dashicons-money-alt',
			30
		);

		add_submenu_page(
			'paythefly',
			__( 'Settings', 'paythefly' ),
			__( 'Settings', 'paythefly' ),
			'manage_options',
			'paythefly-settings',
			[ $this->settings, 'render_settings_page' ]
		);
	}

	/**
	 * Render the main admin page.
	 *
	 * @return void
	 */
	public function render_admin_page(): void {
		echo '<div id="paythefly-admin-app"></div>';
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_scripts( string $hook_suffix ): void {
		// Only load on PayTheFly admin pages.
		if ( strpos( $hook_suffix, 'paythefly' ) === false ) {
			return;
		}

		$asset_file = PAYTHEFLY_DIR . 'dist/admin.asset.php';

		if ( file_exists( $asset_file ) ) {
			$asset = require $asset_file;
		} else {
			$asset = [
				'dependencies' => [ 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' ],
				'version'      => PAYTHEFLY_VERSION,
			];
		}

		wp_enqueue_script(
			'paythefly-admin',
			PAYTHEFLY_URL . 'dist/admin.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'paythefly-admin',
			PAYTHEFLY_URL . 'dist/admin.css',
			[ 'wp-components' ],
			$asset['version']
		);

		wp_localize_script(
			'paythefly-admin',
			'paytheflyAdmin',
			[
				'apiUrl'  => rest_url( 'paythefly/v1' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'version' => PAYTHEFLY_VERSION,
			]
		);
	}
}
