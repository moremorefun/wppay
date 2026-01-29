<?php
/**
 * Admin class.
 *
 * @package PayTheFly
 */

namespace PayTheFly\Admin;

use Kucrut\Vite;

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
			__( 'PayTheFly Settings', 'paythefly-crypto-gateway' ),
			__( 'PayTheFly', 'paythefly-crypto-gateway' ),
			'manage_options',
			'paythefly',
			[ $this->settings, 'render_settings_page' ],
			'dashicons-money-alt',
			30
		);
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

		Vite\enqueue_asset(
			PAYTHEFLY_DIR . 'dist',
			'src/admin/index.tsx',
			[
				'handle'           => 'paythefly-admin',
				'dependencies'     => [ 'react', 'react-dom', 'wp-api-fetch', 'wp-i18n' ],
				'css-dependencies' => [ 'wp-components' ],
				'in-footer'        => true,
			]
		);

		wp_set_script_translations(
			'paythefly-admin',
			'paythefly-crypto-gateway',
			PAYTHEFLY_DIR . 'languages'
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
