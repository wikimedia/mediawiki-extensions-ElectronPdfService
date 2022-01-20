'use strict';

const assert = require( 'assert' ),
	MainPage = require( '../pageobjects/main.page' ),
	SpecialDownloadAsPdfPage = require( '../pageobjects/specialdownloadpdf.page' );

describe( 'ElectronPdfService', function () {

	it( 'pdf download button is visible', async function () {

		await MainPage.open();
		await MainPage.pdflink.click();

		assert( SpecialDownloadAsPdfPage.downloadButton.isDisplayed() );

	} );

} );
