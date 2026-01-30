<?php
/**
 * Shortcode class.
 *
 * @package PayTheFly
 */

namespace PayTheFly\Frontend;

/**
 * Handles shortcode registration and rendering.
 */
class Shortcode {

	/**
	 * Register shortcodes.
	 *
	 * @return void
	 */
	public function register(): void {
		add_shortcode( 'paythefly-crypto-gateway', array( $this, 'render' ) );
		add_shortcode( 'paythefly-crypto-gateway-button', array( $this, 'render_button' ) );
	}

	/**
	 * Render the main PayTheFly shortcode.
	 *
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'amount'      => '',
				'currency'    => 'USDT',
				'description' => '',
			),
			$atts,
			'paythefly-crypto-gateway'
		);

		wp_enqueue_script( 'paythefly-shortcode' );
		wp_enqueue_style( 'paythefly-shortcode' );

		$data_attrs = sprintf(
			'data-amount="%s" data-currency="%s" data-description="%s"',
			esc_attr( $atts['amount'] ),
			esc_attr( $atts['currency'] ),
			esc_attr( $atts['description'] )
		);

		return sprintf(
			'<div class="paythefly-widget" %s></div>',
			$data_attrs
		);
	}

	/**
	 * Render a payment button shortcode.
	 *
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_button( $atts ): string {
		$atts = shortcode_atts(
			array(
				'amount'   => '',
				'currency' => 'USDT',
				'label'    => __( 'Support', 'paythefly-crypto-gateway' ),
			),
			$atts,
			'paythefly-crypto-gateway-button'
		);

		wp_enqueue_script( 'paythefly-shortcode' );
		wp_enqueue_style( 'paythefly-shortcode' );

		$data_attrs = sprintf(
			'data-amount="%s" data-currency="%s"',
			esc_attr( $atts['amount'] ),
			esc_attr( $atts['currency'] )
		);

		return sprintf(
			'<button class="paythefly-button" %s>%s</button>',
			$data_attrs,
			esc_html( $atts['label'] )
		);
	}
}
