#!/bin/bash


if [ -z "${BEHAT_CONTEXTS_SOURCE_PATH}" ]; then
  echo "BEHAT_CONTEXTS_SOURCE_PATH environment variable is not set. Please set it to the local path of the behat-contexts source code before running this script"
fi

if [ -z "${GITHUB_TOKEN}" ]; then
  echo "GITHUB_TOKEN environment variable is not set. Please set it to a valid GitHub token before running this script. This is required to overcome GitHub API rate limits when installing dependencies via Composer."
  exit 1
fi

if [ -z "${TMP_DIR}" ]; then
  TMP_DIR="/tmp/"
  echo "TMP_DIR environment variable is not set. Using temporary directory: $TMP_DIR"
fi

cd "${BEHAT_CONTEXTS_SOURCE_PATH}"
if [ $? -ne 0 ]; then
  echo "Failed to change directory to BEHAT_CONTEXTS_SOURCE_PATH: ${BEHAT_CONTEXTS_SOURCE_PATH}"
  exit 1
fi

bats tests/contexts