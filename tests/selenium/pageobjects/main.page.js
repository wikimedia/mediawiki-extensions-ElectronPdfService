import Page from 'wdio-mediawiki/Page';
import * as Util from 'wdio-mediawiki/Util';

class MainPage extends Page {

	get expandToolsLink() {
		return $( '#vector-page-tools-dropdown' );
	}

	get downloadAsPdfLink() {
		return $( '[id^=coll-download-as-r]' );
	}

	async open() {
		return super.openTitle( 'Main_Page' );
	}

	async usesVector2022() {
		await Util.waitForModuleState( 'mediawiki.base' );
		return await browser.execute( () => mw.config.get( 'skin' ) === 'vector-2022' );
	}

}

export default new MainPage();
