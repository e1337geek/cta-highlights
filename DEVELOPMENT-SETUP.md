# Development Setup Guide

This guide will help you set up your local development environment for the CTA Highlights plugin.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installing PHP (Windows)](#installing-php-windows)
- [Installing Composer](#installing-composer)
- [Installing Node.js](#installing-nodejs)
- [Installing Dependencies](#installing-dependencies)
- [Running Code Quality Tools](#running-code-quality-tools)
- [Running Tests](#running-tests)
- [Troubleshooting](#troubleshooting)

---

## Prerequisites

The CTA Highlights plugin requires the following tools for development:

- **PHP 7.4+** (for running WordPress and PHP development tools)
- **Composer** (for PHP dependency management)
- **Node.js 16+** (for JavaScript development tools)
- **npm 8+** (comes with Node.js)
- **Docker Desktop** (optional, for running E2E tests with wp-env)

---

## Installing PHP (Windows)

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

### Fix Code Style Issues Automatically

```bash
# Auto-fix PHP code style issues
composer run phpcbf

# Auto-fix JavaScript code style issues
npm run lint:fix
```

### Check Code Style (without fixing)

```bash
# Check PHP code style
composer run phpcs

# Check JavaScript code style
npm run lint:js
```

### Run Static Analysis

```bash
# Run PHPStan static analysis
composer run phpstan
```

---

## Running Tests

### PHP Tests

```bash
# Run all PHP tests
npm run test:php

# Run only unit tests
npm run test:php:unit

# Run only integration tests
npm run test:php:integration

# Run with coverage report
npm run test:php:coverage
```

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

**Note**: Requires Docker Desktop to be running.

```bash
# Start WordPress environment
npm run env:start

# Run E2E tests
npm run test:e2e

# Run in specific browser
npm run test:e2e:chrome
npm run test:e2e:firefox
npm run test:e2e:safari

# Stop WordPress environment
npm run env:stop
```

### Run All Tests

```bash
# Run all tests (PHP + JS + E2E)
npm run test:all

# Run quick tests (unit tests only)
npm run test:quick
```

---

## Common Development Commands

```bash
# Lint all code
npm run lint

# Fix all auto-fixable issues
npm run lint:fix

# Build release ZIP
npm run build:zip

# Verify release ZIP
npm run build:zip:verify

# Full release process (lint + test + build)
npm run release
```

---

## Troubleshooting

### "composer: command not found"

**Solution**: Make sure Composer is in your PATH. Restart your terminal after installation.

```powershell
# Verify Composer is in PATH
where composer

# If not found, add to PATH or use full path
C:\ProgramData\ComposerSetup\bin\composer --version
```

### "php: command not found"

**Solution**: Make sure PHP is in your PATH. Restart your terminal after installation.

```powershell
# Verify PHP is in PATH
where php

# If not found, add C:\php to your PATH
```

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
