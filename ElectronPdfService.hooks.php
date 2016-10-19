<?php
/**
 * Hooks for ElectronPdfService extension
 *
 * @file
 * @ingroup Extensions
 * @license GPL-2.0+
 */

use MediaWiki\MediaWikiServices;

class ElectronPdfServiceHooks {

	/*
	 * If present, make the "Download as PDF" link in the sidebar point to the selection screen,
	 * add a new link otherwise
	 *
	 * @param Skin $skin
	 * @param array &$bar
	 *
	 * @return bool
	 */
	public static function onSidebarBeforeOutput( Skin $skin, &$bar ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$title = $skin->getTitle();
		if ( is_null( $title ) || !$title->exists() ) {
			return false;
		}

		if ( $config->has( 'CollectionFormats' ) ) {
			$index = self::getIndexOfDownloadPdfSidebarItem(
				$bar['coll-print_export'],
				$config->get( 'CollectionFormats' )
			);
			// if Collection extension provides a download-as-pdf link, make it point to the selection screen
			if ( $index !== false ) {
				$bar['coll-print_export'][$index]['href'] = self::generateSelectionScreenLink(
					$title,
					$bar['coll-print_export'][$index]['href']
				);
			// if no download-as-pdf link is there, add one and point to the selection screen
			} else {
				$bar['coll-print_export'][] = [
					'text' => $skin->msg( 'electronPdfService-sidebar-portlet-print-text' )->escaped(),
					'id' => 'electron-print_pdf',
					'href' => self::generatePdfDownloadLink( $title )
				];
			}
		} else {
			// in case Collection is not installed, let's add our own portlet with a direct link to the PDF
			$bar['electronPdfService-sidebar-portlet-heading'][] = [
				'text' => $skin->msg( 'electronPdfService-sidebar-portlet-print-text' )->escaped(),
				'id' => 'electron-print_pdf',
				'href' => self::generatePdfDownloadLink( $title )
			];
		}

		return true;
	}

	private static function getIndexOfDownloadPdfSidebarItem( $portlet, $collectionFormats ) {
		$usedPdfLib =  array_search( 'PDF', $collectionFormats );
		if ( $usedPdfLib !== false ) {
			foreach ( $portlet as $index => $element ) {
				if ( $element['id'] === 'coll-download-as-' . $usedPdfLib ) {
					return $index;
				}
			}
		}

		return false;
	}

	private static function generatePdfDownloadLink( Title $title ) {
		$specialPageTitle = SpecialPage::getTitleFor( 'ElectronPdf' );

		return $specialPageTitle->getLocalURL(
			[
				'page' => $title->getPrefixedText(),
				'action' => 'download-electron-pdf'
			]
		);
	}

	private static function generateSelectionScreenLink( Title $title, $collectionUrl ) {
		$specialPageTitle = SpecialPage::getTitleFor( 'ElectronPdf' );

		return $specialPageTitle->getLocalURL(
			[
				'page' => $title->getPrefixedText(),
				'action' => 'show-selection-screen',
				'coll-download-url' => urlencode( $collectionUrl )
			]
		);
	}
}
