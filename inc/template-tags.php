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

/**
 * Builds child market trees using recursion
 * @param $parent_id
 *
 * @return string
 */
function ek_market_tree_children( $parent_id ) {
	$ek = Eve_Kill::init();

	$market_groups = $ek->db->get_market_group_by_parent( intval( $parent_id ) );
	$output = '';
	if ( ! empty( $market_groups ) ) {
		$output .= '<ul class="market-groups">';
		foreach ( $market_groups as $market_group ) {
			$output .= sprintf( '<li class="group-id-%1$s market-group" data-tip="%2$s"><span class="group-name">%3$s</span>', $market_group->marketGroupID, $market_group->description, $market_group->marketGroupName );
			if ( $ek->db->market_group_has_children( $market_group->marketGroupID ) ) {
				$output .= ek_market_tree_children( $market_group->marketGroupID );
			}
			$output .= '</li>';
		}
		$output .= '</ul>';
	}

	return $output;
}

/**
 * Builds the market tree using recursion
 */
function ek_market_tree() {

	$hash = md5( 'ek-market-tree' );
	$output = get_transient( $hash );

	if ( false == $output || isset( $_GET['delete-trans'] ) ) {
		$ek      = Eve_Kill::init();
		$parents = $ek->db->get_all_parent_market_groups();
		$output  = '';

		if ( ! empty( $parents ) ) {
			$output .= '<ul class="market-groups">';
			foreach ( $parents as $market_group ) {
				$output .= sprintf( '<li class="group-id-%1$s market-group" data-tip="%2$s"><span class="group-name">%3$s</span>', $market_group->marketGroupID, $market_group->description, $market_group->marketGroupName );
				$output .= ek_market_tree_children( $market_group->marketGroupID );
				$output .= '</li>';
			}
			$output .= '</ul>';
		}

		set_transient( $hash, $output, 30 * DAY_IN_SECONDS );
	}

	echo $output;
}