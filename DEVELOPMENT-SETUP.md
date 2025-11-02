# Development Setup Guide

This guide will help you set up your local development environment for the CTA Highlights plugin.

## Quick Start (Docker-Based - Recommended)

**TL;DR:** Only Docker Desktop and Node.js are required. Everything else runs in containers.

```bash
# 1. Install Docker Desktop (https://www.docker.com/products/docker-desktop)
# 2. Install Node.js 16+ (https://nodejs.org/)

# 3. ONE-COMMAND SETUP - Sets up everything automatically!
npm run setup:dev

# That's it! The setup script installs dependencies, starts wp-env,
# and verifies everything works. You're ready to develop!

# 4. Run tests (all tests run via wp-env - no local PHP needed!)
npm run test:php        # PHP tests in wp-env
npm run test:js         # JavaScript tests
npm run test:e2e        # E2E tests

# 5. Build plugin ZIP
npm run build:zip
```

That's it! See [CONTRIBUTING.md](CONTRIBUTING.md) for detailed development workflow.

---

## Table of Contents

- [Quick Start](#quick-start-docker-based---recommended)
- [Prerequisites](#prerequisites)
- [Docker-Based Development (Recommended)](#docker-based-development-recommended)
- [Traditional Setup (Advanced)](#traditional-setup-advanced)
- [Installing Dependencies](#installing-dependencies)
- [Running Code Quality Tools](#running-code-quality-tools)
- [Running Tests](#running-tests)
- [Building](#building)
- [Troubleshooting](#troubleshooting)

---

## Prerequisites

### Required (All Developers)

- **Docker Desktop** - Primary requirement for consistent development
  - Windows: Docker Desktop 4.0+ with WSL 2 backend
  - Mac: Docker Desktop 4.0+
  - Linux: Docker Engine 20.10+
  - [Download Docker Desktop](https://www.docker.com/products/docker-desktop)

- **Node.js 16+** and **npm 8+** - For build scripts and npm dependencies
  - [Download Node.js](https://nodejs.org/)

### Optional (Advanced Users Only)

- **PHP 7.4+** - Only if you prefer running tests/linting locally instead of in Docker
- **Composer** - Only if you prefer managing PHP dependencies locally instead of in Docker

**Important:** With our containerized workflow, PHP and Composer are **100% optional**. All PHP testing, linting, and dependency management can be done in Docker containers using the `:docker` script variants (e.g., `npm run test:php:docker`).

---

## Docker-Based Development (Recommended)

This is the recommended approach that ensures consistency across all platforms and developer machines. We use **@wordpress/env (wp-env)** - the official WordPress tool for local development environments.

### Why wp-env?

- ✅ **Zero PHP/Composer installation** - All PHP operations run in containers
- ✅ **Official WordPress tool** - Maintained by the WordPress core team
- ✅ **Consistent environment** - Same exact setup for everyone
- ✅ **Cross-platform** - Works identically on Windows, Mac, Linux
- ✅ **Isolated** - Doesn't interfere with system PHP installations
- ✅ **Test multiple PHP versions** - Easy to test against PHP 7.4, 8.0, 8.1, 8.2
- ✅ **Built-in best practices** - Includes WordPress core, PHPUnit, WP-CLI, Composer

### Setup Steps

1. **Install Docker Desktop**
   - Windows: [Docker Desktop for Windows](https://www.docker.com/products/docker-desktop/)
   - Mac: [Docker Desktop for Mac](https://www.docker.com/products/docker-desktop/)
   - Linux: [Docker Engine](https://docs.docker.com/engine/install/)
   - **Make sure Docker is running** before proceeding

2. **Install Node.js** (if not already installed)
   - Visit https://nodejs.org/
   - Download and install LTS version (16+)

3. **Clone Repository**
   ```bash
   git clone https://github.com/your-org/cta-highlights.git
   cd cta-highlights
   ```

4. **Run One-Command Setup** ⭐
   ```bash
   # This single command does everything:
   # - Checks prerequisites (Docker, Node.js)
   # - Installs npm dependencies
   # - Starts wp-env (WordPress environment)
   # - Installs Composer dependencies
   # - Sets up WordPress test library
   # - Verifies everything works
   npm run setup:dev
   ```

   The script will guide you through the process and display helpful messages.

5. **Access WordPress Sites**
   - Development: http://localhost:8888 (admin/password)
   - Testing: http://localhost:8889 (admin/password)
   - Admin: http://localhost:8888/wp-admin

That's it! You're ready to develop. Skip to [Common Development Commands](#common-development-commands) to see what you can do.

---

## Traditional Setup (Advanced)

**Note:** Only follow this section if you specifically need PHP/Composer installed locally. Most developers should use the Docker-based workflow above.

### When You Might Need This

- Running PHP linting/static analysis outside Docker
- IDE integration that requires local PHP
- Debugging with local PHP installation
- Performance reasons (Docker has some overhead on Windows)

### Installing PHP (Windows)

### Method 1: Using Chocolatey (Recommended)

If you have [Chocolatey](https://chocolatey.org/) installed:

```powershell
# Run PowerShell as Administrator
choco install php --version=8.2.0

# Verify installation
php -v
```

### Method 2: Manual Installation

1. **Download PHP**:
   - Visit https://windows.php.net/download/
   - Download the latest PHP 8.2 "Thread Safe" ZIP file
   - Example: `php-8.2.x-Win32-vs16-x64.zip`

2. **Extract PHP**:
   ```powershell
   # Extract to C:\php
   Expand-Archive -Path "C:\Users\YourName\Downloads\php-8.2.x-Win32-vs16-x64.zip" -DestinationPath "C:\php"
   ```

3. **Configure PHP**:
   ```powershell
   # Copy php.ini
   cd C:\php
   copy php.ini-development php.ini

   # Edit php.ini and enable required extensions:
   # Uncomment these lines (remove the semicolon):
   # extension=curl
   # extension=mbstring
   # extension=openssl
   # extension=fileinfo
   # extension=zip
   ```

4. **Add PHP to PATH**:
   - Open "Edit the system environment variables"
   - Click "Environment Variables"
   - Under "System variables", find "Path" and click "Edit"
   - Click "New" and add: `C:\php`
   - Click "OK" on all dialogs

5. **Verify Installation**:
   ```powershell
   # Restart your terminal/PowerShell
   php -v
   ```

   You should see something like:
   ```
   PHP 8.2.x (cli) (built: ...)
   ```

---

## Installing Composer

Composer is required for managing PHP dependencies.

### Windows Installation

1. **Download Composer Installer**:
   - Visit https://getcomposer.org/download/
   - Download `Composer-Setup.exe`

2. **Run the Installer**:
   - Run `Composer-Setup.exe`
   - The installer will detect your PHP installation
   - Follow the installation wizard
   - Accept the defaults

3. **Verify Installation**:
   ```powershell
   # Restart your terminal
   composer --version
   ```

   You should see something like:
   ```
   Composer version 2.x.x
   ```

### Alternative: Manual Installation

```powershell
# Download composer.phar
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"

# Move to a directory in PATH
move composer.phar C:\php\composer.phar

# Create a batch file at C:\php\composer.bat with:
@echo OFF
php "%~dp0composer.phar" %*
```

---

## Installing Node.js

Node.js is required for JavaScript development tools.

### Windows Installation

1. **Download Node.js**:
   - Visit https://nodejs.org/
   - Download the LTS version (recommended)

2. **Run the Installer**:
   - Run the downloaded `.msi` file
   - Follow the installation wizard
   - Accept all defaults

3. **Verify Installation**:
   ```powershell
   node -v
   npm -v
   ```

**Note**: Based on your current setup, Node.js appears to already be installed.

---

## Installing Dependencies

Once PHP, Composer, and Node.js are installed:

```bash
# Navigate to plugin directory
cd "d:\Local Dev\ghn-test\app\public\wp-content\plugins\cta-highlights"

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

This will install:
- **Composer packages**: PHPUnit, PHPCS, PHPStan, and other PHP dev tools
- **npm packages**: Playwright, Jest, ESLint, and other JS dev tools

---

## Running Code Quality Tools

All linting and static analysis tools run in wp-env containers - **no local PHP needed!**

### Linting

```bash
# Check all code (PHP + JavaScript)
npm run lint

# Check PHP code style (PHPCS)
npm run lint:php

# Check PHP static analysis (PHPStan)
npm run lint:phpstan

# Check JavaScript code style (ESLint)
npm run lint:js

# Auto-fix all issues
npm run lint:fix

# Auto-fix PHP code style
npm run lint:fix:php

# Auto-fix JavaScript code style
npm run lint:fix:js
```

**How it works:**
- All PHP linting runs in wp-env's `cli` container via `wp-env run cli`
- JavaScript linting runs locally (Node.js)
- No local PHP installation required!

### Composer Dependency Management

```bash
# Install PHP dependencies (runs in wp-env container)
npm run composer:install

# Update PHP dependencies (runs in wp-env container)
npm run composer:update
```

**Note:** All Composer commands run inside wp-env containers, so you don't need Composer installed locally.

---

## Running Tests

All tests run via wp-env or in containers - **no local PHP needed!**

### PHP Tests

All PHP tests run inside wp-env's `tests-cli` container:

```bash
# Run all PHP tests (unit + integration)
npm run test:php

# Run only unit tests
npm run test:php:unit

# Run only integration tests
npm run test:php:integration

# Run with coverage report
npm run test:php:coverage
```

**How it works:**
- Tests run in wp-env's `tests-cli` container via `wp-env run tests-cli`
- Connects to wp-env's test database (`tests-mysql`)
- Includes PHPUnit, WordPress test library, and all plugin dependencies
- No local PHP installation required!

### Multi-Version PHP Testing

Test against specific PHP versions to debug CI failures locally:

```bash
# Test with PHP 7.4 (matches CI)
npm run test:php:7.4

# Test with PHP 8.1 (matches CI)
npm run test:php:8.1

# Test with PHP 8.2 (matches CI)
npm run test:php:8.2
```

**How it works:**
- Spins up a Docker container with the specified PHP version
- Connects to wp-env's MySQL database
- Installs WordPress test library in the container
- Runs the full test suite
- Perfect for debugging "works locally but fails in CI" issues!

**Note:** wp-env must be running (`npm run env:start`) for these commands to work.

### JavaScript Tests

```bash
# Run all JavaScript tests
npm run test:js

# Run in watch mode
npm run test:js:watch

# Run with coverage
npm run test:js:coverage
```

### E2E Tests

E2E tests use Playwright and wp-env:

```bash
# wp-env starts automatically, but you can start it manually if needed
npm run env:start

# Run all E2E tests (all browsers)
npm run test:e2e

# Run in specific browser
npm run test:e2e:chrome
npm run test:e2e:firefox
npm run test:e2e:safari

# Run with UI (headed mode)
npm run test:e2e:headed

# Debug mode
npm run test:e2e:debug

# View test report
npm run test:e2e:report

# Stop WordPress environment when done
npm run env:stop
```

### Run All Tests

```bash
# Run all tests (PHP + JS + E2E)
npm run test:all

# Run quick tests (PHP unit + JS only)
npm run test:quick

# Run tests with coverage
npm run test:coverage
```

---

## Building

### Docker-Based Build (Recommended - Works Everywhere)

This is a **100% containerized build** that requires ONLY Docker Desktop. No rsync, no PHP, no Composer, no WSL needed!

```bash
# Build production ZIP (100% containerized)
npm run build:zip

# Build and verify contents
npm run build:zip:verify
```

**What happens:**
1. Docker builds an image using `Dockerfile.build`
2. Inside the container: files are copied (using `.dockerignore`), Composer installs production dependencies, ZIP is created
3. ZIP is extracted from container to your `build/` directory
4. Container is cleaned up

**Requirements:**
- Docker Desktop ONLY
- Works on Windows (without WSL), Mac, and Linux identically

**Output:** `build/cta-highlights-{version}.zip`

**File exclusions:** Managed by `.dockerignore` (matches `.distignore`)

### Local Build (Advanced - Requires WSL on Windows)

If you have PHP, Composer, and rsync installed locally:

```bash
# Build using local tools
npm run build:zip:local
```

**Requirements:**
- PHP 7.4+
- Composer
- rsync (comes with Mac/Linux, requires WSL or Git Bash on Windows)
- zip utility

**Note:** Most developers should use the Docker-based build above.

---

## Common Development Commands

```bash
# Lint all code
npm run lint

# Fix all auto-fixable issues
npm run lint:fix

# Build release ZIP (Docker-based)
npm run build:zip

# Build using local tools (requires PHP/Composer)
npm run build:zip:local

# Verify release ZIP
npm run build:zip:verify

# Full release process (lint + test + build)
npm run release
```

---

## Troubleshooting

### "Docker is not installed or not in PATH"

**Solution**: Install Docker Desktop and ensure it's running.

```bash
# Check if Docker is installed
docker --version

# Check if Docker daemon is running
docker info
```

**Windows specific:**
- Make sure Docker Desktop is set to use WSL 2 backend
- Ensure WSL 2 is installed: `wsl --install`
- Start Docker Desktop from Start menu

### "Could not connect to Docker. Is it running?"

**Solution**: Start Docker Desktop

1. Open Docker Desktop from Start menu / Applications
2. Wait for Docker to fully start (green icon in system tray/menu bar)
3. Try your command again

### Build Script Fails: "bash: command not found"

**Solution**: You need a bash shell to run the build scripts. On Windows:
- Git Bash (comes with Git for Windows - recommended)
- WSL (Windows Subsystem for Linux)
- PowerShell with Git Bash in PATH

```powershell
# Install WSL 2 (Windows 10/11) - if you prefer
wsl --install

# Or install Git for Windows (includes Git Bash)
# Download from: https://git-scm.com/download/win
```

**Note:** The Docker-based build (`npm run build:zip`) only uses bash to launch Docker commands. All actual build operations run inside containers, so you don't need WSL with rsync, PHP, or Composer installed.

### "composer: command not found" (Local Builds Only)

**Note:** Only relevant if using `npm run build:zip:local`

**Solution**: Either:
1. Use Docker-based build: `npm run build:zip` (recommended)
2. Install Composer locally (see Traditional Setup section)

### Composer Install Fails

**Issue**: SSL/TLS errors

**Solution**:
```bash
# Disable SSL verification (temporary, not recommended for production)
composer install --no-secure-http
```

**Better solution**: Enable OpenSSL in php.ini:
1. Edit `C:\php\php.ini`
2. Uncomment: `extension=openssl`
3. Restart your terminal

### npm Install Fails

**Issue**: Permission errors

**Solution**:
```bash
# Clear npm cache
npm cache clean --force

# Try again
npm install
```

### Docker/wp-env Issues

**Issue**: "Could not connect to Docker. Is it running?"

**Solution**:
1. Install [Docker Desktop for Windows](https://www.docker.com/products/docker-desktop/)
2. Start Docker Desktop
3. Wait for Docker to fully start (green icon in system tray)
4. Try again: `npm run env:start`

**Issue**: "Could not find wp-env Docker network"

**Solution**: This happens when wp-env isn't running properly.
```bash
# Stop and clean the environment
npm run env:stop
npm run env:clean

# Start fresh
npm run env:start

# Verify wp-env is running
docker ps | grep wp-env
```

**Issue**: "PHP tests can't connect to database"

**Solution**: Make sure wp-env is running first:
```bash
# Check if wp-env is running
curl http://localhost:8889

# If not running, start it
npm run env:start

# Wait for it to be ready
sleep 5

# Try tests again
npm run test:php
```

**Issue**: Multi-version PHP tests fail

**Solution**: wp-env must be running for multi-version tests:
```bash
# Ensure wp-env is running
npm run env:start

# Check wp-env network exists
docker network ls | grep wp-env

# Try the test again
npm run test:php:7.4
```

### E2E Tests Fail

**Issue**: WordPress not ready

**Solution**: The workflow includes automatic waiting, but if issues persist:
```bash
# Stop and clean the environment
npm run env:stop
npm run env:clean

# Start fresh
npm run env:start

# Wait a bit longer, then run tests
npm run test:e2e
```

### Linting/Composer Fails

**Issue**: "wp-env: command not found" or similar

**Solution**: Make sure npm dependencies are installed:
```bash
# Install npm dependencies
npm install

# Verify wp-env is available
npx wp-env --version

# Try again
npm run lint:php
```

---

## Next Steps

Once you have your development environment set up:

1. **Fix code style issues**:
   ```bash
   composer run phpcbf
   npm run lint:fix
   ```

2. **Run tests to verify everything works**:
   ```bash
   npm run test:quick
   ```

3. **Review the documentation**:
   - [README.md](README.md) - Plugin features and usage
   - [TESTING-GUIDE.md](tests/TESTING-GUIDE.md) - Detailed testing information
   - [RELEASE.md](RELEASE.md) - Release process
   - [GITHUB-SETUP.md](GITHUB-SETUP.md) - CI/CD setup

---

## Additional Resources

- [PHP for Windows](https://windows.php.net/download/)
- [Composer Documentation](https://getcomposer.org/doc/)
- [Node.js Documentation](https://nodejs.org/docs/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [@wordpress/env Documentation](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)

---

**Questions?** Check the [GitHub Issues](https://github.com/your-org/cta-highlights/issues) or create a new issue.
