Then(/^I should see the Download button$/) do
  on(DownloadScreenPage) do |page|
    expect(page.pdf_download_button_element).to be_visible
  end
end
