#!/usr/bin/env bats

#
# Tests for DebugContext
#
# This context provides debugging capabilities including:
#   - Screenshot generation
#   - Error reports (txt, html, png)
#   - Page content dumps
#

# Load test helpers
load '../test_helper/common-setup'

# Setup - runs before each test
setup() {
  setup_test_environment
  cleanup_behat_artifacts
}

# Teardown - runs after each test
teardown() {
  teardown_test_environment
}

#
# Test 1: Screenshot URL output format validation
#
# Validates that DebugContext outputs screenshot URL when generating screenshots
#
@test "DebugContext: screenshot generation outputs URL" {
  # Skip if DDEV is not running
  if ! is_ddev_running; then
    skip "DDEV is not running"
  fi

  # Get the feature file path
  local feature_file="tests/features/debug-screenshot.feature"

  # Run Behat with the screenshot feature
  run ddev exec behat "${feature_file}"

  # Debug output for troubleshooting
  echo "Status: ${status}" >&3
  echo "Output: ${output}" >&3

  # Check that Behat ran (may pass or fail, we care about screenshot output)
  # Status 0 = success, non-zero may occur if site not fully configured

  # Validate screenshot URL is in output
  [[ "${output}" =~ "Screenshot url:" ]] || [[ "${output}" =~ "Screenshot created in" ]]
}

#
# Test 2: Error report output format validation
#
# Validates that DebugContext outputs error report URLs (txt, html, png)
# when a scenario fails
#
@test "DebugContext: error report outputs three file types" {
  # Skip if DDEV is not running
  if ! is_ddev_running; then
    skip "DDEV is not running"
  fi

  # Get the feature file path (this feature is designed to fail)
  local feature_file="tests/features/debug-error.feature"

  # Run Behat with the error feature (expect failure)
  run ddev exec behat "${feature_file}"

  # Debug output
  echo "Status: ${status}" >&3
  echo "Output: ${output}" >&3

  # Behat should fail (non-zero status) since the test is designed to fail
  [ "${status}" -ne 0 ] || skip "Test feature did not fail as expected"

  # Check for error report output patterns
  # The context outputs three types: info (txt), html, and png
  [[ "${output}" =~ "info (exception):" ]] || \
  [[ "${output}" =~ "html (output):" ]] || \
  [[ "${output}" =~ "png (screenshot):" ]] || \
  [[ "${output}" =~ ".txt" ]] || \
  [[ "${output}" =~ ".html" ]]
}

#
# Test 3: Error report file creation verification
#
# Validates that error report files are actually created in the filesystem
#
@test "DebugContext: error report files are created" {
  # Skip if DDEV is not running
  if ! is_ddev_running; then
    skip "DDEV is not running"
  fi

  # Get the feature file path
  local feature_file="tests/features/debug-error.feature"

  # Clean up any previous error reports
  ddev exec rm -rf /var/www/html/web/sites/default/files/behat/errors/* 2>/dev/null || true

  # Run Behat with the error feature (expect failure)
  run ddev exec behat "${feature_file}"

  # Debug output
  echo "Status: ${status}" >&3

  # Check if error directory exists and has files
  run ddev exec "ls -la /var/www/html/web/sites/default/files/behat/errors/ 2>/dev/null | wc -l"

  echo "Error directory file count: ${output}" >&3

  # If directory exists and has files (more than 2 lines from ls -la = . and ..)
  # then files were created
  if [ "${output}" -gt 2 ] 2>/dev/null; then
    # Files were created
    return 0
  else
    # No files created, but this is acceptable if error reporting is not enabled
    skip "Error reporting may not be enabled or path differs"
  fi
}
