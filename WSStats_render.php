<?php
/**
 * Created by  : Designburo.nl
 * Project     : WSStats
 * Filename    : WSStats_render.php
 * Description :
 * Date        : 3-4-2021
 * Time        : 21:09
 */

class WSStats_render {

	public static function renderTable( $q ){
		$data = "{| class=\"sortable wikitable smwtable jquery-tablesorter\"\n";
		$data .= "! Page ID\n";
		$data .= "! Page Title\n";
		$data .= "! Page hits\n";
		while ( $row = $q->fetch_assoc() ) {
			$data .= "|-\n";
			$data .= "| " . $row['page_id'] . "\n";
			$data .= "| " . WSStatsHooks::getPageTitleFromID( $row['page_id'] ) . "\n";
			$data .= "| " . $row['count'] . "\n";
			$data .= "|-\n";
		}
		$data .= "|}\n";
		return $data;
	}

	public static function renderCSV( $q ){
		$data = '';
		while ( $row = $q->fetch_assoc() ) {
			$data .= $row['page_id'] . ";" . $row['count'] . ",";
		}
		$data = rtrim( $data, ',' );
		return $data;
	}

}