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
# Tests the complete context.
#
@test "DebugContext: screenshot generation outputs URL" {

  mkdir -p web/sites/default/files/behat/screenshots
  mkdir -p web/sites/default/files/behat/errors

  prepare_test "debug-test.feature"

  # Run Behat with the screenshot feature
  run ddev exec behat --config="$BEHAT_YML_CURRENT_TEST_PATH" "$FEATURE_UNDER_TEST_PATH"
  # The feature triggers an error to test the functionality of creating error
  # report files, so lest's expect a failure here.
  assert_failure

  local filepath
  filepath="$TEST_ROOT_DIR/web/sites/default/files/behat"

  # Assert the "save last response" screenshot file was created
  assert_file_exists "$filepath/screenshots/varwwwhtmlwebsitesdefaultfilesbehatscreenshots_last_response.png"

  # Assert the error report files were created
  local report_filename_template
  report_filename_template="$filepath/errors/behat-failed__-var-www-html-tests-behat-local-features-feature_under_test.feature-And_I-should-be-on-non-existent-page"

  assert_file_exists "$report_filename_template.html"
  assert_file_exists "$report_filename_template.png"
  assert_file_exists "$report_filename_template.txt"
}
