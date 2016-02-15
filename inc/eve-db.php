<?php

class Eve_DB {
	private $db = null;

	public function __construct() {
		$this->db = new wpdb( DB_USER, DB_PASSWORD, 'eve_sde', 'localhost' );
	}

	/**
	 * Gets the region ID of a specific region
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
}