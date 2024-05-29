<?php
/**
 * Plugin Name: JetBooking
 * Plugin URI:  https://crocoblock.com/plugins/jetbooking/
 * Description: Allows creating a booking functionality for your residence with an availability check, which means your site visitor can select a certain period (check-in and check-out dates) he wants to rent this housing for.
 * Version:     3.3.2
 * Author:      Crocoblock
 * Author URI:  https://crocoblock.com/
 * Text Domain: jet-booking
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

define( 'JET_ABAF_VERSION', '3.3.2' );
define( 'JET_ABAF__FILE__', __FILE__ );
define( 'JET_ABAF_PLUGIN_BASE', plugin_basename( JET_ABAF__FILE__ ) );
define( 'JET_ABAF_PATH', plugin_dir_path( JET_ABAF__FILE__ ) );
define( 'JET_ABAF_URL', plugins_url( '/', JET_ABAF__FILE__ ) );

add_action( 'plugins_loaded', 'jet_abaf_init' );
add_action( 'plugins_loaded', 'jet_abaf_lang' );

/**
 * Plugin initialization.
 *
 * @since 1.0.0
 */
function jet_abaf_init() {
	require JET_ABAF_PATH . 'includes/plugin.php';
}

/**
 * Load plugin text domain.
 *
 * Load gettext translate for JetBooking text domain.
 *
 * @since 1.0.0
 */
function jet_abaf_lang() {
	load_plugin_textdomain( 'jet-booking', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Returns the main instance of JetBooking.
 *
 * @since 1.0.0
 * @return \JET_ABAF\Plugin|null
 */
function jet_abaf() {
	return JET_ABAF\Plugin::instance();
}
