<?php
/**
 * Plugin Name:       B2B Visitor Intelligence
 * Plugin URI:        https://github.com/Anupkankale/b2b
 * Description:       Identifies visiting companies, tracks their journey, and sends hot lead alerts — all stored privately in your own WordPress database.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Anup Kankale
 * Author URI:        https://github.com/Anupkankale
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       b2b-visitor-intelligence
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Minimum PHP version gate — prevents fatal errors on old hosts.
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	add_action( 'admin_notices', static function () {
		echo '<div class="notice notice-error"><p>'
			. esc_html__( 'B2B Visitor Intelligence requires PHP 7.4 or higher. Please upgrade PHP.', 'b2b-visitor-intelligence' )
			. '</p></div>';
	} );
	return;
}

// ── Constants ─────────────────────────────────────────────────────────────────
define( 'BVIP_VERSION',     '1.0.0' );
define( 'BVIP_PLUGIN_FILE', __FILE__ );
define( 'BVIP_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'BVIP_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

// ── Core includes ─────────────────────────────────────────────────────────────
require_once BVIP_PLUGIN_DIR . 'includes/class-database.php';
require_once BVIP_PLUGIN_DIR . 'includes/class-ip-lookup.php';
require_once BVIP_PLUGIN_DIR . 'includes/class-analytics.php';
require_once BVIP_PLUGIN_DIR . 'includes/class-tracker.php';
require_once BVIP_PLUGIN_DIR . 'includes/class-click-tracker.php';
require_once BVIP_PLUGIN_DIR . 'includes/class-session-tracker.php';
require_once BVIP_PLUGIN_DIR . 'includes/class-alerts.php';
require_once BVIP_PLUGIN_DIR . 'includes/class-activator.php';
require_once BVIP_PLUGIN_DIR . 'includes/class-deactivator.php';

// ── Admin includes ────────────────────────────────────────────────────────────
if ( is_admin() ) {
	require_once BVIP_PLUGIN_DIR . 'admin/class-dashboard.php';
}

// ── Lifecycle hooks ───────────────────────────────────────────────────────────
register_activation_hook( BVIP_PLUGIN_FILE, array( 'BVIP_Activator',   'activate' ) );
register_deactivation_hook( BVIP_PLUGIN_FILE, array( 'BVIP_Deactivator', 'deactivate' ) );

// ── Bootstrap ─────────────────────────────────────────────────────────────────
function bvip_init() {
	( new BVIP_Tracker() )->init();
	( new BVIP_Click_Tracker() )->init();
	( new BVIP_Session_Tracker() )->init();
	( new BVIP_Alerts() )->init();

	if ( is_admin() ) {
		( new BVIP_Dashboard() )->init();
	}
}
add_action( 'plugins_loaded', 'bvip_init' );
