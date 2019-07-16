'use strict';
const Page = require( 'wdio-mediawiki/Page' );

class SpecialElectronPdfPage extends Page {

	get downloadButton() { return browser.element( '.mw-electronpdfservice-selection-form .oo-ui-buttonElement-button' ); }

}
module.exports = new SpecialElectronPdfPage();
