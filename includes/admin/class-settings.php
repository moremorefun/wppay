<?php
/**
 * Settings class.
 *
 * @package PayTheFly
 */

namespace PayTheFly\Admin;

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
			'project_id'         => '',
			'project_key'        => '',
			'brand'              => '',
			'webhook_url'        => '',
			'fab_enabled'        => true,
			'inline_button_auto' => false,
		);
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param mixed $input Raw input values.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( $input ): array {
		$sanitized = array();

		if ( isset( $input['project_id'] ) ) {
			$sanitized['project_id'] = sanitize_text_field( $input['project_id'] );
		}

		if ( isset( $input['project_key'] ) ) {
			$sanitized['project_key'] = sanitize_text_field( $input['project_key'] );
		}

		if ( isset( $input['brand'] ) ) {
			$sanitized['brand'] = sanitize_text_field( $input['brand'] );
		}

		if ( isset( $input['webhook_url'] ) ) {
			$sanitized['webhook_url'] = esc_url_raw( $input['webhook_url'] );
		}

		if ( isset( $input['fab_enabled'] ) ) {
			$sanitized['fab_enabled'] = (bool) $input['fab_enabled'];
		}

		if ( isset( $input['inline_button_auto'] ) ) {
			$sanitized['inline_button_auto'] = (bool) $input['inline_button_auto'];
		}

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
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		echo '<div id="paythefly-admin-app"></div>';
	}
}
