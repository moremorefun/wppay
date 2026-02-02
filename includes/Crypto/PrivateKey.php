<?php
/**
 * PrivateKey class.
 *
 * Handles secp256k1 private key generation and address derivation.
 *
 * @package PayTheFly
 */

namespace PayTheFly\Crypto;

use Elliptic\EC;
use kornrunner\Keccak;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles private key operations.
 */
class PrivateKey {

	/**
	 * Base58 alphabet for TRON address encoding.
	 */
	private const BASE58_ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

	/**
	 * Generate a new random private key.
	 *
	 * @return string Hex-encoded private key (64 chars, no 0x prefix).
	 */
	public static function generate(): string {
		$ec      = new EC( 'secp256k1' );
		$keypair = $ec->genKeyPair();
		return $keypair->getPrivate( 'hex' );
	}

	/**
	 * Validate a private key format.
	 *
	 * @param string $key The private key to validate (with or without 0x prefix).
	 * @return bool True if valid.
	 */
	public static function validate( string $key ): bool {
		$hex = self::normalize_hex( $key );
		if ( 64 !== strlen( $hex ) ) {
			return false;
		}
		return 1 === preg_match( '/^[0-9a-fA-F]+$/', $hex );
	}

	/**
	 * Derive EVM address from private key.
	 *
	 * @param string $private_key The private key (with or without 0x prefix).
	 * @return string EVM address with 0x prefix and checksum.
	 */
	public static function derive_evm_address( string $private_key ): string {
		$hex = self::normalize_hex( $private_key );
		$ec  = new EC( 'secp256k1' );

		$keypair    = $ec->keyFromPrivate( $hex );
		$public_key = $keypair->getPublic( false, 'hex' );

		// Remove the '04' prefix from uncompressed public key.
		$public_key_hex = substr( $public_key, 2 );

		// Keccak-256 hash of public key.
		$hash = Keccak::hash( hex2bin( $public_key_hex ), 256 );

		// Take last 20 bytes (40 hex chars).
		$address = '0x' . substr( $hash, -40 );

		return self::to_checksum_address( $address );
	}

	/**
	 * Derive TRON address from private key.
	 *
	 * TRON addresses are Base58Check encoded with 0x41 prefix.
	 *
	 * @param string $private_key The private key (with or without 0x prefix).
	 * @return string TRON address starting with 'T'.
	 */
	public static function derive_tron_address( string $private_key ): string {
		// Get EVM address first (without checksum).
		$evm_address = self::derive_evm_address( $private_key );
		$evm_hex     = strtolower( substr( $evm_address, 2 ) ); // Remove 0x.

		// TRON address: 0x41 + 20-byte address.
		$tron_hex = '41' . $evm_hex;

		return self::hex_to_base58_check( $tron_hex );
	}

	/**
	 * Convert EVM address to TRON address.
	 *
	 * @param string $evm_address EVM address with 0x prefix.
	 * @return string TRON address starting with 'T'.
	 */
	public static function evm_to_tron_address( string $evm_address ): string {
		$evm_hex  = strtolower( substr( $evm_address, 2 ) );
		$tron_hex = '41' . $evm_hex;
		return self::hex_to_base58_check( $tron_hex );
	}

	/**
	 * Convert TRON address to EVM format.
	 *
	 * @param string $tron_address TRON address starting with 'T'.
	 * @return string EVM address with 0x prefix.
	 */
	public static function tron_to_evm_address( string $tron_address ): string {
		if ( 0 === strpos( $tron_address, '0x' ) ) {
			return $tron_address;
		}

		$hex = self::base58_check_to_hex( $tron_address );
		// Remove 41 prefix.
		$evm_hex = substr( $hex, 2, 40 );
		return '0x' . $evm_hex;
	}

	/**
	 * Normalize hex string (remove 0x prefix if present).
	 *
	 * @param string $hex The hex string.
	 * @return string Hex string without 0x prefix.
	 */
	private static function normalize_hex( string $hex ): string {
		if ( 0 === strpos( $hex, '0x' ) || 0 === strpos( $hex, '0X' ) ) {
			return substr( $hex, 2 );
		}
		return $hex;
	}

	/**
	 * Convert address to EIP-55 checksum format.
	 *
	 * @param string $address The address (lowercase or mixed case).
	 * @return string Checksummed address.
	 */
	private static function to_checksum_address( string $address ): string {
		$address_lower = strtolower( substr( $address, 2 ) );
		$hash          = Keccak::hash( $address_lower, 256 );

		$result = '0x';
		for ( $i = 0; $i < 40; $i++ ) {
			$hash_char = hexdec( $hash[ $i ] );
			if ( $hash_char >= 8 ) {
				$result .= strtoupper( $address_lower[ $i ] );
			} else {
				$result .= $address_lower[ $i ];
			}
		}

		return $result;
	}

