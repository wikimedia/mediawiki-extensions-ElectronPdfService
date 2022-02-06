<?php
/**
 * Hooks for ElectronPdfService extension
 *
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\ElectronPdfService;

use Action;
use MediaWiki\MediaWikiServices;
use OutputPage;
use Skin;
use SpecialPage;
use Title;

class Hooks {

	/**
	 * If present, make the "Download as PDF" link in the sidebar point to the download screen,
	 * add a new link otherwise.
	 *
	 * @param Skin $skin
	 * @param mixed &$bar
	 */
	public static function onSidebarBeforeOutput( Skin $skin, &$bar ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$title = $skin->getTitle();
		if ( $title === null || !$title->exists() ) {
			return;
		}

		$action = Action::getActionName( $skin );
		if ( $action !== 'view' && $action !== 'purge' ) {
			return;
		}

		$output = $skin->getOutput();

		if (
			$output->isRevisionCurrent() &&
			$config->has( 'CollectionFormats' ) &&
			array_key_exists( 'coll-print_export', $bar )
		) {
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
					'text' => $skin->msg( 'electronpdfservice-sidebar-portlet-print-text' )->text(),
					'id' => 'electron-print_pdf',
					'href' => self::generateDownloadScreenLink( $title )
				];
			}
		} else {
			// in case Collection is not installed, let's add our own portlet
			// with a link to the download screen
			$out = [];
			if ( $output->isRevisionCurrent() ) {
				$out[] = [
					'text' => $skin->msg( 'electronpdfservice-sidebar-portlet-print-text' )->text(),
					'id' => 'electron-print_pdf',
					'href' => self::generateDownloadScreenLink( $title )
				];
			}

			if ( !$skin->getOutput()->isPrintable() && isset( $bar['TOOLBOX']['print'] ) ) {
				$printItem = $bar['TOOLBOX']['print'];

				// Unset 'print' item and move it to our section
				unset( $bar['TOOLBOX']['print'] );
				$out[] = $printItem;
			}

			$bar['electronpdfservice-sidebar-portlet-heading'] = $out;
		}
	}

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
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
		return SpecialPage::getTitleFor( 'DownloadAsPdf' )->getLocalURL(
			[
				'page' => $title->getPrefixedDBkey(),
				'action' => 'redirect-to-electron'
			]
		);
	}

	private static function generateDownloadScreenLink( Title $title ) {
		return SpecialPage::getTitleFor( 'DownloadAsPdf' )->getLocalURL(
			[
				'page' => $title->getPrefixedDBkey(),
				'action' => 'show-download-screen'
			]
		);
	}

}
