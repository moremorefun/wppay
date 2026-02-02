<?php
/**
 * REST API class.
 *
 * @package PayTheFly
 */

namespace PayTheFly\Api;

use PayTheFly\Admin\Settings;
use PayTheFly\Crypto\Encryption;
use PayTheFly\Crypto\Signer;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Handles REST API endpoints.
 */
class RestApi {

	/**
	 * API namespace.
	 */
	const NAMESPACE = 'paythefly/v1';

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'admin_permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'admin_permission_check' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/payments',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_payments' ),
					'permission_callback' => array( $this, 'admin_permission_check' ),
				),
			)
		);

		/**
		 * Payment creation endpoint for frontend donation flow.
		 * This is a public endpoint to support anonymous donations.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/payments/create',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_payment' ),
					'permission_callback' => array( $this, 'public_donation_permission_check' ),
					'args'                => array(
						'amount'      => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'currency'    => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'description' => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		/**
		 * Order creation endpoint for frontend donation flow.
		 * This is a public endpoint to support anonymous donations.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/orders/create',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_order' ),
					'permission_callback' => array( $this, 'public_donation_permission_check' ),
					'args'                => array(
						'amount'   => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'chainId'  => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'redirect' => array(
							'type'              => 'string',
							'sanitize_callback' => 'esc_url_raw',
						),
					),
				),
			)
		);

		/**
		 * Webhook endpoint for PayTheFly server callbacks.
		 *
		 * This endpoint intentionally uses __return_true for permission_callback
		 * because it receives callbacks from the PayTheFly payment service.
		 * Authentication is handled via signature verification using project_key.
		 *
		 * @see self::handle_webhook() for signature verification implementation.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/webhook',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_webhook' ),
					'permission_callback' => '__return_true', // Intentional: see PHPDoc above.
				),
			)
		);

		/**
		 * Generate a new signing private key.
		 * This is an admin-only endpoint.
		 */
		register_rest_route(
			self::NAMESPACE,
			'/settings/generate-key',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'generate_signing_key' ),
					'permission_callback' => array( $this, 'admin_permission_check' ),
				),
			)
		);
	}

	/**
	 * Permission callback for public donation endpoints.
	 *
	 * These endpoints are intentionally public to support anonymous donations.
	 * Security is provided through input sanitization and validation.
	 *
	 * @return true Always returns true for public access.
	 */
	public function public_donation_permission_check(): bool {
		return true;
	}

	/**
	 * Check if user has admin permissions.
	 *
	 * @return bool|WP_Error
	 */
	public function admin_permission_check() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this endpoint.', 'paythefly-crypto-gateway' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get plugin settings.
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings(): WP_REST_Response {
		$settings = get_option( 'paythefly_settings', array() );

		// Mask sensitive data.
		if ( ! empty( $settings['private_key_encrypted'] ) ) {
			$settings['private_key_encrypted'] = '********';
		}

		// Mask chain-specific project keys.
		if ( isset( $settings['tron']['project_key'] ) && ! empty( $settings['tron']['project_key'] ) ) {
			$settings['tron']['project_key'] = '********';
		}
		if ( isset( $settings['bsc']['project_key'] ) && ! empty( $settings['bsc']['project_key'] ) ) {
			$settings['bsc']['project_key'] = '********';
		}

		// Include webhook URL for display.
		$settings['webhook_url'] = rest_url( self::NAMESPACE . '/webhook' );

		return new WP_REST_Response( $settings, 200 );
	}

	/**
	 * Update plugin settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function update_settings( WP_REST_Request $request ): WP_REST_Response {
		$params   = $request->get_json_params();
		$existing = get_option( 'paythefly_settings', array() );

		// Deep merge for chain-specific settings.
		$updated = $existing;

		// Update top-level non-chain settings.
		$top_level_keys = array( 'brand', 'fab_enabled', 'inline_button_auto', 'debug_log' );
		foreach ( $top_level_keys as $key ) {
			if ( isset( $params[ $key ] ) ) {
				$updated[ $key ] = $params[ $key ];
			}
		}

		// Update chain-specific settings.
		foreach ( array( 'tron', 'bsc' ) as $chain ) {
			if ( isset( $params[ $chain ] ) && is_array( $params[ $chain ] ) ) {
				if ( ! isset( $updated[ $chain ] ) ) {
					$updated[ $chain ] = array();
				}
				foreach ( array( 'project_id', 'project_key', 'contract_address' ) as $field ) {
					if ( isset( $params[ $chain ][ $field ] ) ) {
						// Don't overwrite if placeholder was sent.
						if ( 'project_key' === $field && '********' === $params[ $chain ][ $field ] ) {
							continue;
						}
						$updated[ $chain ][ $field ] = sanitize_text_field( $params[ $chain ][ $field ] );
					}
				}
			}
		}

		update_option( 'paythefly_settings', $updated );

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	/**
	 * Generate a new signing private key.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function generate_signing_key() {
		$result = Settings::generate_private_key();

		if ( false === $result ) {
			return new WP_Error(
				'key_generation_failed',
				__( 'Failed to generate signing key.', 'paythefly-crypto-gateway' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response(
			array(
				'success'      => true,
				'evm_address'  => $result['evm_address'],
				'tron_address' => $result['tron_address'],
			),
			200
		);
	}

	/**
	 * Get payment history.
	 *
	 * @return WP_REST_Response
	 */
	public function get_payments(): WP_REST_Response {
		// TODO: Implement payment history retrieval from database.
		return new WP_REST_Response( array( 'payments' => array() ), 200 );
	}

	/**
	 * Create a new payment.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_payment( WP_REST_Request $request ) {
		$amount      = $request->get_param( 'amount' );
		$currency    = $request->get_param( 'currency' );
		$description = $request->get_param( 'description' ) ?? '';

		// TODO: Implement PayTheFly API integration.
		$payment_data = array(
			'id'          => wp_generate_uuid4(),
			'amount'      => $amount,
			'currency'    => $currency,
			'description' => $description,
			'status'      => 'pending',
			'created_at'  => current_time( 'mysql' ),
		);

		return new WP_REST_Response( $payment_data, 201 );
	}

	/**
	 * USDT contract addresses by chain ID.
	 */
	private const USDT_ADDRESSES = array(
		728126428 => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t', // TRON Mainnet.
		// phpcs:ignore PHPCompatibility.Miscellaneous.ValidIntegers.HexNumericStringFound -- Ethereum address, not a numeric string.
		56        => '0x55d398326f99059fF775485246999027B3197955', // BSC Mainnet.
	);

	/**
	 * PayTheFly Pro payment base URL.
	 */
	private const PAYTHEFLY_PAY_URL = 'https://pro.paythefly.com/pay';

	/**
	 * Get chain config key from chain ID.
	 *
	 * @param int $chain_id Chain ID.
	 * @return string 'tron' or 'bsc'.
	 */
	private function get_chain_key( int $chain_id ): string {
		return Signer::is_tron_chain( $chain_id ) ? 'tron' : 'bsc';
	}

	/**
	 * Create a donation order and return payment URL with signature.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_order( WP_REST_Request $request ) {
		$amount   = $request->get_param( 'amount' );
		$chain_id = $request->get_param( 'chainId' );
		$redirect = $request->get_param( 'redirect' ) ?? '';

		// Validate amount.
		if ( ! is_numeric( $amount ) || floatval( $amount ) <= 0 ) {
			return new WP_Error(
				'invalid_amount',
				__( 'Invalid amount.', 'paythefly-crypto-gateway' ),
				array( 'status' => 400 )
			);
		}

		// Validate chain ID.
		if ( ! isset( self::USDT_ADDRESSES[ $chain_id ] ) ) {
			return new WP_Error(
				'invalid_chain',
				__( 'Invalid chain ID.', 'paythefly-crypto-gateway' ),
				array( 'status' => 400 )
			);
		}

		$settings  = get_option( 'paythefly_settings', array() );
		$chain_key = $this->get_chain_key( $chain_id );

		// Get chain-specific settings.
		$chain_config     = $settings[ $chain_key ] ?? array();
		$project_id       = $chain_config['project_id'] ?? '';
		$contract_address = $chain_config['contract_address'] ?? '';
		$brand            = $settings['brand'] ?? '';

		if ( empty( $project_id ) ) {
			return new WP_Error(
				'missing_project_id',
				/* translators: %s: chain name (TRON or BSC) */
				sprintf( __( 'Project ID for %s is not configured.', 'paythefly-crypto-gateway' ), strtoupper( $chain_key ) ),
				array( 'status' => 500 )
			);
		}

		if ( empty( $contract_address ) ) {
			return new WP_Error(
				'missing_contract_address',
				/* translators: %s: chain name (TRON or BSC) */
				sprintf( __( 'Contract address for %s is not configured.', 'paythefly-crypto-gateway' ), strtoupper( $chain_key ) ),
				array( 'status' => 500 )
			);
		}

		// Get and decrypt private key.
		$encrypted_key = $settings['private_key_encrypted'] ?? '';
		if ( empty( $encrypted_key ) ) {
			return new WP_Error(
				'missing_private_key',
				__( 'Private key is not configured.', 'paythefly-crypto-gateway' ),
				array( 'status' => 500 )
			);
		}

		$private_key = Encryption::decrypt( $encrypted_key );
		if ( false === $private_key ) {
			return new WP_Error(
				'decryption_failed',
				__( 'Failed to decrypt private key.', 'paythefly-crypto-gateway' ),
				array( 'status' => 500 )
			);
		}

		// Generate serial number and deadline.
		$serial_no = 'PTF-' . wp_generate_uuid4();
		$deadline  = Signer::get_deadline( 1800 ); // 30 minutes.

		// Sign the payment.
		try {
			$signature = Signer::sign_payment(
				array(
					'chain_id'         => $chain_id,
					'project_id'       => $project_id,
					'contract_address' => $contract_address,
					'token_address'    => self::USDT_ADDRESSES[ $chain_id ],
					'amount'           => $amount,
					'serial_no'        => $serial_no,
					'deadline'         => $deadline,
					'private_key'      => $private_key,
				)
			);
		} catch ( \Exception $e ) {
			return new WP_Error(
				'signing_failed',
				__( 'Failed to sign payment.', 'paythefly-crypto-gateway' ),
				array( 'status' => 500 )
			);
		}

		// Build payment URL.
		$pay_url = add_query_arg(
			array(
				'projectId' => $project_id,
				'chainId'   => $chain_id,
				'token'     => self::USDT_ADDRESSES[ $chain_id ],
				'amount'    => $amount,
				'serialNo'  => $serial_no,
				'deadline'  => $deadline,
				'signature' => $signature,
				'brand'     => $brand,
				'redirect'  => $redirect,
			),
			self::PAYTHEFLY_PAY_URL
		);

		return new WP_REST_Response(
			array(
				'payUrl'   => $pay_url,
				'serialNo' => $serial_no,
			),
			201
		);
	}

	/**
	 * Timestamp tolerance for webhook validation (5 minutes).
	 */
	private const WEBHOOK_TIMESTAMP_TOLERANCE = 300;

	/**
	 * Handle PayTheFly webhook callback.
	 *
	 * Uses HMAC-SHA256 signature verification with timestamp validation.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_webhook( WP_REST_Request $request ) {
		$data      = $request->get_param( 'data' );
		$sign      = $request->get_param( 'sign' );
		$timestamp = $request->get_param( 'timestamp' );

		// Debug log: raw request body.
		$settings = get_option( 'paythefly_settings', array() );
		if ( ! empty( $settings['debug_log'] ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging when enabled.
			error_log(
				sprintf(
					'[PayTheFly Webhook] %s: Raw request | Body: %s',
					gmdate( 'Y-m-d H:i:s' ),
					wp_json_encode( $request->get_json_params() )
				)
			);
		}

		if ( empty( $data ) || empty( $sign ) ) {
			return new WP_Error(
				'missing_params',
				__( 'Missing data or sign parameter.', 'paythefly-crypto-gateway' ),
				array( 'status' => 400 )
			);
		}

		// Parse payload first to get chain info.
		$payload = json_decode( $data, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error(
				'invalid_json',
				__( 'Invalid JSON in data.', 'paythefly-crypto-gateway' ),
				array( 'status' => 400 )
			);
		}

		// Determine which chain config to use based on payload.
		$settings  = get_option( 'paythefly_settings', array() );
		$chain_id  = $payload['chain_id'] ?? 0;
		$chain_key = $this->get_chain_key( (int) $chain_id );

		$chain_config = $settings[ $chain_key ] ?? array();
		$project_id   = $chain_config['project_id'] ?? '';
		$project_key  = $chain_config['project_key'] ?? '';

		if ( empty( $project_key ) ) {
			return new WP_Error(
				'not_configured',
				/* translators: %s: chain name (TRON or BSC) */
				sprintf( __( 'Project key for %s not configured.', 'paythefly-crypto-gateway' ), strtoupper( $chain_key ) ),
				array( 'status' => 500 )
			);
		}

		// Validate timestamp (if provided).
		if ( ! empty( $timestamp ) ) {
			$timestamp = (int) $timestamp;
			$now       = time();
			if ( abs( $now - $timestamp ) > self::WEBHOOK_TIMESTAMP_TOLERANCE ) {
				return new WP_Error(
					'timestamp_expired',
					__( 'Timestamp expired.', 'paythefly-crypto-gateway' ),
					array( 'status' => 401 )
				);
			}

			// HMAC-SHA256 verification: HMAC(data + "." + timestamp, project_key).
			$message       = $data . '.' . $timestamp;
			$expected_sign = hash_hmac( 'sha256', $message, $project_key );

			if ( ! hash_equals( strtolower( $expected_sign ), strtolower( $sign ) ) ) {
				return new WP_Error(
					'invalid_signature',
					__( 'Invalid signature.', 'paythefly-crypto-gateway' ),
					array( 'status' => 401 )
				);
			}
		} else {
			// Legacy MD5 fallback for backwards compatibility.
			$expected_sign = strtoupper( md5( $data . $project_key ) );
			if ( ! hash_equals( $expected_sign, strtoupper( $sign ) ) ) {
				return new WP_Error(
					'invalid_signature',
					__( 'Invalid signature.', 'paythefly-crypto-gateway' ),
					array( 'status' => 401 )
				);
			}
		}

		// Verify project_id matches.
		if ( ( $payload['project_id'] ?? '' ) !== $project_id ) {
			return new WP_Error(
				'project_mismatch',
				__( 'Project ID mismatch.', 'paythefly-crypto-gateway' ),
				array( 'status' => 403 )
			);
		}

		/**
		 * Fires when a valid webhook is received from PayTheFly.
		 *
		 * @param array $payload The webhook payload data.
		 */
		do_action( 'paythefly_webhook_received', $payload );

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}
}
