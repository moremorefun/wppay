<?php
/**
 * Test case for PayTheFly plugin.
 *
 * @package PayTheFly
 */

namespace PayTheFly\Tests;

use WP_UnitTestCase;
use PayTheFly\PayTheFly;

/**
 * PayTheFly test case.
 */
class PayTheFlyTest extends WP_UnitTestCase {

	/**
	 * Test plugin constants are defined.
	 *
	 * @return void
	 */
	public function test_plugin_constants_defined(): void {
		$this->assertTrue( defined( 'PAYTHEFLY_VERSION' ) );
		$this->assertTrue( defined( 'PAYTHEFLY_FILE' ) );
		$this->assertTrue( defined( 'PAYTHEFLY_DIR' ) );
		$this->assertTrue( defined( 'PAYTHEFLY_URL' ) );
	}

	/**
	 * Test plugin version.
	 *
	 * @return void
	 */
	public function test_plugin_version(): void {
		$this->assertEquals( '1.0.4', PAYTHEFLY_VERSION );
	}

	/**
	 * Test main plugin class exists.
	 *
	 * @return void
	 */
	public function test_main_class_exists(): void {
		$this->assertTrue( class_exists( PayTheFly::class ) );
	}
}
