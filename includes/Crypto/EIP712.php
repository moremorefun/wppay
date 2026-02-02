<?php
/**
 * EIP712 class.
 *
 * Implements EIP-712 typed data hashing for payment signatures.
 *
 * @package PayTheFly
 */

namespace PayTheFly\Crypto;

use kornrunner\Keccak;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles EIP-712 type hashing.
 */
class EIP712 {

	/**
	 * Domain name.
	 */
	private const DOMAIN_NAME = 'PayTheFlyPro';

	/**
	 * Domain version.
	 */
	private const DOMAIN_VERSION = '1';

	/**
	 * EIP712Domain type hash.
	 *
	 * keccak256("EIP712Domain(string name,string version,uint256 chainId,address verifyingContract)")
	 */
	private const DOMAIN_TYPE_HASH = '8b73c3c69bb8fe3d512ecc4cf759cc79239f7b179b0ffacaa9a75d522b39400f';

	/**
	 * Compute the domain separator for a contract.
	 *
	 * @param int    $chain_id         Chain ID.
	 * @param string $contract_address Contract address (0x prefixed).
	 * @return string 32-byte hash as hex string.
	 */
	public static function domain_separator( int $chain_id, string $contract_address ): string {
		$name_hash    = self::keccak256_string( self::DOMAIN_NAME );
		$version_hash = self::keccak256_string( self::DOMAIN_VERSION );

		// Normalize contract address to lowercase without 0x.
		$contract_hex = strtolower( str_replace( '0x', '', $contract_address ) );

		// Encode: typeHash + nameHash + versionHash + chainId (uint256) + verifyingContract (address).
		$encoded  = self::DOMAIN_TYPE_HASH;
		$encoded .= $name_hash;
		$encoded .= $version_hash;
		$encoded .= self::encode_uint256( (string) $chain_id );
		$encoded .= str_pad( $contract_hex, 64, '0', STR_PAD_LEFT );

		return Keccak::hash( hex2bin( $encoded ), 256 );
	}

	/**
	 * Compute the struct hash for a PaymentRequest.
	 *
	 * @param string $project_id    Project ID string.
	 * @param string $token_address Token contract address (0x prefixed).
	 * @param string $amount        Amount in smallest unit (wei).
	 * @param string $serial_no     Serial number string.
	 * @param string $deadline      Deadline timestamp as string.
	 * @return string 32-byte hash as hex string.
	 */
	public static function payment_struct_hash(
		string $project_id,
		string $token_address,
		string $amount,
		string $serial_no,
		string $deadline
	): string {
		$type_hash = self::compute_payment_type_hash();

		// Normalize token address.
		$token_hex = strtolower( str_replace( '0x', '', $token_address ) );

		// Encode: typeHash + projectIdHash + token + amount + serialNoHash + deadline.
		$encoded  = $type_hash;
		$encoded .= self::keccak256_string( $project_id );
		$encoded .= str_pad( $token_hex, 64, '0', STR_PAD_LEFT );
		$encoded .= self::encode_uint256( $amount );
		$encoded .= self::keccak256_string( $serial_no );
		$encoded .= self::encode_uint256( $deadline );

		return Keccak::hash( hex2bin( $encoded ), 256 );
	}

	/**
	 * Compute the final EIP-712 hash for signing.
	 *
	 * @param string $domain_separator Domain separator hash.
	 * @param string $struct_hash      Struct hash.
	 * @return string 32-byte hash as hex string.
	 */
	public static function typed_data_hash( string $domain_separator, string $struct_hash ): string {
		// "\x19\x01" + domainSeparator + hashStruct(message).
		$prefix = '1901';
		return Keccak::hash( hex2bin( $prefix . $domain_separator . $struct_hash ), 256 );
	}

	/**
	 * Compute keccak256 of a string.
	 *
	 * @param string $str The string to hash.
	 * @return string 32-byte hash as hex string.
	 */
	public static function keccak256_string( string $str ): string {
		return Keccak::hash( $str, 256 );
	}

	/**
	 * Encode a uint256 value.
	 *
	 * Uses BCMath for arbitrary precision arithmetic.
	 *
	 * @param string $value The numeric value as string.
	 * @return string 32-byte padded hex string.
	 */
	private static function encode_uint256( string $value ): string {
		$hex = self::bc_to_hex( $value );
		return str_pad( $hex, 64, '0', STR_PAD_LEFT );
	}

	/**
	 * Convert BCMath number to hex string.
	 *
	 * @param string $num BCMath number string.
	 * @return string Hex string (even length).
	 */
	private static function bc_to_hex( string $num ): string {
		if ( '0' === $num || '' === $num ) {
			return '0';
		}

		$hex = '';
		while ( bccomp( $num, '0' ) > 0 ) {
			$rem = bcmod( $num, '16' );
			$num = bcdiv( $num, '16', 0 );
			$hex = dechex( (int) $rem ) . $hex;
		}

		return $hex;
	}

	/**
	 * Compute the PaymentRequest type hash dynamically.
	 *
	 * @return string Type hash as hex string.
	 */
	private static function compute_payment_type_hash(): string {
		$type_string = 'PaymentRequest(string projectId,address token,uint256 amount,string serialNo,uint256 deadline)';
		return Keccak::hash( $type_string, 256 );
	}
}
