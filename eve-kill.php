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

require_once 'inc/lib/eve-db.php';
require_once 'inc/lib/eve-db-utilities.php';
require_once 'inc/lib/zkillboard-api.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	include_once 'cli/import.php';
}

class Eve_Kill {

	protected static $instance;

	private $items = array();

	private $systems = array();

	protected function __construct() {
		$this->zkill = new zKillboard();
		$this->db = new Eve_DB_Utils( $this );
		
		$this->shortcodes();
	}

	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function hooks() {
		$this->db->hooks();
	}

	public function shortcodes() {
		add_shortcode( 'ek-search-form', array( $this, 'form' ) );
	}

	public function form() {
		ob_start();
		$this->render_view( 'ek-search-form' );
		return ob_get_clean();
	}

	public function render_view( $view ) {
		require_once 'inc/template-tags.php';
		include "views/$view.php";
	}
}

add_action( 'plugins_loaded', array( Eve_Kill::init(), 'hooks' ) );
