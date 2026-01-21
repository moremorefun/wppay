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
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings' ],
					'permission_callback' => [ $this, 'admin_permission_check' ],
				],
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_settings' ],
					'permission_callback' => [ $this, 'admin_permission_check' ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/payments',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_payments' ],
					'permission_callback' => [ $this, 'admin_permission_check' ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/payments/create',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_payment' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'amount'      => [
							'required' => true,
							'type'     => 'string',
						],
						'currency'    => [
							'required' => true,
							'type'     => 'string',
						],
						'description' => [
							'type' => 'string',
						],
					],
				],
			]
		);
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
				__( 'You do not have permission to access this endpoint.', 'paythefly' ),
				[ 'status' => 403 ]
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
		$settings = get_option( 'paythefly_settings', [] );

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
		$settings = get_option( 'paythefly_settings', [] );

		// Merge with existing settings.
		$updated = array_merge( $settings, $params );

		// Don't overwrite secret if placeholder was sent.
		if ( isset( $params['api_secret'] ) && $params['api_secret'] === '********' ) {
			$updated['api_secret'] = $settings['api_secret'] ?? '';
		}

		update_option( 'paythefly_settings', $updated );

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Get payment history.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_payments( WP_REST_Request $request ): WP_REST_Response {
		// TODO: Implement payment history retrieval from database.
		return new WP_REST_Response( [ 'payments' => [] ], 200 );
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
		$payment_data = [
			'id'          => wp_generate_uuid4(),
			'amount'      => $amount,
			'currency'    => $currency,
			'description' => $description,
			'status'      => 'pending',
			'created_at'  => current_time( 'mysql' ),
		];

		return new WP_REST_Response( $payment_data, 201 );
	}
}
