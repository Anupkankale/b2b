<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BVIP_Alerts {

	public function init() {
		add_action( 'bvip_check_alerts', array( $this, 'check_hot_leads' ) );
		add_action( 'bvip_prune_data',   array( $this, 'prune_old_data' ) );
	}

	public static function schedule_cron() {
		if ( ! wp_next_scheduled( 'bvip_check_alerts' ) ) {
			wp_schedule_event( time(), 'hourly', 'bvip_check_alerts' );
		}
		if ( ! wp_next_scheduled( 'bvip_prune_data' ) ) {
			wp_schedule_event( time(), 'daily', 'bvip_prune_data' );
		}
	}

	public static function clear_cron() {
		wp_clear_scheduled_hook( 'bvip_check_alerts' );
		wp_clear_scheduled_hook( 'bvip_prune_data' );
	}

	public function check_hot_leads() {
		global $wpdb;

		$threshold   = (int) get_option( 'bvip_alert_threshold', 3 );
		$alert_email = sanitize_email( get_option( 'bvip_alert_email', get_option( 'admin_email' ) ) );
		$date_from   = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );

		$hot_leads = $wpdb->get_results( $wpdb->prepare(
			"SELECT company_name, city, region,
			        COUNT(*) AS visit_count,
			        MAX(visited_at) AS last_visit
			 FROM {$wpdb->prefix}bvip_visits
			 WHERE visited_at > %s
			   AND company_name IS NOT NULL
			   AND company_name != ''
			 GROUP BY company_name
			 HAVING visit_count >= %d
			 ORDER BY visit_count DESC",
			$date_from,
			$threshold
		) );

		if ( empty( $hot_leads ) ) return;

		// Filter out companies already alerted today.
		$pending = array();
		foreach ( $hot_leads as $lead ) {
			if ( ! get_transient( 'bvip_alerted_' . md5( $lead->company_name ) ) ) {
				$pending[] = $lead;
			}
		}
		if ( empty( $pending ) ) return;

		// Fetch pages for ALL pending leads in ONE query (fixes N+1).
		$names        = array_map( static function ( $l ) { return $l->company_name; }, $pending );
		$placeholders = implode( ',', array_fill( 0, count( $names ), '%s' ) );
		$args         = array_merge( $names, array( $date_from ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$all_pages = $wpdb->get_results( $wpdb->prepare(
			"SELECT v.company_name, v.page_url, p.post_title, COUNT(*) AS cnt
			 FROM {$wpdb->prefix}bvip_visits v
			 LEFT JOIN {$wpdb->posts} p ON v.post_id = p.ID
			 WHERE v.company_name IN ({$placeholders}) AND v.visited_at > %s
			 GROUP BY v.company_name, v.post_id
			 ORDER BY v.company_name, cnt DESC",
			...$args
		) );

		// Index pages by company name (limit 5 per company).
		$pages_by_company = array();
		foreach ( $all_pages as $page ) {
			if ( ! isset( $pages_by_company[ $page->company_name ] ) ) {
				$pages_by_company[ $page->company_name ] = array();
			}
			if ( count( $pages_by_company[ $page->company_name ] ) < 5 ) {
				$pages_by_company[ $page->company_name ][] = $page;
			}
		}

		foreach ( $pending as $lead ) {
			set_transient( 'bvip_alerted_' . md5( $lead->company_name ), true, DAY_IN_SECONDS );
			$this->send_alert_email(
				$alert_email,
				$lead,
				isset( $pages_by_company[ $lead->company_name ] ) ? $pages_by_company[ $lead->company_name ] : array()
			);
		}
	}

	/**
	 * Deletes visit, session, and click rows older than the configured retention period.
	 * Fires on the bvip_prune_data daily cron.
	 */
	public function prune_old_data() {
		$retention = (int) get_option( 'bvip_data_retention', 365 );
		if ( $retention <= 0 ) return; // 0 = keep forever.

		global $wpdb;
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$retention} days" ) );

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}bvip_visits   WHERE visited_at    < %s", $cutoff ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}bvip_clicks   WHERE clicked_at    < %s", $cutoff ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}bvip_sessions WHERE last_activity < %s", $cutoff ) );
	}

	private function send_alert_email( $email, $lead, $pages ) {
		$subject = sprintf( 'Hot Lead Alert: %s visited %d times this week', $lead->company_name, $lead->visit_count );

		$pages_list = '';
		foreach ( $pages as $page ) {
			$title       = ! empty( $page->post_title ) ? $page->post_title : $page->page_url;
			$pages_list .= "  - {$title} ({$page->cnt} views)\n";
		}

		$dashboard_url = admin_url( 'admin.php?page=bvip-dashboard' );

		$message  = "HOT LEAD ALERT\n";
		$message .= str_repeat( '-', 40 ) . "\n\n";
		$message .= "Company:     {$lead->company_name}\n";
		$message .= "Location:    {$lead->city}, {$lead->region}\n";
		$message .= "Visits (7d): {$lead->visit_count}\n";
		$message .= "Last Seen:   {$lead->last_visit}\n\n";
		$message .= "Pages Viewed:\n{$pages_list}\n";
		$message .= "View full journey: {$dashboard_url}\n\n";
		$message .= "-- B2B Visitor Intelligence";

		wp_mail( $email, $subject, $message );
	}
}
