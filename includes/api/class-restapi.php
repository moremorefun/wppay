<?php
/**
 * REST API class.
 *
 * @package PayTheFly
 */

namespace PayTheFly\Api;

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

		// Don't expose sensitive data.
		if ( isset( $settings['api_secret'] ) ) {
			$settings['api_secret'] = $settings['api_secret'] ? '********' : '';
		}

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
		$settings = get_option( 'paythefly_settings', array() );

		// Merge with existing settings.
		$updated = array_merge( $settings, $params );

		// Don't overwrite secret if placeholder was sent.
		if ( isset( $params['api_secret'] ) && '********' === $params['api_secret'] ) {
			$updated['api_secret'] = $settings['api_secret'] ?? '';
		}

		update_option( 'paythefly_settings', $updated );

		return new WP_REST_Response( array( 'success' => true ), 200 );
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
		56        => '0x55d398326f99059fF775485246999027B3197955', // BSC Mainnet.
	);

	/**
	 * Create a donation order and return payment URL parameters.
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

		$settings   = get_option( 'paythefly_settings', array() );
		$project_id = $settings['project_id'] ?? '';
		$brand      = $settings['brand'] ?? '';

		if ( empty( $project_id ) ) {
			return new WP_Error(
				'missing_project_id',
				__( 'Project ID is not configured.', 'paythefly-crypto-gateway' ),
				array( 'status' => 500 )
			);
		}

		// Generate serial number.
		$serial_no = 'PTF-' . wp_generate_uuid4();

		return new WP_REST_Response(
			array(
				'serialNo'  => $serial_no,
				'projectId' => $project_id,
				'brand'     => $brand,
				'token'     => self::USDT_ADDRESSES[ $chain_id ],
				'redirect'  => $redirect,
			),
			201
		);
	}

	/**
	 * Handle PayTheFly webhook callback.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_webhook( WP_REST_Request $request ) {
		$data = $request->get_param( 'data' );
		$sign = $request->get_param( 'sign' );

		if ( empty( $data ) || empty( $sign ) ) {
			return new WP_Error(
				'missing_params',
				__( 'Missing data or sign parameter.', 'paythefly-crypto-gateway' ),
				array( 'status' => 400 )
			);
		}

		$settings    = get_option( 'paythefly_settings', array() );
		$project_key = $settings['project_key'] ?? '';

		if ( empty( $project_key ) ) {
			return new WP_Error(
				'not_configured',
				__( 'Project key not configured.', 'paythefly-crypto-gateway' ),
				array( 'status' => 500 )
			);
		}

		// Verify signature.
		$expected_sign = strtoupper( md5( $data . $project_key ) );
		if ( ! hash_equals( $expected_sign, $sign ) ) {
			return new WP_Error(
				'invalid_signature',
				__( 'Invalid signature.', 'paythefly-crypto-gateway' ),
				array( 'status' => 401 )
			);
		}

		// Parse payload.
		$payload = json_decode( $data, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error(
				'invalid_json',
				__( 'Invalid JSON in data.', 'paythefly-crypto-gateway' ),
				array( 'status' => 400 )
			);
		}

		// Verify project_id matches.
		$project_id = $settings['project_id'] ?? '';
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
