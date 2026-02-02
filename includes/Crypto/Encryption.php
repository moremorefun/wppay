<?php
/**
 * Encryption class.
 *
 * Simple pass-through for private key storage.
 * The private key is stored as-is in the database.
 *
 * @package PayTheFly
 */

namespace PayTheFly\Crypto;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles private key storage.
 */
class Encryption {

	/**
	 * Store data (pass-through).
	 *
	 * @param string $plaintext The data to store.
	 * @return string The data as-is.
	 */
	public static function encrypt( string $plaintext ): string {
		return $plaintext;
	}

	/**
	 * Retrieve data (pass-through).
	 *
	 * @param string $data The stored data.
	 * @return string The data as-is.
	 */
	public static function decrypt( string $data ): string {
		return $data;
	}
}
