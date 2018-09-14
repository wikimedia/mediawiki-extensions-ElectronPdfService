'use strict';
const Page = require( 'wdio-mediawiki/Page' );

class SpecialElectronPdfPage extends Page {

	get downloadButton() { return browser.element( '.mw-electronPdfService-selection-form .oo-ui-buttonElement-button' ); }

}
module.exports = new SpecialElectronPdfPage();
