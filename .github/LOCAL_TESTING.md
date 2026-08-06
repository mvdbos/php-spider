# Local Testing with nektos/act

This project is configured to run GitHub Actions locally using [nektos/act](https://nektosact.com/).

## Prerequisites

- Docker Desktop must be running
- `act` is installed (via Homebrew: `brew install act`)

## Quick Start

### Fastest Path: Validate with PHP 8.5

For developers making changes, use this to validate quickly before pushing:

```bash
# Run full CI validation with PHP 8.5 (the only supported version)
./bin/check
```

### Full act Usage

```bash
# Run all workflows (push event) with PHP 8.5
./bin/act --matrix php-versions:8.5

# Run workflows for pull request event
./bin/act pull_request

# Run a specific job
./bin/act -j build

# List all workflows and jobs
./bin/act -l

# Run with specific PHP version matrix
./bin/act --matrix php-versions:8.5

# Dry run (don't execute, just show what would run)
./bin/act -n
```

## Configuration

The project uses `.actrc` to configure act with:
- `shivammathur/node:latest` image for PHP testing
- `--pull=false` to prevent re-pulling images on every run
- Local cache directories for artifacts and cache data
- `--use-gitignore=false` to ensure all project files are available

### Custom Configuration

You can create a `.actrc.local` file (gitignored) for personal overrides:

```bash
# Copy the main config as a starting point and customize
cp .actrc .actrc.local
```

**Note**: You may see warnings like `::warning::Failed to save: reserveCache failed: socket hang up`. This is harmless - refers to GitHub's cloud cache API. Your Composer packages ARE being cached locally.


## Troubleshooting

### Docker not running
```
❌ Docker is not running!
```
**Solution**: Start Docker Desktop

### Permission issues
If you encounter permission errors, ensure the script is executable:
```bash
chmod +x bin/act
```

### Cache issues
Clear act cache:
```bash
rm -rf ~/.cache/act
```

### Image already up to date
If you see "Image is up to date", act is using the cached image (good!). To force update:
```bash
./bin/act --pull
```

### Viewing logs
Run with verbose output:
```bash
./bin/act -v
```

## Matrix Testing

**Recommended for CI validation**: PHP-Spider requires PHP >= 8.5, so there is a single supported version to test:

```bash
# Default and recommended - validates with PHP 8.5
./bin/check

# Explicitly with PHP 8.5 (same as check)
./bin/act --matrix php-versions:8.5
```

## What Gets Tested Locally

The local execution runs:
1. Composer validation
2. Dependency installation
3. Test suite with 100% coverage requirement (`bin/coverage-enforce 100`)
4. Static analysis (`bin/static-analysis`)

## Resources

- [nektos/act documentation](https://nektosact.com/)
- [shivammathur/setup-php local testing guide](https://github.com/shivammathur/setup-php#local-testing-setup)
