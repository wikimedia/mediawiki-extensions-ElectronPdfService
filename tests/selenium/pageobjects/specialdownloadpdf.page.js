import Page from 'wdio-mediawiki/Page';

class SpecialDownloadAsPdfPage extends Page {

	get downloadButton() {
		return $( '.mw-electronpdfservice-selection-form .oo-ui-buttonElement-button' );
	}

}

export default new SpecialDownloadAsPdfPage();
