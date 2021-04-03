<?php
/**
 * WSStatsHooks
 *
 *
 * @license https://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 *
 */

if ( ! defined( 'MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

class WSStatsHooks {

	public static $db_prefix = "";

	public static function init() {
		date_default_timezone_set( 'UTC' );
		global $wgDBprefix;
		WSStatsHooks::$db_prefix = $wgDBprefix;
	}

	public static function isAnon() {
		global $wgUser;
		return $wgUser->isAnon();
	}

	public static function db_open() {
		global $wgDBserver;
		global $wgDBname;
		global $wgDBuser;
		global $wgDBpassword;

		$conn = new MySQLi( $wgDBserver, $wgDBuser, $wgDBpassword, $wgDBname );
		$conn->set_charset( "utf8" );

		return $conn;
	}

	public static function db_real_escape( $txt ) {
		$db  = WSStatsHooks::db_open();
		$txt = $db->real_escape_string( $txt );
		$db->close();

		return $txt;

	}

	public static function getPageTitleFromID( $id ) {
		$artikel = Article::newFromId( $id );
		$title   = $artikel->getTitle();
		$furl    = $title->getFullText();

		return $furl;
	}


	/**
	 * When running maintenance update witch will add the database tables
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
		$tables = __DIR__ . "/WSStats.$dbt";

		if ( file_exists( $tables ) ) {
			$updater->addExtensionUpdate( array( 'addTable', 'WSPS', $tables, true ) );
		} else {
			throw new MWException( "WSStats does not support $dbt." );
		}

		return true;
	}

	public static function getViewsPerPage( $id, $dates = false, $type = false ) {
		if ( $type === 'only anonymous' ) {
			$type = " AND user_id = 0 ";
		}
		if ( $type === 'only user' ) {
			$type = " AND user_id <> 0 ";
		}
		if ( $type === false ) {
			$type = '';
		}
		if ( $dates === false ) {
			$sql = 'SELECT page_id, COUNT(*) AS count FROM ' . WSStatsHooks::$db_prefix . 'WSPS WHERE page_id=\'' . $id . '\' ' . $type . 'GROUP BY page_id ORDER BY count DESC LIMIT 1';
		} else {
			if ( $dates['e'] === false ) {
				$sql = 'SELECT page_id, COUNT(*) AS count FROM ' . WSStatsHooks::$db_prefix . 'WSPS WHERE page_id=\'' . $id . '\' ' . $type . 'AND added BETWEEN \'' . $dates["b"] . '\' AND NOW()';
			} else {
				$sql = 'SELECT page_id, COUNT(*) AS count FROM ' . WSStatsHooks::$db_prefix . 'WSPS WHERE page_id=\'' . $id . '\' ' . $type . 'AND added >= \'' . $dates["b"] . '\' AND added <= \'' . $dates['e'] . '\' GROUP BY page_id ORDER BY COUNT DESC LIMIT 1';
			}
		}

		$db = WSStatsHooks::db_open();
		$q  = $db->query( $sql );
		if ( $q === false ) {
			return 0;
		}
		$row = $q->fetch_assoc();
		if ( ! isset( $row['count'] ) || empty( $row['count'] ) ) {
			return 0;
		} else {
			return $row['count'];
		}
	}

	public static function getMostViewedPages( $dates = false, $render = "table" ) {

		if ( $dates === false ) {
			$sql = 'SELECT page_id, COUNT(*) AS count FROM ' . WSStatsHooks::$db_prefix . 'WSPS GROUP BY page_id ORDER BY count DESC LIMIT 10';
		} else {
			if ( $dates['e'] === false ) {
				$sql = 'SELECT page_id, COUNT(*) AS count FROM ' . WSStatsHooks::$db_prefix . 'WSPS WHERE added BETWEEN \'' . $dates["b"] . '\' AND NOW() GROUP BY page_id ORDER BY count DESC LIMIT 10';
			} else {
				$sql = 'SELECT page_id, COUNT(*) AS count FROM ' . WSStatsHooks::$db_prefix . 'WSPS WHERE added >= \'' . $dates["b"] . '\' AND added <= \'' . $dates['e'] . '\' GROUP BY page_id ORDER BY COUNT DESC LIMIT 10';
			}
		}

		$data = '';
		$db = WSStatsHooks::db_open();
		$q  = $db->query( $sql );
		if ( $q->num_rows > 0 ) {
			switch( $render ) {
				case "table":
					$data = WSStats_render::renderTable( $q );
					$db->close();
					break;
				case "csv":
					$data = WSStats_render::renderCSV( $q );
					$db->close();
					break;
			}
		}
		return $data;
	}

	public static function getOptionSetting( $options, $k ) {
		if ( isset( $options[ $k ] ) && $options[ $k ] !== '' ) {
			return $options[ $k ];
		} else {
			return false;
		}
	}

	public static function onParserFirstCallInit( Parser &$parser ) {
		$parser->setFunctionHook( 'wsstats', 'WSStatsHooks::wsstats' );
	}


	private static function countAllUserGroups(){
		global $wgWSStats, $wgUser;
		if ( $wgWSStats['count_all_usergroups'] !== true ) {
			if ( isset( $wgWSStats['skip_user_groups'] ) && is_array( $wgWSStats['skip_user_groups'] ) ) {
				$groups = $wgWSStats['skip_user_groups'];
				foreach ( $groups as $group ) {
					if ( in_array( $group, $wgUser->getGroups() ) ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	private static function ignoreURL( $ref ){
		global $wgWSStats;
		if ( isset( $wgWSStats['ignore_in_url'] ) && is_array( $wgWSStats['ignore_in_url'] ) && $ref !== false ) {
			$ignore = $wgWSStats['ignore_in_url'];
			foreach ( $ignore as $single ) {
				if ( strpos( $ref, $single ) !== false ) {
					return true;
				}
			}
		}
		return false;
	}

	private static function skipAnonymous(){
		global $wgWSStats, $wgUser;
		if ( isset( $wgWSStats['skip_anonymous'] ) && $wgWSStats['skip_anonymous'] === true ) {
			if ( $wgUser->isAnon() ) {
				return true;
			}
		}
		return false;
	}

	public static function onBeforePageDisplay( outputPage &$output, Skin &$skin ) {
		global $wgUser, $wgWSStats;

		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			$ref = $_SERVER['HTTP_REFERER'];
		} else {
			$ref = false;
		}

		if( WSStatsHooks::countAllUserGroups() === true ) return true;

		if( WSStatsHooks::ignoreURL( $ref ) === true ) return true;

		if( WSStatsHooks::skipAnonymous() === true ) return true;

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
			WSStatsHooks::insertRecord( 'WSPS', $data );
		}

		return true;

	}

	private static function parserStats( $options ){
		$dates  = array();
		$format = WSStatsHooks::getOptionSetting( $options, 'format' );
		if ( $format === false ) {
			$format = 'table';
		}
		$dates['b'] = WSStatsHooks::getOptionSetting( $options, 'start date' );
		$dates['e'] = WSStatsHooks::getOptionSetting( $options, 'end date' );
		if ( $dates['e'] === false && $dates['b'] !== false ) {
			$dates['e'] = false;
		}
		if ( $dates['b'] === false && $dates['e'] !== false ) {
			$dates = false;
		}
		if ( $dates['b'] === false && $dates['e'] === false ) {
			$dates = false;
		}
		$data = WSStatsHooks::getMostViewedPages( $dates, $format );

		return $data;
	}

	public static function wsstats( Parser &$parser ) {
		$options = WSStatsHooks::extractOptions( array_slice( func_get_args(), 1 ) );
		if ( isset( $options['stats'] ) ) {
			return WSStatsHooks::parserStats( $options );
		}
		$pid = WSStatsHooks::getOptionSetting( $options, 'id' );
		if ( $pid !== false ) {
			$type       = WSStatsHooks::getOptionSetting( $options, 'type' );
			$dates      = array();
			$dates['b'] = WSStatsHooks::getOptionSetting( $options, 'start date' );
			$dates['e'] = WSStatsHooks::getOptionSetting( $options, 'end date' );
			if ( $dates['e'] === false && $dates['b'] !== false ) {
				$dates['e'] = false;
			}
			if ( $dates['b'] === false && $dates['e'] !== false ) {
				$dates = false;
			}
			if ( $dates['b'] === false && $dates['e'] === false ) {
				$dates = false;
			}
			$data = WSStatsHooks::getViewsPerPage( $pid, $dates, $type );
			if ( $data !== null ) {
				return $data;
			} else {
				return "";
			}
		}

		return "ok, move along. Nothing to see here..";
	}


	public static function insertRecord( $table, $vals ) {
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
			$pair = explode( '=', $option, 2 );
			if ( $pair[0] !== '//' ) {
				if ( count( $pair ) === 2 ) {
					$name = strtolower( trim( $pair[0] ) );
					if ( $name == 'template' ) {
						$value = trim( $pair[1] );
					} else {
						$value = strtolower( trim( $pair[1] ) );
					}

					$results[ $name ] = $value;
				}
				if ( count( $pair ) === 1 ) {
					$name             = trim( $pair[0] );
					$results[ $name ] = true;
				}
			}
		}
		//Now you've got an array that looks like this:
		//  [foo] => "bar"
		//	[apple] => "orange"
		//	[banana] => true
		return $results;
	}

}
