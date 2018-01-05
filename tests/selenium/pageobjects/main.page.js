'use strict';
const Page = require( '../../../../../tests/selenium/pageobjects/page' );

class MainPage extends Page {

	get pdflink() { return browser.element( '[id^=coll-download-as-r]' ); }

	open() {
		super.open( 'Main_Page' );
	}

}
module.exports = new MainPage();
