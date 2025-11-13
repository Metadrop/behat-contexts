Feature: Cookie Compliance Context - Cookie Detection
  As a tester
  I want to verify that CookieComplianceContext can detect and display cookies
  So that I can validate cookie compliance functionality

  @api @javascript
  Scenario: Check for cookies on homepage
    Given I am on the homepage
    # This step will list all cookies (or show "No cookies set" message)
    Then there should not be any cookies loaded

  @api @javascript
  Scenario: Verify mandatory cookies
    Given I am on the homepage
    When I reload the page
    # Check if mandatory cookies are loaded (depends on site configuration)
    Then the cookies of "mandatory" type have been loaded
