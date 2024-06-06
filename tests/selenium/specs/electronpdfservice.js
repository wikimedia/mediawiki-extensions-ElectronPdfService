'use strict';

const assert = require( 'assert' ),
	MainPage = require( '../pageobjects/main.page' ),
	SpecialDownloadAsPdfPage = require( '../pageobjects/specialdownloadpdf.page' );

describe( 'ElectronPdfService', () => {

	it( 'pdf download button is visible', async () => {

		await MainPage.open();

		if ( await MainPage.usesVector2022() ) {
			await MainPage.expandToolsLink.waitForDisplayed();
			await MainPage.expandToolsLink.click();
		}

		await MainPage.downloadAsPdfLink.waitForDisplayed();
		await MainPage.downloadAsPdfLink.click();

		assert( await SpecialDownloadAsPdfPage.downloadButton.isDisplayed() );

	} );

} );
