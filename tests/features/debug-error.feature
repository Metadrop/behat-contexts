Feature: Debug Context - Error Report Generation
  As a tester
  I want to verify that DebugContext can generate error reports
  So that I can validate error reporting functionality

  @api
  Scenario: Trigger error report generation
    Given I am on the homepage
    # This step is intentionally designed to fail to trigger error report
    When I am on "/non-existent-path-that-should-404"
    Then I should see "This page should exist"
