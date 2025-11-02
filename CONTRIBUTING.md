# Contributing to CTA Highlights

Thank you for your interest in contributing to CTA Highlights! This guide will help you get started with development, testing, and submitting contributions.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
- [Development Workflow](#development-workflow)
- [Testing](#testing)
- [Building](#building)
- [Code Standards](#code-standards)
- [Submitting Changes](#submitting-changes)
- [Release Process](#release-process)

## Prerequisites

### Required

- **Docker Desktop** - Required for local development environment and builds
  - [Install Docker Desktop](https://www.docker.com/products/docker-desktop)
  - Windows: Docker Desktop 4.0+ with WSL 2 backend
  - Mac: Docker Desktop 4.0+
  - Linux: Docker Engine 20.10+

- **Node.js 16+** and **npm 8+** - Required for build scripts and testing
  - [Download Node.js](https://nodejs.org/)
  - Verify: `node --version` and `npm --version`

- **Git** - For version control
  - [Download Git](https://git-scm.com/downloads)

### Optional (for advanced development)

- **PHP 7.4+** - Only needed if you want to run linting/analysis outside Docker
- **Composer** - Only needed if you want to manage dependencies outside Docker
- **VS Code** - Recommended IDE with optional dev container support

**Important:** With our Docker-based workflow, you do NOT need to manually install PHP, Composer, or any other tools. Everything runs in containers.

## Quick Start

### 1. Clone the Repository

```bash
git clone https://github.com/your-org/cta-highlights.git
cd cta-highlights
```

### 2. ONE-COMMAND SETUP ⭐

```bash
# This single command does everything you need!
npm run setup:dev
```

**What it does:**
- ✅ Checks prerequisites (Docker, Node.js)
- ✅ Installs npm dependencies
- ✅ Starts wp-env (WordPress environment)
- ✅ Installs Composer dependencies (in container)
- ✅ Sets up WordPress test library
- ✅ Verifies everything works

**Access WordPress:**
- **Development site:** http://localhost:8888
- **Test site:** http://localhost:8889
- **Admin:** http://localhost:8888/wp-admin
- **Username:** admin
- **Password:** password

### 3. Run Tests

```bash
# Quick tests (PHP unit + JS)
npm run test:quick

# All tests
npm run test:all

# Test specific PHP version (debug CI failures)
npm run test:php:7.4
```

### 4. Build Plugin ZIP

```bash
# Docker-based build (recommended - works everywhere)
npm run build:zip

# Local build (requires PHP, Composer, zip installed)
npm run build:zip:local
```

You're ready to start contributing!

## Quick Reference: Common Commands

All PHP operations run in **wp-env containers** - no local PHP/Composer needed!

### Setup & Dependencies
```bash
npm run setup:dev                # Complete one-command setup (recommended!)
npm run composer:install         # Install PHP dependencies (runs in wp-env)
npm run composer:update          # Update PHP dependencies (runs in wp-env)
```

### Testing
```bash
# PHP Tests (all run via wp-env)
npm run test:php                 # All PHP tests (unit + integration)
npm run test:php:unit            # PHP unit tests only
npm run test:php:integration     # PHP integration tests only

# Multi-Version PHP Testing (debug CI failures)
npm run test:php:7.4             # Test with PHP 7.4
npm run test:php:8.1             # Test with PHP 8.1
npm run test:php:8.2             # Test with PHP 8.2

# Other Tests
npm run test:js                  # JavaScript tests (Jest)
npm run test:e2e                 # E2E tests (Playwright)

# Quick & Comprehensive
npm run test:quick               # PHP unit + JS (~1 min)
npm run test:all                 # All tests (~3 min)
```

### Linting
```bash
npm run lint                     # All linting (PHP + JS)
npm run lint:php                 # PHP linting (PHPCS via wp-env)
npm run lint:phpstan             # Static analysis (PHPStan via wp-env)
npm run lint:js                  # JavaScript linting (ESLint)
npm run lint:fix                 # Auto-fix all issues
npm run lint:fix:php             # Auto-fix PHP issues (PHPCBF via wp-env)
npm run lint:fix:js              # Auto-fix JS issues (ESLint)
```

### Building
```bash
npm run build:zip                # Build ZIP in Docker (recommended)
npm run build:zip:verify         # Build and verify ZIP contents
npm run build:zip:local          # Build using local PHP/Composer (advanced)
```

### WordPress Environment
```bash
npm run env:start                # Start wp-env (http://localhost:8888)
npm run env:stop                 # Stop wp-env
npm run env:clean                # Clean wp-env data
```

**Note:** All PHP commands run in wp-env containers via `wp-env run cli` or `wp-env run tests-cli`.

## Development Workflow

### Project Structure

```
cta-highlights/
├── assets/              # Frontend assets (CSS, JS)
├── includes/            # PHP source code (PSR-4 autoloaded)
│   ├── Admin/          # Admin-related functionality
│   ├── Assets/         # Asset management
│   ├── AutoInsertion/  # Auto-insertion logic
│   ├── Core/           # Core plugin functionality
│   ├── Shortcode/      # Shortcode handling
│   └── Template/       # Template system
├── templates/           # CTA template files
├── tests/              # All test files
│   ├── php/           # PHP unit/integration tests
│   ├── javascript/    # JavaScript unit tests
│   └── e2e/          # End-to-end tests
├── bin/                # Build and utility scripts
└── .wp-env.json        # WordPress environment config
```

### Making Changes

1. **Create a Feature Branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make Your Changes**
   - Follow the [Code Standards](#code-standards) below
   - Write tests for new functionality
   - Update documentation if needed

3. **Test Your Changes**
   ```bash
   # Run linters
   npm run lint

   # Run tests
   npm run test:all
   ```

4. **Commit Your Changes**
   ```bash
   git add .
   git commit -m "Add feature: description of changes"
   ```

5. **Push and Create PR**
   ```bash
   git push origin feature/your-feature-name
   ```
   Then create a Pull Request on GitHub.

### IDE Setup

#### Any IDE (Universal)

Our Docker-based workflow works with any IDE or text editor. Just:
1. Install Docker Desktop
2. Run `npm install`
3. Use `npm run env:start` for local WordPress
4. Use `npm run build:zip` for builds

No IDE-specific configuration needed!

#### VS Code (Enhanced Experience)

For VS Code users, we provide an optional dev container with all tools pre-installed:

1. Install the [Dev Containers extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers)
2. Open command palette (Ctrl/Cmd + Shift + P)
3. Select "Dev Containers: Reopen in Container"
4. Wait for container to build (first time only)

**Benefits:**
- PHP, Composer, and all tools pre-configured
- IntelliSense and debugging works out of the box
- Consistent environment across the team

**Note:** This is entirely optional. The standard workflow works perfectly without VS Code.

## Testing

### Test Types

We maintain comprehensive test coverage across multiple layers:

#### 1. PHP Unit Tests

```bash
# All PHP tests
npm run test:php

# Unit tests only
npm run test:php:unit

# Integration tests only
npm run test:php:integration

# With coverage report
npm run test:php:coverage
```

**Location:** `tests/php/unit/` and `tests/php/integration/`

#### 2. JavaScript Unit Tests

```bash
# Run all JS tests
npm run test:js

# Watch mode (re-run on changes)
npm run test:js:watch

# With coverage report
npm run test:js:coverage
```

**Location:** `tests/javascript/`

#### 3. End-to-End (E2E) Tests

```bash
# Run E2E tests
npm run test:e2e

# Run with visible browser
npm run test:e2e:headed

# Debug mode
npm run test:e2e:debug

# Specific browsers
npm run test:e2e:chrome
npm run test:e2e:firefox
npm run test:e2e:safari

# View test report
npm run test:e2e:report
```

**Location:** `tests/e2e/`

**Requirements:** Requires WordPress environment to be running (`npm run env:start`)

#### 4. Visual Regression Tests

```bash
# Run visual tests
npm run test:visual

# Update baseline snapshots
npm run test:visual:update

# Interactive UI mode
npm run test:visual:ui
```

### Writing Tests

#### PHP Tests Example

```php
<?php
namespace CTAHighlights\Tests\Unit;

use CTAHighlights\Shortcode\ShortcodeHandler;

class ShortcodeHandlerTest extends \WP_UnitTestCase {
    public function test_shortcode_renders_content() {
        $handler = new ShortcodeHandler();
        $result = $handler->render(['template' => 'default'], 'Test content');

        $this->assertStringContainsString('Test content', $result);
    }
}
```

#### JavaScript Tests Example

```javascript
import { CTAHighlights } from '../assets/js/cta-highlights.js';

describe('CTAHighlights', () => {
    test('initializes with correct state', () => {
        const cta = new CTAHighlights();
        expect(cta.isHighlighted).toBe(false);
    });
});
```

## Building

### Docker-Based Build (Recommended)

Works on any platform without manual PHP/Composer installation:

```bash
# Build production ZIP
npm run build:zip

# Build and verify contents
npm run build:zip:verify
```

**What it does:**
1. Copies plugin files (excludes dev files)
2. Installs production Composer dependencies in Docker
3. Creates optimized ZIP file
4. Output: `build/cta-highlights-{version}.zip`

**Requirements:** Docker Desktop only

### Local Build (Advanced)

For users with PHP and Composer installed locally:

```bash
npm run build:zip:local
```

**Requirements:** PHP 7.4+, Composer, WSL/bash, zip utility

### Build Artifacts

The build process creates:
- `build/cta-highlights-{version}.zip` - Production-ready plugin
- Clean package without:
  - Development dependencies
  - Tests
  - Build scripts
  - IDE configuration
  - Git files

## Code Standards

### PHP Standards

- **Coding Standard:** WordPress Coding Standards (WPCS)
- **PHP Version:** 7.4+ (use modern PHP features)
- **Namespace:** All classes use `CTAHighlights\` namespace
- **Autoloading:** PSR-4 autoloading (`includes/` → `CTAHighlights\`)

```bash
# Check PHP standards
npm run lint:php

# Auto-fix PHP issues
vendor/bin/phpcbf
```

**Key Rules:**
- Use type hints for function parameters and return types
- Document all functions with PHPDoc blocks
- Follow WordPress naming conventions for hooks
- Escape all output appropriately
- Sanitize and validate all input

### JavaScript Standards

- **Coding Standard:** WordPress ESLint configuration
- **ES Version:** ES6+ (transpilation not needed, modern browsers only)
- **Style:** Modern JavaScript with const/let, arrow functions, etc.

```bash
# Check JS standards
npm run lint:js

# Auto-fix JS issues
npm run lint:fix
```

**Key Rules:**
- Use `const` by default, `let` when reassignment needed
- Prefer arrow functions for callbacks
- Use template literals for string interpolation
- Destructure objects and arrays where appropriate
- Document complex functions with JSDoc

### CSS Standards

- BEM methodology for class naming
- Mobile-first responsive design
- Use CSS custom properties for theming
- Support WordPress core color schemes

### Documentation

- Update README.md if adding features
- Add code comments for complex logic
- Update CHANGELOG.md for all changes
- Include examples in code documentation

## Submitting Changes

### Pull Request Guidelines

1. **PR Title:** Clear, descriptive title
   - Good: "Add auto-dismissal feature for banner template"
   - Bad: "Updates"

2. **PR Description:** Include:
   - What changed
   - Why it changed
   - How to test
   - Screenshots (for UI changes)
   - Related issues

3. **Tests:** All PRs must include tests
   - New features: Add unit and/or E2E tests
   - Bug fixes: Add regression test
   - Ensure all existing tests pass

4. **Code Quality:** Ensure:
   - Linting passes (`npm run lint`)
   - Tests pass (`npm run test:all`)
   - No console errors or warnings
   - Backwards compatibility maintained

5. **Documentation:** Update if needed:
   - README.md for new features
   - Code comments for complex logic
   - CHANGELOG.md entry (format: `[Added|Changed|Fixed] - description`)

### Review Process

1. Automated checks run on all PRs:
   - Linting (PHP & JS)
   - Unit tests (PHP & JS)
   - E2E tests (Chrome)
   - Code coverage

2. Manual review by maintainer(s)

3. Changes requested if needed

4. Approval and merge to `main` branch

### Commit Message Format

Use clear, descriptive commit messages:

```
Add feature: Auto-dismiss banner after interaction

- Implement auto-dismiss logic in banner template
- Add cooldown tracking for dismissed banners
- Include unit tests for dismiss functionality
- Update template documentation
```

## Release Process

**Note:** Only maintainers can create releases.

### For Maintainers

1. Update version in:
   - `cta-highlights.php` (Version header)
   - `README.md` (Stable tag)
   - `CHANGELOG.md` (Add release notes)

2. Commit version bump:
   ```bash
   git commit -m "Bump version to X.Y.Z"
   git push origin main
   ```

3. Create and push tag:
   ```bash
   git tag -a vX.Y.Z -m "Release version X.Y.Z"
   git push origin vX.Y.Z
   ```

4. GitHub Actions automatically:
   - Validates version
   - Runs tests
   - Builds ZIP
   - Creates GitHub Release

See [RELEASE.md](RELEASE.md) for detailed release documentation.

## Getting Help

- **Questions:** Open a [GitHub Discussion](https://github.com/your-org/cta-highlights/discussions)
- **Bugs:** Open a [GitHub Issue](https://github.com/your-org/cta-highlights/issues)
- **Security:** Email security@standbycxo.com (do not open public issue)

## Code of Conduct

- Be respectful and inclusive
- Welcome newcomers
- Focus on constructive feedback
- Assume good intentions

## License

By contributing, you agree that your contributions will be licensed under the GPL-2.0-or-later license.

---

**Thank you for contributing to CTA Highlights!** 🎉
