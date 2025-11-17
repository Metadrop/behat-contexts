# Behat Contexts Testing Infrastructure

This directory contains a BATS-based testing infrastructure for validating Behat context outputs and behaviors.

## Table of Contents

- [Overview](#overview)
- [Prerequisites](#prerequisites)
- [Running Tests Locally](#running-tests-locally)
- [Running Tests in CI/CD](#running-tests-in-cicd)
- [Test Structure](#test-structure)
- [How to Add New Tests](#how-to-add-new-tests)
- [Troubleshooting](#troubleshooting)

## Overview

This testing infrastructure uses:
- **BATS (Bash Automated Testing System)**: Test framework for bash scripts
- **DDEV**: Local development environment
- **Aljibe**: DDEV add-on that provides automated Drupal + Behat setup

The tests validate that Behat contexts produce expected outputs and behaviors through integration testing.

## Prerequisites

### Local Development

- **DDEV**: [Install DDEV](https://ddev.readthedocs.io/en/stable/)
- **BATS**: Install via your package manager
  ```bash
  # macOS
  brew install bats-core

  # Ubuntu/Debian
  sudo apt-get install bats
  ```
- **BATS Libraries**: Required for assertions
  ```bash
  # Clone to standard library path
  sudo git clone https://github.com/bats-core/bats-support /usr/lib/bats-support
  sudo git clone https://github.com/bats-core/bats-assert /usr/lib/bats-assert
  sudo git clone https://github.com/bats-core/bats-file /usr/lib/bats-file
  ```
- **GitHub Token**: For composer authentication (avoids rate limiting)

### CI/CD

GitHub Actions workflow automatically installs all prerequisites.

## Running Tests Locally

### Quick Start

1. **Set required environment variables**:
   ```bash
   export BEHAT_CONTEXTS_SOURCE_PATH=/path/to/behat-contexts
   export GITHUB_TOKEN=your_github_token
   export TMP_DIR=/tmp  # Optional, defaults to /tmp
   ```

2. **Run the test script**:
   ```bash
   cd /path/to/behat-contexts
   ./tests/run_tests_locally.sh
   ```

### Running Specific Tests

Run individual test files directly:

```bash
# Set environment variables first
export BEHAT_CONTEXTS_SOURCE_PATH=$(pwd)
export GITHUB_TOKEN=your_token

# Run specific test file
bats tests/contexts/cookie-compliance-context.bats
```

Run specific test by name:

```bash
bats tests/contexts/cookie-compliance-context.bats --filter "CookieComplianceContext"
```

### Environment Variables

| Variable | Required | Description | Default |
|----------|----------|-------------|---------|
| `BEHAT_CONTEXTS_SOURCE_PATH` | Yes | Absolute path to behat-contexts source | - |
| `GITHUB_TOKEN` | Yes | GitHub personal access token | - |
| `TMP_DIR` | No | Base directory for temporary test files | `/tmp` |
| `BATS_LIB_PATH` | No | Path to BATS libraries | `/usr/lib` |

## Running Tests in CI/CD

Tests run automatically in GitHub Actions on:
- Push to `main` or `dev` branches
- Pull requests to `main` or `dev` branches
- Manual workflow dispatch

The workflow:
1. Installs DDEV and BATS with all libraries
2. Sets up DDEV with Aljibe
3. Installs behat-contexts from source
4. Runs BATS tests
5. Uploads artifacts on failure

See `.github/workflows/test-contexts.yml` for details.

## Test Structure

```
tests/
├── contexts/                   # BATS test files
│   ├── setup_suite.bash       # Suite-level setup functions
│   ├── cookie-compliance-context.bats
│   ├── debug-context.bats
│   └── logs-context.bats
├── features/                   # Test fixture feature files
│   ├── cookies-test.feature
│   ├── debug-screenshot.feature
│   ├── debug-error.feature
│   └── logs-test.feature
├── test_helper/               # Shared test utilities
│   └── common-setup.bash     # Common setup/teardown functions
└── run_tests_locally.sh      # Local test runner script
```

### Test Files

Each `.bats` file contains tests for a specific Behat context:

- **cookie-compliance-context.bats**: Tests CookieComplianceContext
- **debug-context.bats**: Tests DebugContext (screenshots, error reports)
- **logs-context.bats**: Tests LogsContext (watchdog logs, CSV reports)

### Feature Files

Minimal Behat feature files designed to exercise specific context behaviors:

- **cookies-test.feature**: Cookie acceptance/rejection scenarios
- **debug-screenshot.feature**: Screenshot generation
- **debug-error.feature**: Error report generation
- **logs-test.feature**: Log collection and reporting

## How to Add New Tests

### 1. Create a Feature File

Add a minimal `.feature` file in `tests/features/`:

```gherkin
Feature: MyContext - Test Description
  As a tester
  I want to verify MyContext behavior
  So that I can validate functionality

  @api
  Scenario: Test scenario name
    Given I am on the homepage
    When I perform some action
    Then I should see expected output
```

### 2. Create a BATS Test File

Add a `.bats` file in `tests/contexts/`:

```bash
#!/usr/bin/env bats

# Load test helpers
load '../test_helper/common-setup'
load 'setup_suite'

# Setup - runs before each test
setup() {
  setup_test_environment
}

# Teardown - runs after each test
teardown() {
  teardown_test_environment
}

@test "MyContext: test description" {
  local start='        - Metadrop\Behat\Context\PreviousContext:'
  local end='        - Metadrop\Behat\Context\NextContext:'
  local replacement='            my_param: value'

  prepare_test "my-feature.feature" "$start" "$end" "$replacement"

  # Run Behat
  run ddev exec behat --config="$BEHAT_YML_CURRENT_TEST_PATH" "$FEATURE_UNDER_TEST_PATH"

  # Assertions
  assert_success "Error detected while testing MyContext"
  assert_output --partial "Expected output"
}
```

### 3. Update CI Workflow (Optional)

For PoC, tests run individually. To add your test to CI:

Edit `.github/workflows/test-contexts.yml`:
```yaml
- name: Run BATS tests
  run: |
    bats tests/contexts/my-context.bats
```

## Troubleshooting

### BATS Library Not Found

**Error**: `Could not find library 'bats-assert'`

**Solution**: Ensure BATS libraries are installed and `BATS_LIB_PATH` is set:
```bash
export BATS_LIB_PATH=/usr/lib
ls -la /usr/lib/bats-*
```

### BEHAT_CONTEXTS_SOURCE_PATH Not Set

**Error**: `BEHAT_CONTEXTS_SOURCE_PATH environment variable must be set`

**Solution**: Set the variable to your local repository path:
```bash
export BEHAT_CONTEXTS_SOURCE_PATH=$(pwd)
```

### DDEV Not Running

**Error**: `DDEV is not running`

**Solution**: Tests set up their own DDEV environment. Ensure DDEV is installed:
```bash
ddev version
```

### GitHub Token Rate Limiting

**Error**: Composer rate limit errors

**Solution**: Set a valid GitHub token:
```bash
export GITHUB_TOKEN=ghp_your_token_here
```

Create a token at: https://github.com/settings/tokens

### Test Isolation Issues

Each test runs in a fresh temporary directory with its own DDEV environment. If tests interfere with each other:

1. Check that `setup_suite.bash` functions are called properly
2. Verify cleanup happens in `teardown_test_environment()`
3. Ensure no shared state between tests

### Debugging Tests

Run tests with verbose output:
```bash
bats --verbose-run tests/contexts/my-context.bats
```

Enable trace mode:
```bash
bats --trace tests/contexts/my-context.bats
```

Check DDEV logs:
```bash
ddev logs
```

## Contributing

When adding new tests:

1. Follow existing test patterns
2. Use descriptive test names
3. Include comments explaining complex logic
4. Ensure tests are isolated (no shared state)
5. Test both success and failure scenarios
6. Update this documentation if adding new patterns

## References

- [BATS Documentation](https://bats-core.readthedocs.io/)
- [DDEV Documentation](https://ddev.readthedocs.io/)
- [Aljibe Repository](https://github.com/Metadrop/ddev-aljibe)
- [Behat Documentation](https://docs.behat.org/)
