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
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->api = new RestApi();

		// Set default settings with project_id.
		update_option(
			'paythefly_settings',
			[
				'project_id'  => 'test-project-123',
				'project_key' => 'test-key-456',
				'brand'       => 'Test Brand',
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
		delete_option( 'paythefly_settings' );

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
	 * Test that correct token address is returned for TRON.
	 *
	 * @return void
	 */
	public function test_returns_correct_tron_token(): void {
		$request  = $this->create_request(
			[
				'amount'  => '100',
				'chainId' => 728126428,
			]
		);
		$response = $this->api->create_order( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t', $data['token'] );
	}

	/**
	 * Test that correct token address is returned for BSC.
	 *
	 * @return void
	 */
	public function test_returns_correct_bsc_token(): void {
		$request  = $this->create_request(
			[
				'amount'  => '100',
				'chainId' => 56,
			]
		);
		$response = $this->api->create_order( $request );
		$data     = $response->get_data();

		$this->assertEquals( '0x55d398326f99059fF775485246999027B3197955', $data['token'] );
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

		$this->assertStringStartsWith( 'PTF-', $data['serialNo'] );
		$this->assertMatchesRegularExpression(
			'/^PTF-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
			$data['serialNo']
		);
	}
}
