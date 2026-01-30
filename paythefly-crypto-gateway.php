<?php
/**
 * Plugin Name:       PayTheFly Crypto Gateway
 * Plugin URI:        https://paythefly.com/wordpress-plugin
 * Description:       Integrate PayTheFly cryptocurrency payment services into your WordPress site.
 * Version:           1.0.6
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            PayTheFly Team
 * Author URI:        https://paythefly.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       paythefly-crypto-gateway
 * Domain Path:       /languages
 *
 * @package PayTheFly
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 */
define( 'PAYTHEFLY_VERSION', '1.0.6' );

/**
 * Plugin file path.
 */
define( 'PAYTHEFLY_FILE', __FILE__ );

/**
 * Plugin directory path.
 */
define( 'PAYTHEFLY_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'PAYTHEFLY_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'PAYTHEFLY_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Composer autoloader.
 */
if ( file_exists( PAYTHEFLY_DIR . 'vendor/autoload.php' ) ) {
	require_once PAYTHEFLY_DIR . 'vendor/autoload.php';
}

/**
 * Vite for WP integration.
 */
require_once PAYTHEFLY_DIR . 'includes/vite-for-wp.php';

/**
 * Main plugin class.
 */
require_once PAYTHEFLY_DIR . 'includes/class-paythefly.php';

/**
 * Activation hook.
 *
 * @return void
 */
function paythefly_activate(): void {
	// Activation tasks.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'paythefly_activate' );

/**
 * Deactivation hook.
 *
 * @return void
 */
function paythefly_deactivate(): void {
	// Deactivation tasks.
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'paythefly_deactivate' );

/**
 * Initialize the plugin.
 *
 * @return void
 */
function paythefly_init(): void {
	$plugin = new PayTheFly\PayTheFly();
	$plugin->run();
}
add_action( 'plugins_loaded', 'paythefly_init' );
