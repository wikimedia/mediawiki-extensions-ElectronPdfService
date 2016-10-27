Given(/^I am on the Main Page$/) do
  visit(MainPage)
end

When(/^I click Download as PDF$/) do
  on(MainPage).download_as_pdf_element.when_visible.click
end
