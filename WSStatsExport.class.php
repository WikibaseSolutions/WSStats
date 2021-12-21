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
		$data .= "! " . wfMessage( 'wsstats-page-id' )->text() . "\n";
		$data .= "! " . wfMessage( 'wsstats-page-title' )->text() . "\n";
		$data .= "! " . wfMessage( 'wsstats-page-hits' )->text() . "\n";
		while ( $row = $q->fetch_assoc() ) {
			$pTitle = WSStatsHooks::getPageTitleFromID( $row['page_id'] );
			if( ! is_null( $pTitle ) ) {
				$data .= "|-\n";
				$data .= "| " . $row['page_id'] . "\n";
				$data .= "| " . $pTitle . "\n";
				$data .= "| " . $row['count'] . "\n";
				$data .= "|-\n";
			}
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

	/**
	 * @param string $name Name of the extension
	 *
	 * @return mixed
	 */
	private function extensionInstalled ( $name ) {
		return extensionRegistry::getInstance()->isLoaded( $name );
	}


	/**
	 * @param mysqli_result $q
	 * @param string $wsArrayVariableName
	 *
	 * @return string
	 */
	public function renderWSArrays( mysqli_result $q, string $wsArrayVariableName ): string {
		if( ! $this->extensionInstalled( 'WSArrays' ) || ! class_exists( 'ComplexArrayWrapper' ) ) {
			return "";
		}
		$wsWrapper = new ComplexArrayWrapper();
		$result = array();
		$t = 0;
		while ( $row = $q->fetch_assoc() ) {
			$pTitle = WSStatsHooks::getPageTitleFromID( $row['page_id'] );
			if( !is_null( $pTitle ) ) {
				$result[$t][wfMessage( 'wsstats-page-id' )->text()] = $row['page_id'];
				$result[$t][wfMessage( 'wsstats-page-title' )->text()] = $pTitle;
				$result[$t][wfMessage( 'wsstats-page-hits' )->text()] = $row['count'];
				$t++;
			}
		}
		$wsWrapper->on( $wsArrayVariableName )->set( $result );
		return "";
	}

}