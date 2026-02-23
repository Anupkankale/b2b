<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BVIP_Session_Tracker {

	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
	}

	public function register_rest_route() {
		register_rest_route( 'b2b-analytics/v1', '/duration', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_duration' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function handle_duration( $request ) {
		global $wpdb;

		$session_hash  = sanitize_text_field( $request->get_param( 'session_hash' ) );
		$visit_id      = absint( $request->get_param( 'visit_id' ) );
		$time_on_page  = absint( $request->get_param( 'time_on_page' ) );

		// Cap at 30 minutes — anything longer is probably idle
		$time_on_page = min( $time_on_page, 1800 );

		// Update the specific visit row with time on page
		if ( $visit_id ) {
			$wpdb->update(
				$wpdb->prefix . 'bvip_visits',
				array( 'time_on_page' => $time_on_page ),
				array( 'id' => $visit_id ),
				array( '%d' ),
				array( '%d' )
			);
		}

		// Update session total duration
		if ( $session_hash ) {
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT total_duration FROM {$wpdb->prefix}bvip_sessions WHERE session_hash = %s",
				$session_hash
			) );

			if ( null !== $existing ) {
				$wpdb->update(
					$wpdb->prefix . 'bvip_sessions',
					array(
						'total_duration' => (int) $existing + $time_on_page,
						'last_activity'  => current_time( 'mysql', true ),
					),
					array( 'session_hash' => $session_hash ),
					array( '%d', '%s' ),
					array( '%s' )
				);
			}
		}

		return new WP_REST_Response( array( 'status' => 'ok' ), 200 );
	}
}
