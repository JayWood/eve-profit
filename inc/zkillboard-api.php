<?php

class ZKillboard {

	protected $end_point = 'https://zkillboard.com/api/';

	/**
	 * @param string    $type
	 * @param int|array $ids
	 * @param array     $params
	 *
	 * @return Object|WP_Error
	 */
	public function get_losses_by( $type, $ids, $params = array() ) {
		return $this->get_type( 'losses', $type, $ids, $params );
	}

	/**
	 * Gets the valid type, returns false if no valid type is found.
	 *
	 * @param string $type
	 *
	 * @return bool|string
	 */
	private function validate_fetch_type( $type ) {
		$valid_types = array(
			'character'   => 'characterID',
			'corporation' => 'corporationID',
			'alliance'    => 'allianceID',
			'ship'        => 'shipTypeID',
			'group'       => 'groupID',
			'system'      => 'solarSystemID',
			'region'      => 'regionID',
			'war'         => 'warID',
		);

		if ( array_key_exists( $type, $valid_types ) ) {
			return $valid_types[ $type ];
		}

		foreach ( $valid_types as $k => $v ) {
			if ( $v == $type ) {
				return $type;
			}
		}

		return false;
	}

	/**
	 * A one-stop shop for the URL requests to zKillboard
	 *
	 * @param String $url
	 *
	 * @return mixed|string|WP_Error
	 */
	private function make_request( $url ) {

		$url  = esc_url( $url );
		$hash = md5( $url );

		if ( false === $body = get_transient( $hash ) ) {
			$remote_get = wp_safe_remote_get( $url, array(
				'user-agent' => 'WordPress/Plugish.com - Maintainer: xPhyrax - jjwood2004@gmail.com',
				'headers'    => array( 'Accept-Encoding: gzip' ),
			) );
			$code = wp_remote_retrieve_response_code( $remote_get );
			if ( 200 !== $code ) {
				return new WP_Error( 'http_not_200', sprintf( 'Got a %d response code when expecting 200 for URL: 5s', $code, $url ) );
			}

			$body = wp_remote_retrieve_body( $remote_get );
			set_transient( $hash, $body, 30 * HOUR_IN_SECONDS );
		}

		return $body;
	}

	/**
	 * Helper for building the URL query
	 *
	 * @param string    $type
	 * @param string    $fetch
	 * @param int|array $ids
	 * @param array     $params
	 *
	 * @return string|WP_Error
	 */
	private function get_type( $type, $fetch, $ids, $params = array() ) {

		$fetch = $this->validate_fetch_type( $fetch );
		if ( ! $fetch ) {
			return new WP_Error( 'invalid_type', sprintf( 'The type of %s does not exist in the zKillboard API', $fetch ) );
		}

		if ( isset( $params['startTime'] ) && is_int( $params['startTime'] ) ) {
			$params['startTime'] = date( 'YmdHi', $params['startTime'] );
		}

		if ( is_array( $ids ) ) {
			$ids = implode( ',', $ids );
		}

		$url_params = '';
		if ( ! empty( $params ) && is_array( $params ) ) {
			foreach ( $params as $key => $val ) {
				$url_params .= '/' . $key . '/' . $val;
			}
		}

		$url = $this->end_point . $type . '/' . $fetch . '/' . $ids . $url_params;

		return json_decode( $this->make_request( $url ) );
	}
}