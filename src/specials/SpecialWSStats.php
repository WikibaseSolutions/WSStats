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
		$out->addWikiTextAsContent( WSStatsHooks::getMostViewedPages() );
		//$this->databaseMaintenance();

		return '';
	}

	private function databaseMaintenance() {
		// TODO: Make this a function to be called from the Special page!
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


		if ( !empty( $result ) ) {
			$dbw      = $lb->getConnectionRef( DB_PRIMARY );
			foreach( $result as $id=>$title ) {
				$dbw->update( WSStatsHooks::DBTABLE, [ 'title' => $title ], [ 'id' => $id ] );
			}
		}

	}

}
