Feature: Cookie Compliance Context - Cookie Detection
  As a tester
  I want to verify that CookieComplianceContext can detect and display cookies
  So that I can validate cookie compliance functionality

  @api @javascript
  Scenario: Accept cookies
    Given I am on the homepage
      And I wait for the cookie banner to appear
     Then I accept cookies
      And cookie banner should not be visible
      And the following cookie categories have been accepted: "cms, klaro, vimeo and youtube"

  @api @javascript @cookies-accepted
  Scenario: Use @cookies-accepted tag to accept cookies automatically
    Given I am on the homepage
     Then cookie banner should not be visible
     And the following cookie categories have been accepted: "cms, klaro, vimeo and youtube"


  @api @javascript
  Scenario: Reject cookies
    Given I am on the homepage
      And I wait for the cookie banner to appear
     Then I reject cookies
      And cookie banner should not be visible
     And the following cookie categories have been accepted: "cms and klaro"
     And the following cookie categories have been rejected: "vimeo and youtube"

  @api @javascript @cookies-rejected
  Scenario: Use @cookies-rejected tag to reject cookies automatically
    Given I am on the homepage
     Then cookie banner should not be visible
     And the following cookie categories have been accepted: "cms and klaro"
     And the following cookie categories have been rejected: "vimeo and youtube"
