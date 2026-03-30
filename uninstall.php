<?php
/**
 * Fired when the plugin is deleted from the WordPress admin.
 *
 * Removes all plugin tables, options, and transients.
 * Data cannot be recovered after this point.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// ── Drop plugin tables ────────────────────────────────────────────────────────
$tables = array(
	$wpdb->prefix . 'bvip_visits',
	$wpdb->prefix . 'bvip_sessions',
	$wpdb->prefix . 'bvip_clicks',
);
foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

// ── Delete options ────────────────────────────────────────────────────────────
$options = array(
	'bvip_ipinfo_key',
	'bvip_alert_email',
	'bvip_alert_threshold',
	'bvip_data_retention',
	'bvip_db_version',
);
foreach ( $options as $option ) {
	delete_option( $option );
}

// ── Clear all plugin transients ───────────────────────────────────────────────
$wpdb->query( $wpdb->prepare(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
	$wpdb->esc_like( '_transient_bvip_' ) . '%',
	$wpdb->esc_like( '_transient_timeout_bvip_' ) . '%'
) );
