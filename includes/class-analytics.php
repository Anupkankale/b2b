<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BVIP_Analytics {

	public function get_top_companies( $days = 30, $limit = 20 ) {
		global $wpdb;
		$cache_key = 'bvip_top_companies_' . $days . '_' . $limit;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) return $cached;

		$date_from = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		$results   = $wpdb->get_results( $wpdb->prepare(
			"SELECT company_name, city, region, country_code,
			        COUNT(*) AS visit_count,
			        COUNT(DISTINCT post_id) AS unique_pages,
			        COUNT(DISTINCT session_hash) AS sessions,
			        MAX(visited_at) AS last_visit
			 FROM {$wpdb->prefix}bvip_visits
			 WHERE visited_at > %s
			   AND company_name IS NOT NULL
			   AND company_name != ''
			 GROUP BY company_name
			 ORDER BY visit_count DESC
			 LIMIT %d",
			$date_from,
			$limit
		) );

		set_transient( $cache_key, $results, 15 * MINUTE_IN_SECONDS );
		return $results;
	}

	public function get_company_journey( $company_name, $days = 30 ) {
		global $wpdb;
		$date_from = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT v.post_id, v.page_url,
			        p.post_title,
			        COUNT(*) AS page_views,
			        MIN(v.visited_at) AS first_visit,
			        MAX(v.visited_at) AS last_visit
			 FROM {$wpdb->prefix}bvip_visits v
			 LEFT JOIN {$wpdb->posts} p ON v.post_id = p.ID
			 WHERE v.company_name = %s
			   AND v.visited_at > %s
			 GROUP BY v.post_id
			 ORDER BY page_views DESC",
			$company_name,
			$date_from
		) );
	}

	public function get_summary_stats( $days = 30 ) {
		global $wpdb;
		$cache_key = 'bvip_summary_' . $days;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) return $cached;

		$date_from = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$total_visits = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}bvip_visits WHERE visited_at > %s", $date_from
		) );

		$companies_identified = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT company_name) FROM {$wpdb->prefix}bvip_visits
			 WHERE visited_at > %s AND company_name IS NOT NULL AND company_name != ''", $date_from
		) );

		$total_sessions = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}bvip_sessions WHERE started_at > %s", $date_from
		) );
		$bounced = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}bvip_sessions
			 WHERE started_at > %s AND pages_viewed = 1", $date_from
		) );

		$avg_pages   = $total_sessions > 0 ? round( $total_visits / $total_sessions, 1 ) : 0;
		$bounce_rate = $total_sessions > 0 ? round( ( $bounced / $total_sessions ) * 100, 1 ) : 0;

		$result = array(
			'total_visits'         => (int) $total_visits,
			'companies_identified' => (int) $companies_identified,
			'avg_pages_per_session'=> $avg_pages,
			'bounce_rate'          => $bounce_rate,
		);

		set_transient( $cache_key, $result, 15 * MINUTE_IN_SECONDS );
		return $result;
	}

	public function get_realtime_visitors() {
		global $wpdb;
		$five_min_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-5 minutes' ) );
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT session_hash) FROM {$wpdb->prefix}bvip_visits
			 WHERE visited_at > %s",
			$five_min_ago
		) );
	}

	public function get_traffic_sources( $days = 30 ) {
		global $wpdb;
		$date_from = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT traffic_source, COUNT(*) AS count
			 FROM {$wpdb->prefix}bvip_visits
			 WHERE visited_at > %s
			 GROUP BY traffic_source
			 ORDER BY count DESC",
			$date_from
		) );
	}

	public function get_pageviews_over_time( $days = 30 ) {
		global $wpdb;
		$date_from = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT DATE(visited_at) AS visit_date, COUNT(*) AS count
			 FROM {$wpdb->prefix}bvip_visits
			 WHERE visited_at > %s
			 GROUP BY DATE(visited_at)
			 ORDER BY visit_date ASC",
			$date_from
		) );

		$result = array();
		foreach ( $rows as $row ) {
			$result[ $row->visit_date ] = (int) $row->count;
		}
		return $result;
	}

	public function get_device_breakdown( $days = 30 ) {
		global $wpdb;
		$date_from = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT device_type, COUNT(*) AS count
			 FROM {$wpdb->prefix}bvip_visits
			 WHERE visited_at > %s
			 GROUP BY device_type
			 ORDER BY count DESC",
			$date_from
		) );
	}

	public function get_top_pages( $days = 30, $limit = 10 ) {
		global $wpdb;
		$date_from = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT v.post_id, p.post_title, COUNT(*) AS views
			 FROM {$wpdb->prefix}bvip_visits v
			 LEFT JOIN {$wpdb->posts} p ON v.post_id = p.ID
			 WHERE v.visited_at > %s
			 GROUP BY v.post_id
			 ORDER BY views DESC
			 LIMIT %d",
			$date_from,
			$limit
		) );
	}

	public function flush_cache() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bvip_%' OR option_name LIKE '_transient_timeout_bvip_%'" );
	}
}
