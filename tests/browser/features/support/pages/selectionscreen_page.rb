class SelectionScreenPage
  include PageObject

  div(:selectionscreen_header, css: '.mw-electronPdfService-selection-header')
  radio_button(:singlecolumn_selection, css: '.mw-electronPdfService-selection-form [value=download-electron-pdf]')
  radio_button(:twocolumn_selection, css: '.mw-electronPdfService-selection-form [value=redirect-to-collection]')
  button(:pdf_download_button, css: '.mw-electronPdfService-selection-form .oo-ui-buttonElement-button')
end
