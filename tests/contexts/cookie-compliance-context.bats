#!/usr/bin/env bats

#
# Tests for CookieComplianceContext
#
# This context provides cookie compliance testing:
#   - Cookie detection and display
#   - Third-party cookie warnings
#   - Cookie table output formatting
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
# Test 1: Cookie table output format
#
# Validates that CookieComplianceContext displays cookies in table format
# when cookies are present
#
@test "CookieComplianceContext" {

  local start='        - Metadrop\Behat\Context\CookieComplianceContext:'
  local end='        - Metadrop\Behat\Context\DebugContext:'
  local replacement='            cookie_manager_type: klaro'

  prepare_test "cookies-test.feature" "$start" "$end" "$replacement"

  # Let's a recipe to set up Drupal with Klaro cookie manager.
  ddev composer require drupal/drupal_cms_privacy_basic
  ddev drush recipe recipes/drupal_cms_privacy_basic/

  # Set dialog mode so Klaro displays cookie banner and it can be tested.
  ddev drush cset klaro.settings dialog_mode notice -y

  # Display button to accept all cookies for testing. This is because in a clean
  # Drupal CMS default accept button does the same as reject: only mandatory
  # cookies are accepted. We enable this so clicking accept accepts all cookies
  # we can tell the difference in tests.
  ddev drush cset klaro.settings accept_all true -y



  # Run Behat with the cookies feature
  run ddev exec behat --config="$BEHAT_YML_CURRENT_TEST_PATH" "$FEATURE_UNDER_TEST_PATH"
  assert_success "Error detected while testing CookieComplianceContext"
}
