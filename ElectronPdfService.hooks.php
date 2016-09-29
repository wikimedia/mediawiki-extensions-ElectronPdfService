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

		if ( array_key_exists( 'coll-print_export', $bar ) ) {
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

	public static function onBuildNavUrls( Skin $skin, &$navUrls ) {
		$title = $skin->getTitle();
		if ( is_null( $title ) || !$title->exists() ) {
			return false;
		}

		$specialPageTitle = SpecialPage::getTitleFor( 'ElectronPdf' );

		if ( array_key_exists( 'print', $navUrls ) ) {
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
