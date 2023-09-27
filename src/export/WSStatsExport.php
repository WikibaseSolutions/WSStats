<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : WSStats
 * Filename    : WSStatsExport.class.php
 * Description :
 * Date        : 6-4-2021
 * Time        : 13:37
 *
 * @version 0.8.0 2021
 *
 * @author Sen-Sai
 */

namespace WSStats\export;

use Wikimedia\Rdbms\IResultWrapper;
use WSStats\WSStatsHooks, extensionRegistry;

/**
 * Class WSStatsExport
 */
class WSStatsExport {

	/**
	 * @var bool
	 */
	private bool $specialPages = true;

	public function __construct() {
		$this->specialPages = WSStatsHooks::getConfigSetting( 'countSpecialPages' );
	}

	/**
	 * @param IResultWrapper $q
	 * @param int $pId
	 *
	 * @return string
	 */
	public function renderTable( IResultWrapper $q, int $pId ): string {
		$data = "{| class=\"sortable wikitable smwtable jquery-tablesorter\"\n";
		if ( $pId !== 0 ) {
			$data .= "! " . wfMessage( 'wsstats-page-date' )->text() . "\n";
			$data .= "! " . wfMessage( 'wsstats-page-hits' )->text() . "\n";
		} else {
			$data .= "! " . wfMessage( 'wsstats-page-id' )->text() . "\n";
			$data .= "! " . wfMessage( 'wsstats-page-title' )->text() . "\n";
			$data .= "! " . wfMessage( 'wsstats-page-hits' )->text() . "\n";
		}
		while ( $row = $q->fetchRow() ) {
			if ( $row['title'] === '' ) {
				$pTitle = WSStatsHooks::getPageTitleFromID( $row['page_id'] );
			} else {
				$pTitle = $row['title'];
			}
			if ( !is_null( $pTitle ) ) {
				$data .= "|-\n";
				if ( $pId !== 0 ) {
					$data .= "| " . $row['Date'] . "\n";
					$data .= "| " . $row['count'] . "\n";
				} else {
					if ( $row['isSpecialPage'] != "1" ) {
						$data .= "| " . $row['page_id'] . "\n";
					} else {
						$data .= "| \n";
					}
					$data .= "| " . $pTitle . "\n";
					$data .= "| " . $row['count'] . "\n";
				}
				$data .= "|-\n";
			}
		}
		$data .= "|}\n";

		return $data;
	}

	/**
	 * @param IResultWrapper $q
	 * @param int $pId
	 *
	 * @return string
	 */
	public function renderCSV( IResultWrapper $q, $pId ): string {
		$data = '';
		if ( $pId === 0 ) {
			while ( $row = $q->fetchRow() ) {
				if ( $row['page_id'] == '0' ) {
					$data .= ";" . $row['title'] . $row['count'] . ",";
				} else {
					$data .= $row['page_id'] . ";" . $row['title'] . $row['count'] . ",";
				}
			}
		} else {
			while ( $row = $q->fetchRow() ) {
				$data .= $row['Date'] . ";" . $row['count'] . ",";
			}
		}

		return rtrim(
			$data,
			','
		);
	}

	/**
	 * @param IResultWrapper $q
	 * @param int $pId
	 *
	 * @return array
	 */
	public function renderLua( IResultWrapper $q, $pId ): array {
		$result    = [];
		$t         = 0;
		while ( $row = $q->fetchRow() ) {
			$pTitle = WSStatsHooks::getPageTitleFromID( $row['page_id'] );
			if ( $pTitle !== null ) {
				if ( $pId === 0 ) {
					$result[$t][wfMessage( 'wsstats-page-id' )->text()] = $row['page_id'];
					$result[$t][wfMessage( 'wsstats-page-title' )->text()] = $pTitle;
					$result[$t][wfMessage( 'wsstats-page-hits' )->text()] = $row['count'];
				} else {
					$result[$t][wfMessage( 'wsstats-page-date' )->text()] = $row['Date'];
					$result[$t][wfMessage( 'wsstats-page-hits' )->text()] = $row['count'];
				}
				$t++;
			}
		}
		return $result;
	}

	/**
	 * @param string $name Name of the extension
	 *
	 * @return mixed
	 */
	private function extensionInstalled( string $name ) {
		return extensionRegistry::getInstance()->isLoaded( $name );
	}

	/**
	 * @param mysqli_result $q
	 * @param string $wsArrayVariableName
	 * @param int $pId
	 *
	 * @return string
	 */
	public function renderWSArrays(
		IResultWrapper $q,
		string $wsArrayVariableName,
		int $pId
	): string {
		global $IP;
		if ( !$this->extensionInstalled( 'WSArrays' ) ) {
			return "";
		}
		if ( file_exists( $IP . '/extensions/WSArrays/ComplexArrayWrapper.php' ) ) {
			include_once( $IP . '/extensions/WSArrays/ComplexArrayWrapper.php' );
		} else {
			return "";
		}
		$wsWrapper = new \ComplexArrayWrapper();
		$result    = [];
		$t         = 0;
		while ( $row = $q->fetchRow() ) {
			$pTitle = WSStatsHooks::getPageTitleFromID( $row['page_id'] );
			if ( $pTitle !== null ) {
				if ( $pId === 0 ) {
					$result[$t][wfMessage( 'wsstats-page-id' )->text()] = $row['page_id'];
					$result[$t][wfMessage( 'wsstats-page-title' )->text()] = $pTitle;
					$result[$t][wfMessage( 'wsstats-page-hits' )->text()] = $row['count'];
				} else {
					$result[$t][wfMessage( 'wsstats-page-date' )->text()] = $row['Date'];
					$result[$t][wfMessage( 'wsstats-page-hits' )->text()] = $row['count'];
				}
				$t++;
			}
		}

		$wsWrapper->on( $wsArrayVariableName )->set( $result );

		return "";
	}

}