Feature: Logs Context - Watchdog Log Reporting
  As a tester
  I want to verify that LogsContext can collect and report logs
  So that I can validate log reporting functionality

  @api
  Scenario: Generate watchdog logs
    Given I am on the homepage
    # Visit a path that might generate logs
    When I am on "/admin/reports/status"
    Then the response status code should be 403 or 200
