 <?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BVIP_Dashboard {

	public function init() {
		add_action( 'admin_menu',            array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init',            array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_bvip_realtime',    array( $this, 'ajax_realtime' ) );
		add_action( 'wp_ajax_bvip_flush_cache', array( $this, 'ajax_flush_cache' ) );
	}

	public function add_menu() {
		add_menu_page( __( 'B2B Visitors' ), __( 'B2B Visitors' ), 'manage_options',
			'bvip-dashboard', array( $this, 'render_dashboard' ), 'dashicons-groups', 25 );
		add_submenu_page( 'bvip-dashboard', __( 'Countries' ),  __( 'Countries' ),  'manage_options', 'bvip-countries',  array( $this, 'render_countries' ) );
		add_submenu_page( 'bvip-dashboard', __( 'Click Map' ),  __( 'Click Map' ),  'manage_options', 'bvip-clicks',     array( $this, 'render_clicks' ) );
		add_submenu_page( 'bvip-dashboard', __( 'Time on Page' ), __( 'Time on Page' ), 'manage_options', 'bvip-duration', array( $this, 'render_duration' ) );
		add_submenu_page( 'bvip-dashboard', __( 'Settings' ),   __( 'Settings' ),   'manage_options', 'bvip-settings',   array( $this, 'render_settings' ) );
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'bvip' ) === false ) return;
		wp_enqueue_script( 'chartjs', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js', array(), '4.4.0', true );
		wp_enqueue_script( 'bvip-dashboard', BVIP_PLUGIN_URL . 'assets/js/dashboard.js', array( 'jquery', 'chartjs' ), BVIP_VERSION, true );
		wp_enqueue_style( 'bvip-dashboard', BVIP_PLUGIN_URL . 'assets/css/dashboard.css', array(), BVIP_VERSION );

		$analytics = new BVIP_Analytics();
		$chart     = $analytics->get_pageviews_over_time( 30 );
		$sources   = $analytics->get_traffic_sources( 30 );
		$devices   = $analytics->get_device_breakdown( 30 );
		$countries = $analytics->get_countries( 30, 10 );

		wp_localize_script( 'bvip-dashboard', 'bvipChartData', array(
			'dates'        => array_keys( $chart ),
			'pageviews'    => array_values( $chart ),
			'sourceLbls'   => wp_list_pluck( $sources,   'traffic_source' ),
			'sourceCnts'   => wp_list_pluck( $sources,   'count' ),
			'deviceLbls'   => wp_list_pluck( $devices,   'device_type' ),
			'deviceCnts'   => wp_list_pluck( $devices,   'count' ),
			'countryLbls'  => wp_list_pluck( $countries, 'country_name' ),
			'countryCnts'  => wp_list_pluck( $countries, 'visits' ),
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'bvip_nonce' ),
		) );
	}

	public function register_settings() {
		register_setting( 'bvip_settings', 'bvip_ipinfo_key', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );
		register_setting( 'bvip_settings', 'bvip_alert_email', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_email',
			'default'           => get_option( 'admin_email' ),
		) );
		register_setting( 'bvip_settings', 'bvip_alert_threshold', array(
			'type'              => 'integer',
			'sanitize_callback' => static function ( $v ) {
				return max( 1, min( 50, absint( $v ) ) );
			},
			'default'           => 3,
		) );
		register_setting( 'bvip_settings', 'bvip_data_retention', array(
			'type'              => 'integer',
			'sanitize_callback' => static function ( $v ) {
				return in_array( (int) $v, array( 90, 180, 365, 0 ), true ) ? (int) $v : 365;
			},
			'default'           => 365,
		) );
		register_setting( 'bvip_settings', 'bvip_excluded_ips', array(
			'type'              => 'string',
			'sanitize_callback' => static function ( $v ) {
				$lines = array_filter( array_map( 'trim', explode( "\n", (string) $v ) ) );
				$valid = array_filter( $lines, static function ( $ip ) {
					return (bool) filter_var( $ip, FILTER_VALIDATE_IP );
				} );
				return implode( "\n", $valid );
			},
			'default'           => '',
		) );
	}

	public function render_dashboard() {
		$a = new BVIP_Analytics();
		$stats     = $a->get_summary_stats( 30 );
		$companies = $a->get_top_companies( 30, 25 );
		$top_pages = $a->get_top_pages( 30, 8 );
		include BVIP_PLUGIN_DIR . 'admin/views/dashboard.php';
	}
	public function render_countries() {
		$a = new BVIP_Analytics();
		$countries = $a->get_countries( 30, 50 );
		include BVIP_PLUGIN_DIR . 'admin/views/countries.php';
	}
	public function render_clicks() {
		$a = new BVIP_Analytics();
		$clicks      = $a->get_top_clicks( 30, 30 );
		$clicks_type = $a->get_clicks_by_type( 30 );
		include BVIP_PLUGIN_DIR . 'admin/views/clicks.php';
	}
	public function render_duration() {
		$a = new BVIP_Analytics();
		$durations = $a->get_time_on_page_by_page( 30 );
		include BVIP_PLUGIN_DIR . 'admin/views/duration.php';
	}
	public function render_settings() {
		include BVIP_PLUGIN_DIR . 'admin/views/settings.php';
	}

	public function ajax_realtime() {
		check_ajax_referer( 'bvip_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		$a = new BVIP_Analytics();
		wp_send_json_success( array( 'count' => $a->get_realtime_visitors() ) );
	}
	public function ajax_flush_cache() {
		check_ajax_referer( 'bvip_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		( new BVIP_Analytics() )->flush_cache();
		wp_send_json_success( array( 'message' => 'Cache cleared' ) );
	}
}
