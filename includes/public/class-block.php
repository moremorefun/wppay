<?php
/**
 * Block class.
 *
 * @package PayTheFly
 */

namespace PayTheFly\Frontend;

/**
 * Handles Gutenberg block registration.
 */
class Block {

	/**
	 * Register Gutenberg blocks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Check if block editor is available.
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$asset_file = PAYTHEFLY_DIR . 'dist/block.asset.php';

		if ( file_exists( $asset_file ) ) {
			$asset = require $asset_file;
		} else {
			$asset = [
				'dependencies' => [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ],
				'version'      => PAYTHEFLY_VERSION,
			];
		}

		wp_register_script(
			'paythefly-block-editor',
			PAYTHEFLY_URL . 'dist/block.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_register_style(
			'paythefly-block-editor',
			PAYTHEFLY_URL . 'dist/block.css',
			[ 'wp-edit-blocks' ],
			$asset['version']
		);

		register_block_type(
			'paythefly/payment-widget',
			[
				'editor_script'   => 'paythefly-block-editor',
				'editor_style'    => 'paythefly-block-editor',
				'render_callback' => [ $this, 'render_block' ],
				'attributes'      => [
					'amount'      => [
						'type'    => 'string',
						'default' => '',
					],
					'currency'    => [
						'type'    => 'string',
						'default' => 'USDT',
					],
					'description' => [
						'type'    => 'string',
						'default' => '',
					],
				],
			]
		);
	}

	/**
	 * Render the payment widget block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render_block( array $attributes ): string {
		$shortcode = new Shortcode();

		return $shortcode->render( $attributes );
	}
}
