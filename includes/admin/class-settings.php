<?php
/**
 * Settings class.
 *
 * @package PayTheFly
 */

namespace PayTheFly\Admin;

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
		add_action( 'admin_init', [ $this, 'register_settings' ] );
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
			[
				'type'              => 'object',
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
				'default'           => $this->get_defaults(),
			]
		);
	}

	/**
	 * Get default settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_defaults(): array {
		return [
			'api_key'      => '',
			'api_secret'   => '',
			'sandbox_mode' => true,
			'webhook_url'  => '',
		];
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param mixed $input Raw input values.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( $input ): array {
		$sanitized = [];

		if ( isset( $input['api_key'] ) ) {
			$sanitized['api_key'] = sanitize_text_field( $input['api_key'] );
		}

		if ( isset( $input['api_secret'] ) ) {
			$sanitized['api_secret'] = sanitize_text_field( $input['api_secret'] );
		}

		if ( isset( $input['sandbox_mode'] ) ) {
			$sanitized['sandbox_mode'] = (bool) $input['sandbox_mode'];
		}

		if ( isset( $input['webhook_url'] ) ) {
			$sanitized['webhook_url'] = esc_url_raw( $input['webhook_url'] );
		}

		return $sanitized;
	}

	/**
	 * Get a specific setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value if setting doesn't exist.
	 * @return mixed
	 */
	public function get( string $key, $default = null ) {
		$settings = get_option( self::OPTION_NAME, $this->get_defaults() );

		return $settings[ $key ] ?? $default;
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		echo '<div id="paythefly-settings-app"></div>';
	}
}
