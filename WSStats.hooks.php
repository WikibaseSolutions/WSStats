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


	public static $isAnon = "";

	public function __construct() {
		date_default_timezone_set('UTC');
		self::$isAnon =  User::isAnon();
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
			$tables = __DIR__ . "/WSStats-tables.$dbt";

			if (file_exists($tables)) {
					$updater->addExtensionUpdate(array('addTable', 'WSPS', $tables, true));
			} else {
					throw new MWException("WSStats does not support $dbt.");
			}
			return true;
	}

	public static function onParserFirstCallInit(Parser &$parser) {
			$parser->setFunctionHook('wsstats', 'WSStatsHooks::wsstats');
	}

	public static function onAfterFinalPageOutput ( $output ) {
		global $wgUser;

		// for now, we do not record an anonymous user.
		if(WSStatsHooks::$isAnon) return true;
		$data['user_id'] = $wgUser->getID();
		$title = Title::newFromText( $output->getPageTitle() );
		$data['page_id']=$title->getArticleID();
		if($data['page_id'] != 0) {
			WSStatsHooks::insertRecord('WSPS', $data);
		}
		 return true;

	}

	public static function wsstats(Parser &$parser) {
		//Not implemented yet

		//$options = WSStatsHooks::extractOptions(array_slice(func_get_args(), 1));
		return "ok";
	}


	public static function insertRecord($table, $vals) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->IngoreErrors = true;
			//$dbr->IngoreErrors = true;
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
