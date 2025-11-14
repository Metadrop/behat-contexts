#!/usr/bin/env bash

#
# Common setup functions for BATS tests
#
# This file provides shared utilities for testing Behat contexts
# with DDEV and Aljibe.
#

# Test temporary directory for artifacts
export TEST_TEMP_DIR=""

# DDEV project name (can be overridden)
export DDEV_PROJECT_NAME="${DDEV_PROJECT_NAME:-behat-contexts-test}"

# Base paths inside DDEV container
export DDEV_DOCROOT="/var/www/html/web"
export DDEV_BEHAT_DIR="/var/www/html"

#
# Setup function - called before each test
#
# Creates a unique temporary directory for test artifacts
# and ensures DDEV is running.
#
setup_test_environment() {

  set -eu -o pipefail

  export DDEV_NONINTERACTIVE=true
  export DDEV_NO_INSTRUMENTATION=true

  # Create unique temp directory for this test
  TEST_TEMP_DIR=$(mktemp -d ${TMPDIR:-~/tmp}/bats-behat-XXXXXX)

  cd "$TEST_TEMP_DIR"


  # Configure GitHub token for composer to avoid rate limiting
  # The token is passed as an environment variable from GitHub Actions
  if [ -n "${GITHUB_TOKEN:-}" ]; then
    echo "# Configuring composer with GitHub token to avoid rate limiting" >&3
    echo "{\"github-oauth\": {\"github.com\": \"${GITHUB_TOKEN}\"}}" > auth.json
  fi



  # Configure DDEV and install Aljibe
  echo "# Setting up DDEV and Aljibe..." >&3
  ddev config --auto
  ddev add-on get metadrop/ddev-aljibe
  ddev aljibe-assistant -a

  # Install behat-contexts library from local source
  echo "# Installing behat-contexts from local source..." >&3

  # Add path repository to composer.json
  ddev composer config repositories.behat-contexts path /var/www/html

  # Require the library
  ddev composer require metadrop/behat-contexts:@dev

  # Ensure DDEV is running
  if ! ddev describe >/dev/null 2>&1; then
    echo "# WARNING: DDEV project not running. Tests may fail." >&3
  fi
}

#
# Teardown function - called after each test
#
# Cleans up temporary test artifacts
#
teardown_test_environment() {
  # Destroy DDEV containers before cleanup
  if [[ -n "${TEST_TEMP_DIR}" && -d "${TEST_TEMP_DIR}" ]]; then
    ddev delete -Oy ${TEST_TEMP_DIR} >/dev/null 2>&1
  fi

  # Clean up temp directory if it exists
  if [[ -n "${TEST_TEMP_DIR}" && -d "${TEST_TEMP_DIR}" ]]; then
    rm -rf "${TEST_TEMP_DIR}"
  fi
}

#
# Check if DDEV is running and ready
#
# Returns 0 if DDEV is running, 1 otherwise
#
is_ddev_running() {
  ddev describe >/dev/null 2>&1
  return $?
}

#
# Execute a command inside the DDEV web container
#
# Usage: ddev_exec <command>
#
ddev_exec() {
  ddev exec "$@"
}

#
# Run Behat with specified feature file
#
# Usage: run_behat_feature <feature-file> [additional-args]
#
run_behat_feature() {
  local feature_file="$1"
  shift

  ddev exec behat "${feature_file}" "$@"
}

#
# Run Behat and capture output
#
# Usage: run_behat_with_output <feature-file> [additional-args]
#
# Output is stored in $output variable and return code in $status
#
run_behat_with_output() {
  local feature_file="$1"
  shift

  run ddev exec behat "${feature_file}" "$@"
}

#
# Get absolute path to test features directory
#
get_test_features_dir() {
  echo "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/features"
}

#
# Assert that output contains a specific string
#
# Usage: assert_output_contains <expected-string>
#
assert_output_contains() {
  local expected="$1"

  if [[ ! "${output}" =~ ${expected} ]]; then
    echo "# Expected output to contain: ${expected}" >&3
    echo "# Actual output: ${output}" >&3
    return 1
  fi

  return 0
}

#
# Clean up Behat artifacts from previous runs
#
# Removes error reports, screenshots, logs, etc.
#
cleanup_behat_artifacts() {
  ddev exec rm -rf \
    "${DDEV_DOCROOT}/sites/default/files/behat" \
    /var/www/html/reports/behat \
    /tmp/behat-* 2>/dev/null || true
}

#
# Wait for DDEV to be ready
#
# Usage: wait_for_ddev [timeout_seconds]
#
wait_for_ddev() {
  local timeout="${1:-30}"
  local elapsed=0

  while ! is_ddev_running; do
    if [ $elapsed -ge $timeout ]; then
      echo "# Timeout waiting for DDEV to be ready" >&3
      return 1
    fi

    sleep 1
    elapsed=$((elapsed + 1))
  done

  return 0
}

#
# Get timestamp in format suitable for filenames
#
get_timestamp() {
  date +"%Y%m%d_%H%M%S"
}

#
# Debug: Print test environment info
#
print_test_info() {
  echo "# Test Environment Info:" >&3
  echo "#   TEST_TEMP_DIR: ${TEST_TEMP_DIR}" >&3
  echo "#   DDEV Project: ${DDEV_PROJECT_NAME}" >&3
  echo "#   DDEV Running: $(is_ddev_running && echo 'Yes' || echo 'No')" >&3
}

#
# Load bats-support and bats-assert if available
#
load_bats_libs() {
  # Try to load bats-support
  if [ -f "/usr/lib/bats-support/load.bash" ]; then
    load "/usr/lib/bats-support/load.bash"
  elif [ -f "/usr/local/lib/bats-support/load.bash" ]; then
    load "/usr/local/lib/bats-support/load.bash"
  fi

  # Try to load bats-assert
  if [ -f "/usr/lib/bats-assert/load.bash" ]; then
    load "/usr/lib/bats-assert/load.bash"
  elif [ -f "/usr/local/lib/bats-assert/load.bash" ]; then
    load "/usr/local/lib/bats-assert/load.bash"
  fi
}
