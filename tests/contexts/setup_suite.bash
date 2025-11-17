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

determine_source_path() {
  if [ -z "${BEHAT_CONTEXTS_SOURCE_PATH}" ]; then
    echo "# BEHAT_CONTEXTS_SOURCE_PATH is not set; trying GITHUB_WORKSPACE..." >&3
    if [ -n "${GITHUB_WORKSPACE:-}" ]; then
      echo "# Using GITHUB_WORKSPACE as behat-contexts source: ${GITHUB_WORKSPACE}" >&3
      BEHAT_CONTEXTS_SOURCE_PATH="${GITHUB_WORKSPACE}"
    else
      echo "# ERROR: BEHAT_CONTEXTS_SOURCE_PATH is not set and GITHUB_WORKSPACE is not available." >&3
      exit 1
    fi
  fi

  echo "# BEHAT_CONTEXTS_SOURCE_PATH is set to: ${BEHAT_CONTEXTS_SOURCE_PATH}" >&3
  export BEHAT_CONTEXTS_SOURCE_PATH
}

# Overrides behat-contexts installation to use local source that is being tested
install_behat_contexts_from_source() {


    # Install behat-contexts library from local source
  echo "# Installing behat-contexts from local source..." >&3

  rm vendor/metadrop/behat-contexts -rf || true
  cp -r "${BEHAT_CONTEXTS_SOURCE_PATH}" vendor/metadrop/behat-contexts

  ls -la vendor/metadrop/behat-contexts >&3

}

#
# Setup function - called before tests begin to prepare the environment
setup_suite() {

  echo "# Setting up test suite environment..." >&3

  determine_source_path
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

  echo "# Tearing down test environment..." >&3

  # Destroy DDEV containers before cleanup
  cd "${TEST_TEMP_DIR}" || exit 1
  ddev delete -Oy ${TEST_TEMP_DIR} >/dev/null 2>&1
  echo "# DDEV project deleted." >&3

  cd ..
  rm -rf "${TEST_TEMP_DIR}"
  echo "# Removed temporary test directory: ${TEST_TEMP_DIR}" >&3
}