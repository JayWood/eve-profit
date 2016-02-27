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

	const VERSION = '0.1.0';

	protected static $instance;

	private $items = array();

	private $systems = array();

	public $basename, $url, $path;

	protected function __construct() {

		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );


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

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

		add_action( 'wp_ajax_items_for_group', array( $this, 'get_items_for_market_group' ) );
		add_action( 'wp_ajax_nopriv_items_for_group', array( $this, 'get_items_for_market_group' ) );
	}

	public function get_items_for_market_group() {
		$group_id = intval( $_REQUEST['group'] );

		if ( empty( $group_id ) ) {
			wp_send_json_error( $_POST );
		}

		$items = $this->db->get_items_by_market_group( $group_id );
		if ( empty( $items ) ) {
			wp_send_json_error( 'No Items' );
		}

		$output = '<ul class="items">';
		foreach ( $items as $item ) {
			$output .= '<li class="eve-item item-'. $item->typeID .'" data-typeID="'. $item->typeID .'" data-tip="'.$item->description.'" data-iconID="'.$item->iconID.'" data-graphicID="'.$item->graphicID.'">'.$item->typeName.'</li>';
		}
		$output .= '</ul>';

		wp_send_json_success( $output );

	}

	public function enqueue() {

		wp_register_script( 'eve-kill', $this->url( 'assets/eve-kill.js' ), array( 'jquery' ), self::VERSION, true );
		wp_localize_script( 'eve-kill', 'ek_l10n', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		) );

		wp_enqueue_script( 'eve-kill' );
		wp_enqueue_style( 'eve-kill', $this->url( 'assets/eve-kill.css' ), false, self::VERSION );
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


	/**
	 * Include a file from the includes directory
	 *
	 * @since  0.1.0
	 * @param  string  $filename Name of the file to be included
	 * @return bool    Result of include call.
	 */
	public static function include_file( $filename ) {
		$file = self::dir( 'includes/'. $filename .'.php' );
		if ( file_exists( $file ) ) {
			return include_once( $file );
		}
		return false;
	}

	public static function views( $filename ) {
		$file = self::dir( 'views/'. $filename .'.php' );
		if ( file_exists( $file ) ) {
			return include_once( $file );
		}
		return false;
	}

	/**
	 * This plugin's directory
	 *
	 * @since  0.1.0
	 * @param  string $path (optional) appended path
	 * @return string       Directory and path
	 */
	public static function dir( $path = '' ) {
		static $dir;
		$dir = $dir ? $dir : trailingslashit( dirname( __FILE__ ) );
		return $dir . $path;
	}

	/**
	 * This plugin's url
	 *
	 * @since  0.1.0
	 * @param  string $path (optional) appended path
	 * @return string       URL and path
	 */
	public static function url( $path = '' ) {
		static $url;
		$url = $url ? $url : trailingslashit( plugin_dir_url( __FILE__ ) );
		return $url . $path;
	}
}

add_action( 'plugins_loaded', array( Eve_Kill::init(), 'hooks' ) );
