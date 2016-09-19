<?php
/**
 * ElectronPdfService MediaWiki Extension
 */

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'ElectronPdfService' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['ElectronPdfService'] = __DIR__ . '/i18n';
	wfWarn(
		'Deprecated PHP entry point used for ElectronPdfService extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the ElectronPdfService extension requires MediaWiki 1.25+' );
}
