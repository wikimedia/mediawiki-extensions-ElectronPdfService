'use strict';

const assert = require( 'assert' ),
	MainPage = require( '../pageobjects/main.page' ),
	SpecialDownloadAsPdfPage = require( '../pageobjects/specialdownloadpdf.page' );

describe( 'ElectronPdfService', function () {

	it( 'pdf download button is visible', function () {

		MainPage.open();
		MainPage.pdflink.click();

		assert( SpecialDownloadAsPdfPage.downloadButton.isDisplayed() );

	} );

} );
