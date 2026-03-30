<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BVIP_Tracker {

	public function init() {
		add_action( 'rest_api_init',      array( $this, 'register_rest_route' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_tracker' ) );
		add_action( 'bvip_lookup_ip',     array( $this, 'handle_async_lookup' ), 10, 3 );
	}

	public function register_rest_route() {
		register_rest_route( 'b2b-analytics/v1', '/track', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_track_request' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function enqueue_tracker() {
		if ( is_admin() ) return;
		wp_enqueue_script(
			'bvip-tracker',
			BVIP_PLUGIN_URL . 'assets/js/tracker.js',
			array(),
			BVIP_VERSION,
			true
		);
		wp_localize_script( 'bvip-tracker', 'bvipData', array(
			'restUrl' => esc_url_raw( rest_url() ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'postId'  => get_queried_object_id(),
			'pageUrl' => esc_url( home_url( sanitize_text_field( wp_unslash(
				isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/'
			) ) ) ),
		) );
	}

	public function handle_track_request( $request ) {
		if ( ! $this->should_track() ) {
			return new WP_REST_Response( array( 'status' => 'skipped' ), 200 );
		}

		global $wpdb;

		$post_id      = absint( $request->get_param( 'post_id' ) );
		$referrer     = sanitize_url( $request->get_param( 'referrer' ) );
		$screen_width = absint( $request->get_param( 'screen_width' ) );
		$page_url     = sanitize_url( $request->get_param( 'page_url' ) );
		$page_title   = sanitize_text_field( mb_substr( (string) $request->get_param( 'page_title' ), 0, 300 ) );
		$utm_source   = sanitize_text_field( mb_substr( (string) $request->get_param( 'utm_source' ),   0, 100 ) );
		$utm_medium   = sanitize_text_field( mb_substr( (string) $request->get_param( 'utm_medium' ),   0, 100 ) );
		$utm_campaign = sanitize_text_field( mb_substr( (string) $request->get_param( 'utm_campaign' ), 0, 100 ) );

		$ip = $this->get_visitor_ip();

		if ( $this->is_excluded_ip( $ip ) ) {
			return new WP_REST_Response( array( 'status' => 'skipped' ), 200 );
		}

		$ua           = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$session_hash = hash( 'sha256', $ip . $ua . gmdate( 'Y-m-d' ) . wp_salt() );
		$device       = $this->get_device_type( $screen_width );
		$traffic_src  = $this->detect_traffic_source( $referrer );

		// Use cached IP data immediately; new IPs are enriched asynchronously.
		$lookup       = new BVIP_IP_Lookup();
		$is_cached    = $lookup->is_cached( $ip );
		$company_data = $is_cached ? $lookup->get_company_data( $ip ) : array();

		$company_name   = isset( $company_data['company'] )      ? $company_data['company']      : null;
		$company_domain = isset( $company_data['domain'] )       ? $company_data['domain']       : null;
		$city           = isset( $company_data['city'] )         ? $company_data['city']         : null;
		$region         = isset( $company_data['region'] )       ? $company_data['region']       : null;
		$country_code   = isset( $company_data['country'] )      ? $company_data['country']      : null;
		$country_name   = isset( $company_data['country_name'] ) ? $company_data['country_name'] : null;

		$wpdb->insert(
			$wpdb->prefix . 'bvip_visits',
			array(
				'post_id'        => $post_id,
				'session_hash'   => $session_hash,
				'company_name'   => $company_name,
				'company_domain' => $company_domain,
				'city'           => $city,
				'region'         => $region,
				'country_code'   => $country_code,
				'country_name'   => $country_name,
				'traffic_source' => $traffic_src,
				'device_type'    => $device,
				'page_url'       => $page_url,
				'page_title'     => $page_title ?: null,
				'utm_source'     => $utm_source   ?: null,
				'utm_medium'     => $utm_medium   ?: null,
				'utm_campaign'   => $utm_campaign ?: null,
				'visited_at'     => current_time( 'mysql', true ),
			),
			array( '%d','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s' )
		);
		$visit_id = (int) $wpdb->insert_id;

		// Schedule async enrichment for IPs not yet in cache.
		if ( ! $is_cached ) {
			wp_schedule_single_event( time(), 'bvip_lookup_ip', array( $ip, $visit_id, $session_hash ) );
		}

		// Upsert session.
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}bvip_sessions WHERE session_hash = %s",
			$session_hash
		) );

		if ( $existing ) {
			$wpdb->update(
				$wpdb->prefix . 'bvip_sessions',
				array(
					'pages_viewed'  => $existing->pages_viewed + 1,
					'last_activity' => current_time( 'mysql', true ),
				),
				array( 'session_hash' => $session_hash ),
				array( '%d', '%s' ),
				array( '%s' )
			);
		} else {
			$now = current_time( 'mysql', true );
			$wpdb->insert(
				$wpdb->prefix . 'bvip_sessions',
				array(
					'session_hash'  => $session_hash,
					'company_name'  => $company_name,
					'pages_viewed'  => 1,
					'started_at'    => $now,
					'last_activity' => $now,
				),
				array( '%s','%s','%d','%s','%s' )
			);
		}

		return new WP_REST_Response( array(
			'status'       => 'ok',
			'visit_id'     => $visit_id,
			'session_hash' => $session_hash,
		), 200 );
	}

	/**
	 * Fired by the bvip_lookup_ip WP cron event.
	 * Updates the visit and session rows with company data.
	 */
	public function handle_async_lookup( $ip, $visit_id, $session_hash ) {
		global $wpdb;

		$lookup       = new BVIP_IP_Lookup();
		$company_data = $lookup->get_company_data( $ip );

		if ( empty( $company_data ) ) {
			return;
		}

		$wpdb->update(
			$wpdb->prefix . 'bvip_visits',
			array(
				'company_name'   => isset( $company_data['company'] )      ? $company_data['company']      : null,
				'company_domain' => isset( $company_data['domain'] )       ? $company_data['domain']       : null,
				'city'           => isset( $company_data['city'] )         ? $company_data['city']         : null,
				'region'         => isset( $company_data['region'] )       ? $company_data['region']       : null,
				'country_code'   => isset( $company_data['country'] )      ? $company_data['country']      : null,
				'country_name'   => isset( $company_data['country_name'] ) ? $company_data['country_name'] : null,
			),
			array( 'id' => $visit_id ),
			array( '%s','%s','%s','%s','%s','%s' ),
			array( '%d' )
		);

		// Update session company name only if it wasn't already set.
		if ( ! empty( $company_data['company'] ) ) {
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$wpdb->prefix}bvip_sessions SET company_name = %s WHERE session_hash = %s AND company_name IS NULL",
				$company_data['company'],
				$session_hash
			) );
		}
	}

	private function is_excluded_ip( $ip ) {
		$excluded = get_option( 'bvip_excluded_ips', '' );
		if ( empty( $excluded ) ) return false;
		$list = array_filter( array_map( 'trim', explode( "\n", $excluded ) ) );
		return in_array( $ip, $list, true );
	}

	private function get_visitor_ip() {
		$headers = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);
		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				$ip = trim( explode( ',', $ip )[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}
		return '';
	}

	private function get_device_type( $screen_width ) {
		if ( $screen_width < 768 )  return 'mobile';
		if ( $screen_width < 1024 ) return 'tablet';
		return 'desktop';
	}

	private function detect_traffic_source( $referrer ) {
		if ( empty( $referrer ) ) return 'direct';

		// Same-site navigation.
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( $host && strpos( $referrer, $host ) !== false ) {
			return 'internal';
		}

		$search = array( 'google', 'bing', 'yahoo', 'duckduckgo', 'yandex', 'baidu' );
		$social = array( 'facebook', 'twitter', 'linkedin', 'instagram', 'youtube', 'whatsapp' );

		foreach ( $search as $engine ) {
			if ( strpos( $referrer, $engine ) !== false ) return 'search';
		}
		foreach ( $social as $platform ) {
			if ( strpos( $referrer, $platform ) !== false ) return 'social';
		}
		return 'referral';
	}

	private function should_track() {
		if ( is_admin() )                           return false;
		if ( current_user_can( 'manage_options' ) ) return false;
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) return false;

		$ua   = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
		$bots = array(
			'googlebot','bingbot','slurp','duckduckbot','baiduspider',
			'yandexbot','facebot','ia_archiver','ahrefsbot','semrushbot',
			'mj12bot','dotbot','rogerbot','exabot','gigabot',
		);
		foreach ( $bots as $bot ) {
			if ( strpos( $ua, $bot ) !== false ) return false;
		}
		return true;
	}
}
