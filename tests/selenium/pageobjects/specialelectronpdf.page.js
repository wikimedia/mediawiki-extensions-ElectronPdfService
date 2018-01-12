'use strict';
const Page = require( '../../../../../tests/selenium/pageobjects/page' );

class SpecialElectronPdfPage extends Page {

	get downloadButton() { return browser.element( '.mw-electronPdfService-selection-form .oo-ui-buttonElement-button' ); }

}
module.exports = new SpecialElectronPdfPage();
