Then(/^I should see a print form with a download button$/) do
  on(DownloadScreenPage) do |page|
    expect(page.pdf_download_button_element).to be_visible
  end
end
