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

		$action = $request->getVal( 'action', 'default' );
		$stats = MediaWikiServices::getInstance()->getStatsdDataFactory();

		switch ( $action ) {
			case 'redirect-to-electron':
				$stats->increment( 'electronpdf.action.' . $action );
				$this->redirectToElectron( $title );
				return;
			case 'redirect-to-collection':
				$stats->increment( 'electronpdf.action.' . $action );
				$this->redirectToCollection( $collectionDownloadUrl );
				return;
			default:
				$stats->increment( 'electronpdf.action.show-selection-screen' );
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
					$this->getLabeledOptionField( 'redirect-to-electron', 'single', true ),
					$this->getLabeledOptionField( 'redirect-to-collection', 'two' ),
					$this->getHiddenField( 'page', $title->getPrefixedText() ),
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

	public function setHeaders() {
		parent::setHeaders();
		$this->addModules();
	}

	protected function addModules() {
		$out = $this->getOutput();
		$rl = $out->getResourceLoader();

		if (
			$rl->isModuleRegistered( 'ext.ElectronPdfService.special.styles' )
			&& $rl->isModuleRegistered( 'ext.ElectronPdfService.special' )
		) {
			$out->addModuleStyles( 'ext.ElectronPdfService.special.styles' );
			$out->addModules( 'ext.ElectronPdfService.special' );
		}
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
