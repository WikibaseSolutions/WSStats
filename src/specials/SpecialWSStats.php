<?php

namespace WSStats\specials;

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

		return '';
	}

}
