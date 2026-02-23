<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BVIP_Alerts {

	public function init() {
		add_action( 'bvip_check_alerts', array( $this, 'check_hot_leads' ) );
	}

	public static function schedule_cron() {
		if ( ! wp_next_scheduled( 'bvip_check_alerts' ) ) {
			wp_schedule_event( time(), 'hourly', 'bvip_check_alerts' );
		}
	}

	public static function clear_cron() {
		wp_clear_scheduled_hook( 'bvip_check_alerts' );
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

		foreach ( $hot_leads as $lead ) {
			$transient_key = 'bvip_alerted_' . md5( $lead->company_name );
			if ( get_transient( $transient_key ) ) continue;

			$pages = $wpdb->get_results( $wpdb->prepare(
				"SELECT v.page_url, p.post_title, COUNT(*) as cnt
				 FROM {$wpdb->prefix}bvip_visits v
				 LEFT JOIN {$wpdb->posts} p ON v.post_id = p.ID
				 WHERE v.company_name = %s AND v.visited_at > %s
				 GROUP BY v.post_id
				 ORDER BY cnt DESC LIMIT 5",
				$lead->company_name,
				$date_from
			) );

			$this->send_alert_email( $alert_email, $lead, $pages );
			set_transient( $transient_key, true, DAY_IN_SECONDS );
		}
	}

	private function send_alert_email( $email, $lead, $pages ) {
		$subject = sprintf( '🔥 Hot Lead Alert — %s visited %d times this week', $lead->company_name, $lead->visit_count );

		$pages_list = '';
		foreach ( $pages as $page ) {
			$title       = ! empty( $page->post_title ) ? $page->post_title : $page->page_url;
			$pages_list .= "  • {$title} ({$page->cnt} views)\n";
		}

		$dashboard_url = admin_url( 'admin.php?page=bvip-dashboard' );

		$message  = "🔥 HOT LEAD ALERT\n";
		$message .= str_repeat( '-', 40 ) . "\n\n";
		$message .= "Company:     {$lead->company_name}\n";
		$message .= "Location:    {$lead->city}, {$lead->region}\n";
		$message .= "Visits (7d): {$lead->visit_count}\n";
		$message .= "Last Seen:   {$lead->last_visit}\n\n";
		$message .= "Pages Viewed:\n{$pages_list}\n";
		$message .= "View full journey: {$dashboard_url}\n\n";
		$message .= "— B2B Visitor Intelligence Plugin";

		wp_mail( $email, $subject, $message );
	}
}
