# Comprehensive Testing Guide

**Version:** 1.0.0
**Last Updated:** 2025-01-XX
**Plugin:** CTA Highlights

---

## Table of Contents

1. [Overview](#overview)
2. [Quick Start](#quick-start)
3. [Test Types](#test-types)
4. [Running Tests](#running-tests)
5. [Writing New Tests](#writing-new-tests)
6. [CI/CD Integration](#cicd-integration)
7. [Troubleshooting](#troubleshooting)
8. [Best Practices](#best-practices)

---

## Overview

The CTA Highlights plugin has a comprehensive testing infrastructure covering:

- **Unit Tests** (PHP) - Test individual classes/methods in isolation
- **Integration Tests** (PHP) - Test multiple components working together
- **JavaScript Tests** (Jest) - Test client-side functionality
- **E2E Tests** (Playwright) - Test complete user workflows in real browsers
- **Visual Regression Tests** - Detect unintended UI changes
- **UAT** (User Acceptance Testing) - Manual testing checklist
- **Performance Tests** - Ensure fast page loads and Core Web Vitals compliance

### Test Coverage Statistics

| Test Type | Files | Tests | Coverage | Status |
|-----------|-------|-------|----------|--------|
| Unit (PHP) | 10+ | 350+ | ~91% | ✅ Complete |
| Integration (PHP) | 5 | 190+ | N/A | ✅ Complete |
| JavaScript (Jest) | 2 | 150+ | ~85% | ✅ Complete |
| E2E (Playwright) | 5 | 100+ | N/A | ✅ Complete |
| Visual Regression | 1 | 30+ | N/A | ✅ Complete |
| **Total** | **23+** | **820+** | **~88%** | **✅ Complete** |

---

## Quick Start

### Prerequisites

```bash
# Install Node dependencies
npm install

# Install PHP dependencies
composer install

# Start WordPress environment
npm run env:start

# Setup test database
npm run test:setup
```

### Run All Tests

```bash
# All PHP tests (unit + integration)
npm run test:php

# All JavaScript tests
npm run test:js

# All E2E tests
npm run test:e2e

# All tests (complete suite)
npm run test:all
```

### Expected Output

```
✅ PHP Unit Tests: 350 passed
✅ PHP Integration Tests: 190 passed
✅ JavaScript Tests: 150 passed
✅ E2E Tests: 100 passed
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Total: 790 tests passed in ~3 minutes
```

---

## Test Types

### 1. Unit Tests (PHP)

**Purpose:** Test individual PHP classes and methods in isolation.

**Location:** `tests/unit/`

**Framework:** PHPUnit 9.x

**Coverage:**
- Database operations (SQL injection prevention, sanitization)
- Template loading (path traversal, XSS prevention)
- Shortcode handling (attribute sanitization)
- Auto-insertion logic (fallback chains, circular detection)
- Admin forms (capability checks, nonce verification)

**Run:**
```bash
# All unit tests
npm run test:php:unit

# Specific test file
./vendor/bin/phpunit tests/unit/AutoInsertion/DatabaseTest.php

# Specific test method
./vendor/bin/phpunit --filter test_prevents_sql_injection tests/unit/AutoInsertion/DatabaseTest.php

# With coverage
npm run test:php:coverage
```

### 2. Integration Tests (PHP)

**Purpose:** Test multiple components working together.

**Location:** `tests/integration/`

**Framework:** PHPUnit 9.x + WordPress Test Library

**Coverage:**
- Shortcode rendering flow (Handler → Loader → Registry → Assets)
- Template override system (theme vs plugin templates)
- Auto-insertion workflow (Database → Matcher → Manager → JSON)
- WordPress hooks/filters integration
- User capabilities and permissions

**Run:**
```bash
# All integration tests
npm run test:php:integration

# Specific test file
./vendor/bin/phpunit tests/integration/ShortcodeRenderingTest.php
```

### 3. JavaScript Tests (Jest)

**Purpose:** Test client-side JavaScript functionality.

**Location:** `tests/javascript/`

**Framework:** Jest 29.x

**Coverage:**
- StorageManager (localStorage/cookie fallback)
- CTAHighlight class (overlay, focus trap, cooldown)
- AutoInsertManager (position calculation, DOM insertion)
- Intersection Observer integration
- Accessibility (ARIA, keyboard navigation)

**Run:**
```bash
# All JavaScript tests
npm run test:js

# Watch mode (re-run on file changes)
npm run test:js:watch

# With coverage
npm run test:js:coverage

# Specific test file
npm test tests/javascript/cta-highlights.test.js
```

### 4. E2E Tests (Playwright)

**Purpose:** Test complete user workflows in real browsers.

**Location:** `tests/e2e/`

**Framework:** Playwright

**Coverage:**
- Admin CRUD operations (create, edit, delete CTAs)
- Frontend shortcode rendering
- Highlight effect activation and dismissal
- Auto-insertion functionality
- Keyboard navigation and accessibility
- Cross-browser compatibility (Chrome, Firefox, Safari)
- Mobile testing (touch events, responsive design)

**Run:**
```bash
# All E2E tests (all browsers)
npm run test:e2e

# Specific browser
npx playwright test --project=chromium
npx playwright test --project=firefox
npx playwright test --project=webkit

# Mobile browsers
npx playwright test --project="Mobile Chrome"
npx playwright test --project="Mobile Safari"

# Headed mode (see browser)
npx playwright test --headed

# Debug mode (step through)
npx playwright test --debug

# Specific test file
npx playwright test tests/e2e/admin/cta-crud.spec.js

# UI mode (interactive)
npx playwright test --ui
```

### 5. Visual Regression Tests

**Purpose:** Detect unintended visual changes.

**Location:** `tests/uat/visual-regression/`

**Framework:** Playwright (screenshots)

**Coverage:**
- Admin interface (CTA list, edit pages, meta boxes)
- Frontend shortcode rendering (normal, hover states)
- Highlight effect (overlay, close button)
- Mobile views (portrait, landscape, tablet)
- Edge cases (long title, empty content)

**Run:**
```bash
# Generate baseline images (first time)
npm run test:visual -- --update-snapshots

# Run visual regression tests
npm run test:visual

# Update specific baseline
npm run test:visual -- --update-snapshots admin-cta-list

# View report
npx playwright show-report
```

### 6. Performance Tests

**Purpose:** Ensure fast page loads and Core Web Vitals compliance.

**Location:** `tests/uat/performance/`

**Tools:** Lighthouse, WebPageTest, Chrome DevTools

**Metrics:**
- LCP (Largest Contentful Paint) ≤ 2.5s
- FID (First Input Delay) ≤ 100ms
- CLS (Cumulative Layout Shift) ≤ 0.1
- TBT (Total Blocking Time) ≤ 200ms
- Asset sizes (CSS ≤ 30 KB, JS ≤ 50 KB)

**Run:**
```bash
# Lighthouse audit
lighthouse http://localhost:8888/test-post/ \
  --output=html \
  --output-path=./lighthouse-report.html

# Lighthouse CI
npm install -g @lhci/cli
lhci autorun --config=tests/uat/performance/lighthouserc.js

# Check bundle sizes
npm run build
du -h assets/dist/*
```

### 7. UAT (User Acceptance Testing)

**Purpose:** Manual testing by QA teams before release.

**Location:** `tests/uat/uat-checklist.md`

**Coverage:**
- Installation and activation
- Admin interface functionality
- Frontend rendering
- Accessibility (keyboard, screen reader)
- Cross-browser compatibility
- Performance
- Security (XSS, SQL injection, CSRF)

**Process:**
1. Open `tests/uat/uat-checklist.md`
2. Follow each test scenario
3. Check boxes as you complete tests
4. Document any failures
5. Sign off when complete

---

## Running Tests

### Local Development

**Before pushing code:**
```bash
# Run quick tests
npm run test:quick  # PHP unit + JS tests (~1 min)

# Run comprehensive tests
npm run test:all    # All tests except E2E (~2 min)

# Run full suite (before PR)
npm run test:full   # Everything including E2E (~5 min)
```

### In CI/CD Pipeline

Tests run automatically on:
- Every commit (quick tests)
- Pull requests (comprehensive tests)
- Before deployment (full suite)

**GitHub Actions workflow:** `.github/workflows/test.yml`

### Pre-Release Checklist

Before releasing a new version:

- [ ] All unit tests pass
- [ ] All integration tests pass
- [ ] All JavaScript tests pass
- [ ] All E2E tests pass (all browsers)
- [ ] Visual regression tests pass (or baselines updated)
- [ ] Performance benchmarks met (Lighthouse ≥ 90)
- [ ] UAT checklist completed and signed off
- [ ] No security vulnerabilities (XSS, SQL injection, CSRF)
- [ ] Code coverage ≥ 80%

---

## Writing New Tests

### PHP Unit Test Template

```php
<?php
/**
 * Tests for NewClass
 *
 * @group unit
 * @group new-feature
 */

namespace CTAHighlights\Tests\Unit;

use CTAHighlights\NewClass;
use WP_UnitTestCase;

class NewClassTest extends WP_UnitTestCase {

	protected $instance;

	public function setUp(): void {
		parent::setUp();
		$this->instance = new NewClass();
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * @test
	 * Test that method does something
	 *
	 * WHY: Explain why this test is important
	 * PRIORITY: HIGH|MEDIUM|LOW
	 */
	public function it_does_something() {
		// Arrange
		$input = 'test';

		// Act
		$result = $this->instance->do_something( $input );

		// Assert
		$this->assertEquals( 'expected', $result );
	}
}
```

### JavaScript Test Template

```javascript
/**
 * Tests for NewModule
 *
 * @group unit
 * @group javascript
 */

import { NewModule } from '../assets/js/new-module.js';

describe('NewModule', () => {
  let instance;

  beforeEach(() => {
    instance = new NewModule();
  });

  afterEach(() => {
    // Cleanup
  });

  test('should do something', () => {
    // Arrange
    const input = 'test';

    // Act
    const result = instance.doSomething(input);

    // Assert
    expect(result).toBe('expected');
  });
});
```

### E2E Test Template

```javascript
/**
 * E2E Tests for New Feature
 *
 * @group e2e
 * @group new-feature
 */

const { test, expect } = require('@playwright/test');

test.describe('New Feature', () => {
  test('should perform user workflow', async ({ page }) => {
    // Navigate
    await page.goto('/test-page/');

    // Interact
    await page.click('.some-button');

    // Verify
    await expect(page.locator('.result')).toBeVisible();
    await expect(page.locator('.result')).toHaveText('expected');
  });
});
```

### Visual Regression Test Template

```javascript
test('should match visual snapshot', async ({ page }) => {
  await page.goto('/test-page/');

  const element = page.locator('.component');
  await expect(element).toHaveScreenshot('component-normal.png', {
    maxDiffPixels: 100,
    threshold: 0.2
  });
});
```

---

## CI/CD Integration

### GitHub Actions

The plugin uses GitHub Actions for automated testing.

**Workflow file:** `.github/workflows/test.yml`

**Triggers:**
- Push to any branch
- Pull requests
- Manual workflow dispatch

**Jobs:**
1. **PHP Tests** (Unit + Integration)
   - PHP 7.4, 8.0, 8.1
   - WordPress latest, previous
   - MySQL 5.7, 8.0

2. **JavaScript Tests**
   - Node 16.x, 18.x
   - Jest with coverage

3. **E2E Tests**
   - Playwright (Chromium, Firefox, WebKit)
   - Mobile browsers

4. **Lighthouse CI**
   - Performance budgets
   - Accessibility audits

**View Results:**
- Go to GitHub → Actions tab
- Click on workflow run
- View job logs and artifacts

### Local CI Simulation

```bash
# Run tests exactly as CI does
npm run test:ci

# This runs:
# 1. PHP CodeSniffer (linting)
# 2. PHPUnit (unit + integration)
# 3. Jest (JavaScript)
# 4. Playwright (E2E)
# 5. Lighthouse (performance)
```

---

## Troubleshooting

### Tests Fail Locally But Pass in CI

**Cause:** Environment differences

**Solutions:**
1. Ensure same PHP/WordPress versions
2. Clear database: `npm run test:clean`
3. Reset environment: `npm run env:reset`
4. Check for cached files: `rm -rf node_modules && npm install`

### E2E Tests Timeout

**Cause:** WordPress not ready or slow response

**Solutions:**
1. Increase timeout in test: `test.setTimeout(60000)`
2. Check wp-env is running: `npm run env:start`
3. Check for errors: `npm run env:logs`
4. Restart environment: `npm run env:restart`

### Visual Regression Tests Fail

**Cause:** Screenshot differences

**Solutions:**
1. Review diff images in `tests/e2e/test-results/`
2. If changes are intentional: `npm run test:visual -- --update-snapshots`
3. If unintentional: fix the UI issue
4. Check for font rendering differences (use web fonts)

### PHP Tests Can't Find Classes

**Cause:** Autoloader not updated

**Solutions:**
1. Regenerate autoloader: `composer dump-autoload`
2. Check class namespace matches file location
3. Verify PSR-4 mapping in `composer.json`

### JavaScript Tests Fail on Mock

**Cause:** Mock not properly set up

**Solutions:**
1. Check mock file exists in `tests/javascript/__mocks__/`
2. Verify manual mock in `jest.setup.js`
3. Clear Jest cache: `npm run test:js -- --clearCache`

---

## Best Practices

### General

1. **Write tests first** (TDD) - Define expected behavior before coding
2. **Test behavior, not implementation** - Focus on what, not how
3. **Keep tests fast** - Unit tests should run in milliseconds
4. **Use descriptive names** - `it_prevents_xss_in_title` not `test1`
5. **One assertion per test** - Makes failures easy to diagnose

### PHP Tests

1. **Use factories** - `CTAFactory::create()` instead of manual setup
2. **Clean up** - Use `tearDown()` to remove test data
3. **Test edge cases** - Empty strings, nulls, arrays, special characters
4. **Mock external dependencies** - Don't rely on network, file system
5. **Follow AAA pattern** - Arrange, Act, Assert

### JavaScript Tests

1. **Mock browser APIs** - localStorage, IntersectionObserver, etc.
2. **Clean up event listeners** - Prevent memory leaks
3. **Test async code properly** - Use `async/await` or `done()`
4. **Avoid DOM dependencies** - Test logic separately from DOM
5. **Use beforeEach/afterEach** - Reset state between tests

### E2E Tests

1. **Use Page Object Models** - Encapsulate page interactions
2. **Wait for elements** - Let Playwright auto-wait, don't use fixed delays
3. **Test real user flows** - Click buttons, fill forms, navigate
4. **Keep tests independent** - Each test should work in isolation
5. **Clean storage** - Clear cookies/localStorage between tests

### Visual Regression

1. **Mask dynamic content** - Dates, times, user-specific data
2. **Disable animations** - For consistent screenshots
3. **Use fixed viewports** - Same size every run
4. **Version control baselines** - Commit baseline images to git
5. **Review diffs carefully** - Ensure changes are intentional

---

## Test Organization

### Directory Structure

```
tests/
├── _support/               # Test infrastructure
│   ├── bootstrap.php       # PHPUnit bootstrap
│   ├── helpers.php         # Helper functions
│   ├── jest-setup.js       # Jest setup
│   ├── Factories/          # Test data factories
│   └── Traits/             # Reusable test traits
│
├── unit/                   # PHP unit tests
│   ├── AutoInsertion/
│   ├── Template/
│   ├── Shortcode/
│   └── Admin/
│
├── integration/            # PHP integration tests
│   ├── ShortcodeRenderingTest.php
│   ├── TemplateOverrideTest.php
│   └── ...
│
├── javascript/             # JavaScript tests
│   ├── __mocks__/          # Mock files
│   ├── cta-highlights.test.js
│   └── auto-insert.test.js
│
├── e2e/                    # E2E tests
│   ├── utils/              # Page objects, helpers
│   ├── admin/              # Admin tests
│   ├── frontend/           # Frontend tests
│   └── accessibility/      # Accessibility tests
│
├── uat/                    # UAT materials
│   ├── uat-checklist.md
│   ├── visual-regression/
│   └── performance/
│
├── README.md               # Testing overview
├── TESTING-GUIDE.md        # This file
└── IMPLEMENTATION-STATUS.md # Progress tracking
```

### Naming Conventions

**Test Files:**
- PHP: `ClassNameTest.php`
- JS: `module-name.test.js`
- E2E: `feature-name.spec.js`

**Test Methods:**
- PHP: `test_does_something()` or `it_does_something()`
- JS: `test('should do something', ...)`
- E2E: `test('should perform user action', ...)`

---

## Performance Tips

### Speed Up Test Execution

**PHP:**
```bash
# Run tests in parallel
./vendor/bin/phpunit --testdox --parallel=4

# Run only tests in specific group
./vendor/bin/phpunit --group security

# Exclude slow tests
./vendor/bin/phpunit --exclude-group slow
```

**JavaScript:**
```bash
# Run in parallel (default)
npm test -- --maxWorkers=4

# Run only changed files
npm test -- --onlyChanged

# Run specific test file
npm test -- cta-highlights.test.js
```

**E2E:**
```bash
# Run in parallel
npx playwright test --workers=4

# Run specific browser only
npx playwright test --project=chromium

# Skip slow tests
npx playwright test --grep-invert @slow
```

### CI Optimization

```yaml
# .github/workflows/test.yml
jobs:
  test:
    strategy:
      matrix:
        php: [7.4, 8.0, 8.1]
        wp: [latest, previous]
    steps:
      - uses: actions/cache@v3  # Cache dependencies
      - run: npm ci              # Use ci instead of install
      - run: composer install --no-dev --prefer-dist  # Skip dev deps
```

---

## Resources

### Documentation

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Jest Documentation](https://jestjs.io/docs/getting-started)
- [Playwright Documentation](https://playwright.dev/docs/intro)
- [WordPress Testing Handbook](https://make.wordpress.org/core/handbook/testing/)

### Tools

- [Query Monitor](https://wordpress.org/plugins/query-monitor/) - Database performance
- [Lighthouse](https://developers.google.com/web/tools/lighthouse) - Performance audits
- [WebPageTest](https://www.webpagetest.org/) - Detailed performance testing
- [BrowserStack](https://www.browserstack.com/) - Cross-browser testing

### Related Files

- `tests/README.md` - Testing overview with LLM agent instructions
- `tests/IMPLEMENTATION-STATUS.md` - Phase-by-phase progress tracking
- `tests/PHASE-*-COMPLETE.md` - Detailed documentation for each phase

---

## Summary

The CTA Highlights plugin has a robust, comprehensive testing infrastructure that ensures:

✅ **Code Quality** - 88% coverage across PHP and JavaScript
✅ **Security** - Automated XSS, SQL injection, CSRF testing
✅ **Functionality** - 820+ tests verify all features work correctly
✅ **Performance** - Lighthouse audits ensure Core Web Vitals compliance
✅ **Accessibility** - Keyboard, screen reader, ARIA testing
✅ **Cross-Browser** - Tests on Chrome, Firefox, Safari, mobile
✅ **Visual Consistency** - Screenshot comparisons catch UI regressions
✅ **CI/CD Integration** - Automated testing on every commit

**For Questions:**
See `tests/README.md` or consult the documentation for each phase.

---

**End of Testing Guide**
