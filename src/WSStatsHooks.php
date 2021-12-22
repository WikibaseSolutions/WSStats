<?php
/**
 * WSStatsHooks
 *
 *
 * @license https://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 *
 */

namespace WSStats;

use Parser, Title, ALTree, OutputPage, Skin, WSStats\export\WSStatsExport, MediaWiki\MediaWikiServices;

if ( ! defined( 'MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

/**
 * Class WSStatsHooks
 */
class WSStatsHooks {

	const DBTABLE = 'WSPS';


	/**
	 * WSStatsHooks constructor.
	 */
	public function __construct() {
		date_default_timezone_set( 'UTC' );
	}

	/**
	 * @return bool
	 */
	public static function isAnon() {
		global $wgUser;

		return $wgUser->isAnon();
	}


	/**
	 * @param int $id
	 *
	 * @return mixed
	 */
	public static function getPageTitleFromID( $id ) {
		$title = Title::newFromID( $id );
		if ( is_null( $title ) ) {
			return null;
		}

		return $title->getFullText();
	}

	/**
	 * Implements AdminLinks hook from Extension:Admin_Links.
	 *
	 * @param ALTree &$adminLinksTree
	 *
	 * @return bool
	 */
	public static function addToAdminLinks( ALTree &$adminLinksTree ) {
		global $wgServer;
		$wsSection = $adminLinksTree->getSection( 'WikiBase Solutions' );
		if ( is_null( $wsSection ) ) {
			$section = new ALSection( 'WikiBase Solutions' );
			$adminLinksTree->addSection(
				$section,
				wfMessage( 'adminlinks_general' )->text()
			);
			$wsSection     = $adminLinksTree->getSection( 'WikiBase Solutions' );
			$extensionsRow = new ALRow( 'extensions' );
			$wsSection->addRow( $extensionsRow );
		}

		$extensionsRow = $wsSection->getRow( 'extensions' );

		if ( is_null( $extensionsRow ) ) {
			$extensionsRow = new ALRow( 'extensions' );
			$wsSection->addRow( $extensionsRow );
		}
		$extensionsRow->addItem(
			ALItem::newFromExternalLink(
				$wgServer . '/index.php/Special:WSStats',
				'WS Statistics'
			)
		);

		return true;
	}

	/**
	 * When running maintenance update with will add the database tables
	 *
	 * @param [type] $updater [description]
	 */
	public static function addTables( $updater ) {
		$dbt = $updater->getDB()->getType();
		// If using SQLite, just use the MySQL/MariaDB schema, it's compatible
		// anyway. Only PGSQL and some more exotic variants need a totally
		// different schema.
		if ( $dbt === 'sqlite' ) {
			$dbt = 'sql';
		}
		$tables = __DIR__ . "/sql/WSStats.$dbt";

		if ( file_exists( $tables ) ) {
			$updater->addExtensionUpdate( array(
											  'addTable',
											  'WSPS',
											  $tables,
											  true
										  ) );
		} else {
			throw new MWException( "WSStats does not support $dbt." );
		}

		return true;
	}


	/**
	 * @param int $id
	 * @param array|false $dates
	 * @param string|false $type
	 * @param bool $unique
	 *
	 * @return int|mixed
	 */
	public static function getViewsPerPage( int $id, $dates = false, $type = false, bool $unique = false ) {
		global $wgDBprefix;
		switch ( $type ) {
			case "only anonymous":
				$dbType = "user_id = 0 ";
				break;
			case "only user":
				$dbType = "user_id <> 0 ";
				break;
			default:
				$dbType = false;
		}
		$cnt = '*';
		if ( $unique ) {
			$cnt = 'DISTINCT(user_id)';
		}

		$lb               = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbr              = $lb->getConnectionRef( DB_REPLICA );
		$dbResult         = array();
		$selectWhat       = [
			'page_id',
			"count" => 'COUNT(' . $cnt . ')'
		];
		$selectOptions    = [
			'GROUP BY' => 'page_id',
			'ORDER BY' => 'count DESC',
			'LIMIT'    => 1
		];
		$selectConditions = array();

		if ( $dates === false ) {
			// Set Conditions
			if ( ! $dbType ) {
				$selectConditions = [
					"page_id = " . $id
				];
			} else {
				$selectConditions = [
					"page_id = " . $id,
					$dbType
				];
			}
			//$sql = 'SELECT page_id, COUNT(' . $cnt . ') AS count FROM ' . $wgDBprefix . 'WSPS WHERE page_id=\'' . $id . '\' ' . $dbType . 'GROUP BY page_id ORDER BY count DESC LIMIT 1';
		} else {
			if ( $dates['e'] === false ) {
				// Set Conditions
				if ( ! $dbType ) {
					$selectConditions = [
						"page_id = " . $id,
						'added BETWEEN \'' . $dates["b"] . '\' AND NOW()'
					];
				} else {
					$selectConditions = [
						"page_id = " . $id,
						$dbType,
						'added BETWEEN \'' . $dates["b"] . '\' AND NOW()'
					];
				}
				//$sql = 'SELECT page_id, COUNT(' . $cnt . ') AS count FROM ' . $wgDBprefix . 'WSPS WHERE page_id=\'' . $id . '\' ' . $dbType . 'AND added BETWEEN \'' . $dates["b"] . '\' AND NOW()';
			} else {
				// Set Conditions
				if ( ! $dbType ) {
					$selectConditions = [
						"page_id = " . $id,
						'added >= \'' . $dates["b"] . '\' AND added <= \'' . $dates['e'] . '\''
					];
				} else {
					$selectConditions = [
						"page_id = " . $id,
						$dbType,
						'added >= \'' . $dates["b"] . '\' AND added <= \'' . $dates['e'] . '\''
					];
				}
				//$sql      = 'SELECT page_id, COUNT(' . $cnt . ') AS count FROM ' . $wgDBprefix . 'WSPS WHERE page_id=\'' . $id . '\' ' . $dbType . 'AND added >= \'' . $dates["b"] . '\' AND added <= \'' . $dates['e'] . '\' GROUP BY page_id ORDER BY COUNT DESC LIMIT 1';
			}
		}

		$res      = $dbr->select(
			$wgDBprefix . 'WSPS',
			$selectWhat,
			$selectConditions,
			__METHOD__,
			$selectOptions
		);
		$dbResult = $res->fetchRow();
		if ( ! isset( $dbResult['count'] ) || empty( $dbResult['count'] ) ) {
			return 0;
		} else {
			return $dbResult['count'];
		}
	}

	/**
	 * @param array|false $dates
	 * @param string $render
	 * @param bool $unique
	 * @param string $variable
	 * @param int $limit
	 *
	 * @return string
	 */
	public static function getMostViewedPages(
		$dates = false,
		string $render = "table",
		bool $unique = false,
		string $variable = "",
		int $limit = 10
	) : string {
		global $wgDBprefix;

		$cnt = '*';
		if ( $unique ) {
			$cnt = 'DISTINCT(user_id)';
		}

		$lb       = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbr      = $lb->getConnectionRef( DB_REPLICA );
		$dbResult = array();

		$selectWhat       = [
			'page_id',
			"count" => 'COUNT(' . $cnt . ')'
		];
		$selectOptions    = [
			'GROUP BY' => 'page_id',
			'ORDER BY' => 'count DESC',
			'LIMIT'    => $limit
		];
		$selectConditions = array();

		if ( $dates === false ) {
			//$sql = 'SELECT page_id, COUNT(' . $cnt . ') AS count FROM ' . $wgDBprefix . 'WSPS GROUP BY page_id ORDER BY count DESC LIMIT ' . $limit;
		} else {
			if ( $dates['e'] === false ) {
				$selectConditions = [
					'added BETWEEN \'' . $dates["b"] . '\' AND AND NOW()'
				];
				//$sql = 'SELECT page_id, COUNT(' . $cnt . ') AS count FROM ' . $wgDBprefix . 'WSPS WHERE added BETWEEN \'' . $dates["b"] . '\' AND NOW() GROUP BY page_id ORDER BY count DESC LIMIT ' . $limit;
			} else {
				$selectConditions = [
					'added >= \'' . $dates["b"] . '\' AND added <= \'' . $dates['e'] . '\''
				];
				//$sql = 'SELECT page_id, COUNT(' . $cnt . ') AS count FROM ' . $wgDBprefix . 'WSPS WHERE added >= \'' . $dates["b"] . '\' AND added <= \'' . $dates['e'] . '\' GROUP BY page_id ORDER BY COUNT DESC LIMIT ' . $limit;
			}
		}

		$res  = $dbr->select(
			$wgDBprefix . 'WSPS',
			$selectWhat,
			$selectConditions,
			__METHOD__,
			$selectOptions
		);
		$data = "";
		if ( $res->numRows() > 0 ) {
			$renderMethod = new WSStatsExport();
			switch ( $render ) {
				case "table":
					$data = $renderMethod->renderTable( $res );
					break;
				case "csv":
					$data = $renderMethod->renderCSV( $res );
					break;
				case "wsarrays":
					$data = $renderMethod->renderWSArrays(
						$res,
						$variable
					);
					break;
				default:
					$data = "";
			}
		}

		return $data;
	}

	/**
	 * @param array $options
	 * @param string $k
	 * @param bool $checkEmpty
	 *
	 * @return bool|mixed
	 */
	public static function getOptionSetting( array $options, string $k, bool $checkEmpty = true ) {
		if ( $checkEmpty ) {
			if ( isset( $options[$k] ) && $options[$k] != '' ) {
				return $options[$k];
			} else {
				return false;
			}
		} else {
			if ( isset( $options[$k] ) ) {
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser &$parser ) {
		$parser->setFunctionHook(
			'wsstats',
			'WSStats\WSStatsHooks::wsstats'
		);
	}

	/**
	 * @return bool
	 */
	private static function countAllUserGroups() : bool {
		global $wgUser, $wgWSStats;
		if ( $wgWSStats['count_all_usergroups'] !== true ) {
			if ( isset( $wgWSStats['skip_user_groups'] ) && is_array( $wgWSStats['skip_user_groups'] ) ) {
				$groups = $wgWSStats['skip_user_groups'];
				foreach ( $groups as $group ) {
					if ( in_array(
						$group,
						$wgUser->getGroups()
					) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * @param string|bool $ref
	 *
	 * @return bool
	 */
	private static function ignoreInUrl( $ref ) : bool {
		global $wgWSStats;
		if ( isset( $wgWSStats['ignore_in_url'] ) && is_array( $wgWSStats['ignore_in_url'] ) && $ref !== false ) {
			$ignore = $groups = $wgWSStats['ignore_in_url'];
			foreach ( $ignore as $single ) {
				if ( strpos(
						 $ref,
						 $single
					 ) !== false ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	private static function removeDeletePages() : bool {
		global $wgWSStats;
		if ( $wgWSStats['remove_deleted_pages_from_stats'] === true ) {
			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	private static function skipAnonymous() : bool {
		global $wgUser, $wgWSStats;
		if ( isset( $wgWSStats['skip_anonymous'] ) && $wgWSStats['skip_anonymous'] === true ) {
			if ( $wgUser->isAnon() ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * @param outputPage $output
	 * @param Skin $skin
	 *
	 * @return bool
	 */
	public static function onBeforePageDisplay( outputPage &$output, Skin &$skin ) : bool {
		global $wgUser;

		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			$ref = $_SERVER['HTTP_REFERER'];
		} else {
			$ref = false;
		}

		if ( self::countAllUserGroups() ) {
			return true;
		}
		if ( self::ignoreInUrl( $ref ) ) {
			return true;
		}
		if ( self::skipAnonymous() ) {
			return true;
		}

		if ( $wgUser->isAnon() ) {
			$data['user_id'] = 0;
		} else {
			$data['user_id'] = $wgUser->getID();
		}
		$title = $output->getTitle();

		if ( $title === null ) {
			return true;
		}
		$data['page_id'] = $title->getArticleID();
		if ( $data['page_id'] != 0 ) {
			WSStatsHooks::insertRecord(
				'WSPS',
				$data
			);
		}

		return true;
	}

	/**
	 * @param Parser $parser
	 *
	 * @return int|mixed|string
	 */
	public static function wsstats( Parser &$parser ) {
		$options = WSStatsHooks::extractOptions(
			array_slice(
				func_get_args(),
				1
			)
		);
		$unique  = WSStatsHooks::getOptionSetting(
			$options,
			'unique',
			false
		);
		$limit   = WSStatsHooks::getOptionSetting(
			$options,
			'limit'
		);
		if ( false === $limit ) {
			$limit = 10;
		}
		if ( isset( $options['stats'] ) ) {
			$dates       = array();
			$wsArrayName = "";
			$format      = WSStatsHooks::getOptionSetting(
				$options,
				'format'
			);

			if ( $format === false ) {
				$format = 'table';
			}
			if ( strtolower( $format ) === 'wsarrays' ) {
				$wsArrayName = WSStatsHooks::getOptionSetting(
					$options,
					'name'
				);
				if ( false === $wsArrayName ) {
					$format = 'table';
				}
			}
			$dates['b'] = WSStatsHooks::getOptionSetting(
				$options,
				'start date'
			);
			$dates['e'] = WSStatsHooks::getOptionSetting(
				$options,
				'end date'
			);
			if ( $dates['e'] === false && $dates['b'] !== false ) {
				$dates['e'] = false;
			}
			if ( $dates['b'] === false && $dates['e'] !== false ) {
				$dates = false;
			}
			if ( $dates['b'] === false && $dates['e'] === false ) {
				$dates = false;
			}
			$data = WSStatsHooks::getMostViewedPages(
				$dates,
				$format,
				$unique,
				$wsArrayName,
				$limit
			);

			return $data;
		}
		$pid = WSStatsHooks::getOptionSetting(
			$options,
			'id'
		);
		if ( $pid !== false ) {
			$type       = WSStatsHooks::getOptionSetting(
				$options,
				'type'
			);
			$dates      = array();
			$dates['b'] = WSStatsHooks::getOptionSetting(
				$options,
				'start date'
			);
			$dates['e'] = WSStatsHooks::getOptionSetting(
				$options,
				'end date'
			);
			if ( $dates['e'] === false && $dates['b'] !== false ) {
				$dates['e'] = false;
			}
			if ( $dates['b'] === false && $dates['e'] !== false ) {
				$dates = false;
			}
			if ( $dates['b'] === false && $dates['e'] === false ) {
				$dates = false;
			}
			$data = WSStatsHooks::getViewsPerPage(
				$pid,
				$dates,
				$type,
				$unique
			);
			if ( $data !== null ) {
				return $data;
			} else {
				return "";
			}
		}

		return "ok, move along. Nothing to see here..";
	}

	private static function deleteRecord( $table, $pId ) : bool {
		$dbw               = wfGetDB( DB_MASTER );
		$dbw->IngoreErrors = true;
		try {
			$res = $dbw->delete(
				$table,
				"page_id = " . $pId,
				__METHOD__
			);
		} catch ( Exception $e ) {
			echo $e;

			return false;
		}

		if ( $res ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param string $table
	 * @param array $vals
	 *
	 * @return bool
	 */
	public static function insertRecord( string $table, array $vals ) : bool {
		$dbw               = wfGetDB( DB_MASTER );
		$dbw->IngoreErrors = true;
		try {
			$res = $dbw->insert(
				$table,
				$vals,
				__METHOD__
			);
		} catch ( Exception $e ) {
			echo $e;

			return false;
		}

		if ( $res ) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * Converts an array of values in form [0] => "name=value" into a real
	 * associative array in form [name] => value. If no = is provided,
	 * true is assumed like this: [name] => true
	 *
	 * @param array string $options
	 *
	 * @return array $results
	 */
	public static function extractOptions( array $options ) {
		$results = array();
		foreach ( $options as $option ) {
			$pair = explode(
				'=',
				$option,
				2
			);
			if ( $pair[0] !== '//' ) {
				if ( count( $pair ) === 2 ) {
					$name = strtolower( trim( $pair[0] ) );
					if ( $name == 'template' ) {
						$value = trim( $pair[1] );
					} else {
						$value = strtolower( trim( $pair[1] ) );
					}

					$results[$name] = $value;
				}
				if ( count( $pair ) === 1 ) {
					$name           = trim( $pair[0] );
					$results[$name] = true;
				}
			}
		}

		return $results;
	}

}
