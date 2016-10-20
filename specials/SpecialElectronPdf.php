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
		parent::__construct( 'ElectronPdf', '', false );
		$this->config = MediaWikiServices::getInstance()->getMainConfig();
	}

	public function execute( $subPage ) {
		$request = $this->getRequest();
		$parts = ( $subPage === '' ) ? [] : explode( '/', $subPage, 2 );
		$page = trim( $request->getVal( 'page', isset( $parts[0] ) ? $parts[0] : '' ) );

		$title = Title::newFromText( $page );
		if ( $title === null ) {
			$this->getOutput()->showErrorPage(
				'electronPdfService-invalid-page-title',
				'electronPdfService-invalid-page-text'
			);
			return;
		}

		// TODO: build a propper switch here and consider input if a method was selected

		$this->showRenderModeSelectionPage( $title );
		// $this->renderAndShowPdf( $title, $page );
	}

	public function showRenderModeSelectionPage( Title $title ) {
		$this->setHeaders();

		$out = $this->getOutput();
		$out->enableOOUI();
		$out->setPageTitle( $this->msg( 'electronPdfService-special-page-headline' )->text() );
		$out->addSubtitle( $title->getText() );

		$form = new OOUI\FormLayout( [
			'method' => 'POST',
			'action' => '',
		] );

		$form->addClasses( [ 'mw-electronPdfService-selection-form' ] );

		$form->appendContent(
			( new OOUI\Tag() )
				->addClasses( [ 'mw-electronPdfService-selection-header' ] )
				->appendContent( $this->msg( 'electronPdfService-select-layout-header' )->text() ),
			( new OOUI\Tag() )
				->addClasses( [ 'mw-electronPdfService-selection-body' ] )
				->appendContent(
					$this->getLabeledOptionField( 'single', true ),
					$this->getLabeledOptionField( 'two' ),
					new OOUI\ButtonGroupWidget( [
						'items' => [
							new OOUI\ButtonInputWidget( [
								'type' => 'submit',
								'name' => 'continue',
								'value' => 'go',
								'flags' => [ 'primary', 'progressive' ],
								'label' => $this->msg( 'electronPdfService-download-button' )->text(),
							] ),
						],
					] )
				)
		);

		$out->addHTML( $form );
	}

	private function getLabeledOptionField( $name, $selected = false ) {
		$image = ( new OOUI\Tag() )->addClasses( [
			'mw-electronPdfService-selection-image',
			'mw-electronPdfService-selection-' . $name . '-column-image'
		] );

		$field = ( new OOUI\Tag() )->addClasses( [ 'mw-electronPdfService-selection-field' ] );
		$field->appendContent(
			new OOUI\RadioInputWidget( [
				'name' => 'column-type',
				'value' => $name,
				'selected' => $selected
			] ),
			( new OOUI\Tag( 'b' ) )->addClasses( [ 'mw-electronPdfService-selection-label-text' ] )
				->appendContent( $this->msg( 'electronPdfService-' . $name . '-column-label' )->text() ),
			( new OOUI\Tag() )->addClasses( [ 'mw-electronPdfService-selection-label-desc' ] )
				->appendContent( $this->msg( 'electronPdfService-' . $name . '-column-desc' )->text() )
		);

		$labelBox = ( new OOUI\Tag( 'label' ) )->appendContent(
			$image,
			$field
		);
		return $labelBox;
	}

	public function renderAndShowPdf( Title $title, $page ) {
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

	public function setHeaders() {
		parent::setHeaders();
		$this->addModules();
	}

	protected function addModules() {
		$out = $this->getOutput();
		$rl = $out->getResourceLoader();
		$specialModuleName = 'ext.ElectronPdfService.special';

		if ( $rl->isModuleRegistered( $specialModuleName ) ) {
			$out->addModules( $specialModuleName );
		}
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
