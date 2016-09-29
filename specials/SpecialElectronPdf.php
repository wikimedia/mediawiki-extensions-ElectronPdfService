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
		$page = trim( $request->getVal( 'page', isset( $parts[0] ) ? $parts[0] : '' ) );
		$this->renderAndShowPdf( $page );
	}

	public function renderAndShowPdf( $page ) {
		$title = Title::newFromText( $page );
		if ( $title === null ) {
			$this->getOutput()->showErrorPage(
				'electronPdfService-invalid-page-title',
				'electronPdfService-invalid-page-text'
			);
			return;
		}

		$this->tempFile = tmpfile();

		$request = MWHttpRequest::factory( $this->constructServiceUrl( $title ) );
		$request->setCallback( [ $this, 'writeToTempFile' ] );

		if ( $request->execute()->isOK() ) {
			$this->sendPdfToOutput( $page );
		} else {
			$this->getOutput()->showErrorPage(
				'electronPdfService-page-notfound-title',
				'electronPdfService-page-notfound-text'
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
		// $wgElectronPdfService["pageUrl"] to a publicly accessible URL in your LocalSettings.php!
		if ( !isset( $electronPdfService["pageUrl"] ) ) {
			$pageUrl = $title->getCanonicalURL();
		} else {
			$pageUrl = $electronPdfService["pageUrl"];
		}
		$serviceUrl =
			$electronPdfService["serviceUrl"] . '/' .
			$electronPdfService["format"] .
			'?accessKey=' . $electronPdfService["key"] .
			'&url=' . urlencode( $pageUrl );

		return $serviceUrl;
	}

	private function sendPdfToOutput( $page ) {
		$fileMetaData = stream_get_meta_data( $this->tempFile );
		wfResetOutputBuffers();
		header( 'Content-Type:application/pdf' );
		header( 'Content-Length: ' . filesize( $fileMetaData['uri'] ) );
		header( 'Content-Disposition: inline; filename=' . $page . '.pdf' );
		fseek( $this->tempFile, 0 );
		fpassthru( $this->tempFile );
		$this->getOutput()->disable();
	}
}
