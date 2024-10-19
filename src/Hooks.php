<?php
/**
 * Hooks for ElectronPdfService extension
 *
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\ElectronPdfService;

use MediaWiki\Hook\SidebarBeforeOutputHook;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use Skin;

class Hooks implements SidebarBeforeOutputHook {

	/**
	 * If present, make the "Download as PDF" link in the sidebar point to the download screen,
	 * add a new link otherwise.
	 *
	 * @param Skin $skin
	 * @param array &$bar
	 */
	public function onSidebarBeforeOutput( $skin, &$bar ): void {
		$title = $skin->getTitle();
		if ( $title === null || !$title->exists() ) {
			return;
		}

		$action = $skin->getActionName();
		if ( $action !== 'view' && $action !== 'purge' ) {
			return;
		}

		$output = $skin->getOutput();
		$config = $skin->getConfig();

		if (
			ExtensionRegistry::getInstance()->isLoaded( 'Collection' ) &&
			$config->has( 'CollectionFormats' ) &&
			array_key_exists( 'coll-print_export', $bar )
		) {
			$index = self::getIndexOfDownloadPdfSidebarItem(
				$bar['coll-print_export'],
				$config->get( 'CollectionFormats' )
			);

			if ( $output->isRevisionCurrent() ) {
				if ( $index !== false ) {
					// if Collection extension provides a download-as-pdf link, make it point to the download screen
					$bar['coll-print_export'][$index]['href'] = self::generateDownloadScreenLink(
						$title
					);
				} else {
					// if no download-as-pdf link is there, add one and point to the download screen
					$bar['coll-print_export'][] = [
						'text' => $skin->msg( 'electronpdfservice-sidebar-portlet-print-text' )->text(),
						'id' => 'electron-print_pdf',
						'href' => self::generateDownloadScreenLink( $title )
					];
				}
			} elseif ( $index ) {
				// Electron/Proton do not support generating PDFs for old versions, but Collection did
				unset( $bar['coll-print_export'][$index] );
			}
			return;
		}

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

	private static function generateDownloadScreenLink( Title $title ) {
		return SpecialPage::getTitleFor( 'DownloadAsPdf' )->getLocalURL(
			[
				'page' => $title->getPrefixedDBkey(),
				'action' => 'show-download-screen'
			]
		);
	}

}
