<?php
/**
 * Webhook Handler class.
 *
 * @package PayTheFly\Webhook
 */

namespace PayTheFly\Webhook;

use PayTheFly\Database\Database;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles webhook events from PayTheFly.
 */
class WebhookHandler {

	/**
	 * Database instance.
	 *
	 * @var Database
	 */
	private Database $db;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->db = new Database();
	}

	/**
	 * Register webhook handlers.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'paythefly_webhook_received', array( $this, 'handle_webhook' ) );
	}

	/**
	 * Handle incoming webhook.
	 *
	 * @param array<string, mixed> $payload Webhook payload.
	 * @return void
	 */
	public function handle_webhook( array $payload ): void {
		$this->log( 'Webhook received', $payload );

		// PayTheFly Pro doesn't send an "event" field.
		// Determine event type from payload content.
		$confirmed = $payload['confirmed'] ?? false;
		$tx_hash   = $payload['tx_hash'] ?? '';

		if ( $confirmed && ! empty( $tx_hash ) ) {
			// This is a confirmed payment.
			$this->handle_payment_completed( $payload );
		} elseif ( ! empty( $tx_hash ) ) {
			// Payment detected but not yet confirmed.
			$this->log( 'Payment pending confirmation', $payload );
		} else {
			$this->log( 'Unknown webhook payload', $payload );
		}
	}

	/**
	 * Handle payment completed event.
	 *
	 * @param array<string, mixed> $payload Webhook payload.
	 * @return void
	 */
	private function handle_payment_completed( array $payload ): void {
		$serial_no = $payload['serial_no'] ?? '';
		// PayTheFly Pro uses 'value' instead of 'amount'.
		$amount    = $payload['value'] ?? $payload['amount'] ?? 0;
		// PayTheFly Pro uses 'chain_symbol' instead of 'chain_id'.
		$chain     = $payload['chain_symbol'] ?? $payload['chain_id'] ?? '';
		$tx_hash   = $payload['tx_hash'] ?? '';
		$wallet    = $payload['wallet'] ?? '';

		$this->log( 'Payment completed', compact( 'serial_no', 'amount', 'chain', 'tx_hash', 'wallet' ) );

		// Check if payment already exists.
		$existing = $this->db->get_payment( $serial_no );

		if ( $existing ) {
			// Update existing payment status.
			$this->db->update_status( $serial_no, 'completed' );
			$this->log( 'Payment status updated to completed', array( 'serial_no' => $serial_no ) );
		} else {
			// Insert new payment record.
			$result = $this->db->insert_payment(
				array(
					'payment_id'  => $serial_no,
					'amount'      => $amount,
					'currency'    => 'USDT',
					'status'      => 'completed',
					'description' => '',
					'metadata'    => array(
						'chain'   => $chain,
						'tx_hash' => $tx_hash,
						'wallet'  => $wallet,
						'payload' => $payload,
					),
				)
			);

			if ( $result ) {
				$this->log( 'Payment record created', array( 'serial_no' => $serial_no, 'id' => $result ) );
			} else {
				$this->log( 'Failed to create payment record', array( 'serial_no' => $serial_no ) );
			}
		}

		/**
		 * Fires when a payment is completed.
		 *
		 * @param string               $serial_no Payment serial number.
		 * @param array<string, mixed> $payload   Full webhook payload.
		 */
		do_action( 'paythefly_payment_completed', $serial_no, $payload );
	}

	/**
	 * Handle payment failed event.
	 *
	 * @param array<string, mixed> $payload Webhook payload.
	 * @return void
	 */
	private function handle_payment_failed( array $payload ): void {
		$serial_no = $payload['serial_no'] ?? '';
		$reason    = $payload['reason'] ?? 'unknown';

		$this->log( 'Payment failed', compact( 'serial_no', 'reason' ) );

		// Update payment status if exists.
		$existing = $this->db->get_payment( $serial_no );
		if ( $existing ) {
			$this->db->update_status( $serial_no, 'failed' );
		}

		/**
		 * Fires when a payment fails.
		 *
		 * @param string               $serial_no Payment serial number.
		 * @param string               $reason    Failure reason.
		 * @param array<string, mixed> $payload   Full webhook payload.
		 */
		do_action( 'paythefly_payment_failed', $serial_no, $reason, $payload );
	}

	/**
	 * Log a message.
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	private function log( string $message, array $context = array() ): void {
		$settings = get_option( 'paythefly_settings', array() );

		if ( empty( $settings['debug_log'] ) ) {
			return;
		}

		$log_entry = sprintf(
			'[PayTheFly] %s: %s | Context: %s',
			gmdate( 'Y-m-d H:i:s' ),
			$message,
			wp_json_encode( $context )
		);

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging when enabled.
		error_log( $log_entry );
	}
}
