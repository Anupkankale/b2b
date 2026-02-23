<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BVIP_IP_Lookup {

	public function get_company_data( $ip ) {
		if ( ! $this->is_valid_ip( $ip ) ) {
			return array();
		}

		$cache_key = 'bvip_ip_' . md5( $ip );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$api_key  = get_option( 'bvip_ipinfo_key', '' );
		if ( empty( $api_key ) ) {
			return array();
		}

		$url      = "https://ipinfo.io/{$ip}?token={$api_key}";
		$response = wp_remote_get( $url, array( 'timeout' => 5 ) );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data ) ) {
			return array();
		}

		$loc  = isset( $data['loc'] ) ? explode( ',', $data['loc'] ) : array( '', '' );
		$result = array(
			'company' => $this->strip_as_number( isset( $data['org'] ) ? $data['org'] : '' ),
			'domain'  => isset( $data['hostname'] ) ? $data['hostname'] : '',
			'city'    => isset( $data['city'] )    ? $data['city']    : '',
			'region'  => isset( $data['region'] )  ? $data['region']  : '',
			'country' => isset( $data['country'] ) ? $data['country'] : '',
			'lat'     => isset( $loc[0] )           ? $loc[0]          : '',
			'lng'     => isset( $loc[1] )           ? $loc[1]          : '',
		);

		set_transient( $cache_key, $result, DAY_IN_SECONDS );
		return $result;
	}

	private function strip_as_number( $org ) {
		if ( empty( $org ) ) return '';
		return trim( preg_replace( '/^AS\d+\s*/i', '', $org ) );
	}

	public function is_valid_ip( $ip ) {
		if ( empty( $ip ) ) return false;
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) return false;
		// Exclude private ranges
		$private = array( '10.', '192.168.', '172.16.', '172.17.', '172.18.',
			'172.19.', '172.20.', '127.', '::1', 'localhost' );
		foreach ( $private as $range ) {
			if ( strpos( $ip, $range ) === 0 ) return false;
		}
		return true;
	}
}
