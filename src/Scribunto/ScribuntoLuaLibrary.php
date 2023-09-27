<?php

namespace WSStats\Scribunto;

use Error;
use FormatJson;
use JsonContent;
use MediaWiki\MediaWikiServices;
use MWException;
use WSStats\Helpers\SelectionMaker;
use WSStats\WSStatsHooks;

/**
 * Register the Lua library.
 */
class ScribuntoLuaLibrary extends \Scribunto_LuaLibraryBase {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		$interfaceFuncs = [
			'wsstat' => [ $this, 'wsstat' ],
			'wsstats' => [ $this, 'wsstats' ]
		];

		$this->getEngine()->registerInterface( __DIR__ . '/' . 'mw.wsstats.lua', $interfaceFuncs, [] );
	}

	/**
	 * This mirrors the functionality of the #wsstats parser function and makes it available
	 * in Lua. This function will return a table.
	 * @param string|null $id
	 * @param string|null $unique
	 * @param string|null $startDate
	 * @param string|null $endDate
	 * @param string|null $limit
	 * @param string|null $title
	 *
	 * @return array
	 */
	public function wsstats(
		?string $id,
		?string $unique,
		?string $startDate,
		?string $endDate,
		?string $limit,
		?string $title
	): array {
		if ( $id === null ) {
			$id = 0;
		}

		if ( $title === null ) {
			$title = '';
		}
		if ( $limit === null ) {
			$limit = 10;
		}
		$format = 'lua';

		if ( $unique === null ) {
			$unique = false;
		} else {
			$unique = true;
		}
		$selectionMaker = new SelectionMaker();
		if ( $startDate === null ) {
			$startDate = false;
		}
		if ( $endDate === null ) {
			$endDate = false;
		}
		$dates = $selectionMaker->setDatesArray( $startDate,
			$endDate );
		$dates = $selectionMaker->checkDates( $dates );
		$data = WSStatsHooks::getMostViewedPages(
			$dates,
			$format,
			$unique,
			'',
			$limit,
			$id,
			$title
		);

		return [ $this->convertToLuaTable( $data ) ];
	}

	/**
	 * Returns the content model of the specified slot.
	 *
	 * @param string $slotName
	 * @param string|null $pageName
	 * @return array
	 * @throws MWException
	 */
	public function slotContentModel( string $slotName, ?string $pageName = null ): array {
		$wikiPage = $this->getWikiPage( $pageName );

		if ( !$wikiPage ) {
			return [ null ];
		}

		if ( !$this->userCan( $wikiPage ) ) {
			// The user is not allowed to read the page
			return [ null ];
		}

		$contentObject = WSSlots::getSlotContent( $wikiPage, $slotName );

		if ( !$contentObject instanceof TextContent ) {
			return [ null ];
		}

		return [ $contentObject->getModel() ];
	}

	/**
	 * @param WikiPage $wikiPage
	 *
	 * @return bool
	 */
	private function userCan( WikiPage $wikiPage ): bool {
		// Only do a check for user rights when not in cli mode
		if ( PHP_SAPI === 'cli' ) {
			return true;
		}

		return MediaWikiServices::getInstance()->getPermissionManager()->userCan(
			'read',
			RequestContext::getMain()->getUser(),
			$wikiPage->getTitle()
		);
	}

	/**
	 * @param $array
	 * @return mixed
	 */
	private function convertToLuaTable( $array ) {
		if ( is_array( $array ) ) {
			foreach ( $array as $key => $value ) {
				$array[$key] = $this->convertToLuaTable( $value );
			}

			array_unshift( $array, '' );
			unset( $array[0] );
		}

		return $array;
	}
}
