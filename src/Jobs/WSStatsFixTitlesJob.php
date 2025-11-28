<?php
/**
 * Created by  : Wikibase Solutions B.V.
 * Project     : rvs-143
 * Filename    : WSStatsFixTitlesJob.php
 * Description : 
 * Date        : 28-11-2025
 * Time        : 14:09
 */
namespace WSStats\Jobs;

use GenericParameterJob;
use Job;
use MediaWiki\MediaWikiServices;
use WSStats\WSStatsHooks;

class WSStatsFixTitlesJob extends Job implements GenericParameterJob{


	private const BATCH_SIZE = 1000;

	public function __construct( array $params = [] ) {
		parent::__construct( 'wsstatsFixTitles', $params );
	}

	/**
	 * @inheritDoc
	 */
	public function run() {
		$lb  = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbr = $lb->getConnection( DB_REPLICA );
		$dbw = $lb->getConnection( DB_PRIMARY );
		global $wgDBprefix;
		$res = $dbr->select(
			$wgDBprefix . WSStatsHooks::DBTABLE,
			[ 'id', 'page_id' ],
			[
				"page_id != 0",
				"title = ''"
			],
			__METHOD__,
			[ 'LIMIT' => self::BATCH_SIZE ]
		);

		$count = 0;
		while ( $row = $res->fetchRow() ) {
			$count++;
			$title = WSStatsHooks::getPageTitleFromID( (int)$row['page_id'] );
			if ( $title === null ) {
				$dbw->delete( $wgDBprefix . WSStatsHooks::DBTABLE, [ 'id' => $row['id'] ], __METHOD__ );
			} else {
				$dbw->update(
					$wgDBprefix . WSStatsHooks::DBTABLE,
					[ 'title' => $title ],
					[ 'id' => $row['id'] ],
					__METHOD__
				);
			}
		}
		// If we processed a full batch, there might be more. Queue another job.
		if ( $count === self::BATCH_SIZE ) {
			MediaWikiServices::getInstance()
				->getJobQueueGroupFactory()
				->makeJobQueueGroup()
				->push( new self() );
		}
		return true;
	}
}