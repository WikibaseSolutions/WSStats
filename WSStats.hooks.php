<?php
/**
 * WSStatsHooks
 *
 *
 * @license https://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 *
 */

if (!defined('MEDIAWIKI')) {
	die('This file is a MediaWiki extension, it is not a valid entry point');
}

class WSStatsHooks {

	public static $db_prefix = "";

	public function __construct() {
		date_default_timezone_set('UTC');
		global $wgDBprefix;
		WSStatsHooks::$db_prefix = $wgDBprefix;
	}

	public static function isAnon() {
		return User::isAnon();
}

public static function db_open() {
		global $wgDBserver;
		global $wgDBname;
		global $wgDBuser;
		global $wgDBpassword;

		$conn = new MySQLi($wgDBserver, $wgDBuser, $wgDBpassword, $wgDBname);
		$conn->set_charset("utf8");
		return $conn;
}

public static function db_real_escape($txt) {
		$db = WSssdHooks::db_open();
		$txt = $db->real_escape_string($txt);
		$db->close();
		return $txt;

}

public static function getPageTitleFromID($id) {
	$artikel = Article::newFromId($id);
	$title = $artikel->getTitle();
	$furl = $title->getFullText();
	return $furl;
}


/**
 * When running maintenance update with will add the database tables
 * @param [type] $updater [description]
 */
	public static function addTables($updater) {
			$dbt = $updater->getDB()->getType();
			// If using SQLite, just use the MySQL/MariaDB schema, it's compatible
			// anyway. Only PGSQL and some more exotic variants need a totally
			// different schema.
			if ($dbt === 'sqlite') {
					$dbt = 'sql';
			}
			$tables = __DIR__ . "/WSStats.$dbt";

			if (file_exists($tables)) {
					$updater->addExtensionUpdate(array('addTable', 'WSPS', $tables, true));
			} else {
					throw new MWException("WSStats does not support $dbt.");
			}
			return true;
	}

	public static function getViewsPerPage($id,$dates=false) {
		if( $dates === false ) {
			$sql = 'SELECT page_id, COUNT(*) as count from '.WSStatsHooks::$db_prefix.'WSPS WHERE page_id=\''.$id.'\' GROUP BY page_id ORDER BY count DESC limit 1';
		} else {
			$sql = 'SELECT page_id, COUNT(*) as count from '.WSStatsHooks::$db_prefix.'WSPS WHERE page_id=\''.$id.'\' AND added >= \''.$dates["b"].'\' AND added <= \''.$dates['e'].'\' GROUP BY page_id ORDER BY count DESC limit 1';
		}
		$db = WSStatsHooks::db_open();
		$q=$db->query($sql);
		if( $q === false ) {
			return 0;
		}
		$row = $q->fetch_assoc();
		if(!isset($row['count']) || empty($row['count'])) {
			return 0;
		} else return $row['count'];
	}

	public static function getMostViewedPages($dates=false) {
		$sql = 'SELECT page_id, COUNT(*) as count from '.WSStatsHooks::$db_prefix.'WSPS GROUP BY page_id ORDER BY count DESC limit 10';
		$db = WSStatsHooks::db_open();
	    $q=$db->query($sql);
	    if ( $q->num_rows > 0 ) {
			$data = "{| class=\"sortable wikitable smwtable jquery-tablesorter\"\n";
			$data .= "! Page ID\n";
			$data .= "! Page Title\n";
			$data .= "! Page hits\n";
			while ($row = $q->fetch_assoc()) {
				$data .= "|-\n";
		  	$data .= "| " . $row['page_id'] . "\n";
				$data .= "| " . WSStatsHooks::getPageTitleFromID($row['page_id']) . "\n";
				$data .= "| " . $row['count'] . "\n";
			  $data .= "|-\n";
			}
		$data .= "|}\n";
		$db->close();
		return $data;
		}
}

	public static function getOptionSetting($options, $k) {
		if ( isset( $options[$k] ) && $options[$k] != '' ) {
			return $options[$k];
		} else return false;
	}

	public static function onParserFirstCallInit(Parser &$parser) {
			$parser->setFunctionHook('wsstats', 'WSStatsHooks::wsstats');
	}



	public static function onBeforePageDisplay( outputPage &$output, Skin &$skin ) {
		global $wgUser, $wgWSStats;

        if( isset( $wgWSStats['skip_user_groups'] ) && is_array( $wgWSStats['skip_user_groups'] ) ) {
            $groups = $wgWSStats['skip_user_groups'];
            foreach( $groups as $group ) {
                if( in_array($group, $wgUser->getGroups() ) ) {
                    return true;
                }
            }
        }

		if( isset($wgWSStats['skip_anonymous']) && $wgWSStats['skip_anonymous'] === true) {
            if($wgUser->isAnon()) {
                return true;
            }

        }

		if($wgUser->isAnon()) {
			$data['user_id']=0;
		} else {
			$data['user_id'] = $wgUser->getID();
		}
		$title = $output->getTitle();

		if($title===null) return true;
		$data['page_id']=$title->getArticleID();
		if($data['page_id'] != 0) {
			WSStatsHooks::insertRecord('WSPS', $data);
		}
		 return true;

	}

	public static function wsstats(Parser &$parser) {
		//Not implemented yet
		$options = WSStatsHooks::extractOptions( array_slice( func_get_args(), 1 ) );
		if ( isset( $options['stats'] ) ) {
			$data = WSStatsHooks::getMostViewedPages();
			return $data;
		}
		$pid = WSStatsHooks::getOptionSetting($options,'id');
		if ( $pid !== false ) {
			$dates = array();
			$dates['b'] = WSStatsHooks::getOptionSetting($options,'start date');
			$dates['e'] = WSStatsHooks::getOptionSetting($options,'end date');
			if ($dates['e'] === false && $dates['b'] !== false ) {
				$dates['e'] = date("Y-m-d H:i:s");
			}
			if ($dates['b'] === false && $dates['e'] !== false ) {
				$dates = false;
			}
			if ($dates['b'] === false && $dates['e'] === false ) {
				$dates = false;
			}
			$data = WSStatsHooks::getViewsPerPage($pid,$dates);
			if($data !== NULL) {
				return $data;
			} else return "";
		}
		return "ok, move along. Nothing to see here..";
	}


	public static function insertRecord($table, $vals) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->IngoreErrors = true;
			try {
					$res = $dbw->insert(
							$table,
							$vals,
							__METHOD__
					);
			} catch (Exception $e) {
					echo $e;
					return false;
			}

			if ($res) {
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
	 * @return array $results
	 */
	public static function extractOptions(array $options) {
			$results = array();
			foreach ($options as $option) {
					$pair = explode('=', $option, 2);
					if ($pair[0] !== '//') {
							if (count($pair) === 2) {
									$name = strtolower(trim($pair[0]));
									if ($name=='template') {
										$value = trim($pair[1]);
									} else {
										$value = strtolower(trim($pair[1]));
									}

									$results[$name] = $value;
							}
							if (count($pair) === 1) {
									$name = trim($pair[0]);
									$results[$name] = true;
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
