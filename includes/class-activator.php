<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Fired during plugin activation.
 *
 * Creates database tables and schedules cron events.
 */
class BVIP_Activator {

	public static function activate() {
		BVIP_Database::create_tables();
		BVIP_Alerts::schedule_cron();
	}
}
