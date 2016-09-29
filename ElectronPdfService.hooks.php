<?php
/**
 * Hooks for ElectronPdfService extension
 *
 * @file
 * @ingroup Extensions
 * @license GPL-2.0+
 */

class ElectronPdfServiceHooks {

	public static function onSidebarBeforeOutput( Skin $skin, &$bar ) {
		$title = $skin->getTitle();
		if ( is_null( $title ) || !$title->exists() ) {
			return false;
		}

		$specialPageTitle = SpecialPage::getTitleFor( 'ElectronPdf' );

		// if the Collection extension is installed, modify their portlet
		if ( array_key_exists( 'coll-print_export', $bar ) ) {
			// TODO: don't add a new element to the sidebar, but reuse the "Download as PDF" link instead
			$bar['coll-print_export'][] = [
				'text' => $skin->msg( 'electronPdfService-sidebar-portlet-print-text' )->escaped(),
				'id' => 'electron-print_pdf',
				'href' => $specialPageTitle->getLocalURL(
					[ 'page' => $title->getPrefixedText() ]
				)
			];

			// find the element for the print version and override it with the link to our SpecialPage
			foreach ( $bar['coll-print_export'] as $index => $element ) {
				if ( $element['id'] === 't-print' ) {
					$bar['coll-print_export'][$index]['href'] = $specialPageTitle->getLocalURL(
						[ 'page' => $title->getPrefixedText() ]
					);
				}
			}
		}

		return true;
	}

	public static function onBuildNavUrls( Skin $skin, &$navUrls ) {
		$title = $skin->getTitle();
		if ( is_null( $title ) || !$title->exists() ) {
			return false;
		}

		$specialPageTitle = SpecialPage::getTitleFor( 'ElectronPdf' );

		// if there's an element for a print version, override it with the link to our SpecialPage
		if ( array_key_exists( 'print', $navUrls ) && $navUrls['print'] !== false ) {
			$navUrls['print'] = [
				'text' => $skin->msg( 'printableversion' )->text(),
				'href' => $specialPageTitle->getLocalURL(
					[ 'page' => $title->getPrefixedText() ]
				)
			];
		}

		return true;
	}
}
