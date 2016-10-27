@chrome @en.wikipedia.beta.wmflabs.org @firefox @integration
Feature: ElectronPdfService Selection Screen
  Background:
    Given I am on the Main Page

  Scenario: Selection screen is shown with correct default selection
    When I click Download as PDF
    Then Selection screen header should be there
    And Selection elements should be there
    And Download button should be there
    And Single column option should be selected
