<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BVIP_Database {

	public static function create_tables() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$visits = "CREATE TABLE {$wpdb->prefix}bvip_visits (
			id            bigint(20)   NOT NULL AUTO_INCREMENT,
			post_id       bigint(20)   NOT NULL DEFAULT 0,
			session_hash  varchar(64)  NOT NULL DEFAULT '',
			company_name  varchar(255)          DEFAULT NULL,
			company_domain varchar(255)         DEFAULT NULL,
			city          varchar(100)          DEFAULT NULL,
			region        varchar(100)          DEFAULT NULL,
			country_code  varchar(5)            DEFAULT NULL,
			traffic_source varchar(50)  NOT NULL DEFAULT 'direct',
			device_type   varchar(20)  NOT NULL DEFAULT 'desktop',
			page_url      varchar(500)          DEFAULT NULL,
			visited_at    datetime     NOT NULL,
			PRIMARY KEY  (id),
			KEY company_name (company_name(191)),
			KEY visited_at   (visited_at),
			KEY post_id      (post_id)
		) $charset;";

		$sessions = "CREATE TABLE {$wpdb->prefix}bvip_sessions (
			session_hash  varchar(64)  NOT NULL,
			company_name  varchar(255)          DEFAULT NULL,
			pages_viewed  int(11)      NOT NULL DEFAULT 1,
			started_at    datetime     NOT NULL,
			last_activity datetime     NOT NULL,
			PRIMARY KEY  (session_hash),
			KEY company_name (company_name(191))
		) $charset;";

		dbDelta( $visits );
		dbDelta( $sessions );

		update_option( 'bvip_db_version', BVIP_VERSION );
	}
}
