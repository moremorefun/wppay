<?php
/**
 * Database integration tests.
 *
 * @package PayTheFly\Tests\Integration\Database
 */

namespace PayTheFly\Tests\Integration\Database;

use WP_UnitTestCase;
use PayTheFly\Database\Database;

/**
 * Integration tests for Database class with real database operations.
 */
class DatabaseIntegrationTest extends WP_UnitTestCase {

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
		$this->db->create_tables();
	}

	/**
	 * Test that tables are created successfully.
	 *
	 * @return void
	 */
	public function test_create_tables(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'paythefly_payments';
		$result     = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		);

		$this->assertEquals( $table_name, $result );
	}

	/**
	 * Test inserting a payment and querying it back.
	 *
	 * @return void
	 */
	public function test_insert_payment(): void {
		$payment_id = 'integration-test-' . wp_generate_uuid4();
		$data       = [
			'payment_id'  => $payment_id,
			'amount'      => 150.50,
			'currency'    => 'USDT',
			'status'      => 'pending',
			'description' => 'Integration test payment',
		];

		$insert_id = $this->db->insert_payment( $data );
		$payment   = $this->db->get_payment( $payment_id );

		$this->assertIsInt( $insert_id );
		$this->assertGreaterThan( 0, $insert_id );
		$this->assertNotNull( $payment );
		$this->assertEquals( $payment_id, $payment->payment_id );
		$this->assertEquals( '150.50000000', $payment->amount );
		$this->assertEquals( 'USDT', $payment->currency );
		$this->assertEquals( 'pending', $payment->status );
		$this->assertEquals( 'Integration test payment', $payment->description );
	}

	/**
	 * Test that duplicate payment IDs fail.
	 *
	 * @return void
	 */
	public function test_duplicate_payment_id_fails(): void {
		$payment_id = 'duplicate-test-' . wp_generate_uuid4();
		$data       = [
			'payment_id' => $payment_id,
			'amount'     => 100.00,
			'currency'   => 'USDT',
		];

		$first_insert  = $this->db->insert_payment( $data );
		$second_insert = $this->db->insert_payment( $data );

		$this->assertIsInt( $first_insert );
		$this->assertGreaterThan( 0, $first_insert );
		$this->assertFalse( $second_insert );
	}

	/**
	 * Test updating payment status.
	 *
	 * @return void
	 */
	public function test_update_status(): void {
		$payment_id = 'status-test-' . wp_generate_uuid4();
		$this->db->insert_payment(
			[
				'payment_id' => $payment_id,
				'amount'     => 75.00,
				'currency'   => 'USDT',
				'status'     => 'pending',
			]
		);

		$result  = $this->db->update_status( $payment_id, 'completed' );
		$payment = $this->db->get_payment( $payment_id );

		$this->assertTrue( $result );
		$this->assertEquals( 'completed', $payment->status );
	}

	/**
	 * Test get_payment returns correct data.
	 *
	 * @return void
	 */
	public function test_get_payment(): void {
		$payment_id = 'get-test-' . wp_generate_uuid4();
		$metadata   = [
			'chain_id' => 728126428,
			'tx_hash'  => '0xabc123def456',
			'sender'   => 'TUserAddress123',
		];

		$this->db->insert_payment(
			[
				'payment_id'  => $payment_id,
				'amount'      => 200.00,
				'currency'    => 'USDT',
				'status'      => 'completed',
				'description' => 'Test with metadata',
				'metadata'    => $metadata,
			]
		);

		$payment = $this->db->get_payment( $payment_id );

		$this->assertNotNull( $payment );
		$this->assertEquals( $payment_id, $payment->payment_id );
		$this->assertEquals( 'completed', $payment->status );

		$decoded_metadata = json_decode( $payment->metadata, true );
		$this->assertEquals( $metadata, $decoded_metadata );
	}

	/**
	 * Test get_payments pagination.
	 *
	 * @return void
	 */
	public function test_get_payments_pagination(): void {
		// Insert multiple payments.
		for ( $i = 1; $i <= 15; $i++ ) {
			$this->db->insert_payment(
				[
					'payment_id' => 'pagination-test-' . $i . '-' . wp_generate_uuid4(),
					'amount'     => $i * 10,
					'currency'   => 'USDT',
					'status'     => 'completed',
				]
			);
		}

		$page1 = $this->db->get_payments( 1, 5 );
		$page2 = $this->db->get_payments( 2, 5 );
		$page3 = $this->db->get_payments( 3, 5 );

		$this->assertCount( 5, $page1['items'] );
		$this->assertCount( 5, $page2['items'] );
		$this->assertCount( 5, $page3['items'] );
		$this->assertGreaterThanOrEqual( 15, $page1['total'] );

		// Ensure different pages have different items.
		$page1_ids = array_map( fn( $p ) => $p->payment_id, $page1['items'] );
		$page2_ids = array_map( fn( $p ) => $p->payment_id, $page2['items'] );
		$this->assertEmpty( array_intersect( $page1_ids, $page2_ids ) );
	}

	/**
	 * Test get_payments status filter.
	 *
	 * @return void
	 */
	public function test_get_payments_filter_status(): void {
		// Insert payments with different statuses.
		for ( $i = 1; $i <= 5; $i++ ) {
			$this->db->insert_payment(
				[
					'payment_id' => 'filter-pending-' . $i . '-' . wp_generate_uuid4(),
					'amount'     => 10.00,
					'currency'   => 'USDT',
					'status'     => 'pending',
				]
			);
		}

		for ( $i = 1; $i <= 3; $i++ ) {
			$this->db->insert_payment(
				[
					'payment_id' => 'filter-completed-' . $i . '-' . wp_generate_uuid4(),
					'amount'     => 20.00,
					'currency'   => 'USDT',
					'status'     => 'completed',
				]
			);
		}

		$pending_result   = $this->db->get_payments( 1, 20, 'pending' );
		$completed_result = $this->db->get_payments( 1, 20, 'completed' );

		// All items should have the filtered status.
		foreach ( $pending_result['items'] as $item ) {
			$this->assertEquals( 'pending', $item->status );
		}

		foreach ( $completed_result['items'] as $item ) {
			$this->assertEquals( 'completed', $item->status );
		}

		$this->assertGreaterThanOrEqual( 5, $pending_result['total'] );
		$this->assertGreaterThanOrEqual( 3, $completed_result['total'] );
	}

	/**
	 * Test payments are ordered by created_at DESC.
	 *
	 * @return void
	 */
	public function test_payments_ordered_by_created_at_desc(): void {
		$ids = [];
		for ( $i = 1; $i <= 3; $i++ ) {
			$id    = 'order-test-' . $i . '-' . wp_generate_uuid4();
			$ids[] = $id;
			$this->db->insert_payment(
				[
					'payment_id' => $id,
					'amount'     => $i * 100,
					'currency'   => 'USDT',
				]
			);
			// Small delay to ensure different timestamps.
			usleep( 10000 );
		}

		$result = $this->db->get_payments( 1, 10 );

		// Most recent should be first.
		$this->assertCount( 3, $result['items'] );
		// The last inserted should be first in results.
		$result_ids = array_map( fn( $p ) => $p->payment_id, $result['items'] );
		$this->assertEquals( $ids[2], $result_ids[0] );
	}

	/**
	 * Test payment with special characters in description.
	 *
	 * @return void
	 */
	public function test_payment_with_special_characters(): void {
		$payment_id  = 'special-chars-' . wp_generate_uuid4();
		$description = "Test payment with special chars: <script>alert('xss')</script> & \"quotes\" 'apostrophe' äöü 中文";

		$this->db->insert_payment(
			[
				'payment_id'  => $payment_id,
				'amount'      => 50.00,
				'currency'    => 'USDT',
				'description' => $description,
			]
		);

		$payment = $this->db->get_payment( $payment_id );

		$this->assertNotNull( $payment );
		$this->assertEquals( $description, $payment->description );
	}

	/**
	 * Test large amount handling.
	 *
	 * @return void
	 */
	public function test_large_amount_handling(): void {
		$payment_id = 'large-amount-' . wp_generate_uuid4();
		$amount     = 9999999999.12345678;

		$this->db->insert_payment(
			[
				'payment_id' => $payment_id,
				'amount'     => $amount,
				'currency'   => 'USDT',
			]
		);

		$payment = $this->db->get_payment( $payment_id );

		$this->assertNotNull( $payment );
		$this->assertEquals( '9999999999.12345678', $payment->amount );
	}
}
