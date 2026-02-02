<?php
/**
 * EIP712 class unit tests.
 *
 * @package PayTheFly\Tests\Unit\Crypto
 */

namespace PayTheFly\Tests\Unit\Crypto;

use WP_UnitTestCase;
use PayTheFly\Crypto\EIP712;

/**
 * Unit tests for EIP712 class.
 */
class EIP712Test extends WP_UnitTestCase {

	/**
	 * Test domain_separator returns 32-byte hash.
	 *
	 * @return void
	 */
	public function test_domain_separator_returns_valid_hash(): void {
		$chain_id         = 56;
		$contract_address = '0x1234567890123456789012345678901234567890';

		$result = EIP712::domain_separator( $chain_id, $contract_address );

		$this->assertIsString( $result );
		$this->assertEquals( 64, strlen( $result ) ); // 32 bytes = 64 hex chars.
	}

	/**
	 * Test domain_separator is deterministic.
	 *
	 * @return void
	 */
	public function test_domain_separator_is_deterministic(): void {
		$chain_id         = 56;
		$contract_address = '0x1234567890123456789012345678901234567890';

		$result1 = EIP712::domain_separator( $chain_id, $contract_address );
		$result2 = EIP712::domain_separator( $chain_id, $contract_address );

		$this->assertEquals( $result1, $result2 );
	}

	/**
	 * Test domain_separator differs for different chains.
	 *
	 * @return void
	 */
	public function test_domain_separator_differs_for_chains(): void {
		$contract_address = '0x1234567890123456789012345678901234567890';

		$result1 = EIP712::domain_separator( 56, $contract_address );
		$result2 = EIP712::domain_separator( 97, $contract_address );

		$this->assertNotEquals( $result1, $result2 );
	}

	/**
	 * Test domain_separator differs for different contracts.
	 *
	 * @return void
	 */
	public function test_domain_separator_differs_for_contracts(): void {
		$chain_id = 56;

		$result1 = EIP712::domain_separator( $chain_id, '0x1111111111111111111111111111111111111111' );
		$result2 = EIP712::domain_separator( $chain_id, '0x2222222222222222222222222222222222222222' );

		$this->assertNotEquals( $result1, $result2 );
	}

	/**
	 * Test payment_struct_hash returns valid hash.
	 *
	 * @return void
	 */
	public function test_payment_struct_hash_returns_valid_hash(): void {
		$result = EIP712::payment_struct_hash(
			'project-123',
			'0x55d398326f99059fF775485246999027B3197955',
			'1000000000000000000', // 1 with 18 decimals.
			'PTF-12345',
			'1704067200'
		);

		$this->assertIsString( $result );
		$this->assertEquals( 64, strlen( $result ) );
	}

	/**
	 * Test payment_struct_hash is deterministic.
	 *
	 * @return void
	 */
	public function test_payment_struct_hash_is_deterministic(): void {
		$params = array(
			'project-123',
			'0x55d398326f99059fF775485246999027B3197955',
			'1000000000000000000',
			'PTF-12345',
			'1704067200',
		);

		$result1 = EIP712::payment_struct_hash( ...$params );
		$result2 = EIP712::payment_struct_hash( ...$params );

		$this->assertEquals( $result1, $result2 );
	}

	/**
	 * Test payment_struct_hash differs for different amounts.
	 *
	 * @return void
	 */
	public function test_payment_struct_hash_differs_for_amounts(): void {
		$base_params = array(
			'project-123',
			'0x55d398326f99059fF775485246999027B3197955',
			'PTF-12345',
			'1704067200',
		);

		$result1 = EIP712::payment_struct_hash(
			$base_params[0],
			$base_params[1],
			'1000000000000000000',
			$base_params[2],
			$base_params[3]
		);

		$result2 = EIP712::payment_struct_hash(
			$base_params[0],
			$base_params[1],
			'2000000000000000000',
			$base_params[2],
			$base_params[3]
		);

		$this->assertNotEquals( $result1, $result2 );
	}

	/**
	 * Test typed_data_hash returns valid hash.
	 *
	 * @return void
	 */
	public function test_typed_data_hash_returns_valid_hash(): void {
		$domain_separator = str_repeat( 'ab', 32 );
		$struct_hash      = str_repeat( 'cd', 32 );

		$result = EIP712::typed_data_hash( $domain_separator, $struct_hash );

		$this->assertIsString( $result );
		$this->assertEquals( 64, strlen( $result ) );
	}

	/**
	 * Test keccak256_string returns valid hash.
	 *
	 * @return void
	 */
	public function test_keccak256_string_returns_valid_hash(): void {
		$result = EIP712::keccak256_string( 'hello world' );

		$this->assertIsString( $result );
		$this->assertEquals( 64, strlen( $result ) );
	}

	/**
	 * Test keccak256_string with known value.
	 *
	 * @return void
	 */
	public function test_keccak256_string_known_value(): void {
		// keccak256("") = c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470.
		$result   = EIP712::keccak256_string( '' );
		$expected = 'c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470';

		$this->assertEquals( $expected, $result );
	}
}
