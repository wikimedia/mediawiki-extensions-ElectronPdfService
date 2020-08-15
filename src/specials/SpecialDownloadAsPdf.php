<?php
/**
 * ElectronPdf SpecialPage for ElectronPdfService extension
 *
 * @file
 * @ingroup Extensions
 */

use MediaWiki\MediaWikiServices;

class SpecialDownloadAsPdf extends SpecialPage {

	/**
	 * @var Config
	 */
	public $config;

	public function __construct() {
		parent::__construct( 'DownloadAsPdf', '', false );
		$this->config = MediaWikiServices::getInstance()->getMainConfig();
	}

	/**
	 * @param null|string $subPage
	 */
	public function execute( $subPage ) {
		$request = $this->getRequest();
		$parts = ( $subPage === '' ) ? [] : explode( '/', $subPage, 2 );
		$page = trim( $request->getVal( 'page', $parts[0] ?? '' ) );

		$title = Title::newFromText( $page );
		if ( $title === null ) {
			$this->getOutput()->showErrorPage(
				'electronpdfservice-invalid-page-title',
				'electronpdfservice-invalid-page-text'
			);
			return;
		}

		$action = $request->getVal( 'action', 'default' );
		$stats = MediaWikiServices::getInstance()->getStatsdDataFactory();
		$dbName = $this->getConfig()->get( 'DBname' );

		switch ( $action ) {
			case 'redirect-to-electron':
				$stats->increment( 'electronpdf.action.' . $action );
				$stats->increment( 'electronpdf.actionsPerWiki.' . $dbName . '.' . $action );
				$this->redirectToElectron( $title );
				return;
			default:
				$stats->increment( 'electronpdf.action.show-download-screen' );
				$stats->increment( 'electronpdf.actionsPerWiki.' . $dbName . '.show-download-screen' );
				$this->showRenderModeSelectionPage( $title );
		}
	}

	/**
	 * @param Title $title page to download as PDF
	 */
	public function showRenderModeSelectionPage( Title $title ) {
		$this->setHeaders();

		$out = $this->getOutput();
		$out->enableOOUI();
		$out->setPageTitle( $this->msg( 'electronpdfservice-special-page-headline' )->text() );
		$out->addSubtitle( $title->getText() );

		$form = new OOUI\FormLayout( [
			'method' => 'POST',
			'action' => $this->getPageTitle()->getLocalURL(),
		] );

		$form->addClasses( [ 'mw-electronpdfservice-selection-form' ] );

		$form->appendContent(
			( new OOUI\Tag() )
				->addClasses( [ 'mw-electronpdfservice-selection-body' ] )
				->appendContent(
					$this->getLabeledHiddenField( 'redirect-to-electron',  $title->getDBKey() ),
					$this->getHiddenField( 'page', $title->getPrefixedText() ),
					new OOUI\ButtonGroupWidget( [
						'items' => [
							new OOUI\ButtonInputWidget( [
								'type' => 'submit',
								'flags' => [ 'primary', 'progressive' ],
								'label' => $this->msg( 'electronpdfservice-download-button' )->text(),
							] ),
						],
					] )
				)
		);
		$out->addHTML( $form );
	}

	/**
	 * @param string $action
	 * @param string $pageTitle
	 * @return OOUI\Tag
	 */
	private function getLabeledHiddenField( $action, $pageTitle ) {
		$image = ( new OOUI\Tag() )->addClasses( [
			'mw-electronpdfservice-selection-image',
			'mw-electronpdfservice-selection-download-image'
		] );

		$field = ( new OOUI\Tag() )->addClasses( [ 'mw-electronpdfservice-selection-field' ] );
		$field->appendContent(
			$this->getHiddenField( 'action', $action ),
			( new OOUI\Tag( 'div' ) )->addClasses( [ 'mw-electronpdfservice-selection-label-text' ] )
				->appendContent( $this->msg( 'electronpdfservice-download-label' )->text() ),
			( new OOUI\Tag( 'div' ) )->addClasses( [ 'mw-electronpdfservice-selection-label-desc' ] )
				->appendContent( $pageTitle . '.pdf' )
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
	 * Sets headers
	 */
	public function setHeaders() {
		parent::setHeaders();
		$this->addModules();
	}

	/**
	 * Adds CSS modules
	 */
	protected function addModules() {
		$this->getOutput()->addModuleStyles( [
			'ext.ElectronPdfService.special.styles',
			'ext.ElectronPdfService.special.selectionImages',
		] );
	}

	/**
	 * @param Title $title
	 * @return string
	 */
	private function getServiceUrl( Title $title ) {
		$restBaseUrl = $this->config->get( 'ElectronPdfServiceRESTbaseURL' );

		return $restBaseUrl . urlencode( $title->getPrefixedDBkey() );
	}

	/**
	 * @param Title $title
	 */
	private function redirectToElectron( Title $title ) {
		$this->getOutput()->redirect(
			$this->getServiceUrl( $title )
		);
	}

}
