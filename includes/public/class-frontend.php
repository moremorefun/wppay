<?php
/**
 * Frontend class.
 *
 * @package PayTheFly
 */

namespace PayTheFly\Frontend;

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
	 * Constructor.
	 */
	public function __construct() {
		$this->shortcode = new Shortcode();
		$this->block     = new Block();
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		$asset_file = PAYTHEFLY_DIR . 'dist/shortcode.asset.php';

		if ( file_exists( $asset_file ) ) {
			$asset = require $asset_file;
		} else {
			$asset = [
				'dependencies' => [ 'wp-element' ],
				'version'      => PAYTHEFLY_VERSION,
			];
		}

		wp_register_script(
			'paythefly-shortcode',
			PAYTHEFLY_URL . 'dist/shortcode.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_register_style(
			'paythefly-shortcode',
			PAYTHEFLY_URL . 'dist/shortcode.css',
			[],
			$asset['version']
		);

		wp_localize_script(
			'paythefly-shortcode',
			'paytheflyFrontend',
			[
				'apiUrl' => rest_url( 'paythefly/v1' ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
			]
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
}
