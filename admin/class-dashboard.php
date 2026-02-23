<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BVIP_Dashboard {

	public function init() {
		add_action( 'admin_menu',          array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init',          array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_bvip_realtime', array( $this, 'ajax_realtime' ) );
		add_action( 'wp_ajax_bvip_flush_cache', array( $this, 'ajax_flush_cache' ) );
	}

	public function add_menu() {
		add_menu_page(
			__( 'B2B Visitors', 'b2b-visitor-intelligence' ),
			__( 'B2B Visitors', 'b2b-visitor-intelligence' ),
			'manage_options',
			'bvip-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-groups',
			25
		);
		add_submenu_page(
			'bvip-dashboard',
			__( 'Settings', 'b2b-visitor-intelligence' ),
			__( 'Settings', 'b2b-visitor-intelligence' ),
			'manage_options',
			'bvip-settings',
			array( $this, 'render_settings' )
		);
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'bvip' ) === false ) return;

		wp_enqueue_script(
			'chartjs',
			'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js',
			array(),
			'4.4.0',
			true
		);
		wp_enqueue_script(
			'bvip-dashboard',
			BVIP_PLUGIN_URL . 'assets/js/dashboard.js',
			array( 'jquery', 'chartjs' ),
			BVIP_VERSION,
			true
		);
		wp_enqueue_style(
			'bvip-dashboard',
			BVIP_PLUGIN_URL . 'assets/css/dashboard.css',
			array(),
			BVIP_VERSION
		);

		// Pass chart data
		$analytics   = new BVIP_Analytics();
		$chart_data  = $analytics->get_pageviews_over_time( 30 );
		$sources     = $analytics->get_traffic_sources( 30 );
		$devices     = $analytics->get_device_breakdown( 30 );

		wp_localize_script( 'bvip-dashboard', 'bvipChartData', array(
			'dates'      => array_keys( $chart_data ),
			'pageviews'  => array_values( $chart_data ),
			'sourceLbls' => wp_list_pluck( $sources, 'traffic_source' ),
			'sourceCnts' => wp_list_pluck( $sources, 'count' ),
			'deviceLbls' => wp_list_pluck( $devices, 'device_type' ),
			'deviceCnts' => wp_list_pluck( $devices, 'count' ),
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'bvip_nonce' ),
		) );
	}

	public function register_settings() {
		register_setting( 'bvip_settings', 'bvip_ipinfo_key',       'sanitize_text_field' );
		register_setting( 'bvip_settings', 'bvip_alert_email',      'sanitize_email' );
		register_setting( 'bvip_settings', 'bvip_alert_threshold',  'absint' );
		register_setting( 'bvip_settings', 'bvip_data_retention',   'absint' );
	}

	public function render_dashboard() {
		$analytics = new BVIP_Analytics();
		$stats     = $analytics->get_summary_stats( 30 );
		$companies = $analytics->get_top_companies( 30, 25 );
		$top_pages = $analytics->get_top_pages( 30, 5 );
		include BVIP_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	public function render_settings() {
		include BVIP_PLUGIN_DIR . 'admin/views/settings.php';
	}

	public function ajax_realtime() {
		check_ajax_referer( 'bvip_nonce', 'nonce' );
		$analytics = new BVIP_Analytics();
		wp_send_json_success( array( 'count' => $analytics->get_realtime_visitors() ) );
	}

	public function ajax_flush_cache() {
		check_ajax_referer( 'bvip_nonce', 'nonce' );
		$analytics = new BVIP_Analytics();
		$analytics->flush_cache();
		wp_send_json_success( array( 'message' => 'Cache cleared' ) );
	}
}
