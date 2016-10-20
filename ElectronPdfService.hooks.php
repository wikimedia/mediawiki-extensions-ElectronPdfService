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
		}

		return true;
	}
}
