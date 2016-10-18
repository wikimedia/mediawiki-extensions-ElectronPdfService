( function () {
	function setSelected( $element ) {
		$( '.mw-electronPdfService-selection-form label' ).removeClass( 'mw-electronPdfService-selection-form-selected' );
		$element.closest( 'label' ).addClass( 'mw-electronPdfService-selection-form-selected' );
		$element.blur();
	}

	setSelected( $( '.mw-electronPdfService-selection-form .oo-ui-radioInputWidget :checked' ) );

	$( '.mw-electronPdfService-selection-form .oo-ui-radioInputWidget [type="radio"]' ).click( function () {
		setSelected( $( this ) );
	} );
}() );
