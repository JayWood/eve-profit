<?php

class Eve_DB_Utils extends Eve_DB {

	/**
	 * Instance of main plugin
	 * @var Eve_Kill string
	 */
	protected $plugin;

	public function __construct( $plugin ) {

		$this->plugin = $plugin;

		parent::__construct( $plugin );
	}

	/**
	 * Gets the region ID for a solar system
	 *
	 * @param string|int $system
	 *
	 * @return null|string
	 */
	public function get_system_region( $system ) {
		$where = 'solarSystemName = %s';
		if ( is_int( $system ) ) {
			$where = 'solarSystemID = %d';
		}
		$sql     = $this->db->prepare( 'SELECT regionID FROM mapSolarSystems WHERE ' . $where . ' LIMIT 1', $system );
		$results = $this->db->get_var( $sql );

		return $results;
	}

	/**
	 * Gets the region ID of a specific region
	 *
	 * @param String $region_name The name of the region
	 *
	 * @return int 0 on failure, regionID otherwise
	 */
	public function get_region_id( $region_name ) {
		$sql = $this->db->prepare( 'SELECT regionID FROM mapRegions WHERE regionName = %s LIMIT 1', $region_name );

		$results = $this->db->get_var( $sql );

		return $results;
	}

	public function get_item_data( $item_id ) {
		$sql = $this->db->prepare( '
			SELECT i.typeID, i.typeName, i.description, g.groupName FROM invTypes i
			INNER JOIN invGroups g
			ON g.groupID = i.groupID
			WHERE i.typeID = %d
		', $item_id );

		return $this->db->get_row( $sql );
	}

	/**
	 * Inserts a loss into the database.
	 *
	 * @param Object $loss
	 */
	public function insert_loss( $loss ) {

		// Instance of zKillboard API
		$zkill = $this->plugin->zkill;

		$items = $zkill->get_loss_items( $loss );

		if ( empty( $items ) ) {
			return;
		}

		// Insert all lost/dropped items
		foreach ( $items as $item ) {
			$this->insert_loss_item( $item, $loss->killID );
		}

		// Insert the lost ship
		$ship_lost = $zkill->get_lost_ship( $loss );
		$this->insert_loss_item( array(
			'typeID' => $ship_lost,
			'qtyDestroyed' => 1,
		), $loss->killID );

		$this->insert_losses_table( $loss );

		$this->insert_characters( $loss );

		$this->insert_corps( $loss );
		$this->insert_alliances( $loss );

		$this->insert_attacker_items( $loss );
	}

	private function insert_attacker_items( $loss ) {
		global $wpdb;
		$zkill = $this->plugin->zkill;

		$attackers = $zkill->get_attackers( $loss );

		if ( empty( $attackers ) ) {
			return false;
		}

		foreach( $attackers as $attacker ) {

			if ( ! isset( $attacker->weaponTypeID )
			     || ! isset( $attacker->shipTypeID )
			     || empty( $attacker->weaponTypeID )
			     || empty( $attacker->shipTypeID ) )
			{
				// Something is wonky about this, so skip it.
				continue;
			}

			$attacker_data = array(
				'killID' => $loss->killID,
				'characterID' => $attacker->characterID,
				'itemID' => $attacker->shipTypeID,
			);
			$wpdb->insert( $this->attacker_items, $attacker_data, array( '%d', '%d', '%d' ) );

			// Now for their weapon of choice
			$attacker_data['itemID'] = $attacker->weaponTypeID;
			$wpdb->insert( $this->attacker_items, $attacker_data, array( '%d', '%d', '%d' ) );
		}

		return true;

	}

	private function insert_alliances( $loss ) {
		global $wpdb;

		$zkill = $this->plugin->zkill;

		$alliance = $zkill->get_alliances( $loss );

		if ( empty( $alliance ) ) {
			return;
		}

		foreach ( $alliance as $alliance_id => $alliance_name ) {
			$wpdb->replace( $this->alliances, array(
				'allianceID' => $alliance_id,
				'allianceName' => $alliance_name,
			), array( '%d', '%s' ) );
		}
	}

	private function insert_corps( $loss ) {
		global $wpdb;

		$zkill = $this->plugin->zkill;

		$corps = $zkill->get_corps( $loss );

		if ( empty( $corps ) ) {
			return;
		}

		foreach ( $corps as $corp_id => $corp_name ) {
			$wpdb->replace( $this->corporations, array(
				'corporationID' => $corp_id,
				'corporationName' => $corp_name,
			), array( '%d', '%s' ) );
		}

	}

	private function insert_characters( $loss ) {
		global $wpdb;

		$zkill = $this->plugin->zkill;

		$attackers = $zkill->get_attackers( $loss );
		$victim = $zkill->get_victim( $loss );

		$insert = $zkill->get_victim( $loss, array( 'characterID', 'corporationID', 'allianceID', 'characterName' ) );

		if ( ! empty( $insert['characterName'] ) && ! empty( $insert['characterID'] ) ) {
			$wpdb->replace( $this->characters, $insert, array( '%d', '%d', '%d', '%s' ) );
		}

		// TODO: Make a helper function just like get_victim
		foreach ( $attackers as $attacker ) {
			$insert = array(
				'characterID'   => isset( $attacker->characterID ) ? $attacker->characterID : '',
				'corporationID' => isset( $attacker->corporationID ) ? $attacker->corporationID : '',
				'allianceID'    => isset( $attacker->allianceID ) ? $attacker->allianceID : '',
				'characterName' => isset( $attacker->characterName ) ? $attacker->characterName : '',
			);

			if ( empty( $insert['characterName'] ) || empty( $insert['characterID'] ) ) {
				continue; // Need these at minimum;
			}

			$wpdb->replace( $this->characters, $insert, array( '%d', '%d', '%d', '%s' ) );
		}
	}

	private function insert_losses_table( $loss ) {
		global $wpdb;
		$zkill      = $this->plugin->zkill;
		$aggressors = $zkill->get_attackers( $loss, 'characterID' );

		if ( ! empty( $aggressors ) ) {
			$aggressors = array_map( 'intval', $aggressors );
		}

		$region    = $this->get_system_region( (int) $loss->solarSystemID );
		$timestamp = $zkill->get_timestamp( $loss );

		return $wpdb->insert( $this->losses, array(
			'killID'        => intval( $loss->killID ),
			'solarSystemID' => intval( $loss->solarSystemID ),
			'regionID'      => intval( $region ),
			'killTIme'      => strtotime( $timestamp ),
			'victim'        => intval( $zkill->get_victim( $loss, 'characterID' ) ),
			'aggressors'    => maybe_serialize( $aggressors ),
		), array( '%d', '%d', '%d', '%d', '%s' )  );
	}

	/**
	 * @param $item
	 * @param $kill_id
	 *
	 * @return bool|int
	 */
	private function insert_loss_item( $item, $kill_id ) {
		global $wpdb;

		if ( ! is_array( $item ) ) {
			$item = (array) $item;
		}

		$defaults = array(
			'typeID'       => '',
			'qtyDropped'   => 0,
			'qtyDestroyed' => 0,
		);

		$item = wp_parse_args( $item, $defaults );

		if ( empty( $item['typeID'] ) ) {
			return false;
		}

		$insert = array(
			'killID'    => $kill_id,
			'itemID'    => $item['typeID'],
			'destroyed' => $item['qtyDestroyed'],
			'dropped'   => $item['qtyDropped'],
		);

		return $wpdb->insert( $this->items, $insert, array( '%d', '%d', '%d', '%d' ) );
	}

	/**
	 * Gets the last ID that was added.
	 * @return null|string
	 */
	public function get_last_id() {
		global $wpdb;

		return $wpdb->get_var( "SELECT killID from {$this->losses} ORDER BY killTime DESC LIMIT 1" );

	}
}