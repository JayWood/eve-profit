<?php

// A library for reading zkillboard data

require_once 'inc/eve-db.php';
require_once 'inc/zkillboard-api.php';
function jay() {
	if ( ! isset( $_GET['eve'] ) ) { return; }

	$eve = new Eve_DB();
	$zkill = new ZKillboard();

	$region_id = $eve->get_region_id( 'The Bleak Lands' );
	$losses = $zkill->get_losses_by( 'region', $region_id, array(
		'limit' => 10,
	) );

//	error_log( print_r( $losses, 1 ) );
}
add_action( 'init', 'jay' );
