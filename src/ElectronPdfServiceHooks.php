<?php
/**
 * Hooks for ElectronPdfService extension
 *
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\MediaWikiServices;

class ElectronPdfServiceHooks {

	/**
	 * If present, make the "Download as PDF" link in the sidebar point to the download screen,
	 * add a new link otherwise.
	 *
	 * @param Skin $skin
	 * @param mixed &$bar
	 * @return bool
	 */
	public static function onSidebarBeforeOutput( Skin $skin, &$bar ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$title = $skin->getTitle();
		if ( is_null( $title ) || !$title->exists() ) {
			return true;
		}

		$action = Action::getActionName( $skin );
		if ( $action !== 'view' && $action !== 'purge' ) {
			return true;
		}

		if ( $config->has( 'CollectionFormats' ) && array_key_exists( 'coll-print_export', $bar ) ) {
			$index = self::getIndexOfDownloadPdfSidebarItem(
				$bar['coll-print_export'],
				$config->get( 'CollectionFormats' )
			);
			// if Collection extension provides a download-as-pdf link, make it point to the download screen
			if ( $index !== false ) {
				$bar['coll-print_export'][$index]['href'] = self::generateDownloadScreenLink(
					$title
				);
			// if no download-as-pdf link is there, add one and point to the download screen
			} else {
				$bar['coll-print_export'][] = [
					'text' => $skin->msg( 'electronPdfService-sidebar-portlet-print-text' ),
					'id' => 'electron-print_pdf',
					'href' => self::generateDownloadScreenLink( $title )
				];
			}
		} else {
			// in case Collection is not installed, let's add our own portlet
			// with a link to the download screen
			$bar['electronPdfService-sidebar-portlet-heading'][] = [
				'text' => $skin->msg( 'electronPdfService-sidebar-portlet-print-text' ),
				'id' => 'electron-print_pdf',
				'href' => self::generateDownloadScreenLink( $title )
			];
		}

		return true;
	}

	/**
	 * @param OutputPage &$out
	 * @param Skin &$skin
	 */
	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
		$userAgent = $out->getRequest()->getHeader( 'User-Agent' );

		if ( strstr( $userAgent, 'electron-render-service' ) ) {
			$out->addModuleStyles( 'ext.ElectronPdfService.print.styles' );
		}
	}

	private static function getIndexOfDownloadPdfSidebarItem( $portlet, $collectionFormats ) {
		$usedPdfLib = array_search( 'PDF', $collectionFormats );
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
				'action' => 'redirect-to-electron'
			]
		);
	}

	private static function generateDownloadScreenLink( Title $title ) {
		$specialPageTitle = SpecialPage::getTitleFor( 'ElectronPdf' );

		return $specialPageTitle->getLocalURL(
			[
				'page' => $title->getPrefixedText(),
				'action' => 'show-download-screen'
			]
		);
	}

}
