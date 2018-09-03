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
		$out->setPageTitle( $this->msg( 'electronPdfService-special-page-headline' )->text() );
		$out->addSubtitle( $title->getText() );

		$form = new OOUI\FormLayout( [
			'method' => 'POST',
			'action' => $this->getPageTitle()->getLocalURL(),
		] );

		$form->addClasses( [ 'mw-electronPdfService-selection-form' ] );

		$form->appendContent(
			( new OOUI\Tag() )
				->addClasses( [ 'mw-electronPdfService-selection-body' ] )
				->appendContent(
					$this->getLabeledHiddenField( 'redirect-to-electron',  $title->getDBKey() ),
					$this->getHiddenField( 'page', $title->getPrefixedText() ),
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
		$out->addHTML( $this->createWarningBox()->toString() );
		$out->addHTML( $form );
	}

	/**
	 * @param string $action
	 * @param string $pageTitle
	 * @return OOUI\Tag
	 */
	private function getLabeledHiddenField( $action, $pageTitle ) {
		$image = ( new OOUI\Tag() )->addClasses( [
			'mw-electronPdfService-selection-image',
			'mw-electronPdfService-selection-download-image'
		] );

		$field = ( new OOUI\Tag() )->addClasses( [ 'mw-electronPdfService-selection-field' ] );
		$field->appendContent(
			$this->getHiddenField( 'action', $action ),
			( new OOUI\Tag( 'div' ) )->addClasses( [ 'mw-electronPdfService-selection-label-text' ] )
				->appendContent( $this->msg( 'electronPdfService-download-label' )->text() ),
			( new OOUI\Tag( 'div' ) )->addClasses( [ 'mw-electronPdfService-selection-label-desc' ] )
				->appendContent( $pageTitle . ".pdf" )
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
	 * Creates a warning box
	 *
	 * @return OOUI\Tag
	 * @suppress SecurityCheck-DoubleEscaped Issue with OOUI, see T193837 for more information
	 */
	private function createWarningBox() {
		$warning = new OOUI\Tag();
		$warning->addClasses( [ 'warningbox' ] );

		$text = new OOUI\Tag( 'p' );
		$text->appendContent(
			new OOUI\HtmlSnippet( $this->msg( 'electronPDFService-warning-message' )->parse() )
		);

		$list = new OOUI\Tag( 'ul' );
		$list->addClasses( [ 'hlist' ] )
			->appendContent(
				( new OOUI\Tag( 'li' ) )
					->appendContent(
						( new OOUI\Tag( 'a' ) )
							->setAttributes( [
								'href' => 'https://www.mediawiki.org/wiki/Talk:Reading/Web/PDF_Functionality'
							] )
							->appendContent( $this->msg( 'electronPDFService-warning-leave-feedback' )->text() )
					)
			);

		$list->appendContent(
			( new OOUI\Tag( 'li' ) )
				->appendContent(
					( new OOUI\Tag( 'a' ) )
						->setAttributes( [
							'href' => 'https://www.mediawiki.org/wiki/Reading/Web/PDF_Functionality'
						] )
						->appendContent( $this->msg( 'electronPDFService-warning-read-more' )->text() )
				)
		);

		$warning->appendContent( $text, $list );
		return $warning;
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
			'mediawiki.hlist'
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
