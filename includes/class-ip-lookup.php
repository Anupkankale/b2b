<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * IP Lookup class  */
class BVIP_IP_Lookup {

	/** Country code → full name map (ISO 3166-1 alpha-2). */
	private static $country_names = array(
		'AF'=>'Afghanistan','AL'=>'Albania','DZ'=>'Algeria','AR'=>'Argentina','AU'=>'Australia',
		'AT'=>'Austria','BE'=>'Belgium','BR'=>'Brazil','CA'=>'Canada','CL'=>'Chile',
		'CN'=>'China','CO'=>'Colombia','HR'=>'Croatia','CZ'=>'Czech Republic','DK'=>'Denmark',
		'EG'=>'Egypt','FI'=>'Finland','FR'=>'France','DE'=>'Germany','GH'=>'Ghana',
		'GR'=>'Greece','HK'=>'Hong Kong','HU'=>'Hungary','IN'=>'India','ID'=>'Indonesia',
		'IE'=>'Ireland','IL'=>'Israel','IT'=>'Italy','JP'=>'Japan','KE'=>'Kenya',
		'MY'=>'Malaysia','MX'=>'Mexico','MA'=>'Morocco','NL'=>'Netherlands','NZ'=>'New Zealand',
		'NG'=>'Nigeria','NO'=>'Norway','PK'=>'Pakistan','PE'=>'Peru','PH'=>'Philippines',
		'PL'=>'Poland','PT'=>'Portugal','RO'=>'Romania','RU'=>'Russia','SA'=>'Saudi Arabia',
		'ZA'=>'South Africa','KR'=>'South Korea','ES'=>'Spain','SE'=>'Sweden','CH'=>'Switzerland',
		'TW'=>'Taiwan','TH'=>'Thailand','TR'=>'Turkey','UA'=>'Ukraine','AE'=>'United Arab Emirates',
		'GB'=>'United Kingdom','US'=>'United States','VN'=>'Vietnam',
	);

	/** Returns true if the IP result is already in the transient cache. */
	public function is_cached( $ip ) {
		return false !== get_transient( 'bvip_ip_' . md5( $ip ) );
	}

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
		$country_code = isset( $data['country'] ) ? $data['country'] : '';
		$result = array(
			'company'      => $this->strip_as_number( isset( $data['org'] ) ? $data['org'] : '' ),
			'domain'       => isset( $data['hostname'] ) ? $data['hostname'] : '',
			'city'         => isset( $data['city'] )    ? $data['city']    : '',
			'region'       => isset( $data['region'] )  ? $data['region']  : '',
			'country'      => $country_code,
			'country_name' => isset( self::$country_names[ $country_code ] ) ? self::$country_names[ $country_code ] : $country_code,
			'lat'          => isset( $loc[0] ) ? $loc[0] : '',
			'lng'          => isset( $loc[1] ) ? $loc[1] : '',
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
