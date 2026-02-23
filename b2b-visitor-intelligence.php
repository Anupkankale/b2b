<?php
/**
 * Plugin Name: B2B Visitor Intelligence
 * Plugin URI:  https://yourwebsite.com
 * Description: Identifies visiting companies, tracks their journey, and sends hot lead alerts — all stored privately in your own WordPress database.
 * Version:     1.0.0
 * Author:      Your Name
 * License:     GPL-2.0+
 * Text Domain: b2b-visitor-intelligence
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BVIP_VERSION',    '1.0.0' );
define( 'BVIP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BVIP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once BVIP_PLUGIN_DIR . 'includes/class-database.php';
require_once BVIP_PLUGIN_DIR . 'includes/class-ip-lookup.php';
require_once BVIP_PLUGIN_DIR . 'includes/class-tracker.php';
require_once BVIP_PLUGIN_DIR . 'includes/class-analytics.php';
require_once BVIP_PLUGIN_DIR . 'includes/class-alerts.php';
require_once BVIP_PLUGIN_DIR . 'admin/class-dashboard.php';

register_activation_hook( __FILE__, array( 'BVIP_Database', 'create_tables' ) );
register_activation_hook( __FILE__, array( 'BVIP_Alerts',   'schedule_cron' ) );
register_deactivation_hook( __FILE__, array( 'BVIP_Alerts', 'clear_cron' ) );

function bvip_init() {
	$tracker   = new BVIP_Tracker();
	$alerts    = new BVIP_Alerts();
	$dashboard = new BVIP_Dashboard();

	$tracker->init();
	$alerts->init();
	$dashboard->init();
}
add_action( 'plugins_loaded', 'bvip_init' );
