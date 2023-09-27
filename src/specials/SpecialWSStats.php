<?php

namespace WSStats\specials;

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
			$result = $this->doDatabaseMaintenance();
			$out->addHTML( '<p><strong>' . wfMessage( 'wwstats-special-db-need-update-result', $result ) );
			$out->addHTML( '</strong></p>' );
		}
		$result = $this->getRowsForMaintenance();
		if ( !empty( $result ) ) {
			$out->addWikiMsg( 'wwstats-special-db-need-update' );
			$form = '<form method="post">';
			$form .= '<input type="submit" name="doDBUpdate"';
			$form .= 'value="'. wfMessage( 'wwstats-special-db-need-update-btn', count( $result ) ) . '"></form>';
			$out->addHTML( $form );
		}
		$out->addWikiTextAsContent( WSStatsHooks::getMostViewedPages() );
		//$this->databaseMaintenance();

		return '';
	}


	/**
	* @return array
	 */
	private function getRowsForMaintenance(): array {
	$lb       = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbr      = $lb->getConnectionRef( DB_REPLICA );
		global $wgDBprefix;
		$res = $dbr->select(
			$wgDBprefix . WSStatsHooks::DBTABLE,
			'*',
			[],
			__METHOD__,
			[]
		);
		$result = [];
		if ( $res->numRows() > 0 ) {
			while ( $row = $res->fetchRow() ) {
				if ( $row['page_id'] !== 0 && empty( $row['title'] ) ) {
					$id = $row['id'];
					$result[$id] = WSStatsHooks::getPageTitleFromID( $row['page_id'] );
				}
			}
		}
		return $result;
	}

	/**
	* @return int
	 */
	private function doDatabaseMaintenance(): int {
		$result = $this->getRowsForMaintenance();
		if ( !empty( $result ) ) {
			$lb       = MediaWikiServices::getInstance()->getDBLoadBalancer();
			$dbw      = $lb->getConnectionRef( DB_PRIMARY );
			foreach( $result as $id=>$title ) {
				$dbw->update( WSStatsHooks::DBTABLE, [ 'title' => $title ], [ 'id' => $id ] );
			}
		}
		return count( $result );

	}

}
