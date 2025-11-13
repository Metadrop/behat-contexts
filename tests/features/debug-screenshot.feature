Feature: Debug Context - Screenshot Generation
  As a tester
  I want to verify that DebugContext can generate screenshots
  So that I can validate screenshot functionality

  @api
  Scenario: Generate a screenshot with specific width
    Given I am on the homepage
    Then capture full page with a width of 1200
