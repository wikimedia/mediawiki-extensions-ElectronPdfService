'use strict';

const Page = require( 'wdio-mediawiki/Page' ),
	Util = require( 'wdio-mediawiki/Util' );

class MainPage extends Page {

	get expandToolsLink() {
		return $( '#vector-page-tools-dropdown' );
	}

	get downloadAsPdfLink() {
		return $( '[id^=coll-download-as-r]' );
	}

	open() {
		super.openTitle( 'Main_Page' );
	}

	async usesVector2022() {
		await Util.waitForModuleState( 'mediawiki.base' );
		return await browser.execute( () => mw.config.get( 'skin' ) === 'vector-2022' );
	}

}
module.exports = new MainPage();
