<?php
/**
 * Webhook class.
 *
 * @package PayTheFly
 */

namespace PayTheFly\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Handles webhook endpoints for payment notifications.
 */
class Webhook {

	/**
	 * API namespace.
	 */
	const NAMESPACE = 'paythefly/v1';

	/**
	 * Register webhook routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/webhook',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_webhook' ],
					'permission_callback' => '__return_true',
				],
			]
		);
	}

	/**
	 * Handle incoming webhook from PayTheFly.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_webhook( WP_REST_Request $request ) {
		$payload   = $request->get_json_params();
		$signature = $request->get_header( 'X-PayTheFly-Signature' );

		// Verify webhook signature.
		if ( ! $this->verify_signature( $payload, $signature ) ) {
			return new WP_Error(
				'invalid_signature',
				__( 'Invalid webhook signature.', 'paythefly' ),
				[ 'status' => 401 ]
			);
		}

		// Process webhook event.
		$event_type = $payload['event'] ?? '';

		switch ( $event_type ) {
			case 'payment.completed':
				$this->handle_payment_completed( $payload );
				break;

			case 'payment.failed':
				$this->handle_payment_failed( $payload );
				break;

			case 'payment.expired':
				$this->handle_payment_expired( $payload );
				break;

			default:
				// Log unknown event type.
				error_log( sprintf( 'PayTheFly: Unknown webhook event type: %s', $event_type ) );
		}

		return new WP_REST_Response( [ 'received' => true ], 200 );
	}

	/**
	 * Verify webhook signature.
	 *
	 * @param array<string, mixed> $payload   Webhook payload.
	 * @param string|null          $signature Signature from header.
	 * @return bool
	 */
	private function verify_signature( array $payload, ?string $signature ): bool {
		if ( empty( $signature ) ) {
			return false;
		}

		$settings   = get_option( 'paythefly_settings', [] );
		$api_secret = $settings['api_secret'] ?? '';

		if ( empty( $api_secret ) ) {
			return false;
		}

		$expected = hash_hmac( 'sha256', wp_json_encode( $payload ), $api_secret );

		return hash_equals( $expected, $signature );
	}

	/**
	 * Handle payment completed event.
	 *
	 * @param array<string, mixed> $payload Event payload.
	 * @return void
	 */
	private function handle_payment_completed( array $payload ): void {
		$payment_id = $payload['data']['payment_id'] ?? '';

		/**
		 * Fires when a payment is completed.
		 *
		 * @param string               $payment_id Payment ID.
		 * @param array<string, mixed> $payload    Full webhook payload.
		 */
		do_action( 'paythefly_payment_completed', $payment_id, $payload );
	}

	/**
	 * Handle payment failed event.
	 *
	 * @param array<string, mixed> $payload Event payload.
	 * @return void
	 */
	private function handle_payment_failed( array $payload ): void {
		$payment_id = $payload['data']['payment_id'] ?? '';

		/**
		 * Fires when a payment fails.
		 *
		 * @param string               $payment_id Payment ID.
		 * @param array<string, mixed> $payload    Full webhook payload.
		 */
		do_action( 'paythefly_payment_failed', $payment_id, $payload );
	}

	/**
	 * Handle payment expired event.
	 *
	 * @param array<string, mixed> $payload Event payload.
	 * @return void
	 */
	private function handle_payment_expired( array $payload ): void {
		$payment_id = $payload['data']['payment_id'] ?? '';

		/**
		 * Fires when a payment expires.
		 *
		 * @param string               $payment_id Payment ID.
		 * @param array<string, mixed> $payload    Full webhook payload.
		 */
		do_action( 'paythefly_payment_expired', $payment_id, $payload );
	}
}
