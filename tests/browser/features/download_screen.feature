@chrome @en.wikipedia.beta.wmflabs.org @firefox @integration
Feature: ElectronPdfService Download Screen
  Background:
    Given I am on the Main Page

  Scenario: Download screen is shown with correct data
    When I click Download as PDF
    Then I should see the Download button
