class DownloadScreenPage
  include PageObject

  hidden_field(:redirect_to_electron_action, css: '.mw-electronPdfService-selection-form [value=redirect-to-electron]')
  button(:pdf_download_button, css: '.mw-electronPdfService-selection-form .oo-ui-buttonElement-button')
end
