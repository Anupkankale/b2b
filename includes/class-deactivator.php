<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Fired during plugin deactivation.
 *
 * Clears scheduled cron events. Data is kept; use uninstall.php to remove it.
 */
class BVIP_Deactivator {

	public static function deactivate() {
		BVIP_Alerts::clear_cron();
	}
}