	/**
	 * Convert hex string to Base58Check encoded string.
	 *
	 * @param string $hex The hex string.
	 * @return string Base58Check encoded string.
	 */
	private static function hex_to_base58_check( string $hex ): string {
		$bytes = hex2bin( $hex );

		// Double SHA256 for checksum.
		$hash1    = hash( 'sha256', $bytes, true );
		$hash2    = hash( 'sha256', $hash1, true );
		$checksum = substr( $hash2, 0, 4 );

		$bytes_with_checksum = $bytes . $checksum;

		return self::base58_encode( $bytes_with_checksum );
	}

	/**
	 * Convert Base58Check string to hex.
	 *
	 * @param string $base58 The Base58Check string.
	 * @return string Hex string.
	 */
	private static function base58_check_to_hex( string $base58 ): string {
		$bytes = self::base58_decode( $base58 );
		// Remove 4-byte checksum.
		$payload = substr( $bytes, 0, -4 );
		return bin2hex( $payload );
	}

	/**
	 * Encode bytes to Base58.
	 *
	 * Uses BCMath for arbitrary precision arithmetic.
	 *
	 * @param string $bytes Binary data.
	 * @return string Base58 encoded string.
	 */
	private static function base58_encode( string $bytes ): string {
		$alphabet = self::BASE58_ALPHABET;

		// Count leading zeros.
		$leading_zeros = 0;
		$len           = strlen( $bytes );
		for ( $i = 0; $i < $len && "\x00" === $bytes[ $i ]; $i++ ) {
			++$leading_zeros;
		}

		// Convert to big integer using BCMath.
		$num = self::hex_to_bc( bin2hex( $bytes ) );

		$result = '';
		while ( bccomp( $num, '0' ) > 0 ) {
			$rem    = bcmod( $num, '58' );
			$num    = bcdiv( $num, '58', 0 );
			$result = $alphabet[ (int) $rem ] . $result;
		}

		// Add leading '1's for each leading zero byte.
		return str_repeat( '1', $leading_zeros ) . $result;
	}

	/**
	 * Decode Base58 to bytes.
	 *
	 * Uses BCMath for arbitrary precision arithmetic.
	 *
	 * @param string $base58 Base58 encoded string.
	 * @return string Binary data.
	 */
	private static function base58_decode( string $base58 ): string {
		$alphabet = self::BASE58_ALPHABET;

		// Count leading '1's.
		$leading_ones = 0;
		$len          = strlen( $base58 );
		for ( $i = 0; $i < $len && '1' === $base58[ $i ]; $i++ ) {
			++$leading_ones;
		}

		// Convert from base58 using BCMath.
		$num = '0';
		for ( $i = 0; $i < $len; $i++ ) {
			$pos = strpos( $alphabet, $base58[ $i ] );
			if ( false === $pos ) {
				return '';
			}
			$num = bcadd( bcmul( $num, '58' ), (string) $pos );
		}

		$hex = self::bc_to_hex( $num );

		$bytes = hex2bin( $hex );

		// Add leading zero bytes.
		return str_repeat( "\x00", $leading_ones ) . $bytes;
	}

	/**
	 * Convert hex string to BCMath number.
	 *
	 * @param string $hex Hex string.
	 * @return string BCMath number string.
	 */
	private static function hex_to_bc( string $hex ): string {
		$result = '0';
		$len    = strlen( $hex );
		for ( $i = 0; $i < $len; $i++ ) {
			$digit  = hexdec( $hex[ $i ] );
			$result = bcadd( bcmul( $result, '16' ), (string) $digit );
		}
		return $result;
	}

	/**
	 * Convert BCMath number to hex string.
	 *
	 * @param string $num BCMath number string.
	 * @return string Hex string (even length).
	 */
	private static function bc_to_hex( string $num ): string {
		if ( '0' === $num ) {
			return '00';
		}

		$hex = '';
		while ( bccomp( $num, '0' ) > 0 ) {
			$rem = bcmod( $num, '16' );
			$num = bcdiv( $num, '16', 0 );
			$hex = dechex( (int) $rem ) . $hex;
		}

		// Pad to even length.
		if ( strlen( $hex ) % 2 !== 0 ) {
			$hex = '0' . $hex;
		}

		return $hex;
	}
}
