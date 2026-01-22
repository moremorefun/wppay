<?php
/**
 * Database class unit tests.
 *
 * @package PayTheFly\Tests\Unit\Database
 */

namespace PayTheFly\Tests\Unit\Database;

use WP_UnitTestCase;
use PayTheFly\Database\Database;

/**
 * Unit tests for Database class input validation and table name construction.
 */
class DatabaseTest extends WP_UnitTestCase {

	/**
	 * Database instance.
	 *
	 * @var Database
	 */
	private Database $db;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->db = new Database();
	}

	/**
	 * Test that table name uses WordPress prefix.
	 *
	 * @return void
	 */
	public function test_table_name_uses_wp_prefix(): void {
		global $wpdb;

		// Use reflection to access private property.
		$reflection = new \ReflectionClass( $this->db );
		$property   = $reflection->getProperty( 'table_name' );
		$property->setAccessible( true );
		$table_name = $property->getValue( $this->db );

		$this->assertStringStartsWith( $wpdb->prefix, $table_name );
		$this->assertEquals( $wpdb->prefix . 'paythefly_payments', $table_name );
	}

	/**
	 * Test insert_payment with minimal required data.
	 *
	 * @return void
	 */
	public function test_insert_payment_with_minimal_data(): void {
		$data = [
			'payment_id' => 'test-payment-' . wp_generate_uuid4(),
			'amount'     => 100.00,
			'currency'   => 'USDT',
		];

		$result = $this->db->insert_payment( $data );

		$this->assertIsInt( $result );
		$this->assertGreaterThan( 0, $result );
	}

	/**
	 * Test insert_payment with full data including metadata.
	 *
	 * @return void
	 */
	public function test_insert_payment_with_full_data(): void {
		$data = [
			'payment_id'  => 'test-payment-' . wp_generate_uuid4(),
			'amount'      => 250.50,
			'currency'    => 'USDT',
			'status'      => 'completed',
			'description' => 'Test donation',
			'metadata'    => [
				'chain_id' => 728126428,
				'tx_hash'  => '0xabc123',
			],
		];

		$result = $this->db->insert_payment( $data );

		$this->assertIsInt( $result );
		$this->assertGreaterThan( 0, $result );
	}

	/**
	 * Test get_payment returns null for non-existent payment.
	 *
	 * @return void
	 */
	public function test_get_payment_returns_null_for_missing(): void {
		$result = $this->db->get_payment( 'non-existent-payment-id' );

		$this->assertNull( $result );
	}

	/**
	 * Test update_status for non-existent payment.
	 *
	 * @return void
	 */
	public function test_update_status_non_existent(): void {
		$result = $this->db->update_status( 'non-existent-payment-id', 'completed' );

		// Should return true (no error) even if no rows affected.
		$this->assertTrue( $result );
	}

	/**
	 * Test get_payments with default pagination.
	 *
	 * @return void
	 */
	public function test_get_payments_default_pagination(): void {
		$result = $this->db->get_payments();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'items', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertIsArray( $result['items'] );
		$this->assertIsInt( $result['total'] );
	}

	/**
	 * Test get_payments with custom pagination.
	 *
	 * @return void
	 */
	public function test_get_payments_custom_pagination(): void {
		$result = $this->db->get_payments( 2, 10 );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'items', $result );
		$this->assertArrayHasKey( 'total', $result );
	}

	/**
	 * Test get_payments with status filter.
	 *
	 * @return void
	 */
	public function test_get_payments_with_status_filter(): void {
		// Insert some test payments.
		$this->db->insert_payment(
			[
				'payment_id' => 'pending-payment-' . wp_generate_uuid4(),
				'amount'     => 10.00,
				'currency'   => 'USDT',
				'status'     => 'pending',
			]
		);
		$this->db->insert_payment(
			[
				'payment_id' => 'completed-payment-' . wp_generate_uuid4(),
				'amount'     => 20.00,
				'currency'   => 'USDT',
				'status'     => 'completed',
			]
		);

		$pending_results = $this->db->get_payments( 1, 20, 'pending' );

		$this->assertIsArray( $pending_results['items'] );
		foreach ( $pending_results['items'] as $payment ) {
			$this->assertEquals( 'pending', $payment->status );
		}
	}

	/**
	 * Test that decimal amounts are preserved correctly.
	 *
	 * @return void
	 */
	public function test_decimal_amount_precision(): void {
		$payment_id = 'precision-test-' . wp_generate_uuid4();
		$amount     = 123.45678901;

		$this->db->insert_payment(
			[
				'payment_id' => $payment_id,
				'amount'     => $amount,
				'currency'   => 'USDT',
			]
		);

		$payment = $this->db->get_payment( $payment_id );

		$this->assertNotNull( $payment );
		// Database stores with 8 decimal precision.
		$this->assertEquals( '123.45678901', $payment->amount );
	}
}
