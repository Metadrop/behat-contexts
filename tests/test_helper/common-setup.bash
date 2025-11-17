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
# Currently a no-op, can be extended for setup if needed.
#
setup_test_environment() {
  set -eu -o pipefail

  bats_load_library bats-assert
  bats_load_library bats-file
  bats_load_library bats-support
}

#
# Teardown function - called after each test
#
# Currently a no-op, can be extended for cleanup if needed.
teardown_test_environment() {
  set -eu -o pipefail
}

# Prepares a test by copying feature file and modifying behat.yml accordingly.
#
# Parameters:
#   $1 - Feature file name (relative to tests/features/)
#   $2 - Begin marker in behat.yml to locate insertion point
#   $3 - End marker in behat.yml to locate insertion point
#   $4 - Replacement text to insert between markers
#
# Parameters $2, $3, and $4 are optional; if not provided, the behat.yml
# will be copied without modifications.
prepare_test() {

  local BEHAT_YML_TEMPLATE_PATH="tests/behat/local/behat.yml"
  export BEHAT_YML_CURRENT_TEST_PATH="tests/behat/local/behat-test.yml"
  export FEATURE_UNDER_TEST_PATH="tests/behat/local/features/feature_under_test.feature"

  # Skip if DDEV is not running
  if ! is_ddev_running; then
    skip "DDEV is not running"
  fi

  if [ -f "$BEHAT_YML_CURRENT_TEST_PATH" ]; then
    rm "$BEHAT_YML_CURRENT_TEST_PATH"
  fi

  if [ -f "$FEATURE_UNDER_TEST_PATH" ]; then
    rm "$FEATURE_UNDER_TEST_PATH"
  fi

  # At this point all checks and clean up actions are done.

  # Now copy the feature file and the behat.yml template
  local FEATURE_FILE="tests/features/$1"
  cp "$BEHAT_CONTEXTS_SOURCE_PATH/$FEATURE_FILE" "$FEATURE_UNDER_TEST_PATH"

  # Now decide whether to modify behat.yml or just copy it depending on
  # given parameters.
  if [ $# -eq 1 ]; then
    cp "$BEHAT_YML_TEMPLATE_PATH" "$BEHAT_YML_CURRENT_TEST_PATH"
    return
  fi

  if [ -z "$2" ] || [ -z "$3" ] || [ -z "$4" ]; then
      echo "Error: Missing parameters for prepare_test function: begin marker, end marker and replacement are all required if any of them is provided (please provide all or none)." >&2
      exit 1
  fi

  local BEGIN_MARK="$2"
  local END_MARK="$3"
  local REPLACEMENT="$4"

  # Escape backslashes for sed safely
  sed_start=$(printf '%s\n' "$BEGIN_MARK" | sed 's/\\/\\\\/g')
  sed_end=$(printf '%s\n' "$END_MARK" | sed 's/\\/\\\\/g')

  sed \
  "
    /$sed_start/,/$sed_end/{
      /$sed_start/!{
        /$sed_end/!d
      }
    }
    /$sed_start/a\\
  $REPLACEMENT
  " "$BEHAT_YML_TEMPLATE_PATH" > "$BEHAT_YML_CURRENT_TEST_PATH"
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
