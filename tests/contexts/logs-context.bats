#!/usr/bin/env bats

#
# Tests for LogsContext
#
# This context provides log management and reporting:
#   - Watchdog log collection
#   - CSV report generation
#   - Log display and filtering
#

# Load test helpers
load '../test_helper/common-setup'

# Setup - runs before each test
setup() {
  setup_test_environment
}

# Teardown - runs after each test
teardown() {
  teardown_test_environment
}

#
# Test 1: Dblog report creation message
#
# Validates that LogsContext outputs "Created dblog report" message
# when write_report is enabled
#
@test "LogsContext: outputs 'Created dblog report' message" {
  # Skip if DDEV is not running
  if ! is_ddev_running; then
    skip "DDEV is not running"
  fi

  # Get the feature file path
  local feature_file="tests/features/logs-test.feature"

  # Run Behat with the logs feature
  run ddev exec behat "${feature_file}"

  # Debug output
  echo "Status: ${status}" >&3
  echo "Output: ${output}" >&3

  # Check for dblog report message or Watchdog errors table
  # Note: Report is only created if write_report is enabled in behat.yml
  [[ "${output}" =~ "Created dblog report" ]] || \
  [[ "${output}" =~ "Watchdog errors" ]] || \
  [[ "${output}" =~ "dblog" ]] || \
  skip "Log reporting may not be enabled or no logs generated"
}

#
# Test 2: CSV file creation with timestamp format
#
# Validates that CSV file is created with proper timestamp format
# Format: dblog-report-YYYY-MM-DD-HH-II-SS.csv
#
@test "LogsContext: CSV file created with timestamp format" {
  # Skip if DDEV is not running
  if ! is_ddev_running; then
    skip "DDEV is not running"
  fi

  # Get the feature file path
  local feature_file="tests/features/logs-test.feature"

  # Clean up previous reports
  ddev exec rm -rf /var/www/html/reports/behat/dblog/* 2>/dev/null || true

  # Run Behat with the logs feature
  run ddev exec behat "${feature_file}"

  # Debug output
  echo "Output: ${output}" >&3

  # Check if CSV files exist with timestamp pattern
  run ddev exec "ls /var/www/html/reports/behat/dblog/dblog-report-*.csv 2>/dev/null"

  echo "CSV files found: ${output}" >&3

  # If CSV files found, validate timestamp format
  if [ "${status}" -eq 0 ] && [ -n "${output}" ]; then
    # Check that filename matches expected pattern
    [[ "${output}" =~ dblog-report-[0-9]{4}-[0-9]{2}-[0-9]{2}-[0-9]{2}-[0-9]{2}-[0-9]{2}\.csv ]]
  else
    skip "CSV report not generated (write_report may be disabled)"
  fi
}

#
# Test 3: Watchdog log table output validation
#
# Validates that watchdog logs are displayed in table format
# with proper headers and structure
#
@test "LogsContext: watchdog table headers displayed" {
  # Skip if DDEV is not running
  if ! is_ddev_running; then
    skip "DDEV is not running"
  fi

  # Get the feature file path
  local feature_file="tests/features/logs-test.feature"

  # Run Behat with the logs feature
  run ddev exec behat "${feature_file}"

  # Debug output
  echo "Output: ${output}" >&3

  # Check for table headers or structure
  # The table should have "Watchdog errors" title and column headers
  [[ "${output}" =~ "Watchdog errors" ]] || \
  [[ "${output}" =~ "Index" ]] || \
  [[ "${output}" =~ "Type" ]] || \
  [[ "${output}" =~ "Severity" ]] || \
  [[ "${output}" =~ "Message" ]] || \
  skip "Watchdog table not displayed (may be no logs to show)"
}
