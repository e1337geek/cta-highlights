# CTA Highlights Plugin - Testing Guide

## Overview

This directory contains a comprehensive testing infrastructure for the CTA Highlights WordPress plugin. The testing system is designed to be fully executable by LLM agents (like Claude Code) for both running existing tests and generating new tests when code changes are made.

**Coverage Target**: 80% (prioritizing security and database operations)

## Table of Contents

- [Quick Start](#quick-start)
- [Testing Stack](#testing-stack)
- [Directory Structure](#directory-structure)
- [Running Tests](#running-tests)
- [Writing Tests](#writing-tests)
- [Test Priorities](#test-priorities)
- [LLM Agent Guide](#llm-agent-guide)
- [CI/CD Integration](#cicd-integration)
- [Troubleshooting](#troubleshooting)

---

## Quick Start

### Prerequisites

```bash
# Install Node.js dependencies
npm install

# Install PHP dependencies
composer install

# Setup WordPress test environment
npm run env:start
npm run test:setup
```

### Run All Tests

```bash
# Run everything
npm run test:all

# Or run individually
npm run test:php        # PHPUnit tests
npm run test:js         # Jest tests
npm run test:e2e        # Playwright E2E tests
```

---

## Testing Stack

| Component | Technology | Purpose |
|-----------|-----------|---------|
| **PHP Unit Tests** | PHPUnit 9.x | Test individual PHP classes and functions |
| **PHP Integration Tests** | PHPUnit + WordPress Test Library | Test WordPress integration and hooks |
| **JavaScript Tests** | Jest 29.x | Test JavaScript modules (cta-highlights.js, auto-insert.js) |
| **E2E Tests** | Playwright 1.x | Test complete user workflows across browsers |
| **WordPress Environment** | wp-env | Local WordPress development environment |
| **Code Coverage** | PHPUnit Coverage, Jest Coverage, c8 | Track test coverage |
| **CI/CD** | GitHub Actions | Automated testing on push/PR |

---

## Directory Structure

```
tests/
├── README.md                          # This file - master testing guide
├── phpunit.xml                        # PHPUnit configuration
├── jest.config.js                     # Jest configuration
├── playwright.config.js               # Playwright configuration
├── .env.testing                       # Test environment variables
│
├── bin/                               # Test runner scripts
│   ├── install-wp-tests.sh           # WordPress test library installer
│   ├── run-unit-tests.sh             # Quick PHPUnit runner
│   ├── run-js-tests.sh               # Quick Jest runner
│   ├── run-e2e-tests.sh              # Quick Playwright runner
│   └── run-all-tests.sh              # Full test suite
│
├── unit/                              # PHPUnit unit tests (isolated class tests)
│   ├── Core/
│   │   └── PluginTest.php            # Test Plugin singleton and initialization
│   ├── Shortcode/
│   │   └── HandlerTest.php           # Test shortcode processing (HIGH PRIORITY)
│   ├── Template/
│   │   ├── LoaderTest.php            # Test template loading (HIGH PRIORITY - Security)
│   │   ├── RegistryTest.php          # Test template tracking
│   │   └── ViewDataTest.php          # Test safe data access
│   ├── Assets/
│   │   └── ManagerTest.php           # Test asset enqueuing logic
│   ├── AutoInsertion/
│   │   ├── DatabaseTest.php          # Test CRUD operations (HIGH PRIORITY - Security)
│   │   ├── MatcherTest.php           # Test conditional logic
│   │   ├── ManagerTest.php           # Test fallback chains (HIGH PRIORITY)
│   │   └── InserterTest.php          # Test position calculation
│   └── Admin/
│       ├── AutoInsertAdminTest.php   # Test admin forms (HIGH PRIORITY - Security)
│       ├── ListTableTest.php         # Test list table rendering
│       └── PostMetaBoxTest.php       # Test meta box operations (HIGH PRIORITY)
│
├── integration/                       # WordPress integration tests
│   ├── ShortcodeRenderingTest.php    # Test [cta_highlights] end-to-end
│   ├── TemplateOverrideTest.php      # Test theme override hierarchy
│   ├── AutoInsertionFlowTest.php     # Test auto-insertion from DB to output
│   ├── HooksFiltersTest.php          # Test hook/filter system
│   └── CapabilitiesTest.php          # Test user permission checks
│
├── javascript/                        # Jest tests for JavaScript
│   ├── cta-highlights.test.js        # Test highlight effect & cooldowns
│   ├── auto-insert.test.js           # Test auto-insertion logic
│   ├── storage-manager.test.js       # Test localStorage/cookie handling
│   └── __mocks__/                    # Mock objects
│       ├── localStorage.js
│       ├── intersectionObserver.js
│       └── wordpress.js
│
├── e2e/                               # Playwright end-to-end tests
│   ├── admin/
│   │   ├── cta-creation.spec.js      # Test CTA creation workflow
│   │   ├── cta-editing.spec.js       # Test CTA editing
│   │   └── cta-duplication.spec.js   # Test CTA duplication
│   ├── frontend/
│   │   ├── shortcode-rendering.spec.js    # Test shortcode display
│   │   ├── highlight-effect.spec.js       # Test highlight activation/dismiss
│   │   ├── cooldown-behavior.spec.js      # Test cooldown timers
│   │   └── auto-insertion.spec.js         # Test auto-inserted CTAs
│   ├── accessibility/
│   │   ├── keyboard-navigation.spec.js    # Test keyboard controls
│   │   ├── screen-reader.spec.js          # Test ARIA & announcements
│   │   └── focus-management.spec.js       # Test focus trap
│   ├── cross-browser/
│   │   ├── chrome.spec.js
│   │   ├── firefox.spec.js
│   │   └── safari.spec.js
│   └── fixtures/                      # Test data
│       ├── test-posts.json
│       ├── test-ctas.json
│       └── test-users.json
│
├── uat/                               # User Acceptance Testing
│   ├── uat-checklist.md              # Manual testing checklist
│   ├── visual-regression/            # Visual comparison tests
│   │   ├── baseline-images/
│   │   └── test-scenarios.md
│   └── performance/                   # Performance benchmarks
│       ├── benchmarks.md
│       └── lighthouse-config.json
│
└── _support/                          # Test support utilities
    ├── bootstrap.php                  # PHPUnit bootstrap
    ├── jest-setup.js                  # Jest setup
    ├── playwright-setup.js            # Playwright global setup
    ├── helpers.php                    # PHP helper functions
    ├── helpers.js                     # JavaScript helper functions
    ├── Factories/                     # Test data factories
    │   ├── CTAFactory.php            # Create test CTA data
    │   ├── PostFactory.php           # Create test posts
    │   ├── UserFactory.php           # Create test users
    │   └── TemplateFactory.php       # Create test templates
    ├── Traits/                        # Reusable test traits
    │   ├── CreatesDatabase.php       # Database setup/teardown
    │   ├── CreatesTemplates.php      # Template file creation
    │   ├── CreatesShortcodes.php     # Shortcode testing helpers
    │   └── AssertsHTML.php           # Custom HTML assertions
    └── Mocks/                         # Mock objects
        ├── MockWordPress.php         # WordPress function mocks
        └── MockGravityForms.php      # Gravity Forms mocks
```

---

## Running Tests

### PHPUnit Tests (PHP)

```bash
# Run all PHP tests
npm run test:php

# Run only unit tests
./vendor/bin/phpunit --testsuite unit

# Run only integration tests
./vendor/bin/phpunit --testsuite integration

# Run specific test file
./vendor/bin/phpunit tests/unit/AutoInsertion/DatabaseTest.php

# Run with coverage report
npm run test:php:coverage

# Run tests matching a filter
./vendor/bin/phpunit --filter testDatabaseCRUD
```

### Jest Tests (JavaScript)

```bash
# Run all JavaScript tests
npm run test:js

# Run in watch mode (auto-rerun on changes)
npm run test:js:watch

# Run with coverage
npm run test:js:coverage

# Run specific test file
npm run test:js -- cta-highlights.test.js

# Update snapshots
npm run test:js -- -u
```

### Playwright Tests (E2E)

```bash
# Run all E2E tests
npm run test:e2e

# Run on specific browser
npm run test:e2e -- --project=chromium
npm run test:e2e -- --project=firefox
npm run test:e2e -- --project=webkit

# Run in headed mode (see browser)
npm run test:e2e -- --headed

# Run specific test file
npm run test:e2e -- tests/e2e/admin/cta-creation.spec.js

# Debug mode (step through)
npm run test:e2e -- --debug

# Generate test report
npm run test:e2e:report
```

### Run Everything

```bash
# Full test suite (unit + integration + js + e2e)
npm run test:all

# Quick tests only (unit + js, no E2E)
npm run test:quick
```

---

## Writing Tests

### PHP Unit Test Template

```php
<?php
/**
 * Test ClassName functionality
 *
 * @package CTAHighlights\Tests\Unit
 */

namespace CTAHighlights\Tests\Unit;

use CTAHighlights\Namespace\ClassName;
use WP_UnitTestCase;

class ClassNameTest extends WP_UnitTestCase {

    private $instance;

    /**
     * Setup before each test
     */
    public function setUp(): void {
        parent::setUp();
        $this->instance = new ClassName();
    }

    /**
     * Cleanup after each test
     */
    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * @test
     * Test that method does what it should
     *
     * WHY: This is critical because [business reason]
     * PRIORITY: HIGH (security/database/business logic/UI)
     */
    public function it_does_something_correctly() {
        // Arrange
        $input = 'test value';

        // Act
        $result = $this->instance->method($input);

        // Assert
        $this->assertSame('expected', $result);
    }
}
```

### JavaScript Test Template

```javascript
/**
 * Test ModuleName functionality
 */

import { ClassName } from '../assets/js/module.js';

describe('ClassName', () => {
    let instance;

    beforeEach(() => {
        instance = new ClassName();
        // Reset mocks
        localStorage.clear();
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    describe('methodName()', () => {
        test('does something correctly', () => {
            // Arrange
            const input = 'test';

            // Act
            const result = instance.methodName(input);

            // Assert
            expect(result).toBe('expected');
        });

        test('handles edge case', () => {
            // Test edge case
        });
    });
});
```

### E2E Test Template

```javascript
/**
 * E2E Test: Feature Name
 *
 * @priority HIGH
 * @browsers chrome, firefox, safari
 */

import { test, expect } from '@playwright/test';

test.describe('Feature Name', () => {

    test.beforeEach(async ({ page }) => {
        // Login as admin
        await page.goto('/wp-admin');
        // Setup test data
    });

    test('user can complete workflow', async ({ page }) => {
        // Navigate to page
        await page.goto('/wp-admin/admin.php?page=cta-auto-insert');

        // Interact with elements
        await page.fill('#cta-name', 'Test CTA');
        await page.click('button[type="submit"]');

        // Assert outcome
        await expect(page.locator('.notice-success')).toBeVisible();
    });
});
```

---

## Test Priorities

Tests are prioritized based on business risk:

### HIGH PRIORITY (Must be tested first)

**Security & Data Integrity:**
- `DatabaseTest.php` - SQL injection, prepared statements, data sanitization
- `LoaderTest.php` - Path traversal, file inclusion security
- `HandlerTest.php` - XSS prevention, input sanitization
- `AutoInsertAdminTest.php` - Nonce verification, capability checks
- `PostMetaBoxTest.php` - Meta data security

**Critical Business Logic:**
- `ManagerTest.php` - Fallback chains, circular reference detection
- `MatcherTest.php` - Conditional logic correctness

### MEDIUM PRIORITY

**Business Logic:**
- Template system tests
- Shortcode rendering tests
- Auto-insertion flow tests
- JavaScript functionality tests

### LOWER PRIORITY

**UI & Polish:**
- Asset enqueuing tests
- Admin UI rendering tests
- CSS/styling tests

---

## LLM Agent Guide

### When to Run Tests

LLM agents should run tests:

1. **Before making changes** - Establish baseline
2. **After each significant change** - Verify no regressions
3. **When test failures occur** - Analyze and fix
4. **Before creating commits** - Ensure quality

### How to Run Tests as an LLM Agent

```bash
# Step 1: Run quick tests
npm run test:quick

# Step 2: If quick tests pass, run full suite
npm run test:all

# Step 3: Check coverage
npm run test:coverage

# Step 4: If coverage < 80%, generate new tests
# (See "Generating Tests" section below)
```

### Generating New Tests

When code changes are detected, LLM agents should:

1. **Identify changed components**
   ```bash
   git diff --name-only HEAD~1
   ```

2. **Check if tests exist**
   - For `includes/Namespace/ClassName.php`
   - Look for `tests/unit/Namespace/ClassNameTest.php`

3. **Generate test if missing**
   - Use templates from this README
   - Follow naming convention: `ClassNameTest.php`
   - Include docblocks explaining WHY each test matters

4. **Update existing tests if needed**
   - Add tests for new methods
   - Update assertions for changed behavior
   - Add edge case tests

### Interpreting Test Results

**PHPUnit Output:**
```
OK (15 tests, 42 assertions)           # All tests passed
FAILURES!                              # Some tests failed
Tests: 15, Assertions: 42, Failures: 2 # 2 tests failed
```

**Jest Output:**
```
PASS tests/javascript/cta-highlights.test.js
FAIL tests/javascript/auto-insert.test.js
  ● AutoInsertManager › processFallbackChain › handles empty chain
    Expected: "expected value"
    Received: "actual value"
```

**Coverage Reports:**
- Generated in `coverage/` directory
- Open `coverage/index.html` for visual report
- Aim for 80%+ coverage
- Prioritize security-critical code

### Test Generation Patterns

**For new PHP class:**
```php
// includes/NewNamespace/NewClass.php created
// Generate: tests/unit/NewNamespace/NewClassTest.php
```

**For new JavaScript module:**
```javascript
// assets/js/new-module.js created
// Generate: tests/javascript/new-module.test.js
```

**For new admin page:**
```php
// includes/Admin/NewAdminPage.php created
// Generate:
// - tests/unit/Admin/NewAdminPageTest.php
// - tests/e2e/admin/new-admin-page.spec.js
```

### Debugging Failed Tests

```bash
# Run single failing test
./vendor/bin/phpunit tests/unit/Path/ClassTest.php --filter testMethodName

# Run with verbose output
./vendor/bin/phpunit --verbose

# Run with debug mode
./vendor/bin/phpunit --debug

# For JavaScript tests
npm run test:js -- --no-coverage --verbose
```

---

## CI/CD Integration

### GitHub Actions

Tests run automatically on:
- Push to any branch
- Pull requests
- Manual workflow dispatch

**Workflow file:** `.github/workflows/test.yml`

**Test stages:**
1. **Lint** - PHP CodeSniffer, ESLint
2. **Unit Tests** - PHPUnit unit tests
3. **Integration Tests** - PHPUnit integration tests
4. **JavaScript Tests** - Jest tests
5. **E2E Tests** - Playwright (Chrome only in CI)
6. **Coverage** - Upload to Codecov

### Local CI Simulation

Run the same tests as CI locally:

```bash
npm run ci:test
```

### GitLab CI (Future)

The test structure is designed to be CI-agnostic. To migrate to GitLab:

1. Create `.gitlab-ci.yml` (template provided in `tests/_support/gitlab-ci.yml.template`)
2. Use the same test commands
3. Configure GitLab runners with Node.js and PHP

---

## Troubleshooting

### WordPress Test Library Issues

```bash
# Reinstall WordPress test library
npm run test:setup:force

# Check wp-env status
npx wp-env status

# Restart wp-env
npx wp-env stop
npx wp-env start
```

### Database Connection Errors

```bash
# Check .env.testing configuration
cat tests/.env.testing

# Verify database exists
npx wp-env run cli wp db check
```

### Jest Module Resolution Errors

```bash
# Clear Jest cache
npm run test:js -- --clearCache

# Reinstall node_modules
rm -rf node_modules package-lock.json
npm install
```

### Playwright Browser Issues

```bash
# Install browsers
npx playwright install

# Install browser dependencies (Linux)
npx playwright install-deps
```

### Coverage Not Generating

```bash
# Ensure coverage tools installed
npm install --save-dev @jest/globals c8
composer require --dev phpunit/php-code-coverage

# Run with coverage flag
npm run test:php:coverage
npm run test:js:coverage
```

---

## Best Practices

### General Testing Principles

1. **Test behavior, not implementation** - Test what the code does, not how it does it
2. **Arrange-Act-Assert pattern** - Clear test structure
3. **One assertion per test (when possible)** - Easier to debug
4. **Test edge cases** - Empty arrays, null values, boundary conditions
5. **Use descriptive test names** - `it_saves_cta_with_valid_data()` not `test1()`
6. **Explain WHY** - Add docblocks explaining business importance

### Security Testing

Always test for:
- SQL injection (use invalid SQL in inputs)
- XSS (use `<script>alert('xss')</script>` in inputs)
- Path traversal (use `../../etc/passwd` in paths)
- CSRF (verify nonce checks)
- Capability checks (test as non-admin user)
- Data sanitization (test special characters)

### Database Testing

- Always use transactions (auto-rollback)
- Test with realistic data volumes
- Test concurrent operations
- Verify prepared statements used
- Check for N+1 queries

### JavaScript Testing

- Mock WordPress globals (`wp`, `jQuery`)
- Mock browser APIs (`localStorage`, `IntersectionObserver`)
- Test async operations with proper waiting
- Test event handlers
- Test error conditions

### E2E Testing

- Use data attributes for selectors (more stable)
- Wait for network idle before assertions
- Take screenshots on failure (automatic)
- Test mobile viewports
- Test different user roles

---

## Coverage Goals

### Current Coverage

Run to see current coverage:
```bash
npm run test:coverage
```

### Target Coverage by Component

| Component | Target | Priority | Current |
|-----------|--------|----------|---------|
| AutoInsertion/Database | 95%+ | HIGH | - |
| Template/Loader | 95%+ | HIGH | - |
| Shortcode/Handler | 90%+ | HIGH | - |
| Admin Classes | 90%+ | HIGH | - |
| AutoInsertion/Matcher | 85%+ | MEDIUM | - |
| AutoInsertion/Manager | 85%+ | MEDIUM | - |
| JavaScript Modules | 85%+ | MEDIUM | - |
| Template/ViewData | 80%+ | MEDIUM | - |
| Assets/Manager | 75%+ | LOW | - |

**Overall Target: 80%+**

---

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Jest Documentation](https://jestjs.io/docs/getting-started)
- [Playwright Documentation](https://playwright.dev/)
- [WordPress Unit Testing](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
- [wp-env Documentation](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)

---

## Contributing

When adding new tests:

1. Follow the templates in this README
2. Place tests in the correct directory
3. Use descriptive names
4. Add docblocks explaining WHY
5. Ensure tests are isolated (no dependencies between tests)
6. Update this README if adding new test types

---

## Questions?

For questions about testing:
1. Check this README first
2. Review existing tests for patterns
3. Check CI logs for examples
4. Consult the main plugin README.md

---

**Last Updated:** 2025-01-XX
**Test Framework Version:** 1.0.0
**Minimum PHP Version:** 7.4
**Minimum Node Version:** 16.0
