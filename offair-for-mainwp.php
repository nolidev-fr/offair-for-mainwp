<?php
/**
 * Plugin Name:       Offair for MainWP
 * Plugin URI:        https://github.com/nolidev-fr/offair-for-mainwp
 * Description:       Shows in your MainWP Dashboard the outages your visitors ran into on all your sites, recorded by Offair.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  mainwp
 * Author:            Nolidev
 * Author URI:        https://nolidev.fr
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       offair-for-mainwp
 * Domain Path:       /languages
 *
 * @package OffairForMainWP
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'OFFAIR_MAINWP_VERSION' ) ) {
	return;
}

define( 'OFFAIR_MAINWP_VERSION', '0.1.0' );
define( 'OFFAIR_MAINWP_FILE', __FILE__ );
define( 'OFFAIR_MAINWP_DIR', plugin_dir_path( __FILE__ ) );
define( 'OFFAIR_MAINWP_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Loads plugin classes from the includes directory on demand.
 *
 * OffairForMainWP\Sites_Column maps to includes/class-sites-column.php.
 *
 * @param string $class_name Fully qualified class name.
 */
function offair_mainwp_autoload( $class_name ) {
	$prefix = 'OffairForMainWP\\';

	if ( 0 !== strpos( $class_name, $prefix ) ) {
		return;
	}

	$relative = substr( $class_name, strlen( $prefix ) );
	$file     = OFFAIR_MAINWP_DIR . 'includes/class-' . str_replace( '_', '-', strtolower( $relative ) ) . '.php';

	if ( is_readable( $file ) ) {
		require_once $file;
	}
}
spl_autoload_register( 'offair_mainwp_autoload' );

/**
 * Returns the shared plugin instance.
 *
 * @return \OffairForMainWP\Plugin
 */
function offair_mainwp() {
	return \OffairForMainWP\Plugin::instance();
}

add_action( 'plugins_loaded', 'offair_mainwp' );
