class MainPage
  include PageObject

  li(:download_as_pdf, css: '#coll-download-as-rl')

  page_url 'Main_Page'
end
