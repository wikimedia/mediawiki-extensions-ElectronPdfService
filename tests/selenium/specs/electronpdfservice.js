'use strict';

var assert = require( 'assert' ),
	MainPage = require( '../pageobjects/main.page' ),
	SpecialElectronPdfPage = require( '../pageobjects/specialelectronpdf.page' );

describe( 'ElectronPdfService', function () {

	it( 'pdf download button is visible', function () {

		MainPage.open();
		MainPage.pdflink.click();

		assert( SpecialElectronPdfPage.downloadButton.isVisible() );

	} );

} );
