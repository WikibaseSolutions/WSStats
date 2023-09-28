<?php

namespace WSStats\Helpers;

use WSStats\WSStatsHooks;

class SelectionMaker {

	/**
	 * @param int $id
	 * @param string $title
	 * @param string|bool $dbType
	 *
	 * @return array
	 */
	public function createSelectionNoDates( int $id, string $title, string|bool $dbType ): array {
		// Set Conditions
		$countSpecialPages = WSStatsHooks::getConfigSetting( 'countSpecialPages' );
		if ( !$dbType ) {
			if ( $countSpecialPages && $title !== '' && $id == "0" ) {
				$selectConditions = [ "title = '" . $title . "'" ];
			} else {
				$selectConditions = [ "page_id = " . $id ];
			}
		} else {
			if ( $countSpecialPages && $title !== '' && $id == "0" ) {
				$selectConditions = [ "title = '" . $title . "'",	$dbType ];
			} else {
				$selectConditions = [ "page_id = " . $id,
					$dbType ];
			}
		}

		return $selectConditions;
	}

	/**
	 * @param int $id
	 * @param string $title
	 * @param string|bool $dbType
	 * @param array $dates
	 *
	 * @return array
	 */
	public function createSelectionUsingDates( int $id, string $title, string|bool $dbType, array $dates ): array {
		$countSpecialPages = WSStatsHooks::getConfigSetting( 'countSpecialPages' );
		if ( $dates['e'] === false ) {
			// Set Conditions
			if ( !$dbType ) {
				if ( $countSpecialPages && $title !== '' && $id == "0" ) {
					$selectConditions = [ "title = '" . $title . "'",
						'added BETWEEN \'' . $dates["b"] . '\' AND NOW()' ];
				} else {
					$selectConditions = [ "page_id = " . $id,
						'added BETWEEN \'' . $dates["b"] . '\' AND NOW()' ];
				}
			} else {
				if ( $countSpecialPages && $title !== '' && $id == "0" ) {
					$selectConditions = [ "title = '" . $title . "'",
						$dbType,
						'added BETWEEN \'' . $dates["b"] . '\' AND NOW()' ];
				} else {
					$selectConditions = [ "page_id = " . $id,
						$dbType,
						'added BETWEEN \'' . $dates["b"] . '\' AND NOW()' ];
				}
			}
			//$sql = 'SELECT page_id, COUNT(' . $cnt . ') AS count FROM ' . $wgDBprefix . 'WSPS WHERE page_id=\'' . $id . '\' ' . $dbType . 'AND added BETWEEN \'' . $dates["b"] . '\' AND NOW()';
		} else {
			// Set Conditions
			if ( !$dbType ) {
				if ( $countSpecialPages && $title !== '' && $id == "0" ) {
					$selectConditions = [ "title = " . $title,
						'added >= \'' . $dates["b"] . '\' AND added <= \'' . $dates['e'] . '\'' ];
				} else {
					$selectConditions = [ "page_id = " . $id,
						'added >= \'' . $dates["b"] . '\' AND added <= \'' . $dates['e'] . '\'' ];
				}
			} else {
				if ( $countSpecialPages && $title !== '' && $id == "0" ) {
					$selectConditions = [ "title = " . $title,
						$dbType,
						'added >= \'' . $dates["b"] . '\' AND added <= \'' . $dates['e'] . '\'' ];
				} else {
					$selectConditions = [ "page_id = " . $id,
						$dbType,
						'added >= \'' . $dates["b"] . '\' AND added <= \'' . $dates['e'] . '\'' ];
				}
			}
		}

		return $selectConditions;
	}

	/**
	 * @param string|bool $startDate
	 * @param string|bool $endDate
	 *
	 * @return array
	 */
	public function setDatesArray( string|bool $startDate, string|bool $endDate ): array {
		$dates = [];
		$dates['b'] = $startDate;
		$dates['e'] = $endDate;

		if ( $dates['b'] !== false && !strpos( $dates['b'], ' ' ) ) {
			$dates['b'] = $dates['b'] . ' 00:00:00';
		}
		if ( $dates['e'] !== false && !strpos( $dates['e'], ' ' ) ) {
			$dates['e'] = $dates['e'] . ' 00:00:00';
		}
		if ( $dates['b'] !== false && WSStatsHooks::validateDate( $dates['b'] ) === false ) {
			$dates['b'] = false;
		}
		if ( $dates['e'] !== false && WSStatsHooks::validateDate( $dates['e'] ) === false ) {
			$dates['e'] = false;
		}
		return $dates;
	}

	/**
	 * @param array $dates
	 *
	 * @return array|false
	 */
	public function checkDates( array $dates ): bool|array {
		if ( $dates['e'] === false && $dates['b'] !== false ) {
			$dates['e'] = false;
		}
		if ( $dates['b'] === false && $dates['e'] !== false ) {
			$dates = false;
		}
		if ( $dates['b'] === false && $dates['e'] === false ) {
			$dates = false;
		}
		return $dates;
	}
}