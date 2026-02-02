<?php
/**
 * Signer class unit tests.
 *
 * @package PayTheFly\Tests\Unit\Crypto
 */

namespace PayTheFly\Tests\Unit\Crypto;

use WP_UnitTestCase;
use PayTheFly\Crypto\Signer;
use PayTheFly\Crypto\PrivateKey;

/**
 * Unit tests for Signer class.
 */
class SignerTest extends WP_UnitTestCase {

	/**
	 * Test private key for testing.
	 *
	 * This is Hardhat's default test account #0 private key.
	 * It is publicly known and should NEVER be used in production.
	 *
	 * @var string
	 */
	private string $test_key;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		// Use a fixed test key for deterministic tests.
		$this->test_key = 'ac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80';
	}

	/**
	 * Test sign_payment returns valid signature format.
	 *
	 * @return void
	 */
	public function test_sign_payment_returns_valid_signature(): void {
		$signature = Signer::sign_payment(
			array(
				'chain_id'         => 56,
				'project_id'       => 'test-project',
				'contract_address' => '0x1234567890123456789012345678901234567890',
				'token_address'    => '0x55d398326f99059fF775485246999027B3197955',
				'amount'           => '10',
				'serial_no'        => 'PTF-12345',
				'deadline'         => '1704067200',
				'private_key'      => $this->test_key,
			)
		);

		$this->assertStringStartsWith( '0x', $signature );
		$this->assertEquals( 132, strlen( $signature ) ); // 0x + r (64) + s (64) + v (2).
	}

	/**
	 * Test sign_payment is deterministic.
	 *
	 * @return void
	 */
	public function test_sign_payment_is_deterministic(): void {
		$params = array(
			'chain_id'         => 56,
			'project_id'       => 'test-project',
			'contract_address' => '0x1234567890123456789012345678901234567890',
			'token_address'    => '0x55d398326f99059fF775485246999027B3197955',
			'amount'           => '10',
			'serial_no'        => 'PTF-12345',
			'deadline'         => '1704067200',
			'private_key'      => $this->test_key,
		);

		$sig1 = Signer::sign_payment( $params );
		$sig2 = Signer::sign_payment( $params );

		$this->assertEquals( $sig1, $sig2 );
	}

	/**
	 * Test sign_payment differs for different amounts.
	 *
	 * @return void
	 */
	public function test_sign_payment_differs_for_amounts(): void {
		$base_params = array(
			'chain_id'         => 56,
			'project_id'       => 'test-project',
			'contract_address' => '0x1234567890123456789012345678901234567890',
			'token_address'    => '0x55d398326f99059fF775485246999027B3197955',
			'serial_no'        => 'PTF-12345',
			'deadline'         => '1704067200',
			'private_key'      => $this->test_key,
		);

		$sig1 = Signer::sign_payment( array_merge( $base_params, array( 'amount' => '10' ) ) );
		$sig2 = Signer::sign_payment( array_merge( $base_params, array( 'amount' => '20' ) ) );

		$this->assertNotEquals( $sig1, $sig2 );
	}

	/**
	 * Test sign_payment for TRON chain.
	 *
	 * @return void
	 */
	public function test_sign_payment_for_tron(): void {
		$signature = Signer::sign_payment(
			array(
				'chain_id'         => 728126428,
				'project_id'       => 'test-project',
				'contract_address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
				'token_address'    => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
				'amount'           => '10',
				'serial_no'        => 'PTF-12345',
				'deadline'         => '1704067200',
				'private_key'      => $this->test_key,
			)
		);

		$this->assertStringStartsWith( '0x', $signature );
		$this->assertEquals( 132, strlen( $signature ) );
	}

	/**
	 * Test sign_payment with decimal amount.
	 *
	 * @return void
	 */
	public function test_sign_payment_decimal_amount(): void {
		$signature = Signer::sign_payment(
			array(
				'chain_id'         => 56,
				'project_id'       => 'test-project',
				'contract_address' => '0x1234567890123456789012345678901234567890',
				'token_address'    => '0x55d398326f99059fF775485246999027B3197955',
				'amount'           => '10.5',
				'serial_no'        => 'PTF-12345',
				'deadline'         => '1704067200',
				'private_key'      => $this->test_key,
			)
		);

		$this->assertStringStartsWith( '0x', $signature );
	}

	/**
	 * Test sign_payment throws for unsupported chain.
	 *
	 * @return void
	 */
	public function test_sign_payment_throws_for_unsupported_chain(): void {
		$this->expectException( \InvalidArgumentException::class );

		Signer::sign_payment(
			array(
				'chain_id'         => 999999,
				'project_id'       => 'test-project',
				'contract_address' => '0x1234567890123456789012345678901234567890',
				'token_address'    => '0x55d398326f99059fF775485246999027B3197955',
				'amount'           => '10',
				'serial_no'        => 'PTF-12345',
				'deadline'         => '1704067200',
				'private_key'      => $this->test_key,
			)
		);
	}

	/**
	 * Test get_deadline returns future timestamp.
	 *
	 * @return void
	 */
	public function test_get_deadline_returns_future_timestamp(): void {
		$deadline = Signer::get_deadline( 1800 );
		$now      = time();

		$this->assertIsString( $deadline );
		$this->assertGreaterThan( $now, (int) $deadline );
		$this->assertLessThanOrEqual( $now + 1800 + 1, (int) $deadline );
	}

	/**
	 * Test is_tron_chain for TRON mainnet.
	 *
	 * @return void
	 */
	public function test_is_tron_chain_mainnet(): void {
		$this->assertTrue( Signer::is_tron_chain( 728126428 ) );
	}

	/**
	 * Test is_tron_chain for TRON testnet.
	 *
	 * @return void
	 */
	public function test_is_tron_chain_testnet(): void {
		$this->assertTrue( Signer::is_tron_chain( 3448148188 ) );
	}

	/**
	 * Test is_tron_chain for BSC.
	 *
	 * @return void
	 */
	public function test_is_tron_chain_false_for_bsc(): void {
		$this->assertFalse( Signer::is_tron_chain( 56 ) );
	}

	/**
	 * Test get_decimals for BSC.
	 *
	 * @return void
	 */
	public function test_get_decimals_bsc(): void {
		$this->assertEquals( 18, Signer::get_decimals( 56 ) );
	}

	/**
	 * Test get_decimals for TRON.
	 *
	 * @return void
	 */
	public function test_get_decimals_tron(): void {
		$this->assertEquals( 6, Signer::get_decimals( 728126428 ) );
	}
}
