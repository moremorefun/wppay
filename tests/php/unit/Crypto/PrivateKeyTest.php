<?php
/**
 * PrivateKey class unit tests.
 *
 * @package PayTheFly\Tests\Unit\Crypto
 */

namespace PayTheFly\Tests\Unit\Crypto;

use WP_UnitTestCase;
use PayTheFly\Crypto\PrivateKey;

/**
 * Unit tests for PrivateKey class.
 */
class PrivateKeyTest extends WP_UnitTestCase {

	/**
	 * Test generate returns valid hex string.
	 *
	 * @return void
	 */
	public function test_generate_returns_valid_hex(): void {
		$key = PrivateKey::generate();

		$this->assertIsString( $key );
		$this->assertEquals( 64, strlen( $key ) );
		$this->assertMatchesRegularExpression( '/^[0-9a-fA-F]+$/', $key );
	}

	/**
	 * Test generate returns different keys each time.
	 *
	 * @return void
	 */
	public function test_generate_returns_different_keys(): void {
		$key1 = PrivateKey::generate();
		$key2 = PrivateKey::generate();

		$this->assertNotEquals( $key1, $key2 );
	}

	/**
	 * Test validate accepts valid key without prefix.
	 *
	 * @return void
	 */
	public function test_validate_accepts_valid_key(): void {
		$key = str_repeat( 'ab', 32 ); // 64 hex chars.

		$this->assertTrue( PrivateKey::validate( $key ) );
	}

	/**
	 * Test validate accepts valid key with 0x prefix.
	 *
	 * @return void
	 */
	public function test_validate_accepts_key_with_prefix(): void {
		$key = '0x' . str_repeat( 'ab', 32 );

		$this->assertTrue( PrivateKey::validate( $key ) );
	}

	/**
	 * Test validate rejects too short key.
	 *
	 * @return void
	 */
	public function test_validate_rejects_short_key(): void {
		$key = str_repeat( 'ab', 16 ); // Only 32 chars.

		$this->assertFalse( PrivateKey::validate( $key ) );
	}

	/**
	 * Test validate rejects invalid hex.
	 *
	 * @return void
	 */
	public function test_validate_rejects_invalid_hex(): void {
		$key = str_repeat( 'zz', 32 ); // Invalid hex chars.

		$this->assertFalse( PrivateKey::validate( $key ) );
	}

	/**
	 * Test derive_evm_address returns valid format.
	 *
	 * @return void
	 */
	public function test_derive_evm_address_valid_format(): void {
		$key     = PrivateKey::generate();
		$address = PrivateKey::derive_evm_address( $key );

		$this->assertStringStartsWith( '0x', $address );
		$this->assertEquals( 42, strlen( $address ) );
	}

	/**
	 * Test derive_evm_address is deterministic.
	 *
	 * @return void
	 */
	public function test_derive_evm_address_deterministic(): void {
		$key      = PrivateKey::generate();
		$address1 = PrivateKey::derive_evm_address( $key );
		$address2 = PrivateKey::derive_evm_address( $key );

		$this->assertEquals( $address1, $address2 );
	}

	/**
	 * Test derive_evm_address with known key.
	 *
	 * Uses Hardhat's default test account #0 private key.
	 * This is publicly known and should NEVER be used in production.
	 *
	 * @return void
	 */
	public function test_derive_evm_address_known_key(): void {
		// Hardhat test account #0 (DO NOT use in production!).
		$key             = 'ac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80';
		$address         = PrivateKey::derive_evm_address( $key );
		$expected_prefix = '0xf39Fd6e51aad88F6F4ce6aB8827279cffFb92266';

		$this->assertEquals( strtolower( $expected_prefix ), strtolower( $address ) );
	}

	/**
	 * Test derive_tron_address returns valid format.
	 *
	 * @return void
	 */
	public function test_derive_tron_address_valid_format(): void {
		$key     = PrivateKey::generate();
		$address = PrivateKey::derive_tron_address( $key );

		$this->assertStringStartsWith( 'T', $address );
		$this->assertEquals( 34, strlen( $address ) );
	}

	/**
	 * Test derive_tron_address is deterministic.
	 *
	 * @return void
	 */
	public function test_derive_tron_address_deterministic(): void {
		$key      = PrivateKey::generate();
		$address1 = PrivateKey::derive_tron_address( $key );
		$address2 = PrivateKey::derive_tron_address( $key );

		$this->assertEquals( $address1, $address2 );
	}

	/**
	 * Test tron_to_evm_address conversion.
	 *
	 * @return void
	 */
	public function test_tron_to_evm_address(): void {
		// Known USDT address on TRON.
		$tron_address = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';
		$evm_address  = PrivateKey::tron_to_evm_address( $tron_address );

		$this->assertStringStartsWith( '0x', $evm_address );
		$this->assertEquals( 42, strlen( $evm_address ) );
	}

	/**
	 * Test tron_to_evm_address passthrough for EVM addresses.
	 *
	 * @return void
	 */
	public function test_tron_to_evm_address_passthrough(): void {
		$evm_address = '0x55d398326f99059fF775485246999027B3197955';
		$result      = PrivateKey::tron_to_evm_address( $evm_address );

		$this->assertEquals( $evm_address, $result );
	}

	/**
	 * Test evm_to_tron_address and back.
	 *
	 * @return void
	 */
	public function test_address_conversion_roundtrip(): void {
		$key         = PrivateKey::generate();
		$evm_address = PrivateKey::derive_evm_address( $key );

		$tron_address  = PrivateKey::evm_to_tron_address( $evm_address );
		$back_to_evm   = PrivateKey::tron_to_evm_address( $tron_address );

		$this->assertEquals( strtolower( $evm_address ), strtolower( $back_to_evm ) );
	}
}
