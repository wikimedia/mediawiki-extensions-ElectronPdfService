<?php
/**
 * ElectronPdf SpecialPage for ElectronPdfService extension
 *
 * @file
 * @ingroup Extensions
 */

use MediaWiki\MediaWikiServices;

class SpecialElectronPdf extends SpecialPage {
	/**
	 * @var $tempFile
	 *
	 * Temporary file the PDF will be written to
	 */
	public $tempFile;

	public $config;

	public function __construct() {
		parent::__construct( 'ElectronPdf' );
		$this->config = MediaWikiServices::getInstance()->getMainConfig();
	}

	public function execute( $subPage ) {
		$request = $this->getRequest();
		$parts = ( $subPage === '' ) ? [] : explode( '/', $subPage, 2 );
		$articleTitle = trim( $request->getVal( 'articletitle', isset( $parts[0] ) ? $parts[0] : '' ) );
		$this->renderAndShowPdf( $articleTitle );
	}

	public function renderAndShowPdf( $articleName ) {
		$title = Title::newFromText( $articleName );
		if ( $title === null ) {
			$this->getOutput()->showErrorPage(
				'electronPdfService-invalid-article-title',
				'electronPdfService-invalid-article-text'
			);
			return;
		}

		$this->tempFile = tmpfile();

		$request = MWHttpRequest::factory( $this->constructServiceUrl( $title ) );
		$request->setCallback( [ $this, 'writeToTempFile' ] );

		if ( $request->execute()->isOK() ) {
			$this->sendPdfToOutput( $articleName );
		} else {
			$this->getOutput()->showErrorPage(
				'electronPdfService-article-notfound-title',
				'electronPdfService-article-notfound-text'
			);
		}

		return;
	}

	public function writeToTempFile( $res, $content ) {
		return fwrite( $this->tempFile, $content );
	}

	private function constructServiceUrl( Title $title ) {
		$electronPdfService = $this->config->get( 'ElectronPdfService' );

		// for testing the functionality on localhost please set
		// $wgElectronPdfService["articleUrl"] to a publicly accessible URL in your LocalSettings.php!
		if ( !isset( $electronPdfService["articleUrl"] ) ) {
			$articleUrl = $title->getCanonicalURL();
		} else {
			$articleUrl = $electronPdfService["articleUrl"];
		}
		$serviceUrl =
			$electronPdfService["serviceUrl"] . '/' .
			$electronPdfService["format"] .
			'?accessKey=' . $electronPdfService["key"] .
			'&url=' . $articleUrl;

		return $serviceUrl;
	}

	private function sendPdfToOutput( $articleName ) {
		$fileMetaData = stream_get_meta_data( $this->tempFile );
		wfResetOutputBuffers();
		header( 'Content-Type:application/pdf' );
		header( 'Content-Length: ' . filesize( $fileMetaData['uri'] ) );
		header( 'Content-Disposition: inline; filename=' . $articleName . '.pdf' );
		fseek( $this->tempFile, 0 );
		fpassthru( $this->tempFile );
		$this->getOutput()->disable();
	}
}
