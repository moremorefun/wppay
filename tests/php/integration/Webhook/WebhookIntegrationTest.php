<?php
/**
 * Webhook integration tests.
 *
 * @package PayTheFly\Tests\Integration\Webhook
 */

namespace PayTheFly\Tests\Integration\Webhook;

use WP_UnitTestCase;
use WP_REST_Request;
use WP_REST_Server;
use PayTheFly\Api\RestApi;
use PayTheFly\Database\Database;

/**
 * Integration tests for complete webhook processing flow.
 */
class WebhookIntegrationTest extends WP_UnitTestCase {

	/**
	 * REST API instance.
	 *
	 * @var RestApi
	 */
	private RestApi $api;

	/**
	 * Database instance.
	 *
	 * @var Database
	 */
	private Database $db;

	/**
	 * Test project key.
	 *
	 * @var string
	 */
	private string $project_key = 'integration-test-key-789';

	/**
	 * Test project ID.
	 *
	 * @var string
	 */
	private string $project_id = 'integration-project-123';

	/**
	 * Test chain ID (TRON mainnet).
	 *
	 * @var int
	 */
	private int $chain_id = 728126428;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->api = new RestApi();
		$this->db  = new Database();
		$this->db->create_tables();

		// Configure settings with new chain-specific structure.
		update_option(
			'paythefly_settings',
			[
				'tron'  => [
					'project_id'       => $this->project_id,
					'project_key'      => $this->project_key,
					'contract_address' => 'TContractAddress123456789012345678',
				],
				'bsc'   => [
					'project_id'       => 'bsc-project-456',
					'project_key'      => 'bsc-key-456',
					'contract_address' => '0x1234567890123456789012345678901234567890',
				],
				'brand' => 'Integration Test',
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
	 * Generate valid signature for data.
	 *
	 * @param string $data JSON data string.
	 * @return string
	 */
	private function generate_signature( string $data ): string {
		return strtoupper( md5( $data . $this->project_key ) );
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
	 * Test complete webhook flow with database update.
	 *
	 * @return void
	 */
	public function test_complete_webhook_flow(): void {
		// First, simulate creating an order.
		$serial_no = 'PTF-' . wp_generate_uuid4();

		// Insert pending payment.
		$this->db->insert_payment(
			[
				'payment_id' => $serial_no,
				'amount'     => 100.00,
				'currency'   => 'USDT',
				'status'     => 'pending',
			]
		);

		// Setup webhook handler to update payment status.
		add_action(
			'paythefly_webhook_received',
			function ( $payload ) {
				if ( isset( $payload['serial_no'] ) && isset( $payload['status'] ) ) {
					$this->db->update_status( $payload['serial_no'], $payload['status'] );
				}
			}
		);

		// Simulate webhook callback.
		$payload = [
			'project_id' => $this->project_id,
			'chain_id'   => $this->chain_id,
			'serial_no'  => $serial_no,
			'amount'     => '100.00',
			'status'     => 'completed',
			'tx_hash'    => '0xabcdef123456789',
		];
		$data    = wp_json_encode( $payload );
		$sign    = $this->generate_signature( $data );

		$request  = $this->create_request(
			[
				'data' => $data,
				'sign' => $sign,
			]
		);
		$response = $this->api->handle_webhook( $request );

		// Verify response.
		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertEquals( 200, $response->get_status() );

		// Verify database was updated.
		$payment = $this->db->get_payment( $serial_no );
		$this->assertNotNull( $payment );
		$this->assertEquals( 'completed', $payment->status );
	}

	/**
	 * Test webhook with failed status.
	 *
	 * @return void
	 */
	public function test_webhook_failed_status(): void {
		$serial_no = 'PTF-' . wp_generate_uuid4();

		$this->db->insert_payment(
			[
				'payment_id' => $serial_no,
				'amount'     => 50.00,
				'currency'   => 'USDT',
				'status'     => 'pending',
			]
		);

		add_action(
			'paythefly_webhook_received',
			function ( $payload ) {
				if ( isset( $payload['serial_no'] ) && isset( $payload['status'] ) ) {
					$this->db->update_status( $payload['serial_no'], $payload['status'] );
				}
			}
		);

		$payload = [
			'project_id' => $this->project_id,
			'chain_id'   => $this->chain_id,
			'serial_no'  => $serial_no,
			'amount'     => '50.00',
			'status'     => 'failed',
			'error'      => 'Insufficient funds',
		];
		$data    = wp_json_encode( $payload );
		$sign    = $this->generate_signature( $data );

		$request = $this->create_request(
			[
				'data' => $data,
				'sign' => $sign,
			]
		);
		$this->api->handle_webhook( $request );

		$payment = $this->db->get_payment( $serial_no );
		$this->assertEquals( 'failed', $payment->status );
	}

	/**
	 * Test webhook is rejected with tampered data.
	 *
	 * @return void
	 */
	public function test_webhook_rejects_tampered_data(): void {
		$original_payload = [
			'project_id' => $this->project_id,
			'chain_id'   => $this->chain_id,
			'serial_no'  => 'PTF-test-123',
			'amount'     => '100.00',
			'status'     => 'completed',
		];
		$original_data    = wp_json_encode( $original_payload );
		$original_sign    = $this->generate_signature( $original_data );

		// Tamper with the data (change amount).
		$tampered_payload        = $original_payload;
		$tampered_payload['amount'] = '1000.00';
		$tampered_data           = wp_json_encode( $tampered_payload );

		$request  = $this->create_request(
			[
				'data' => $tampered_data,
				'sign' => $original_sign, // Original signature won't match.
			]
		);
		$response = $this->api->handle_webhook( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'invalid_signature', $response->get_error_code() );
	}

	/**
	 * Test multiple hooks can be attached.
	 *
	 * @return void
	 */
	public function test_multiple_webhook_handlers(): void {
		$handler1_called = false;
		$handler2_called = false;

		add_action(
			'paythefly_webhook_received',
			function () use ( &$handler1_called ) {
				$handler1_called = true;
			}
		);

		add_action(
			'paythefly_webhook_received',
			function () use ( &$handler2_called ) {
				$handler2_called = true;
			}
		);

		$payload = [
			'project_id' => $this->project_id,
			'chain_id'   => $this->chain_id,
			'test'       => 'data',
		];
		$data    = wp_json_encode( $payload );
		$sign    = $this->generate_signature( $data );

		$request = $this->create_request(
			[
				'data' => $data,
				'sign' => $sign,
			]
		);
		$this->api->handle_webhook( $request );

		$this->assertTrue( $handler1_called );
		$this->assertTrue( $handler2_called );
	}

	/**
	 * Test webhook payload contains expected fields.
	 *
	 * @return void
	 */
	public function test_webhook_payload_structure(): void {
		$received_payload = null;

		add_action(
			'paythefly_webhook_received',
			function ( $payload ) use ( &$received_payload ) {
				$received_payload = $payload;
			}
		);

		$expected_payload = [
			'project_id'   => $this->project_id,
			'serial_no'    => 'PTF-test-structure',
			'amount'       => '250.00',
			'status'       => 'completed',
			'tx_hash'      => '0x1234567890abcdef',
			'chain_id'     => $this->chain_id,
			'token'        => 'USDT',
			'sender'       => 'TUserAddress123',
			'receiver'     => 'TReceiverAddress456',
			'completed_at' => '2024-01-15T10:30:00Z',
		];
		$data             = wp_json_encode( $expected_payload );
		$sign             = $this->generate_signature( $data );

		$request = $this->create_request(
			[
				'data' => $data,
				'sign' => $sign,
			]
		);
		$this->api->handle_webhook( $request );

		$this->assertNotNull( $received_payload );
		$this->assertEquals( $expected_payload, $received_payload );
	}

	/**
	 * Test webhook with empty project settings.
	 *
	 * @return void
	 */
	public function test_webhook_without_settings(): void {
		delete_option( 'paythefly_settings' );

		$data    = wp_json_encode( [ 'chain_id' => $this->chain_id, 'test' => 'data' ] );
		$request = $this->create_request(
			[
				'data' => $data,
				'sign' => 'anysign',
			]
		);
		$response = $this->api->handle_webhook( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'not_configured', $response->get_error_code() );
	}
}
