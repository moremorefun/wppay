<?php
/**
 * Settings class.
 *
 * @package PayTheFly
 */

namespace PayTheFly\Admin;

use PayTheFly\Crypto\Encryption;
use PayTheFly\Crypto\PrivateKey;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin settings.
 */
class Settings {

	/**
	 * Option name for plugin settings.
	 */
	const OPTION_NAME = 'paythefly_settings';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'paythefly_settings_group',
			self::OPTION_NAME,
			array(
				'type'              => 'object',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_defaults(),
			)
		);
	}

	/**
	 * Get default settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_defaults(): array {
		return array(
			'private_key_encrypted' => '',
			'evm_address'           => '',
			'tron_address'          => '',
			'tron'                  => array(
				'project_id'       => '',
				'project_key'      => '',
				'contract_address' => '',
			),
			'bsc'                   => array(
				'project_id'       => '',
				'project_key'      => '',
				'contract_address' => '',
			),
			'brand'                 => '',
			'fab_enabled'           => true,
			'inline_button_auto'    => false,
			'debug_log'             => false,
		);
	}

	/**
	 * Sanitize chain-specific settings.
	 *
	 * @param mixed $input Chain config input.
	 * @return array<string, string>
	 */
	private function sanitize_chain_config( $input ): array {
		$sanitized = array(
			'project_id'       => '',
			'project_key'      => '',
			'contract_address' => '',
		);

		if ( ! is_array( $input ) ) {
			return $sanitized;
		}

		if ( isset( $input['project_id'] ) ) {
			$sanitized['project_id'] = sanitize_text_field( $input['project_id'] );
		}

		if ( isset( $input['project_key'] ) ) {
			// Don't overwrite if placeholder was sent.
			if ( '********' !== $input['project_key'] ) {
				$sanitized['project_key'] = sanitize_text_field( $input['project_key'] );
			}
		}

		if ( isset( $input['contract_address'] ) ) {
			$sanitized['contract_address'] = sanitize_text_field( $input['contract_address'] );
		}

		return $sanitized;
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param mixed $input Raw input values.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( $input ): array {
		$existing  = get_option( self::OPTION_NAME, $this->get_defaults() );
		$sanitized = array();

		// Handle private key - preserve existing if placeholder sent.
		if ( isset( $input['private_key_encrypted'] ) ) {
			if ( '********' === $input['private_key_encrypted'] ) {
				$sanitized['private_key_encrypted'] = $existing['private_key_encrypted'] ?? '';
			} else {
				$sanitized['private_key_encrypted'] = $input['private_key_encrypted'];
			}
		} else {
			$sanitized['private_key_encrypted'] = $existing['private_key_encrypted'] ?? '';
		}

		// Addresses are read-only, preserve from existing.
		$sanitized['evm_address']  = $existing['evm_address'] ?? '';
		$sanitized['tron_address'] = $existing['tron_address'] ?? '';

		// Chain-specific settings.
		if ( isset( $input['tron'] ) ) {
			$sanitized['tron'] = $this->sanitize_chain_config( $input['tron'] );
			// Preserve project_key if placeholder was sent.
			if ( isset( $input['tron']['project_key'] ) && '********' === $input['tron']['project_key'] ) {
				$sanitized['tron']['project_key'] = $existing['tron']['project_key'] ?? '';
			}
		} else {
			$sanitized['tron'] = $existing['tron'] ?? $this->get_defaults()['tron'];
		}

		if ( isset( $input['bsc'] ) ) {
			$sanitized['bsc'] = $this->sanitize_chain_config( $input['bsc'] );
			// Preserve project_key if placeholder was sent.
			if ( isset( $input['bsc']['project_key'] ) && '********' === $input['bsc']['project_key'] ) {
				$sanitized['bsc']['project_key'] = $existing['bsc']['project_key'] ?? '';
			}
		} else {
			$sanitized['bsc'] = $existing['bsc'] ?? $this->get_defaults()['bsc'];
		}

		// Other settings.
		if ( isset( $input['brand'] ) ) {
			$sanitized['brand'] = sanitize_text_field( $input['brand'] );
		} else {
			$sanitized['brand'] = $existing['brand'] ?? '';
		}

		$sanitized['fab_enabled']        = isset( $input['fab_enabled'] ) ? (bool) $input['fab_enabled'] : ( $existing['fab_enabled'] ?? true );
		$sanitized['inline_button_auto'] = isset( $input['inline_button_auto'] ) ? (bool) $input['inline_button_auto'] : ( $existing['inline_button_auto'] ?? false );
		$sanitized['debug_log']          = isset( $input['debug_log'] ) ? (bool) $input['debug_log'] : ( $existing['debug_log'] ?? false );

		return $sanitized;
	}

	/**
	 * Get a specific setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default_value Default value if setting doesn't exist.
	 * @return mixed
	 */
	public function get( string $key, $default_value = null ) {
		$settings = get_option( self::OPTION_NAME, $this->get_defaults() );

		return $settings[ $key ] ?? $default_value;
	}

	/**
	 * Generate and store a new private key.
	 *
	 * @return array{evm_address: string, tron_address: string}|false The derived addresses, or false on failure.
	 */
	public static function generate_private_key() {
		$private_key = PrivateKey::generate();

		// Encrypt the private key.
		$encrypted = Encryption::encrypt( $private_key );
		if ( false === $encrypted ) {
			return false;
		}

		// Derive addresses.
		$evm_address  = PrivateKey::derive_evm_address( $private_key );
		$tron_address = PrivateKey::derive_tron_address( $private_key );

		// Update settings.
		$settings                          = get_option( self::OPTION_NAME, array() );
		$settings['private_key_encrypted'] = $encrypted;
		$settings['evm_address']           = $evm_address;
		$settings['tron_address']          = $tron_address;

		update_option( self::OPTION_NAME, $settings );

		return array(
			'evm_address'  => $evm_address,
			'tron_address' => $tron_address,
		);
	}

	/**
	 * Check if private key is configured.
	 *
	 * @return bool
	 */
	public static function has_private_key(): bool {
		$settings = get_option( self::OPTION_NAME, array() );
		return ! empty( $settings['private_key_encrypted'] );
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		echo '<div id="paythefly-admin-app"></div>';
	}
}
