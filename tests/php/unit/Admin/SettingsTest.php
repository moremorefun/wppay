<?php
/**
 * Settings class unit tests.
 *
 * @package PayTheFly\Tests\Unit\Admin
 */

namespace PayTheFly\Tests\Unit\Admin;

use WP_UnitTestCase;
use PayTheFly\Admin\Settings;

/**
 * Unit tests for Settings class.
 */
class SettingsTest extends WP_UnitTestCase {

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->settings = new Settings();
	}

	/**
	 * Clean up after tests.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		delete_option( Settings::OPTION_NAME );
		parent::tearDown();
	}

	/**
	 * Test get_defaults returns expected structure.
	 *
	 * @return void
	 */
	public function test_get_defaults_returns_expected_structure(): void {
		$defaults = $this->settings->get_defaults();

		$this->assertIsArray( $defaults );
		$this->assertArrayHasKey( 'private_key_encrypted', $defaults );
		$this->assertArrayHasKey( 'evm_address', $defaults );
		$this->assertArrayHasKey( 'tron_address', $defaults );
		$this->assertArrayHasKey( 'tron', $defaults );
		$this->assertArrayHasKey( 'bsc', $defaults );
		$this->assertArrayHasKey( 'brand', $defaults );
		$this->assertArrayHasKey( 'fab_enabled', $defaults );
		$this->assertArrayHasKey( 'inline_button_auto', $defaults );
		$this->assertArrayHasKey( 'debug_log', $defaults );
	}

	/**
	 * Test get_defaults returns chain config structure.
	 *
	 * @return void
	 */
	public function test_get_defaults_chain_config_structure(): void {
		$defaults = $this->settings->get_defaults();

		$this->assertIsArray( $defaults['tron'] );
		$this->assertArrayHasKey( 'project_id', $defaults['tron'] );
		$this->assertArrayHasKey( 'project_key', $defaults['tron'] );
		$this->assertArrayHasKey( 'contract_address', $defaults['tron'] );

		$this->assertIsArray( $defaults['bsc'] );
		$this->assertArrayHasKey( 'project_id', $defaults['bsc'] );
		$this->assertArrayHasKey( 'project_key', $defaults['bsc'] );
		$this->assertArrayHasKey( 'contract_address', $defaults['bsc'] );
	}

	/**
	 * Test get_defaults returns empty strings for credentials.
	 *
	 * @return void
	 */
	public function test_get_defaults_empty_credentials(): void {
		$defaults = $this->settings->get_defaults();

		$this->assertEquals( '', $defaults['private_key_encrypted'] );
		$this->assertEquals( '', $defaults['evm_address'] );
		$this->assertEquals( '', $defaults['tron_address'] );
		$this->assertEquals( '', $defaults['brand'] );
		$this->assertEquals( '', $defaults['tron']['project_id'] );
		$this->assertEquals( '', $defaults['bsc']['project_id'] );
	}

	/**
	 * Test get_defaults returns correct boolean defaults.
	 *
	 * @return void
	 */
	public function test_get_defaults_boolean_values(): void {
		$defaults = $this->settings->get_defaults();

		$this->assertTrue( $defaults['fab_enabled'] );
		$this->assertFalse( $defaults['inline_button_auto'] );
		$this->assertFalse( $defaults['debug_log'] );
	}

	/**
	 * Test sanitize_settings sanitizes brand.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_sanitizes_brand(): void {
		$input = [
			'brand' => '<b>My Brand</b>',
		];

		$sanitized = $this->settings->sanitize_settings( $input );

		$this->assertArrayHasKey( 'brand', $sanitized );
		$this->assertStringNotContainsString( '<b>', $sanitized['brand'] );
	}

	/**
	 * Test sanitize_settings sanitizes chain config.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_sanitizes_chain_config(): void {
		$input = [
			'tron' => [
				'project_id'       => '<script>alert("xss")</script>test-id',
				'project_key'      => 'secret-key',
				'contract_address' => 'T12345',
			],
		];

		$sanitized = $this->settings->sanitize_settings( $input );

		$this->assertArrayHasKey( 'tron', $sanitized );
		$this->assertStringNotContainsString( '<script>', $sanitized['tron']['project_id'] );
	}

	/**
	 * Test sanitize_settings preserves project_key placeholder.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_preserves_project_key_placeholder(): void {
		// Set up existing settings.
		update_option(
			Settings::OPTION_NAME,
			[
				'tron' => [
					'project_key' => 'original-secret',
				],
			]
		);

		$input = [
			'tron' => [
				'project_id'  => 'new-id',
				'project_key' => '********',
			],
		];

		$sanitized = $this->settings->sanitize_settings( $input );

		$this->assertEquals( 'original-secret', $sanitized['tron']['project_key'] );
	}

	/**
	 * Test sanitize_settings converts fab_enabled to boolean.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_converts_fab_enabled_to_boolean(): void {
		$input = [
			'fab_enabled' => '1',
		];

		$sanitized = $this->settings->sanitize_settings( $input );

		$this->assertArrayHasKey( 'fab_enabled', $sanitized );
		$this->assertIsBool( $sanitized['fab_enabled'] );
		$this->assertTrue( $sanitized['fab_enabled'] );
	}

	/**
	 * Test sanitize_settings converts debug_log to boolean.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_converts_debug_log_to_boolean(): void {
		$input = [
			'debug_log' => 'yes',
		];

		$sanitized = $this->settings->sanitize_settings( $input );

		$this->assertArrayHasKey( 'debug_log', $sanitized );
		$this->assertIsBool( $sanitized['debug_log'] );
		$this->assertTrue( $sanitized['debug_log'] );
	}

	/**
	 * Test get method returns default value when not set.
	 *
	 * @return void
	 */
	public function test_get_returns_default_when_not_set(): void {
		$value = $this->settings->get( 'brand', 'default-value' );

		$this->assertEquals( '', $value ); // Returns from get_defaults.
	}

	/**
	 * Test get method returns saved value when set.
	 *
	 * @return void
	 */
	public function test_get_returns_saved_value(): void {
		update_option(
			Settings::OPTION_NAME,
			[
				'brand' => 'my-saved-brand',
			]
		);

		$value = $this->settings->get( 'brand' );

		$this->assertEquals( 'my-saved-brand', $value );
	}

	/**
	 * Test get method with non-existent key returns null.
	 *
	 * @return void
	 */
	public function test_get_non_existent_key_returns_null(): void {
		$value = $this->settings->get( 'non_existent_key' );

		$this->assertNull( $value );
	}

	/**
	 * Test has_private_key returns false when not configured.
	 *
	 * @return void
	 */
	public function test_has_private_key_returns_false_when_not_configured(): void {
		$this->assertFalse( Settings::has_private_key() );
	}

	/**
	 * Test has_private_key returns true when configured.
	 *
	 * @return void
	 */
	public function test_has_private_key_returns_true_when_configured(): void {
		update_option(
			Settings::OPTION_NAME,
			[
				'private_key_encrypted' => 'some-encrypted-data',
			]
		);

		$this->assertTrue( Settings::has_private_key() );
	}

	/**
	 * Test generate_private_key creates key and addresses.
	 *
	 * @return void
	 */
	public function test_generate_private_key_creates_addresses(): void {
		$result = Settings::generate_private_key();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'evm_address', $result );
		$this->assertArrayHasKey( 'tron_address', $result );
		$this->assertStringStartsWith( '0x', $result['evm_address'] );
		$this->assertStringStartsWith( 'T', $result['tron_address'] );
	}

	/**
	 * Test generate_private_key saves to database.
	 *
	 * @return void
	 */
	public function test_generate_private_key_saves_to_database(): void {
		Settings::generate_private_key();

		$settings = get_option( Settings::OPTION_NAME );

		$this->assertNotEmpty( $settings['private_key_encrypted'] );
		$this->assertNotEmpty( $settings['evm_address'] );
		$this->assertNotEmpty( $settings['tron_address'] );
	}
}
