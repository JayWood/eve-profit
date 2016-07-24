<?php

class EK_CLI_Import extends WP_CLI_Command {

	protected $args;
	protected $assoc_args;

	protected $progress_bar;

	/**
	 * Runs the importer
	 *
	 * ## Options
	 *
	 * <region_name>
	 * : The name of the region, case sensitive
	 *
	 * --nocache
	 * : Ignores local cache
	 *
	 * @synopsis <region_name> [--nocache]
	 */
	public function import( $args, $assoc_args ) {
		$this->args = $args;
		$this->assoc_args = $assoc_args;

		$region_name = $this->args[0];
		$ek_inst = Eve_Kill::init();
		$region_id = $ek_inst->db->get_region_id( $region_name );
		if ( empty( $region_id ) ) {
			$this->log( "Cannot read region $region_name from the database.", 1 );
		}

		$params = array();

		if ( ! isset( $assoc_args['nocache'] ) ) {
			$after_id = $ek_inst->db->get_last_id();
			if ( ! empty( $after_id ) ) {
				$params['afterKillID'] = $after_id;
			}
		}

		if ( isset( $this->assoc_args['nocache'] ) ) {
			$params['nocache'] = true;
		}

		$losses = $ek_inst->zkill->get_losses_by( 'region', $region_id, $params );
		if ( empty( $losses ) ) {
			$this->log( 'No losses', 1 );
		}

		$this->progress_bar( count( $losses ) );
		foreach ( $losses as $loss ) {
			$ek_inst->db->insert_loss( $loss );
			$this->progress_bar( 'tick' );
		}
		$this->progress_bar( 'finish' );
	}

	private function log( $msg, $error = false ) {
		if ( $error ) {
			WP_CLI::error( $msg );
		}

		WP_CLI::warning( $msg );
	}

	private function progress_bar( $param, $action = 'Importing' ) {

		if ( $param && is_numeric( $param ) ) {
			$this->progress_bar = \WP_CLI\Utils\make_progress_bar( "$action $param records", $param );
		} elseif ( $this->progress_bar && 'tick' == $param ) {
			$this->progress_bar->tick();
		} elseif ( $this->progress_bar && 'finish' == $param ) {
			$this->progress_bar->finish();
		}

		return $this->progress_bar;
	}
}

WP_CLI::add_command( 'eve-kill', 'EK_CLI_Import' );
