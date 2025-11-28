<?php

namespace WSStats\specials;

use WSStats\Jobs\WSStatsFixTitlesJob;
use MediaWiki\MediaWikiServices;
use SpecialPage;
use WSStats\WSStatsHooks;

/**
 * Overview for the WSStats extension
 *
 * @file
 * @ingroup Extensions
 */
class SpecialWSStats extends SpecialPage {
	public function __construct() {
		parent::__construct( 'WSStats' );
	}

	/**
	 * @param null|string $sub
	 *
	 * @return string
	 */
	public function execute( $sub ) {
		$out = $this->getOutput();
		$out->setPageTitle( "WSStats" );
		$out->addWikiMsg( 'wsstats-special-list' );

		if ( isset ( $_POST['doDBUpdate'] ) ) {
			MediaWikiServices::getInstance()
				->getJobQueueGroupFactory()
				->makeJobQueueGroup()
				->push( new WSStatsFixTitlesJob() );
			$out->addHTML( '<p><strong>' . wfMessage( 'wsstats-special-db-need-update-result' ) );
			$out->addHTML( '</strong></p>' );
		} else {
			$result = $this->getRowCountForMaintenance();
			if ( $result !== 0 ) {
				$out->addWikiMsg( 'wsstats-special-db-need-update' );
				$form = '<form method="post">';
				$form .= '<input type="submit" name="doDBUpdate"';
				$form .= 'value="' . wfMessage( 'wsstats-special-db-need-update-btn', $result ) . '"></form>';
				$out->addHTML( $form );
			}
		}
		$out->addWikiTextAsContent( WSStatsHooks::getMostViewedPages() );
		return '';
	}


	/**
	* @return int
	 */
	private function getRowCountForMaintenance(): int {
		$lb  = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbr = $lb->getConnection( DB_REPLICA );
		global $wgDBprefix;
		$selectConditions[] = "page_id != 0";
		$selectConditions[] = "title = ''";
		$res = $dbr->select(
			$wgDBprefix . WSStatsHooks::DBTABLE,
			[ "cnt" => 'COUNT(*)' ],
			$selectConditions,
			__METHOD__,
			[]
		);
		$count = (int)$res->fetchRow()['cnt'];
		if ( $count > 0 ) {
			return $count;
		} else {
			return 0;
		}
	}
}
