'use strict';

const MainPage = require( '../pageobjects/main.page' );
const SpecialDownloadAsPdfPage = require( '../pageobjects/specialdownloadpdf.page' );

describe( 'ElectronPdfService', () => {

	it( 'pdf download button is visible', async () => {

		await MainPage.open();

		if ( await MainPage.usesVector2022() ) {
			await MainPage.expandToolsLink.waitForDisplayed();
			await MainPage.expandToolsLink.click();
		}

		await MainPage.downloadAsPdfLink.waitForDisplayed();
		await MainPage.downloadAsPdfLink.click();

		await expect( SpecialDownloadAsPdfPage.downloadButton ).toBeDisplayed();

	} );

} );
