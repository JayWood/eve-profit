<?php

function get_ek_regions_dropdown() {
	$ek = Eve_Kill::init();

	$regions = $ek->db->get_regions();
	if ( empty( $regions ) ) {
		return;
	}

	$output  = '<label for="eve-region">' . __( 'Select a Region', 'evekill' ) . '</label>';
	$output .= '<select name="eve-region" id="eve-region">';
	foreach ( $regions as $region ) {
		if ( ! isset( $region->regionID ) || ! isset( $region->regionName ) ) {
			continue;
		}
		$output .= '<option value="'.$region->regionID.'">' .$region->regionName. '</option>';
	}
	$output .= '</select>';

	return $output;
}

function ek_regions_dropdown() {
	echo get_ek_regions_dropdown();
}
