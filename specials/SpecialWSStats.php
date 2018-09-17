<?php
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
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 *  [[Special:HelloWorld/subpage]].
	 */
	public function execute( $sub ) {

        $out = $this->getOutput();
				$out->setPageTitle( "WSStats" );
        return;
	}

}
