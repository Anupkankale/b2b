<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BVIP_Tracker {

	public function init() {
		add_action( 'rest_api_init',       array( $this, 'register_rest_route' ) );
		add_action( 'wp_enqueue_scripts',  array( $this, 'enqueue_tracker' ) );
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

		$ip = $this->get_visitor_ip();

		$lookup       = new BVIP_IP_Lookup();
		$company_data = $lookup->get_company_data( $ip );

		$ua           = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$session_hash = hash( 'sha256', $ip . $ua . gmdate( 'Y-m-d' ) . wp_salt() );

		$device        = $this->get_device_type( $screen_width );
		$traffic_src   = $this->detect_traffic_source( $referrer );
		$company_name  = isset( $company_data['company'] ) ? $company_data['company'] : null;
		$company_domain= isset( $company_data['domain'] )  ? $company_data['domain']  : null;
		$city          = isset( $company_data['city'] )    ? $company_data['city']    : null;
		$region        = isset( $company_data['region'] )  ? $company_data['region']  : null;
		$country       = isset( $company_data['country'] ) ? $company_data['country'] : null;

		$wpdb->insert(
			$wpdb->prefix . 'bvip_visits',
			array(
				'post_id'        => $post_id,
				'session_hash'   => $session_hash,
				'company_name'   => $company_name,
				'company_domain' => $company_domain,
				'city'           => $city,
				'region'         => $region,
				'country_code'   => $country,
				'traffic_source' => $traffic_src,
				'device_type'    => $device,
				'page_url'       => $page_url,
				'visited_at'     => current_time( 'mysql', true ),
			),
			array( '%d','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s' )
		);

		// Update session
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

		return new WP_REST_Response( array( 'status' => 'ok' ), 200 );
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

		$search  = array( 'google', 'bing', 'yahoo', 'duckduckgo', 'yandex', 'baidu' );
		$social  = array( 'facebook', 'twitter', 'linkedin', 'instagram', 'youtube', 'whatsapp' );

		foreach ( $search as $engine ) {
			if ( strpos( $referrer, $engine ) !== false ) return 'search';
		}
		foreach ( $social as $platform ) {
			if ( strpos( $referrer, $platform ) !== false ) return 'social';
		}
		return 'referral';
	}

	private function should_track() {
		if ( is_admin() )                         return false;
		if ( current_user_can( 'manage_options' ) ) return false;
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) return false;

		$ua   = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
		$bots = array( 'googlebot','bingbot','slurp','duckduckbot','baiduspider',
			'yandexbot','facebot','ia_archiver','ahrefsbot','semrushbot',
			'mj12bot','dotbot','rogerbot','exabot','gigabot' );
		foreach ( $bots as $bot ) {
			if ( strpos( $ua, $bot ) !== false ) return false;
		}
		return true;
	}
}
