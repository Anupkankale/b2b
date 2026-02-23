 <?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BVIP_Analytics {

	// ── Summary ───────────────────────────────────────────────────────────
	public function get_summary_stats( $days = 30 ) {
		global $wpdb;
		$cache_key = 'bvip_summary_' . $days;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) return $cached;

		$d = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$total_visits = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}bvip_visits WHERE visited_at > %s", $d ) );
		$companies    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT company_name) FROM {$wpdb->prefix}bvip_visits WHERE visited_at > %s AND company_name IS NOT NULL AND company_name != ''", $d ) );
		$countries    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT country_code) FROM {$wpdb->prefix}bvip_visits WHERE visited_at > %s AND country_code IS NOT NULL", $d ) );

		$total_sessions  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}bvip_sessions WHERE started_at > %s", $d ) );
		$bounced         = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}bvip_sessions WHERE started_at > %s AND pages_viewed = 1", $d ) );
		$avg_duration    = (float) $wpdb->get_var( $wpdb->prepare( "SELECT AVG(total_duration) FROM {$wpdb->prefix}bvip_sessions WHERE started_at > %s AND total_duration > 0", $d ) );

		$result = array(
			'total_visits'          => $total_visits,
			'companies_identified'  => $companies,
			'countries'             => $countries,
			'avg_pages_per_session' => $total_sessions > 0 ? round( $total_visits / $total_sessions, 1 ) : 0,
			'bounce_rate'           => $total_sessions > 0 ? round( ( $bounced / $total_sessions ) * 100, 1 ) : 0,
			'avg_duration_seconds'  => round( $avg_duration ),
		);

		set_transient( $cache_key, $result, 15 * MINUTE_IN_SECONDS );
		return $result;
	}

	// ── Companies ─────────────────────────────────────────────────────────
	public function get_top_companies( $days = 30, $limit = 25 ) {
		global $wpdb;
		$d = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT v.company_name, v.city, v.region, v.country_code, v.country_name,
			        COUNT(*) AS visit_count,
			        COUNT(DISTINCT v.post_id) AS unique_pages,
			        COUNT(DISTINCT v.session_hash) AS sessions,
			        ROUND(AVG(v.time_on_page)) AS avg_time_on_page,
			        MAX(v.visited_at) AS last_visit
			 FROM {$wpdb->prefix}bvip_visits v
			 WHERE v.visited_at > %s AND v.company_name IS NOT NULL AND v.company_name != ''
			 GROUP BY v.company_name
			 ORDER BY visit_count DESC LIMIT %d",
			$d, $limit
		) );
	}

	public function get_company_journey( $company_name, $days = 30 ) {
		global $wpdb;
		$d = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT v.post_id, v.page_url, v.page_title,
			        COUNT(*) AS page_views,
			        ROUND(AVG(v.time_on_page)) AS avg_time,
			        MIN(v.visited_at) AS first_visit, MAX(v.visited_at) AS last_visit
			 FROM {$wpdb->prefix}bvip_visits v
			 WHERE v.company_name = %s AND v.visited_at > %s
			 GROUP BY v.post_id ORDER BY page_views DESC",
			$company_name, $d
		) );
	}

	// ── Countries ─────────────────────────────────────────────────────────
	public function get_countries( $days = 30, $limit = 20 ) {
		global $wpdb;
		$d = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT country_code, country_name,
			        COUNT(*) AS visits,
			        COUNT(DISTINCT session_hash) AS sessions,
			        COUNT(DISTINCT company_name) AS companies
			 FROM {$wpdb->prefix}bvip_visits
			 WHERE visited_at > %s AND country_code IS NOT NULL
			 GROUP BY country_code ORDER BY visits DESC LIMIT %d",
			$d, $limit
		) );
	}

	// ── Time on Page ─────────────────────────────────────────────────────
	public function get_time_on_page_by_page( $days = 30 ) {
		global $wpdb;
		$d = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT v.post_id, v.page_title,
			        COUNT(*) AS views,
			        ROUND(AVG(v.time_on_page)) AS avg_seconds,
			        MAX(v.time_on_page) AS max_seconds
			 FROM {$wpdb->prefix}bvip_visits v
			 WHERE v.visited_at > %s AND v.time_on_page > 0
			 GROUP BY v.post_id ORDER BY avg_seconds DESC LIMIT 10",
			$d
		) );
	}

	// ── Clicks ───────────────────────────────────────────────────────────
	public function get_top_clicks( $days = 30, $limit = 20 ) {
		global $wpdb;
		$d = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT element_type, element_text, target_url, page_url,
			        COUNT(*) AS click_count,
			        COUNT(DISTINCT session_hash) AS unique_clickers
			 FROM {$wpdb->prefix}bvip_clicks
			 WHERE clicked_at > %s AND element_text != ''
			 GROUP BY element_text, target_url
			 ORDER BY click_count DESC LIMIT %d",
			$d, $limit
		) );
	}

	public function get_clicks_by_type( $days = 30 ) {
		global $wpdb;
		$d = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT element_type, COUNT(*) AS count FROM {$wpdb->prefix}bvip_clicks
			 WHERE clicked_at > %s GROUP BY element_type ORDER BY count DESC",
			$d
		) );
	}

	// ── Traffic ──────────────────────────────────────────────────────────
	public function get_pageviews_over_time( $days = 30 ) {
		global $wpdb;
		$d    = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT DATE(visited_at) AS d, COUNT(*) AS c FROM {$wpdb->prefix}bvip_visits
			 WHERE visited_at > %s GROUP BY DATE(visited_at) ORDER BY d ASC", $d
		) );
		$result = array();
		foreach ( $rows as $r ) $result[ $r->d ] = (int) $r->c;
		return $result;
	}

	public function get_traffic_sources( $days = 30 ) {
		global $wpdb;
		$d = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT traffic_source, COUNT(*) AS count FROM {$wpdb->prefix}bvip_visits
			 WHERE visited_at > %s GROUP BY traffic_source ORDER BY count DESC", $d
		) );
	}

	public function get_device_breakdown( $days = 30 ) {
		global $wpdb;
		$d = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT device_type, COUNT(*) AS count FROM {$wpdb->prefix}bvip_visits
			 WHERE visited_at > %s GROUP BY device_type ORDER BY count DESC", $d
		) );
	}

	 public function get_top_pages( $days = 30, $limit = 10 ) {
    global $wpdb;
    $d = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
    return $wpdb->get_results( $wpdb->prepare(
        "SELECT 
            v.post_id,
            v.page_url,
            COALESCE( NULLIF(p.post_title, ''), NULLIF(v.page_title, ''), v.page_url, CONCAT('Page ID: ', v.post_id) ) AS page_title,
            COUNT(*) AS views,
            ROUND(AVG(v.time_on_page)) AS avg_time
         FROM {$wpdb->prefix}bvip_visits v
         LEFT JOIN {$wpdb->posts} p ON v.post_id = p.ID AND p.post_status = 'publish'
         WHERE v.visited_at > %s
         GROUP BY v.post_id
         ORDER BY views DESC
         LIMIT %d",
        $d, $limit
    ) );
}

	// ── Realtime ─────────────────────────────────────────────────────────
	public function get_realtime_visitors() {
		global $wpdb;
		$ago = gmdate( 'Y-m-d H:i:s', strtotime( '-5 minutes' ) );
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT session_hash) FROM {$wpdb->prefix}bvip_visits WHERE visited_at > %s", $ago
		) );
	}

	public function flush_cache() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bvip_%' OR option_name LIKE '_transient_timeout_bvip_%'" );
	}
}
