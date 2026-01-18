# Git Hooks

This directory contains Git hooks to help maintain code quality.

## Installation

To enable the pre-commit hook:

```bash
# Install the hook
cp .githooks/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

Or use Git's built-in support (Git 2.9+):

```bash
git config core.hooksPath .githooks
```

## Available Hooks

### pre-commit

Runs before each commit to ensure basic quality checks pass:

- ✅ Verifies composer dependencies are installed
- ✅ Runs PHPUnit tests (fast, no coverage calculation)
- ✅ Reminds you to run `./bin/check` before creating/updating PRs

**To bypass the hook** (not recommended):
```bash
git commit --no-verify
```

## Workflow

### During Development (Fast Iteration)

```bash
# Make changes to code

# Run specific tests for quick feedback
./vendor/bin/phpunit tests/Discoverer/DiscovererSetTest.php

# Commit (pre-commit hook runs tests automatically)
git commit -m "Your commit message"
```

### Before Creating/Updating a PR (Full Validation)

```bash
# ALWAYS run full validation before PR
./bin/check

# This runs:
# - All tests with 100% coverage requirement
# - Static analysis (phpcs, phpmd, phan)
# - Lint checks
```

## Why This Workflow?

- **Fast feedback during development**: Tests run quickly without coverage/static analysis overhead
- **Comprehensive validation before PR**: Full CI checks ensure nothing is missed
- **Prevents broken commits**: Pre-commit hook catches test failures early
- **Consistent with CI**: `./bin/check` runs the exact same checks as GitHub Actions
