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
		$this->assertArrayHasKey( 'project_id', $defaults );
		$this->assertArrayHasKey( 'project_key', $defaults );
		$this->assertArrayHasKey( 'brand', $defaults );
		$this->assertArrayHasKey( 'webhook_url', $defaults );
		$this->assertArrayHasKey( 'fab_enabled', $defaults );
		$this->assertArrayHasKey( 'inline_button_auto', $defaults );
	}

	/**
	 * Test get_defaults returns empty strings for ID/key.
	 *
	 * @return void
	 */
	public function test_get_defaults_empty_credentials(): void {
		$defaults = $this->settings->get_defaults();

		$this->assertEquals( '', $defaults['project_id'] );
		$this->assertEquals( '', $defaults['project_key'] );
		$this->assertEquals( '', $defaults['brand'] );
		$this->assertEquals( '', $defaults['webhook_url'] );
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
	}

	/**
	 * Test sanitize_settings sanitizes project_id.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_sanitizes_project_id(): void {
		$input = [
			'project_id' => '<script>alert("xss")</script>test-id',
		];

		$sanitized = $this->settings->sanitize_settings( $input );

		$this->assertArrayHasKey( 'project_id', $sanitized );
		$this->assertStringNotContainsString( '<script>', $sanitized['project_id'] );
	}

	/**
	 * Test sanitize_settings sanitizes project_key.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_sanitizes_project_key(): void {
		$input = [
			'project_key' => "test-key\n\r\t<tag>",
		];

		$sanitized = $this->settings->sanitize_settings( $input );

		$this->assertArrayHasKey( 'project_key', $sanitized );
		$this->assertStringNotContainsString( '<tag>', $sanitized['project_key'] );
		$this->assertStringNotContainsString( "\n", $sanitized['project_key'] );
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
	 * Test sanitize_settings sanitizes webhook_url.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_sanitizes_webhook_url(): void {
		$input = [
			'webhook_url' => 'https://example.com/webhook?param=value',
		];

		$sanitized = $this->settings->sanitize_settings( $input );

		$this->assertArrayHasKey( 'webhook_url', $sanitized );
		$this->assertEquals( 'https://example.com/webhook?param=value', $sanitized['webhook_url'] );
	}

	/**
	 * Test sanitize_settings rejects invalid URLs.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_rejects_invalid_url(): void {
		$input = [
			'webhook_url' => 'javascript:alert("xss")',
		];

		$sanitized = $this->settings->sanitize_settings( $input );

		$this->assertArrayHasKey( 'webhook_url', $sanitized );
		$this->assertEquals( '', $sanitized['webhook_url'] );
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
	 * Test sanitize_settings converts inline_button_auto to boolean.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_converts_inline_button_auto_to_boolean(): void {
		$input = [
			'inline_button_auto' => 'yes',
		];

		$sanitized = $this->settings->sanitize_settings( $input );

		$this->assertArrayHasKey( 'inline_button_auto', $sanitized );
		$this->assertIsBool( $sanitized['inline_button_auto'] );
		$this->assertTrue( $sanitized['inline_button_auto'] );
	}

	/**
	 * Test sanitize_settings with false boolean values.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_with_false_boolean(): void {
		$input = [
			'fab_enabled'        => '',
			'inline_button_auto' => 0,
		];

		$sanitized = $this->settings->sanitize_settings( $input );

		$this->assertFalse( $sanitized['fab_enabled'] );
		$this->assertFalse( $sanitized['inline_button_auto'] );
	}

	/**
	 * Test sanitize_settings only includes known keys.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_only_known_keys(): void {
		$input = [
			'project_id'   => 'valid-id',
			'unknown_key'  => 'should-be-ignored',
			'another_junk' => 'also-ignored',
		];

		$sanitized = $this->settings->sanitize_settings( $input );

		$this->assertArrayHasKey( 'project_id', $sanitized );
		$this->assertArrayNotHasKey( 'unknown_key', $sanitized );
		$this->assertArrayNotHasKey( 'another_junk', $sanitized );
	}

	/**
	 * Test get method returns default value when not set.
	 *
	 * @return void
	 */
	public function test_get_returns_default_when_not_set(): void {
		$value = $this->settings->get( 'project_id', 'default-value' );

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
				'project_id' => 'my-saved-project-id',
			]
		);

		$value = $this->settings->get( 'project_id' );

		$this->assertEquals( 'my-saved-project-id', $value );
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
	 * Test get method with custom default for non-existent key.
	 *
	 * @return void
	 */
	public function test_get_non_existent_key_returns_custom_default(): void {
		$value = $this->settings->get( 'non_existent_key', 'my-default' );

		$this->assertEquals( 'my-default', $value );
	}
}
