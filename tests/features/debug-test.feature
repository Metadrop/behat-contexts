Feature: Debug Context - Error Report Generation
  As a tester
  I want to verify that DebugContext can generate error reports
  So that I can validate error reporting functionality

  @api @javascript
  Scenario: Trigger error report generation
    Given I am on the homepage
     Then save last response
    # This step is intentionally designed to fail to trigger error report
      And I should be on "non-existent-page"