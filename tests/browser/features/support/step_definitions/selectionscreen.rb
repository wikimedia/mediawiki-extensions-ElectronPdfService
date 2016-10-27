Then(/^Selection screen header should be there$/) do
  expect(on(SelectionScreenPage).selectionscreen_header_element).to be_visible
end

Then(/^Selection elements should be there$/) do
  on(SelectionScreenPage) do |page|
    expect(page.singlecolumn_selection_element.exists?).to be true
    expect(page.twocolumn_selection_element.exists?).to be true
  end
end

Then(/^Download button should be there$/) do
  on(SelectionScreenPage) do |page|
    expect(page.pdf_download_button_element).to be_visible
  end
end

Then(/^Single column option should be selected$/) do
  on(SelectionScreenPage) do |page|
    expect(page.singlecolumn_selection_element.attribute('checked')).to be_truthy
    expect(page.twocolumn_selection_element.attribute('checked')).to be_falsey
  end
end
