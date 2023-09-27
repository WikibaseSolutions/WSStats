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
	 * @param array $arguments
	 *
	 * @return array
	 */
	public function wsstats(
		?array $arguments
	): array {

		if ( $arguments === null ) {
			$arguments = [];
		}
		$id = WSStatsHooks::getOptionSetting(
			$arguments,
			'id'
		);

		$title = WSStatsHooks::getOptionSetting(
			$arguments,
			'title'
		);

		if ( $id === false ) {
			$id = 0;
		} else {
			$id = intval( $id );
		}

		if ( $title === false ) {
			$title = '';
		}

		$limit   = WSStatsHooks::getOptionSetting(
			$arguments,
			'limit'
		);
		if ( $limit === false ) {
			$limit = 10;
		}
		$format = 'lua';

		$unique  = WSStatsHooks::getOptionSetting(
			$arguments,
			'unique',
			false
		);
		$selectionMaker = new SelectionMaker();

		$startDate   = WSStatsHooks::getOptionSetting(
			$arguments,
			'startDate'
		);

		$endDate   = WSStatsHooks::getOptionSetting(
			$arguments,
			'endDate'
		);

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
