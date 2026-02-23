<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BVIP_Click_Tracker {

	public function init() {
		add_action( 'rest_api_init',      array( $this, 'register_rest_route' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_click_tracker' ) );
	}

	public function register_rest_route() {
		register_rest_route( 'b2b-analytics/v1', '/click', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_click' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function enqueue_click_tracker() {
		if ( is_admin() ) return;
		wp_enqueue_script(
			'bvip-click-tracker',
			BVIP_PLUGIN_URL . 'assets/js/click-tracker.js',
			array(),
			BVIP_VERSION,
			true
		);
		wp_localize_script( 'bvip-click-tracker', 'bvipClickData', array(
			'restUrl' => esc_url_raw( rest_url() ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'postId'  => get_queried_object_id(),
			'pageUrl' => esc_url( ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ),
		) );
	}

	public function handle_click( $request ) {
		global $wpdb;

		$session_hash = sanitize_text_field( $request->get_param( 'session_hash' ) );
		$post_id      = absint( $request->get_param( 'post_id' ) );
		$element_type = sanitize_text_field( $request->get_param( 'element_type' ) );
		$element_text = sanitize_text_field( mb_substr( $request->get_param( 'element_text' ), 0, 255 ) );
		$element_id   = sanitize_html_class( $request->get_param( 'element_id' ) );
		$element_class= sanitize_text_field( mb_substr( $request->get_param( 'element_class' ), 0, 255 ) );
		$target_url   = sanitize_url( $request->get_param( 'target_url' ) );
		$x_pos        = absint( $request->get_param( 'x_position' ) );
		$y_pos        = absint( $request->get_param( 'y_position' ) );
		$page_url     = sanitize_url( $request->get_param( 'page_url' ) );

		// Look up company from session
		$company_name = $wpdb->get_var( $wpdb->prepare(
			"SELECT company_name FROM {$wpdb->prefix}bvip_sessions WHERE session_hash = %s",
			$session_hash
		) );

		$wpdb->insert(
			$wpdb->prefix . 'bvip_clicks',
			array(
				'session_hash'  => $session_hash,
				'post_id'       => $post_id,
				'company_name'  => $company_name,
				'element_type'  => $element_type,
				'element_text'  => $element_text,
				'element_id'    => $element_id,
				'element_class' => $element_class,
				'target_url'    => $target_url,
				'x_position'    => $x_pos,
				'y_position'    => $y_pos,
				'page_url'      => $page_url,
				'clicked_at'    => current_time( 'mysql', true ),
			),
			array( '%s','%d','%s','%s','%s','%s','%s','%s','%d','%d','%s','%s' )
		);

		return new WP_REST_Response( array( 'status' => 'ok' ), 200 );
	}
}
