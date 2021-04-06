<?php
/**
 * The WSStats extension to MediaWiki allows to track page hits
 *
 * @version 0.8.0 2021
 *
 * @author Sen-Sai
 *
 * @copyright Copyright (C) 2017-2021, Sen-Sai
 *
 * @license https://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */


 if ( function_exists( 'wfLoadExtension' ) ) {
 	wfLoadExtension( 'WSStats' );
 	// Keep i18n globals so mergeMessageFileList.php doesn't break
 	$wgMessagesDirs['WSStats'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['WSStatsAlias'] = __DIR__ . '/WSStats.i18n.alias.php';
	$wgExtensionMessagesFiles['WSStatsMagic'] = __DIR__ . '/WSStats.i18n.magic.php';
 	wfWarn(
 		'Deprecated PHP entry point used for WSStats extension. Please use wfLoadExtension ' .
 		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
 	);
 	return true;
 } else {
 	die( 'This version of the WSStats extension requires MediaWiki 1.24+' );
 }
