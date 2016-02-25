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
		return $this->_get_type( 'losses', $type, $ids, $params );
	}

	/**
	 * Gets the valid type, returns false if no valid type is found.
	 *
	 * @param string $type
	 *
	 * @return bool|string
	 */
	private function _validate_fetch_type( $type ) {
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
	private function _make_request( $url ) {

		$url  = esc_url( $url );
		$hash = md5( $url );

		if ( false === $body = get_transient( $hash ) ) {
			$remote_get = wp_safe_remote_get( $url, array(
				'user-agent' => 'WordPress/Plugish.com - Maintainer: xPhyrax - jjwood2004@gmail.com',
				'headers'    => array( 'Accept-Encoding: gzip' ),
			) );
			$code       = wp_remote_retrieve_response_code( $remote_get );
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
	private function _get_type( $type, $fetch, $ids, $params = array() ) {

		$fetch = $this->_validate_fetch_type( $fetch );
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

		return json_decode( $this->_make_request( $url ) );
	}

	public function get_timestamp( $loss ) {
		if ( ! isset( $loss->killTime ) ) {
			throw new Exception( 'killTime is not available in the zKillboard API' );
		}

		return $loss->killTime;
	}

	/**
	 * Retrieves the ship lost in battle.
	 *
	 * @param Object $loss
	 *
	 * @return int
	 * @throws Exception
	 */
	public function get_lost_ship( $loss ) {
		$victim = $this->get_victim( $loss );

		if ( ! isset( $victim->shipTypeID ) ) {
			throw new Exception( 'shipTypeID key not available from vicitm object of zKillboard API.' );
		}

		return $victim->shipTypeID;
	}

	/**
	 * Getter method for Victim properties
	 *
	 * @param Object $victim
	 * @param string $property
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function get_victim_prop( $victim, $property ) {

		$victim_properties = array(
			'shipTypeID',
			'characterID',
			'characterName',
			'corporationID',
			'corporationName',
			'allianceID',
			'allianceName',
			'factionID',
			'factionName',
			'damageTaken',
		);

		if ( ! array_search( $property, $victim_properties ) ) {
			throw new Exception( "$property is not a valid property of the Victim object." );
		}

		return ! isset( $victim->{$property} ) ? '' : $victim->{$property};
	}

	/**
	 * @param Object $loss
	 * @param string|array $key
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function get_victim( $loss, $key = '' ) {
		if ( ! isset( $loss->victim ) ) {
			throw new Exception( 'Victim key is not available in zKillboard API loss.' );
		}

		if ( empty( $key ) ) {
			return $loss->victim;
		}

		$vic = $loss->victim;
		if ( is_array( $key ) ) {
			$victim_data = array();
			foreach ( $key as $k ) {
				$prop_data = $this->get_victim_prop( $vic, $k );
				$victim_data[ $k ] = $prop_data;
			}

			return $victim_data;
		}

		if ( isset( $vic->{$key} ) ) {
			return $vic->{$key};
		}

		return false;
	}

	/**
	 * Gets the list of items that were lost, not including the lost ship.
	 *
	 * @param Object $loss
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_loss_items( $loss ) {
		if ( ! isset( $loss->items ) ) {
			throw new Exception( 'The items key is not available within the kill board API.' );
		}

		return $loss->items;
	}

	/**
	 * Gets all attackers from a battle.
	 *
	 * @param Object $loss
	 * @param String $key
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_attackers( $loss, $key = '' ) {
		if ( ! isset( $loss->attackers ) ) {
			throw new Exception( 'The attackers key does not exist within the kill board API' );
		}

		if ( empty( $loss->attackers ) || ! is_array( $loss->attackers ) ) {
			return array();
		}

		if ( empty( $key ) ) {
			return $loss->attackers;
		}

		$attackers = array();
		foreach ( $loss->attackers as $attacker ) {
			if ( isset( $attacker->{$key} ) ) {
				$attackers[] = $attacker->{$key};
			}
		}

		return $attackers;
	}

	/**
	 * Helper method for returning a list of corporations involved, including the vic.
	 *
	 * @param Object $loss
	 * @return array
	 */
	public function get_corps( $loss ) {
		$victim = $this->get_victim( $loss );
		$attackers = $this->get_attackers( $loss );

		$corps = array();

		$vic_corp = $this->get_victim_prop( $victim, 'corporationID' );
		$corps[ $vic_corp ] = $this->get_victim_prop( $victim, 'corporationName' );

		if ( ! empty( $attackers ) ) {
			foreach( $attackers as $attacker ) {
				if ( isset( $attacker->corporationID ) && isset( $attacker->corporationName ) ) {
					$corp_id = $attacker->corporationID;
					$corp_name = $attacker->corporationName;

					if ( ! empty( $corp_id ) && ! empty( $corp_name ) ) {
						$corps[ $corp_id ] = $corp_name;
					}
				}
			}
		}

		return $corps;
	}

	/**
	 * Helper method for returning a list of alliances involved, including the vic.
	 *
	 * @param Object $loss
	 * @return array
	 */
	public function get_alliances( $loss ) {
		$victim = $this->get_victim( $loss );
		$attackers = $this->get_attackers( $loss );

		$alliance = array();

		$vic_alliance = $this->get_victim_prop( $victim, 'allianceID' );

		if ( ! empty( $vic_alliance ) ) {
			$alliance[ $vic_alliance ] = $this->get_victim_prop( $victim, 'allianceName' );
		}

		if ( ! empty( $attackers ) ) {
			foreach( $attackers as $attacker ) {
				if ( isset( $attacker->allianceID ) && isset( $attacker->allianceName ) ) {
					$alliance_id = $attacker->allianceID;
					$alliance_name = $attacker->allianceName;

					if ( ! empty( $alliance_id ) && ! empty( $alliance_name ) ) {
						$alliance[ $alliance_id ] = $alliance_name;
					}
				}
			}
		}

		return $alliance;
	}
}
