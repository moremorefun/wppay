<?php
/**
 * Signer class.
 *
 * Implements EIP-712 payment signing using secp256k1.
 *
 * @package PayTheFly
 */

namespace PayTheFly\Crypto;

use Elliptic\EC;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles payment signature generation.
 */
class Signer {

	/**
	 * Chain configurations.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private const CHAIN_CONFIG = array(
		// TRON Mainnet.
		728126428  => array(
			'type'     => 'tron',
			'decimals' => 6,
		),
		// TRON Shasta Testnet.
		3448148188 => array(
			'type'     => 'tron',
			'decimals' => 6,
		),
		// BSC Mainnet.
		56         => array(
			'type'     => 'evm',
			'decimals' => 18,
		),
		// BSC Testnet.
		97         => array(
			'type'     => 'evm',
			'decimals' => 18,
		),
	);

	/**
	 * Sign a payment request.
	 *
	 * @param array<string, mixed> $params Payment parameters.
	 * @return string The signature as 0x-prefixed hex string.
	 * @throws \InvalidArgumentException If chain is not supported.
	 */
	public static function sign_payment( array $params ): string {
		$chain_id         = (int) $params['chain_id'];
		$project_id       = $params['project_id'];
		$contract_address = $params['contract_address'];
		$token_address    = $params['token_address'];
		$amount           = $params['amount'];
		$serial_no        = $params['serial_no'];
		$deadline         = $params['deadline'];
		$private_key      = $params['private_key'];

		if ( ! isset( self::CHAIN_CONFIG[ $chain_id ] ) ) {
			throw new \InvalidArgumentException( "Unsupported chain ID: $chain_id" );
		}

		$config = self::CHAIN_CONFIG[ $chain_id ];

		// Convert amount to smallest unit.
		$amount_wei = self::parse_units( $amount, $config['decimals'] );

		// For TRON, convert addresses to EVM format.
		if ( 'tron' === $config['type'] ) {
			$contract_address = PrivateKey::tron_to_evm_address( $contract_address );
			$token_address    = PrivateKey::tron_to_evm_address( $token_address );
		}

		// Compute domain separator.
		$domain_separator = EIP712::domain_separator( $chain_id, $contract_address );

		// Compute struct hash.
		$struct_hash = EIP712::payment_struct_hash(
			$project_id,
			$token_address,
			$amount_wei,
			$serial_no,
			$deadline
		);

		// Compute final hash to sign.
		$message_hash = EIP712::typed_data_hash( $domain_separator, $struct_hash );

		// Sign the hash.
		return self::sign_hash( $message_hash, $private_key );
	}

	/**
	 * Sign a hash with a private key.
	 *
	 * @param string $hash        32-byte hash as hex string.
	 * @param string $private_key Private key (with or without 0x prefix).
	 * @return string Signature as 0x-prefixed hex string (r + s + v).
	 */
	private static function sign_hash( string $hash, string $private_key ): string {
		// Normalize private key (remove 0x if present).
		$key_hex = $private_key;
		if ( 0 === strpos( $key_hex, '0x' ) || 0 === strpos( $key_hex, '0X' ) ) {
			$key_hex = substr( $key_hex, 2 );
		}

		$ec        = new EC( 'secp256k1' );
		$keypair   = $ec->keyFromPrivate( $key_hex );
		$signature = $keypair->sign( $hash, array( 'canonical' => true ) );

		$r = str_pad( $signature->r->toString( 16 ), 64, '0', STR_PAD_LEFT );
		$s = str_pad( $signature->s->toString( 16 ), 64, '0', STR_PAD_LEFT );

		// Recovery ID (v): 27 or 28 for legacy, or just 0/1.
		$recovery_param = $signature->recoveryParam;
		$v              = dechex( $recovery_param + 27 );

		return '0x' . $r . $s . $v;
	}

	/**
	 * Parse a decimal string to smallest unit.
	 *
	 * Uses BCMath for arbitrary precision arithmetic.
	 *
	 * @param string $value    The decimal value (e.g., "1.5").
	 * @param int    $decimals Number of decimals.
	 * @return string The value in smallest unit as string.
	 */
	private static function parse_units( string $value, int $decimals ): string {
		// Handle integer values.
		if ( false === strpos( $value, '.' ) ) {
			$multiplier = bcpow( '10', (string) $decimals );
			return bcmul( $value, $multiplier, 0 );
		}

		// Split into integer and decimal parts.
		list( $integer, $fraction ) = explode( '.', $value );

		// Pad or truncate fraction.
		$fraction = substr( str_pad( $fraction, $decimals, '0' ), 0, $decimals );

		// Combine.
		$combined = ltrim( $integer . $fraction, '0' );
		if ( '' === $combined ) {
			return '0';
		}

		return $combined;
	}

	/**
	 * Get the deadline timestamp (current time + duration).
	 *
	 * @param int $duration_seconds Duration in seconds (default 30 minutes).
	 * @return string Unix timestamp as string.
	 */
	public static function get_deadline( int $duration_seconds = 1800 ): string {
		return (string) ( time() + $duration_seconds );
	}

	/**
	 * Check if a chain ID is TRON.
	 *
	 * @param int $chain_id Chain ID.
	 * @return bool True if TRON chain.
	 */
	public static function is_tron_chain( int $chain_id ): bool {
		return isset( self::CHAIN_CONFIG[ $chain_id ] )
			&& 'tron' === self::CHAIN_CONFIG[ $chain_id ]['type'];
	}

	/**
	 * Get token decimals for a chain.
	 *
	 * @param int $chain_id Chain ID.
	 * @return int Number of decimals.
	 */
	public static function get_decimals( int $chain_id ): int {
		return self::CHAIN_CONFIG[ $chain_id ]['decimals'] ?? 18;
	}
}
