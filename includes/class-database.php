<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BVIP_Database {

	public static function create_tables() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$visits = "CREATE TABLE {$wpdb->prefix}bvip_visits (
			id               bigint(20)    NOT NULL AUTO_INCREMENT,
			post_id          bigint(20)    NOT NULL DEFAULT 0,
			session_hash     varchar(64)   NOT NULL DEFAULT '',
			company_name     varchar(255)           DEFAULT NULL,
			company_domain   varchar(255)           DEFAULT NULL,
			city             varchar(100)           DEFAULT NULL,
			region           varchar(100)           DEFAULT NULL,
			country_code     varchar(5)             DEFAULT NULL,
			country_name     varchar(100)           DEFAULT NULL,
			latitude         varchar(20)            DEFAULT NULL,
			longitude        varchar(20)            DEFAULT NULL,
			traffic_source   varchar(50)   NOT NULL DEFAULT 'direct',
			referrer_url     varchar(500)           DEFAULT NULL,
			device_type      varchar(20)   NOT NULL DEFAULT 'desktop',
			page_url         varchar(500)           DEFAULT NULL,
			page_title       varchar(300)           DEFAULT NULL,
			utm_source       varchar(100)           DEFAULT NULL,
			utm_medium       varchar(100)           DEFAULT NULL,
			utm_campaign     varchar(100)           DEFAULT NULL,
			time_on_page     int(11)       NOT NULL DEFAULT 0,
			visited_at       datetime      NOT NULL,
			PRIMARY KEY  (id),
			KEY company_name (company_name(191)),
			KEY visited_at   (visited_at),
			KEY post_id      (post_id),
			KEY country_code (country_code),
			KEY session_hash (session_hash),
			KEY utm_source   (utm_source(50))
		) $charset;";

		$sessions = "CREATE TABLE {$wpdb->prefix}bvip_sessions (
			session_hash     varchar(64)   NOT NULL,
			company_name     varchar(255)           DEFAULT NULL,
			city             varchar(100)           DEFAULT NULL,
			country_code     varchar(5)             DEFAULT NULL,
			pages_viewed     int(11)       NOT NULL DEFAULT 1,
			total_duration   int(11)       NOT NULL DEFAULT 0,
			started_at       datetime      NOT NULL,
			last_activity    datetime      NOT NULL,
			PRIMARY KEY  (session_hash),
			KEY company_name (company_name(191)),
			KEY country_code (country_code)
		) $charset;";

		$clicks = "CREATE TABLE {$wpdb->prefix}bvip_clicks (
			id               bigint(20)    NOT NULL AUTO_INCREMENT,
			session_hash     varchar(64)   NOT NULL DEFAULT '',
			post_id          bigint(20)    NOT NULL DEFAULT 0,
			company_name     varchar(255)           DEFAULT NULL,
			element_type     varchar(50)   NOT NULL DEFAULT 'link',
			element_text     varchar(255)           DEFAULT NULL,
			element_id       varchar(100)           DEFAULT NULL,
			element_class    varchar(255)           DEFAULT NULL,
			target_url       varchar(500)           DEFAULT NULL,
			x_position       int(11)       NOT NULL DEFAULT 0,
			y_position       int(11)       NOT NULL DEFAULT 0,
			page_url         varchar(500)           DEFAULT NULL,
			clicked_at       datetime      NOT NULL,
			PRIMARY KEY  (id),
			KEY session_hash (session_hash),
			KEY company_name (company_name(191)),
			KEY clicked_at   (clicked_at),
			KEY element_type (element_type)
		) $charset;";

		dbDelta( $visits );
		dbDelta( $sessions );
		dbDelta( $clicks );

		update_option( 'bvip_db_version', BVIP_VERSION );
	}

	/**
	 * Run on every plugin load — only calls create_tables() when the stored
	 * DB version doesn't match the current plugin version.
	 */
	public static function maybe_upgrade() {
		if ( get_option( 'bvip_db_version' ) !== BVIP_VERSION ) {
			self::create_tables();
		}
	}
}
