'use strict';
const Page = require( 'wdio-mediawiki/Page' );

class MainPage extends Page {

	get pdflink() { return browser.element( '[id^=coll-download-as-r]' ); }

	open() {
		super.openTitle( 'Main_Page' );
	}

}
module.exports = new MainPage();
