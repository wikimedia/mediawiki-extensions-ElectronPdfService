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
	 * @var $tempFileHandle
	 *
	 * Temporary file the PDF will be written to
	 */
	public $tempFileHandle;

	/**
	 * @var int $totalBytesWritten
	 *
	 * Variable to keep track of total number of bytes written to the temporary file
	 */
	public $totalBytesWritten;

	/**
	 * @var Config $config
	 */
	public $config;

	public function __construct() {
		parent::__construct( 'ElectronPdf', '', false );
		$this->config = MediaWikiServices::getInstance()->getMainConfig();
	}

	/**
	 * @param null|string $subPage
	 */
	public function execute( $subPage ) {
		$request = $this->getRequest();
		$parts = ( $subPage === '' ) ? [] : explode( '/', $subPage, 2 );
		$page = trim( $request->getVal( 'page', isset( $parts[0] ) ? $parts[0] : '' ) );
		$collectionDownloadUrl = trim(
			$request->getVal( 'coll-download-url', isset( $parts[0] ) ? $parts[0] : '' )
		);

		$title = Title::newFromText( $page );
		if ( $title === null ) {
			$this->getOutput()->showErrorPage(
				'electronPdfService-invalid-page-title',
				'electronPdfService-invalid-page-text'
			);
			return;
		}

		switch ( $request->getVal( 'action', '' ) ) {
			case 'download-electron-pdf':
				$this->renderAndShowPdf( $title );
				return;
			case 'redirect-to-collection':
				$this->redirectToCollection( $collectionDownloadUrl );
				return;
			default:
				$this->showRenderModeSelectionPage( $title, $collectionDownloadUrl );
		}
	}

	/**
	 * @param Title $title page to download as PDF
	 * @param string $collectionDownloadUrl URL to the download page of the Collection extension
	 */
	public function showRenderModeSelectionPage( Title $title, $collectionDownloadUrl ) {
		$this->setHeaders();

		$out = $this->getOutput();
		$out->enableOOUI();
		$out->setPageTitle( $this->msg( 'electronPdfService-special-page-headline' )->text() );
		$out->addSubtitle( $title->getText() );

		$form = new OOUI\FormLayout( [
			'method' => 'POST',
			'action' => $this->getPageTitle()->getLocalURL(),
		] );

		$form->addClasses( [ 'mw-electronPdfService-selection-form' ] );

		$form->appendContent(
			( new OOUI\Tag() )
				->addClasses( [ 'mw-electronPdfService-selection-header' ] )
				->appendContent( $this->msg( 'electronPdfService-select-layout-header' )->text() ),
			( new OOUI\Tag() )
				->addClasses( [ 'mw-electronPdfService-selection-body' ] )
				->appendContent(
					$this->getLabeledOptionField( 'download-electron-pdf', 'single', true ),
					$this->getLabeledOptionField( 'redirect-to-collection', 'two' ),
					$this->getHiddenField( 'page', $title->getText() ),
					$this->getHiddenField( 'coll-download-url', $collectionDownloadUrl ),
					new OOUI\ButtonGroupWidget( [
						'items' => [
							new OOUI\ButtonInputWidget( [
								'type' => 'submit',
								'flags' => [ 'primary', 'progressive' ],
								'label' => $this->msg( 'electronPdfService-download-button' )->text(),
							] ),
						],
					] )
				)
		);

		$out->addHTML( $form );
	}

	/**
	 * @param string $action
	 * @param string $name
	 * @param boolean $selected
	 * @return OOUI\Tag
	 */
	private function getLabeledOptionField( $action, $name, $selected = false ) {
		$image = ( new OOUI\Tag() )->addClasses( [
			'mw-electronPdfService-selection-image',
			'mw-electronPdfService-selection-' . $name . '-column-image'
		] );

		$field = ( new OOUI\Tag() )->addClasses( [ 'mw-electronPdfService-selection-field' ] );
		$field->appendContent(
			new OOUI\RadioInputWidget( [
				'name' => 'action',
				'value' => $action,
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

	/**
	 * @param string $name
	 * @param string $value
	 * @return OOUI\Tag
	 */
	private function getHiddenField( $name, $value ) {
		$element = new OOUI\Tag( 'input' );
		$element->setAttributes(
			[
				'type' => 'hidden',
				'name' => $name,
				'value' => $value
			]
		);

		return $element;
	}

	/**
	 * @param Title $title page to download as PDF
	 */
	public function renderAndShowPdf( Title $title ) {
		if ( !$this->getRequest()->checkUrlExtension() ) {
			$this->getOutput()->showErrorPage(
				'electronPdfService-page-notfound-title',
				'electronPdfService-page-notfound-text'
			);
			return;
		}
		$tempFile = TempFSFile::factory( 'electron_', 'pdf' );
		$this->tempFileHandle = fopen( $tempFile->getPath(), 'w+' );
		$this->totalBytesWritten = 0;

		$request = MWHttpRequest::factory( $this->constructServiceUrl( $title ) );
		$request->setCallback( [ $this, 'writeToTempFile' ] );

		if ( $request->execute()->isOK() ) {
			$this->sendPdfToOutput( $title->getPrefixedText() );
		} else {
			$this->getOutput()->showErrorPage(
				'electronPdfService-page-notfound-title',
				'electronPdfService-page-notfound-text'
			);
		}

		fclose( $this->tempFileHandle );
		$tempFile->purge();
		return;
	}

	/**
	 * Callback used by the MWHttpRequest to the Electron service writing the result into a file
	 * If writing fails or the $maxDocumentSize is reached it returns -1 to abort the HTTP fetch.
	 *
	 * @param resource $res
	 * @param string $content
	 * @return int
	 */
	public function writeToTempFile( $res, $content ) {
		$maxDocumentSize = $this->config->get( 'ElectronPdfServiceMaxDocumentSize' );
		$bytes = fwrite(
			$this->tempFileHandle,
			$content,
			$maxDocumentSize - $this->totalBytesWritten + 1
		);

		if ( $bytes === false ) {
			return -1;
		}

		$this->totalBytesWritten += $bytes;

		if ( $this->totalBytesWritten > $maxDocumentSize ) {
			return -1;
		}

		return $bytes;
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

	/**
	 * @param Title $title
	 * @return string
	 */
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

	/**
	 * @param string $page
	 */
	private function sendPdfToOutput( $page ) {
		$fileMetaData = stream_get_meta_data( $this->tempFileHandle );
		$contentDisposition = FileBackend::makeContentDisposition( 'inline', $page . '.pdf' );

		$headers = [
			'Content-Type:application/pdf',
			'Content-Length: ' . filesize( $fileMetaData['uri'] ),
			'Content-Disposition: ' . $contentDisposition
		];

		StreamFile::stream( $fileMetaData['uri'], $headers );
	}

	/**
	 * @param string $collectionDownloadUrl
	 */
	private function redirectToCollection( $collectionDownloadUrl ) {
		$queryString = parse_url(
			urldecode( $collectionDownloadUrl ),
			PHP_URL_QUERY
		);
		parse_str( $queryString, $params );
		unset( $params['title'] );

		$this->getOutput()->redirect( wfAppendQuery(
			SkinTemplate::makeSpecialUrl( 'Book' ),
			http_build_query( $params )
		) );
	}
}
