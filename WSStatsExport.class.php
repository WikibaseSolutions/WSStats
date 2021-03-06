<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : WSStats
 * Filename    : WSStatsExport.class.php
 * Description :
 * Date        : 6-4-2021
 * Time        : 13:37
 * @version 0.8.0 2021
 *
 * @author Sen-Sai
 */

/**
 * Class WSStatsExport
 */
class WSStatsExport {

	/**
	 * @param mysqli_result $q
	 *
	 * @return string
	 */
	public function renderTable( mysqli_result $q ): string {
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

	/**
	 * @param mysqli_result $q
	 *
	 * @return string
	 */
	public function renderCSV( mysqli_result $q ): string {
		$data = '';
		while ( $row = $q->fetch_assoc() ) {
			$data .= $row['page_id'] . ";" . $row['count'] . ",";
		}
		$data = rtrim( $data, ',' );
		return $data;
	}

}