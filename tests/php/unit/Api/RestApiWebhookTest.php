<?php
/**
 * REST API Webhook endpoint tests.
 *
 * @package PayTheFly\Tests\Unit\Api
 */

namespace PayTheFly\Tests\Unit\Api;

use WP_UnitTestCase;
use WP_REST_Request;
use PayTheFly\Api\RestApi;

/**
 * Tests for the handle_webhook endpoint.
 */
class RestApiWebhookTest extends WP_UnitTestCase {

	/**
	 * REST API instance.
	 *
	 * @var RestApi
	 */
	private RestApi $api;

	/**
	 * Test project key.
	 *
	 * @var string
	 */
	private string $project_key = 'test-secret-key-123';

	/**
	 * Test project ID.
	 *
	 * @var string
	 */
	private string $project_id = 'test-project-456';

	/**
	 * BSC chain ID.
	 *
	 * @var int
	 */
	private int $chain_id = 56;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->api = new RestApi();

		// Set default settings with new structure.
		update_option(
			'paythefly_settings',
			[
				'bsc' => [
					'project_id'  => $this->project_id,
					'project_key' => $this->project_key,
				],
				'tron' => [
					'project_id'  => 'tron-project-789',
					'project_key' => 'tron-secret-key-456',
				],
			]
		);
	}

	/**
	 * Clean up after tests.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		delete_option( 'paythefly_settings' );
		remove_all_actions( 'paythefly_webhook_received' );
		parent::tearDown();
	}

	/**
	 * Create a mock request for webhook.
	 *
	 * @param array<string, mixed> $params Request parameters.
	 * @return WP_REST_Request
	 */
	private function create_request( array $params ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/paythefly/v1/webhook' );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return $request;
	}

	/**
	 * Generate valid signature for data (legacy MD5).
	 *
	 * @param string $data JSON data string.
	 * @return string
	 */
	private function generate_signature( string $data ): string {
		return strtoupper( md5( $data . $this->project_key ) );
	}

	/**
	 * Generate HMAC-SHA256 signature.
	 *
	 * @param string $data      JSON data string.
	 * @param int    $timestamp Unix timestamp.
	 * @return string
	 */
	private function generate_hmac_signature( string $data, int $timestamp ): string {
		$message = $data . '.' . $timestamp;
		return hash_hmac( 'sha256', $message, $this->project_key );
	}

	/**
	 * Test that missing data parameter is rejected.
	 *
	 * @return void
	 */
	public function test_rejects_missing_data(): void {
		$request  = $this->create_request(
			[
				'sign' => 'somesignature',
			]
		);
		$response = $this->api->handle_webhook( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'missing_params', $response->get_error_code() );
		$this->assertEquals( 400, $response->get_error_data()['status'] );
	}

	/**
	 * Test that missing sign parameter is rejected.
	 *
	 * @return void
	 */
	public function test_rejects_missing_sign(): void {
		$request  = $this->create_request(
			[
				'data' => '{"test": "data"}',
			]
		);
		$response = $this->api->handle_webhook( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'missing_params', $response->get_error_code() );
		$this->assertEquals( 400, $response->get_error_data()['status'] );
	}

	/**
	 * Test that invalid signature is rejected (legacy MD5).
	 *
	 * @return void
	 */
	public function test_rejects_invalid_signature(): void {
		$data     = wp_json_encode(
			[
				'project_id' => $this->project_id,
				'chain_id'   => $this->chain_id,
			]
		);
		$request  = $this->create_request(
			[
				'data' => $data,
				'sign' => 'INVALID_SIGNATURE_12345678901234',
			]
		);
		$response = $this->api->handle_webhook( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'invalid_signature', $response->get_error_code() );
		$this->assertEquals( 401, $response->get_error_data()['status'] );
	}

	/**
	 * Test that invalid JSON in data is rejected.
	 *
	 * @return void
	 */
	public function test_rejects_invalid_json(): void {
		$data     = 'not valid json {';
		$sign     = $this->generate_signature( $data );
		$request  = $this->create_request(
			[
				'data' => $data,
				'sign' => $sign,
			]
		);
		$response = $this->api->handle_webhook( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'invalid_json', $response->get_error_code() );
		$this->assertEquals( 400, $response->get_error_data()['status'] );
	}

	/**
	 * Test that project ID mismatch is rejected.
	 *
	 * @return void
	 */
	public function test_rejects_project_mismatch(): void {
		$data     = wp_json_encode(
			[
				'project_id' => 'wrong-project-id',
				'chain_id'   => $this->chain_id,
			]
		);
		$sign     = $this->generate_signature( $data );
		$request  = $this->create_request(
			[
				'data' => $data,
				'sign' => $sign,
			]
		);
		$response = $this->api->handle_webhook( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'project_mismatch', $response->get_error_code() );
		$this->assertEquals( 403, $response->get_error_data()['status'] );
	}

	/**
	 * Test that valid signature is accepted (legacy MD5).
	 *
	 * @return void
	 */
	public function test_accepts_valid_signature(): void {
		$data     = wp_json_encode(
			[
				'project_id' => $this->project_id,
				'chain_id'   => $this->chain_id,
				'order_id'   => 'test-order-123',
				'amount'     => '100.00',
				'status'     => 'completed',
			]
		);
		$sign     = $this->generate_signature( $data );
		$request  = $this->create_request(
			[
				'data' => $data,
				'sign' => $sign,
			]
		);
		$response = $this->api->handle_webhook( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
	}

	/**
	 * Test that HMAC-SHA256 signature is accepted.
	 *
	 * @return void
	 */
	public function test_accepts_hmac_signature(): void {
		$timestamp = time();
		$data      = wp_json_encode(
			[
				'project_id' => $this->project_id,
				'chain_id'   => $this->chain_id,
				'order_id'   => 'test-order-hmac',
				'amount'     => '50.00',
				'status'     => 'completed',
			]
		);
		$sign      = $this->generate_hmac_signature( $data, $timestamp );
		$request   = $this->create_request(
			[
				'data'      => $data,
				'sign'      => $sign,
				'timestamp' => $timestamp,
			]
		);
		$response  = $this->api->handle_webhook( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
	}

	/**
	 * Test that expired timestamp is rejected.
	 *
	 * @return void
	 */
	public function test_rejects_expired_timestamp(): void {
		$timestamp = time() - 400; // 6+ minutes ago (exceeds 5 min tolerance).
		$data      = wp_json_encode(
			[
				'project_id' => $this->project_id,
				'chain_id'   => $this->chain_id,
			]
		);
		$sign      = $this->generate_hmac_signature( $data, $timestamp );
		$request   = $this->create_request(
			[
				'data'      => $data,
				'sign'      => $sign,
				'timestamp' => $timestamp,
			]
		);
		$response  = $this->api->handle_webhook( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'timestamp_expired', $response->get_error_code() );
		$this->assertEquals( 401, $response->get_error_data()['status'] );
	}

	/**
	 * Test that action hook is triggered on successful webhook.
	 *
	 * @return void
	 */
	public function test_triggers_action_hook(): void {
		$hook_called  = false;
		$hook_payload = null;

		add_action(
			'paythefly_webhook_received',
			function ( $payload ) use ( &$hook_called, &$hook_payload ) {
				$hook_called  = true;
				$hook_payload = $payload;
			}
		);

		$payload  = [
			'project_id' => $this->project_id,
			'chain_id'   => $this->chain_id,
			'order_id'   => 'test-order-789',
			'amount'     => '50.00',
			'status'     => 'completed',
		];
		$data     = wp_json_encode( $payload );
		$sign     = $this->generate_signature( $data );
		$request  = $this->create_request(
			[
				'data' => $data,
				'sign' => $sign,
			]
		);

		$this->api->handle_webhook( $request );

		$this->assertTrue( $hook_called );
		$this->assertEquals( $payload, $hook_payload );
	}

	/**
	 * Test that webhook fails when project key is not configured.
	 *
	 * @return void
	 */
	public function test_fails_without_project_key(): void {
		update_option(
			'paythefly_settings',
			[
				'bsc' => [
					'project_id' => $this->project_id,
					// No project_key.
				],
			]
		);

		$data     = wp_json_encode(
			[
				'project_id' => $this->project_id,
				'chain_id'   => $this->chain_id,
			]
		);
		$request  = $this->create_request(
			[
				'data' => $data,
				'sign' => 'somesign',
			]
		);
		$response = $this->api->handle_webhook( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'not_configured', $response->get_error_code() );
		$this->assertEquals( 500, $response->get_error_data()['status'] );
	}
}
