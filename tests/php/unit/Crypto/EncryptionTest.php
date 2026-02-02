<?php
/**
 * Encryption class unit tests.
 *
 * @package PayTheFly\Tests\Unit\Crypto
 */

namespace PayTheFly\Tests\Unit\Crypto;

use WP_UnitTestCase;
use PayTheFly\Crypto\Encryption;

/**
 * Unit tests for Encryption class (pass-through storage).
 */
class EncryptionTest extends WP_UnitTestCase {

	/**
	 * Test encrypt returns the same string.
	 *
	 * @return void
	 */
	public function test_encrypt_returns_same_string(): void {
		$plaintext = 'test-secret-data';
		$result    = Encryption::encrypt( $plaintext );

		$this->assertIsString( $result );
		$this->assertEquals( $plaintext, $result );
	}

	/**
	 * Test decrypt returns the same string.
	 *
	 * @return void
	 */
	public function test_decrypt_returns_same_string(): void {
		$data   = 'test-secret-data';
		$result = Encryption::decrypt( $data );

		$this->assertEquals( $data, $result );
	}

	/**
	 * Test encrypt/decrypt roundtrip.
	 *
	 * @return void
	 */
	public function test_encrypt_decrypt_roundtrip(): void {
		$plaintext = 'ac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80';
		$encrypted = Encryption::encrypt( $plaintext );
		$decrypted = Encryption::decrypt( $encrypted );

		$this->assertEquals( $plaintext, $decrypted );
	}

	/**
	 * Test encrypt/decrypt with empty string.
	 *
	 * @return void
	 */
	public function test_encrypt_decrypt_empty_string(): void {
		$encrypted = Encryption::encrypt( '' );
		$decrypted = Encryption::decrypt( $encrypted );

		$this->assertEquals( '', $decrypted );
	}
}
