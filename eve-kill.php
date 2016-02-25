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
require_once 'inc/eve-db-utilities.php';
require_once 'inc/zkillboard-api.php';

class Eve_Kill {

	protected static $instance;

	private $items = array();

	private $systems = array();

	protected function __construct() {
		$this->zkill = new zKillboard();
		$this->db = new Eve_DB_Utils( $this );
	}

	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function hooks() {
		add_action( 'template_redirect', array( $this, 'override' ) );

		$this->db->hooks();
	}

	public function override() {
		if ( ! isset( $_GET['eve'] ) ) {
			return;
		}

		$region_id = $this->db->get_region_id( 'The Bleak Lands' );

		$params = array(
//			'limit' => 10,
		);

		$after_id = $this->db->get_last_id();

		if ( ! empty( $after_id ) ) {
			$params['afterKillID'] = $after_id;
		}


		$losses = $this->zkill->get_losses_by( 'region', $region_id, $params );

		if ( empty( $losses ) ) {
			error_log( 'No losses' );
			return;
		}

		foreach ( $losses as $loss ) {

			$this->db->insert_loss( $loss );

		}
	}
}

add_action( 'plugins_loaded', array( Eve_Kill::init(), 'hooks' ) );
