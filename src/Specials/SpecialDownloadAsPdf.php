<?php
/**
 * ElectronPdf SpecialPage for ElectronPdfService extension
 *
 * @file
 * @ingroup Extensions
 */

namespace MediaWiki\Extension\ElectronPdfService\Specials;

use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use OOUI\ButtonGroupWidget;
use OOUI\ButtonInputWidget;
use OOUI\FormLayout;
use OOUI\Tag;
use Wikimedia\Stats\StatsFactory;

class SpecialDownloadAsPdf extends SpecialPage {

	public function __construct(
		private readonly StatsFactory $statsFactory
	) {
		parent::__construct( 'DownloadAsPdf', '', false );
	}

	/**
	 * @param null|string $subPage
	 */
	public function execute( $subPage ) {
		$request = $this->getRequest();
		$parts = ( $subPage === '' || $subPage === null ) ? [] : explode( '/', $subPage, 2 );
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
		$dbName = $this->getConfig()->get( 'DBname' );

		switch ( $action ) {
			case 'redirect-to-electron':
				$this->statsFactory->getCounter( 'electronpdf_action_total' )
					->setLabel( 'action', $action )
					->copyToStatsdAt( 'electronpdf.action.' . $action )
					->increment();
				$this->statsFactory->getCounter( 'electronpdf_actions_per_wiki_total' )
					->setLabel( 'action', $action )
					->setLabel( 'wiki', $dbName )
					->copyToStatsdAt( 'electronpdf.actionsPerWiki.' . $dbName . '.' . $action )
					->increment();
				$this->redirectToElectron( $title );
				return;
			default:
				$this->statsFactory->getCounter( 'electronpdf_action_total' )
					->setLabel( 'action', 'show-download-screen' )
					->copyToStatsdAt( 'electronpdf.action.show-download-screen' )
					->increment();
				$this->statsFactory->getCounter( 'electronpdf_actions_per_wiki_total' )
					->setLabel( 'action', 'show-download-screen' )
					->setLabel( 'wiki', $dbName )
					->copyToStatsdAt( 'electronpdf.actionsPerWiki.' . $dbName . '.show-download-screen' )
					->increment();

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
		$out->setPageTitleMsg( $this->msg( 'electronpdfservice-special-page-headline' ) );
		$out->addSubtitle( $title->getText() );

		$form = new FormLayout( [
			'method' => 'POST',
			'action' => $this->getPageTitle()->getLocalURL(),
		] );

		$form->addClasses( [ 'mw-electronpdfservice-selection-form' ] );

		$form->appendContent(
			( new Tag() )
				->addClasses( [ 'mw-electronpdfservice-selection-body' ] )
				->appendContent(
					$this->getLabeledHiddenField( 'redirect-to-electron', $title->getDBKey() ),
					$this->getHiddenField( 'page', $title->getPrefixedText() ),
					new ButtonGroupWidget( [
						'items' => [
							new ButtonInputWidget( [
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
	 * @return Tag
	 */
	private function getLabeledHiddenField( $action, $pageTitle ) {
		$image = ( new Tag() )->addClasses( [
			'mw-electronpdfservice-selection-image',
			'mw-electronpdfservice-selection-download-image'
		] );

		$field = ( new Tag() )->addClasses( [ 'mw-electronpdfservice-selection-field' ] );
		$field->appendContent(
			$this->getHiddenField( 'action', $action ),
			( new Tag( 'div' ) )->addClasses( [ 'mw-electronpdfservice-selection-label-text' ] )
				->appendContent( $this->msg( 'electronpdfservice-download-label' )->text() ),
			( new Tag( 'div' ) )->addClasses( [ 'mw-electronpdfservice-selection-label-desc' ] )
				->appendContent( $pageTitle . '.pdf' )
		);

		return ( new Tag( 'label' ) )->appendContent(
			$image,
			$field
		);
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @return Tag
	 */
	private function getHiddenField( $name, $value ) {
		$element = new Tag( 'input' );
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
		$restBaseUrl = $this->getConfig()->get( 'ElectronPdfServiceRESTbaseURL' );

		return $restBaseUrl . urlencode( $title->getPrefixedDBkey() );
	}

	private function redirectToElectron( Title $title ) {
		$this->getOutput()->redirect(
			$this->getServiceUrl( $title )
		);
	}

}
