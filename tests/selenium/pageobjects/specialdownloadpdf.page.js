'use strict';

const Page = require( 'wdio-mediawiki/Page' );

class SpecialDownloadAsPdfPage extends Page {

	get downloadButton() {
		return $( '.mw-electronpdfservice-selection-form .oo-ui-buttonElement-button' );
	}

}
module.exports = new SpecialDownloadAsPdfPage();
