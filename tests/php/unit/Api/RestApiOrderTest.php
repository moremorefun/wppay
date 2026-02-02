<?php
/**
 * REST API Order endpoint tests.
 *
 * @package PayTheFly\Tests\Unit\Api
 */

namespace PayTheFly\Tests\Unit\Api;

use WP_UnitTestCase;
use WP_REST_Request;
use PayTheFly\Api\RestApi;
use PayTheFly\Crypto\Encryption;
use PayTheFly\Crypto\PrivateKey;

/**
 * Tests for the create_order endpoint.
 */
class RestApiOrderTest extends WP_UnitTestCase {

	/**
	 * REST API instance.
	 *
	 * @var RestApi
	 */
	private RestApi $api;

	/**
	 * Test private key (hex).
	 *
	 * This is Hardhat's default test account #0 private key.
	 * It is publicly known and should NEVER be used in production.
	 *
	 * @var string
	 */
	private string $test_private_key = 'ac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80';

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->api = new RestApi();

		// Set default settings with new structure.
		$encrypted_key = Encryption::encrypt( $this->test_private_key );
		update_option(
			'paythefly_settings',
			[
				'private_key_encrypted' => $encrypted_key,
				'evm_address'           => '0xf39Fd6e51aad88F6F4ce6aB8827279cffFb92266',
				'tron_address'          => 'TYAmMSxfNLAoifcLvuDxMpKTwQG3eUXwkw',
				'tron'                  => [
					'project_id'       => 'tron-project-123',
					'project_key'      => 'tron-key-456',
					'contract_address' => 'TContractAddress123456789012345678',
				],
				'bsc'                   => [
					'project_id'       => 'bsc-project-789',
					'project_key'      => 'bsc-key-012',
					'contract_address' => '0x1234567890123456789012345678901234567890',
				],
				'brand'                 => 'Test Brand',
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
		parent::tearDown();
	}

	/**
	 * Create a mock request for create_order.
	 *
	 * @param array<string, mixed> $params Request parameters.
	 * @return WP_REST_Request
	 */
	private function create_request( array $params ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/paythefly/v1/orders/create' );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return $request;
	}

	/**
	 * Test that non-numeric amount is rejected.
	 *
	 * @return void
	 */
	public function test_rejects_non_numeric_amount(): void {
		$request  = $this->create_request(
			[
				'amount'  => 'abc',
				'chainId' => 728126428,
			]
		);
		$response = $this->api->create_order( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'invalid_amount', $response->get_error_code() );
		$this->assertEquals( 400, $response->get_error_data()['status'] );
	}

	/**
	 * Test that zero amount is rejected.
	 *
	 * @return void
	 */
	public function test_rejects_zero_amount(): void {
		$request  = $this->create_request(
			[
				'amount'  => '0',
				'chainId' => 728126428,
			]
		);
		$response = $this->api->create_order( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'invalid_amount', $response->get_error_code() );
		$this->assertEquals( 400, $response->get_error_data()['status'] );
	}

	/**
	 * Test that negative amount is rejected.
	 *
	 * @return void
	 */
	public function test_rejects_negative_amount(): void {
		$request  = $this->create_request(
			[
				'amount'  => '-10',
				'chainId' => 728126428,
			]
		);
		$response = $this->api->create_order( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'invalid_amount', $response->get_error_code() );
		$this->assertEquals( 400, $response->get_error_data()['status'] );
	}

	/**
	 * Test that unsupported chain ID is rejected.
	 *
	 * @return void
	 */
	public function test_rejects_unsupported_chain_id(): void {
		$request  = $this->create_request(
			[
				'amount'  => '100',
				'chainId' => 999999,
			]
		);
		$response = $this->api->create_order( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'invalid_chain', $response->get_error_code() );
		$this->assertEquals( 400, $response->get_error_data()['status'] );
	}

	/**
	 * Test that TRON chain ID is accepted.
	 *
	 * @return void
	 */
	public function test_accepts_tron_chain_id(): void {
		$request  = $this->create_request(
			[
				'amount'  => '100',
				'chainId' => 728126428,
			]
		);
		$response = $this->api->create_order( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertEquals( 201, $response->get_status() );
	}

	/**
	 * Test that BSC chain ID is accepted.
	 *
	 * @return void
	 */
	public function test_accepts_bsc_chain_id(): void {
		$request  = $this->create_request(
			[
				'amount'  => '100',
				'chainId' => 56,
			]
		);
		$response = $this->api->create_order( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertEquals( 201, $response->get_status() );
	}

	/**
	 * Test that request fails without project ID configured.
	 *
	 * @return void
	 */
	public function test_fails_without_project_id(): void {
		update_option(
			'paythefly_settings',
			[
				'private_key_encrypted' => Encryption::encrypt( $this->test_private_key ),
				'tron'                  => [
					// No project_id.
					'contract_address' => 'TContractAddress123456789012345678',
				],
			]
		);

		$request  = $this->create_request(
			[
				'amount'  => '100',
				'chainId' => 728126428,
			]
		);
		$response = $this->api->create_order( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'missing_project_id', $response->get_error_code() );
		$this->assertEquals( 500, $response->get_error_data()['status'] );
	}

	/**
	 * Test that request fails without contract address configured.
	 *
	 * @return void
	 */
	public function test_fails_without_contract_address(): void {
		update_option(
			'paythefly_settings',
			[
				'private_key_encrypted' => Encryption::encrypt( $this->test_private_key ),
				'tron'                  => [
					'project_id' => 'tron-project-123',
					// No contract_address.
				],
			]
		);

		$request  = $this->create_request(
			[
				'amount'  => '100',
				'chainId' => 728126428,
			]
		);
		$response = $this->api->create_order( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'missing_contract_address', $response->get_error_code() );
		$this->assertEquals( 500, $response->get_error_data()['status'] );
	}

	/**
	 * Test that request fails without private key configured.
	 *
	 * @return void
	 */
	public function test_fails_without_private_key(): void {
		update_option(
			'paythefly_settings',
			[
				// No private_key_encrypted.
				'tron' => [
					'project_id'       => 'tron-project-123',
					'contract_address' => 'TContractAddress123456789012345678',
				],
			]
		);

		$request  = $this->create_request(
			[
				'amount'  => '100',
				'chainId' => 728126428,
			]
		);
		$response = $this->api->create_order( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'missing_private_key', $response->get_error_code() );
		$this->assertEquals( 500, $response->get_error_data()['status'] );
	}

	/**
	 * Test that response includes payUrl.
	 *
	 * @return void
	 */
	public function test_response_includes_pay_url(): void {
		$request  = $this->create_request(
			[
				'amount'  => '100',
				'chainId' => 728126428,
			]
		);
		$response = $this->api->create_order( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'payUrl', $data );
		$this->assertStringStartsWith( 'https://pro.paythefly.com/pay', $data['payUrl'] );
	}

	/**
	 * Test that payUrl includes signature.
	 *
	 * @return void
	 */
	public function test_pay_url_includes_signature(): void {
		$request  = $this->create_request(
			[
				'amount'  => '100',
				'chainId' => 728126428,
			]
		);
		$response = $this->api->create_order( $request );
		$data     = $response->get_data();

		$this->assertStringContainsString( 'signature=0x', $data['payUrl'] );
		$this->assertStringContainsString( 'deadline=', $data['payUrl'] );
	}

	/**
	 * Test that serial number format is correct.
	 *
	 * @return void
	 */
	public function test_serial_number_format(): void {
		$request  = $this->create_request(
			[
				'amount'  => '100',
				'chainId' => 728126428,
			]
		);
		$response = $this->api->create_order( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'serialNo', $data );
		$this->assertStringStartsWith( 'PTF-', $data['serialNo'] );
		$this->assertMatchesRegularExpression(
			'/^PTF-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
			$data['serialNo']
		);
	}
}
