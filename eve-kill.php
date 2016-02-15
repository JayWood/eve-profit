<?php
/**
 * Plugin Name:     Eve-Kill
 * Plugin URI:      http://plugish.com
 * Description:     A zKillboard datamining script for WordPress
 * Author:          JayWood
 * Author URI:      http://plugish.com
 * Version:         0.1.0
 */

// A library for reading zkillboard data

require_once 'inc/eve-db.php';
require_once 'inc/zkillboard-api.php';
class Eve_Kill {

	protected static $instance;

	private $items = array();

	private $systems = array();

	protected function __construct() {
		$this->db = new Eve_DB();
		$this->zkill = new zKillboard();
	}

	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function hooks() {
		add_action( 'template_redirect', array( $this, 'override' ) );
	}

	public function override() {
		if ( ! isset( $_GET['eve'] ) ) {
			return;
		}

		$region_id = $this->db->get_region_id( 'The Bleak Lands' );
		$losses = $this->zkill->get_losses_by( 'region', $region_id, array(
			'limit' => 10,
		) );

		foreach ( $losses as $loss ) {
			$this->increment_item( $loss->victim->shipTypeID );
			$this->increment_system( $loss->solarSystemID );
			foreach ( $loss->items as $_key => $_val ) {
				$this->increment_item( $loss->items[ $_key ]->typeID );
			}
		}

		foreach ( $this->items as $item_id => $count ) {
			$item_data = $this->db->get_item_data( $item_id );
			error_log( sprintf( 'x%d - %s', $count, $item_data->typeName ) );
		}
	}

	public function increment_system( $system_id ) {
		$systems = $this->systems;
		if ( array_key_exists( $system_id, $systems ) ) {
			$systems[ $system_id ]++;
		} else {
			$this->systems[ $system_id ] = 1;
		}
	}

	public function increment_item( $item ) {
		$items = $this->items;
		if ( array_key_exists( $item, $items ) ) {
			$items[ $item ]++;
		} else {
			$this->items[ $item ] = 1;
		}
	}
}

add_action( 'plugins_loaded', array( Eve_Kill::init(), 'hooks' ) );
