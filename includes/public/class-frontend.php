<?php
/**
 * Frontend class.
 *
 * @package PayTheFly
 */

namespace PayTheFly\Frontend;

use Kucrut\Vite;

/**
 * Handles public-facing functionality.
 */
class Frontend {

	/**
	 * Shortcode instance.
	 *
	 * @var Shortcode
	 */
	private Shortcode $shortcode;

	/**
	 * Block instance.
	 *
	 * @var Block
	 */
	private Block $block;

	/**
	 * ContentFilter instance.
	 *
	 * @var ContentFilter
	 */
	private ContentFilter $content_filter;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->shortcode      = new Shortcode();
		$this->block          = new Block();
		$this->content_filter = new ContentFilter();
	}

	/**
	 * Get donation configuration for frontend.
	 *
	 * @return array<string, mixed>
	 */
	private function get_donation_config(): array {
		$settings = get_option( 'paythefly_settings', array() );

		// Get admin user info for recipient display.
		$admin_users = get_users(
			array(
				'role'   => 'administrator',
				'number' => 1,
			)
		);
		$admin_user  = ! empty( $admin_users ) ? $admin_users[0] : null;

		$recipient_name   = $settings['brand'] ?? '';
		$recipient_avatar = '';

		if ( $admin_user ) {
			if ( empty( $recipient_name ) ) {
				$recipient_name = $admin_user->display_name;
			}
			$recipient_avatar = get_avatar_url( $admin_user->ID, array( 'size' => 160 ) );
		}

		return array(
			'apiUrl'          => rest_url( 'paythefly/v1' ),
			'nonce'           => wp_create_nonce( 'wp_rest' ),
			'projectId'       => $settings['project_id'] ?? '',
			'brand'           => $settings['brand'] ?? '',
			'fabEnabled'      => $settings['fab_enabled'] ?? true,
			'recipientName'   => $recipient_name,
			'recipientAvatar' => $recipient_avatar,
		);
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		// Register shortcode assets (will be enqueued on demand).
		Vite\register_asset(
			PAYTHEFLY_DIR . 'dist',
			'src/shortcode/index.tsx',
			array(
				'handle'       => 'paythefly-shortcode',
				'dependencies' => array( 'react', 'react-dom', 'wp-i18n' ),
				'in-footer'    => true,
			)
		);

		wp_set_script_translations(
			'paythefly-shortcode',
			'paythefly',
			PAYTHEFLY_DIR . 'languages'
		);

		$config = $this->get_donation_config();

		wp_localize_script(
			'paythefly-shortcode',
			'paytheflyFrontend',
			$config
		);

		// Enqueue FAB if enabled.
		$settings = get_option( 'paythefly_settings', array() );
		if ( ! empty( $settings['fab_enabled'] ) && ! empty( $settings['project_id'] ) ) {
			$this->enqueue_fab_scripts( $config );
		}
	}

	/**
	 * Enqueue FAB scripts.
	 *
	 * @param array<string, mixed> $config Donation config.
	 * @return void
	 */
	private function enqueue_fab_scripts( array $config ): void {
		Vite\enqueue_asset(
			PAYTHEFLY_DIR . 'dist',
			'src/fab/index.tsx',
			array(
				'handle'       => 'paythefly-fab',
				'dependencies' => array( 'react', 'react-dom', 'wp-i18n' ),
				'in-footer'    => true,
			)
		);

		wp_set_script_translations(
			'paythefly-fab',
			'paythefly',
			PAYTHEFLY_DIR . 'languages'
		);

		wp_localize_script(
			'paythefly-fab',
			'paytheflyFrontend',
			$config
		);
	}

	/**
	 * Register shortcodes.
	 *
	 * @return void
	 */
	public function register_shortcodes(): void {
		$this->shortcode->register();
	}

	/**
	 * Register Gutenberg blocks.
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		$this->block->register();
	}

	/**
	 * Register content filter.
	 *
	 * @return void
	 */
	public function register_content_filter(): void {
		$this->content_filter->register();
	}
}
