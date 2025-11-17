#!/usr/bin/env bats

# External variables used by this file:
#   - TMP_DIR: Base temp directory for tests (default: /tmp)
#   - GITHUB_TOKEN: GitHub token for composer auth (optional)


# Configure GitHub token for composer to avoid rate limiting
#
# Parameters:
#  (Global) GITHUB_TOKEN: GitHub token string
configure_composer_github_token() {
  if [ -n "${GITHUB_TOKEN:-}" ]; then
    echo "# Configuring composer with GitHub token to avoid rate limiting" >&3
    printf '%s\n' "{\"github-oauth\": {\"github.com\": \"${GITHUB_TOKEN}\"}}" > auth.json
    chmod 600 auth.json || true
  fi
}

# Create a unique temporary directory where to run the tests
create_temp_dir() {
  # Create unique temp directory for this test
  local tmp_dir="${1:-/tmp}"
  TEST_TEMP_DIR=$(mktemp -d ${tmp_dir:-~/tmp}/bats-behat-XXXXXX)
  echo "# Created temporary test directory: ${TEST_TEMP_DIR}" >&3
  cd "$TEST_TEMP_DIR" || exit 1
}

# Configure DDEV and install Aljibe
setup_ddev_and_aljibe() {

  echo "# Setting up DDEV and Aljibe..." >&3

  export DDEV_NONINTERACTIVE=true
  export DDEV_NO_INSTRUMENTATION=true

  ddev config --auto
  ddev add-on get metadrop/ddev-aljibe
  ddev aljibe-assistant -a -p standard
}

# Overrides behat-contexts installation to use local source that is being tested
install_behat_contexts_from_source() {
    # Install behat-contexts library from local source
  echo "# Installing behat-contexts from local source..." >&3

  # Check that BEHAT_CONTEXTS_SOURCE_PATH is set
  if [ -z "${BEHAT_CONTEXTS_SOURCE_PATH}" ]; then
    echo "# ERROR: BEHAT_CONTEXTS_SOURCE_PATH environment variable must be set" >&3
    echo "#        It should point to the behat-contexts source path under testing" >&3
    return 1
  fi

  # Check that BEHAT_CONTEXTS_BRANCH is set
  if [ -z "${BEHAT_CONTEXTS_BRANCH}" ]; then
    echo "# ERROR: BEHAT_CONTEXTS_BRANCH environment variable must be set" >&3
    echo "#        It should contain the branch tat needs to be tested" >&3
    return 1
  fi

  cp -r "${BEHAT_CONTEXTS_SOURCE_PATH}" .

  # Add path repository to composer.json
  ddev composer config repositories.behat-contexts path "./$(basename "${BEHAT_CONTEXTS_SOURCE_PATH}")"


  echo "# GitHub REF: ${GITHUB_REF}"
  echo "# GitHub REF NAME: ${GITHUB_REF_NAME}"
  echo "# DEBUG_GITHUB_REF: ${DEBUG_GITHUB_REF}..."
  env

  # Require the library. Use alias to force specific branch.
  ddev composer require "metadrop/behat-contexts:dev-${BEHAT_CONTEXTS_BRANCH} as 1.99"
}

#
# Setup function - called before tests begin to prepare the environment
setup_suite() {

  echo "# Setting up test suite environment..." >&3

  create_temp_dir "$TMP_DIR"
  configure_composer_github_token
  setup_ddev_and_aljibe
  install_behat_contexts_from_source

  # Ensure DDEV is running
  if ! ddev describe >/dev/null 2>&1; then
    echo "# ERROR: DDEV project not running." >&3
    exit 1
  fi
}

#
# Teardown function - called after test suite to clean up the environment
#
teardown_suite() {

  return

  echo "# Tearing down test environment..." >&3

  # Destroy DDEV containers before cleanup
  cd "${TEST_TEMP_DIR}" || exit 1
  ddev delete -Oy ${TEST_TEMP_DIR} >/dev/null 2>&1
  echo "# DDEV project deleted." >&3

  cd ..
  rm -rf "${TEST_TEMP_DIR}"
  echo "# Removed temporary test directory: ${TEST_TEMP_DIR}" >&3
}