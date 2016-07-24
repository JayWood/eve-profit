<?php

class Eve_DB {

	/**
	 * Instance of WPDB
	 * @var wpdb wpdb
	 */
	protected $db;

	/**
	 * Database tables
	 * @var String
	 */
	protected $losses, $characters, $corporations, $alliances, $kill_pivot, $items, $attacker_items;

	/**
	 * Instance of main plugin
	 * @var Eve_Kill plugin
	 */
	protected $plugin;

	const DB_VERSION = '0.2';

	public function __construct( $plugin, $database = 'eve', $host = 'localhost' ) {

		$this->plugin = $plugin;

		global $wpdb;

		// Now setup the properties of the DB class
		$tables = array(
			'losses'         => 'ek_losses',
			'characters'     => 'ek_characters',
			'corporations'   => 'ek_corporations',
			'alliances'      => 'ek_alliances',
			'kill_pivot'     => 'ek_kill_pivot',
			'items'          => 'ek_items',
			'attacker_items' => 'ek_attacker_items',
		);

		foreach ( $tables as $prop => $name ) {
			$this->{$prop} = $wpdb->prefix . $name;
		}

		$this->db = new wpdb( DB_USER, DB_PASSWORD, $database, $host );
	}

	public function hooks() {
		add_action( 'admin_init', array( $this, 'install' ) );
	}

	public function install() {
		global $wpdb;
		if ( self::DB_VERSION == get_option( 'eve-kill-db-ver', false ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$losses_table = "
			CREATE TABLE {$this->losses} (
			killID INT(11) NOT NULL,
			solarSystemID INT(11) NOT NULL,
			regionID INT(11) NOT NULL,
			killTime INT(11) NOT NULL,
			victim INT(11) NOT NULL,
			aggressors TEXT,
			UNIQUE KEY killID (killID)
		) $charset_collate;";
		dbDelta( $losses_table );

		$character_table = "
			CREATE TABLE {$this->characters} (
			characterID INT(11) NOT NULL,
			characterName VARCHAR(64) NOT NULL,
			corporationID INT(11) NOT NULL,
			allianceID INT(11) NOT NULL,
			UNIQUE KEY characterID (characterID)
		) $charset_collate;";
		dbDelta( $character_table );

		$corp_table = "
			CREATE TABLE {$this->corporations} (
			corporationID INT(11) NOT NULL,
			corporationName VARCHAR(64) NOT NULL,
			UNIQUE KEY corporationID (corporationID)
		) $charset_collate;";
		dbDelta( $corp_table );

		$alliance_table = "
			CREATE TABLE {$this->alliances} (
			allianceID INT(11) NOT NULL,
			allianceName VARCHAR(64) NOT NULL,
			UNIQUE KEY allianceID (allianceID)
		) $charset_collate;";
		dbDelta( $alliance_table );

		$kill_pivot = "
			CREATE TABLE {$this->kill_pivot} (
			id INT(11) NOT NULL AUTO_INCREMENT,
			killID INT(11) NOT NULL,
			characterID INT(11) NOT NULL,
			corporationID INT(11) NOT NULL,
			allianceID INT(11) NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";
		dbDelta( $kill_pivot );

		$items_table = "
			CREATE TABLE {$this->items} (
			id INT(11) NOT NULL AUTO_INCREMENT,
			killID INT(11) NOT NULL,
			itemID INT(11) NOT NULL,
			destroyed INT(11) NOT NULL DEFAULT 0,
			dropped INT(11) NOT NULL DEFAULT 0,
			flag INT(11) NOT NULL DEFAULT 0,
			UNIQUE KEY id (id)
		) $charset_collate;";
		dbDelta( $items_table );

		$attackers_table = "
			CREATE TABLE {$this->attacker_items} (
			id INT(11) NOT NULL AUTO_INCREMENT,
			killID INT(11) NOT NULL,
			characterID INT(11) NOT NULL,
			itemID INT(11) NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";
		dbDelta( $attackers_table );

		update_option( 'eve-kill-db-ver', self::DB_VERSION );
	}
}
