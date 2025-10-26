# Visual Regression Testing Scenarios

**Version:** 1.0.0
**Last Updated:** 2025-01-XX
**Tool:** Playwright Visual Comparisons

---

## Overview

Visual regression testing captures screenshots of the plugin's UI and compares them against baseline images to detect unintended visual changes. This ensures CSS changes, browser updates, or code refactoring don't break the visual design.

### How Visual Regression Works

1. **Baseline Creation**: Capture screenshots of all UI states (first run)
2. **Comparison**: On subsequent runs, capture new screenshots and compare to baselines
3. **Diff Generation**: If differences detected, generate visual diff images
4. **Review**: Developer reviews diffs to determine if intentional or bugs

---

## Setup

### Install Dependencies

```bash
npm install --save-dev @playwright/test
```

### Directory Structure

```
tests/uat/visual-regression/
├── baseline-images/          # Baseline screenshots (git-tracked)
│   ├── admin/
│   ├── frontend/
│   └── mobile/
├── test-results/             # Test run screenshots (git-ignored)
│   ├── actual/
│   ├── diff/
│   └── expected/
├── test-scenarios.md         # This file
└── visual-regression.spec.js # Test file
```

### Running Visual Tests

```bash
# Generate baseline images (first time)
npm run test:visual -- --update-snapshots

# Run visual regression tests
npm run test:visual

# Update specific baseline
npm run test:visual -- --update-snapshots admin-cta-list
```

---

## Test Scenarios

### 1. Admin Interface

#### 1.1 CTA List Page

**Scenario:** Empty state
- **URL:** `/wp-admin/edit.php?post_type=cta_highlight`
- **State:** No CTAs created
- **Viewport:** 1280x720
- **Capture:** Full page
- **Baseline:** `admin/cta-list-empty.png`

**Scenario:** With CTAs
- **URL:** `/wp-admin/edit.php?post_type=cta_highlight`
- **Setup:** Create 5 CTAs (3 primary, 2 fallback)
- **Viewport:** 1280x720
- **Capture:** Full page
- **Baseline:** `admin/cta-list-populated.png`

**Scenario:** Search results
- **URL:** `/wp-admin/edit.php?post_type=cta_highlight&s=test`
- **Setup:** Search for "test"
- **Viewport:** 1280x720
- **Capture:** Full page
- **Baseline:** `admin/cta-list-search.png`

#### 1.2 CTA Edit Page

**Scenario:** New CTA
- **URL:** `/wp-admin/post-new.php?post_type=cta_highlight`
- **State:** Empty form
- **Viewport:** 1280x720
- **Capture:** Full page
- **Baseline:** `admin/cta-edit-new.png`

**Scenario:** Edit Primary CTA
- **URL:** `/wp-admin/post.php?post=[id]&action=edit`
- **Setup:** Primary CTA with all fields filled
- **Viewport:** 1280x720
- **Capture:** Full page
- **Baseline:** `admin/cta-edit-primary.png`

**Scenario:** Edit Fallback CTA
- **URL:** `/wp-admin/post.php?post=[id]&action=edit`
- **Setup:** Fallback CTA with parent selected
- **Viewport:** 1280x720
- **Capture:** Full page
- **Baseline:** `admin/cta-edit-fallback.png`

**Scenario:** Meta boxes
- **Element:** `.postbox`
- **Setup:** All meta boxes open
- **Viewport:** 1280x720
- **Capture:** Each meta box separately
- **Baselines:**
  - `admin/metabox-template.png`
  - `admin/metabox-cta-type.png`
  - `admin/metabox-post-types.png`
  - `admin/metabox-insertion-settings.png`

#### 1.3 Post Edit Page (Meta Box)

**Scenario:** Disable auto-insert meta box
- **URL:** `/wp-admin/post.php?post=[post-id]&action=edit`
- **Element:** `#cta-highlights-post-meta`
- **Setup:** Regular post edit page
- **Viewport:** 1280x720
- **Capture:** Meta box only
- **Baseline:** `admin/post-metabox.png`

---

### 2. Frontend - Shortcode Rendering

#### 2.1 Default Template

**Scenario:** Basic CTA - Default state
- **URL:** `/test-post/` (with shortcode)
- **Shortcode:** `[cta_highlights title="Subscribe" template="default"]Join our newsletter![/cta_highlights]`
- **State:** Not highlighted
- **Viewport:** 1280x720
- **Capture:** CTA element only (`.cta-highlights-wrapper`)
- **Baseline:** `frontend/shortcode-default-normal.png`

**Scenario:** Basic CTA - Hover state
- **URL:** Same as above
- **State:** Mouse hover over CTA
- **Viewport:** 1280x720
- **Capture:** CTA element only
- **Baseline:** `frontend/shortcode-default-hover.png`

#### 2.2 Multiple CTAs

**Scenario:** Two CTAs in sequence
- **URL:** `/test-post-multiple/`
- **Shortcodes:** Two CTAs with different titles
- **State:** Normal display
- **Viewport:** 1280x720
- **Capture:** Post content area
- **Baseline:** `frontend/shortcode-multiple.png`

#### 2.3 Custom Template

**Scenario:** Custom template rendering
- **URL:** `/test-post-custom/`
- **Shortcode:** `[cta_highlights template="custom" title="Custom"]Content[/cta_highlights]`
- **State:** Normal display
- **Viewport:** 1280x720
- **Capture:** CTA element only
- **Baseline:** `frontend/shortcode-custom-template.png`

---

### 3. Frontend - Highlight Effect

#### 3.1 Overlay and Elevated CTA

**Scenario:** Highlight activated
- **URL:** `/test-post/`
- **State:** CTA highlighted (scrolled into view)
- **Viewport:** 1280x720
- **Capture:** Full viewport
- **Baseline:** `frontend/highlight-activated-desktop.png`

**Scenario:** Overlay appearance
- **URL:** Same as above
- **Element:** `.cta-highlights-overlay`
- **State:** Overlay visible
- **Viewport:** 1280x720
- **Capture:** Full viewport
- **Baseline:** `frontend/highlight-overlay.png`

**Scenario:** Close button
- **URL:** Same as above
- **Element:** `.cta-highlights-close`
- **State:** Highlighted
- **Viewport:** 1280x720
- **Capture:** Close button only (zoomed)
- **Baseline:** `frontend/highlight-close-button.png`

**Scenario:** Close button hover
- **URL:** Same as above
- **Element:** `.cta-highlights-close`
- **State:** Hover over close button
- **Viewport:** 1280x720
- **Capture:** Close button only
- **Baseline:** `frontend/highlight-close-button-hover.png`

---

### 4. Mobile Views

#### 4.1 Mobile Portrait

**Scenario:** CTA on mobile
- **URL:** `/test-post/`
- **Viewport:** 375x667 (iPhone SE)
- **State:** Normal display
- **Capture:** CTA element
- **Baseline:** `mobile/shortcode-mobile-portrait.png`

**Scenario:** Highlight on mobile
- **URL:** `/test-post/`
- **Viewport:** 375x667
- **State:** Highlighted
- **Capture:** Full viewport
- **Baseline:** `mobile/highlight-mobile-portrait.png`

#### 4.2 Mobile Landscape

**Scenario:** CTA landscape
- **URL:** `/test-post/`
- **Viewport:** 667x375
- **State:** Normal display
- **Capture:** CTA element
- **Baseline:** `mobile/shortcode-mobile-landscape.png`

#### 4.3 Tablet

**Scenario:** CTA on tablet
- **URL:** `/test-post/`
- **Viewport:** 768x1024 (iPad)
- **State:** Normal display
- **Capture:** CTA element
- **Baseline:** `mobile/shortcode-tablet.png`

**Scenario:** Highlight on tablet
- **URL:** `/test-post/`
- **Viewport:** 768x1024
- **State:** Highlighted
- **Capture:** Full viewport
- **Baseline:** `mobile/highlight-tablet.png`

---

### 5. Theme Compatibility

#### 5.1 Default Theme (Twenty Twenty-Four)

**Scenario:** In default theme
- **URL:** `/test-post/`
- **Theme:** Twenty Twenty-Four
- **State:** Normal CTA display
- **Viewport:** 1280x720
- **Capture:** Post content area
- **Baseline:** `themes/twentytwentyfour-cta.png`

**Scenario:** Highlighted in default theme
- **URL:** `/test-post/`
- **Theme:** Twenty Twenty-Four
- **State:** Highlighted
- **Viewport:** 1280x720
- **Capture:** Full viewport
- **Baseline:** `themes/twentytwentyfour-highlight.png`

#### 5.2 Popular Theme (Astra)

**Scenario:** In Astra theme
- **URL:** `/test-post/`
- **Theme:** Astra
- **State:** Normal CTA display
- **Viewport:** 1280x720
- **Capture:** Post content area
- **Baseline:** `themes/astra-cta.png`

---

### 6. Auto-Insertion

**Scenario:** Auto-inserted CTA
- **URL:** `/test-post-auto/`
- **Setup:** Post with auto-insert CTA (no shortcode)
- **State:** CTA inserted via JS
- **Viewport:** 1280x720
- **Capture:** Post content area
- **Baseline:** `frontend/auto-insert-normal.png`

**Scenario:** Auto-inserted with highlight
- **URL:** `/test-post-auto/`
- **State:** Auto-inserted CTA highlighted
- **Viewport:** 1280x720
- **Capture:** Full viewport
- **Baseline:** `frontend/auto-insert-highlighted.png`

---

### 7. Edge Cases

**Scenario:** Very long title
- **Shortcode:** `[cta_highlights title="This is a very long title that might wrap to multiple lines and we need to test how it renders"]Content[/cta_highlights]`
- **Viewport:** 1280x720
- **Capture:** CTA element
- **Baseline:** `edge-cases/long-title.png`

**Scenario:** Very long content
- **Shortcode:** CTA with 500 words of content
- **Viewport:** 1280x720
- **Capture:** CTA element (scrollable)
- **Baseline:** `edge-cases/long-content.png`

**Scenario:** Empty content
- **Shortcode:** `[cta_highlights title="Test"][/cta_highlights]`
- **Viewport:** 1280x720
- **Capture:** CTA element
- **Baseline:** `edge-cases/empty-content.png`

**Scenario:** RTL (Right-to-Left) language
- **Setup:** Site language set to Arabic/Hebrew
- **Viewport:** 1280x720
- **Capture:** CTA element
- **Baseline:** `edge-cases/rtl-cta.png`

---

## Playwright Visual Regression Test

### Example Test File

```javascript
// tests/uat/visual-regression/visual-regression.spec.js

const { test, expect } = require('@playwright/test');

test.describe('Visual Regression Tests', () => {

  // Admin tests
  test('CTA list page - empty state', async ({ page }) => {
    await page.goto('/wp-admin/edit.php?post_type=cta_highlight');
    await expect(page).toHaveScreenshot('admin/cta-list-empty.png', {
      fullPage: true,
      maxDiffPixels: 100
    });
  });

  test('CTA edit page - new CTA', async ({ page }) => {
    await page.goto('/wp-admin/post-new.php?post_type=cta_highlight');
    await expect(page).toHaveScreenshot('admin/cta-edit-new.png', {
      fullPage: true,
      maxDiffPixels: 100
    });
  });

  // Frontend tests
  test('Shortcode - default template normal state', async ({ page }) => {
    await page.goto('/test-post/');
    const cta = page.locator('.cta-highlights-wrapper').first();
    await expect(cta).toHaveScreenshot('frontend/shortcode-default-normal.png', {
      maxDiffPixels: 50
    });
  });

  test('Highlight effect - activated', async ({ page }) => {
    await page.goto('/test-post/');

    // Scroll CTA into view to activate highlight
    const cta = page.locator('.cta-highlights-wrapper').first();
    await cta.scrollIntoViewIfNeeded();
    await page.waitForTimeout(1000); // Wait for animation

    await expect(page).toHaveScreenshot('frontend/highlight-activated-desktop.png', {
      fullPage: false,
      maxDiffPixels: 100
    });
  });

  // Mobile tests
  test('Mobile - CTA portrait', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('/test-post/');

    const cta = page.locator('.cta-highlights-wrapper').first();
    await expect(cta).toHaveScreenshot('mobile/shortcode-mobile-portrait.png', {
      maxDiffPixels: 50
    });
  });
});
```

---

## Configuration Options

### Playwright Screenshot Options

```javascript
{
  // Maximum number of different pixels allowed
  maxDiffPixels: 100,

  // Maximum percentage of different pixels (0-1)
  maxDiffPixelRatio: 0.01,

  // Capture full page (scrolling)
  fullPage: true,

  // Omit background (transparent)
  omitBackground: false,

  // Screenshot animations: 'disabled' or 'allow'
  animations: 'disabled',

  // CSS media type: 'screen' or 'print'
  cspSetting: 'screen',

  // Threshold for pixel diff (0-1, higher = more lenient)
  threshold: 0.2
}
```

---

## Best Practices

### 1. Consistent Environment
- Use fixed viewport sizes
- Disable animations during screenshot
- Use fixed test data (same content, images)
- Clear browser cache between runs
- Use same browser version

### 2. Selective Capture
- Capture specific elements, not full pages (when possible)
- Mask dynamic content (dates, times, user-specific data)
- Ignore scroll bars if not relevant

### 3. Baseline Management
- **Commit baselines to git** - Ensures team uses same baselines
- **Update intentionally** - Only when design changes are intentional
- **Review diffs carefully** - Ensure changes are expected

### 4. Diff Thresholds
- Allow small pixel differences (anti-aliasing, rendering differences)
- Use `maxDiffPixels` or `maxDiffPixelRatio` appropriately
- Be more lenient with complex UIs, strict with simple elements

### 5. CI Integration
- Run visual tests in CI pipeline
- Fail build on unexpected visual changes
- Generate HTML report with diffs for review
- Store artifacts for manual inspection

---

## Masking Dynamic Content

For elements that change on every run (timestamps, user names, etc.), mask them:

```javascript
await expect(page).toHaveScreenshot('admin/dashboard.png', {
  mask: [
    page.locator('.timestamp'), // Mask timestamp elements
    page.locator('.user-greeting'), // Mask "Hello, [username]"
  ],
  maxDiffPixels: 100
});
```

---

## Troubleshooting

### Screenshots Don't Match

**Possible causes:**
1. **Font rendering differences** - Use web fonts, not system fonts
2. **Anti-aliasing** - Increase `threshold` or `maxDiffPixels`
3. **Different browser version** - Pin Playwright browser version
4. **Dynamic content** - Mask or stabilize dynamic elements
5. **Animations** - Disable animations or wait for completion

### Baseline Doesn't Exist

**Solution:**
```bash
# Generate missing baselines
npm run test:visual -- --update-snapshots
```

### Too Many Failures

**Solution:**
1. Review diffs in `test-results/` directory
2. If changes are intentional, update all baselines:
   ```bash
   npm run test:visual -- --update-snapshots
   ```
3. Commit new baselines to git

---

## Maintenance

### When to Update Baselines

✅ **Update baselines when:**
- Intentional design changes
- New features with UI changes
- Browser updates (if rendering changes)
- Plugin updates with visual improvements

❌ **Don't update baselines when:**
- Tests fail unexpectedly
- Visual bugs are introduced
- Unsure about the changes

### Review Process

1. Run visual tests locally before committing
2. Review any failures (check diff images)
3. If failures are bugs, fix them
4. If failures are intentional changes, update baselines
5. Commit baseline changes with clear message

---

## NPM Scripts

Add to `package.json`:

```json
{
  "scripts": {
    "test:visual": "playwright test tests/uat/visual-regression/",
    "test:visual:update": "playwright test tests/uat/visual-regression/ --update-snapshots",
    "test:visual:ui": "playwright test tests/uat/visual-regression/ --ui",
    "test:visual:report": "playwright show-report tests/uat/visual-regression/test-results/"
  }
}
```

---

## Summary

Visual regression testing ensures the plugin's UI remains consistent across updates. By capturing and comparing screenshots:

- **Prevent accidental visual bugs**
- **Document visual changes**
- **Maintain design consistency**
- **Test across browsers and devices**
- **Catch CSS regressions early**

Run visual tests regularly (before releases, in CI) to catch issues before users do.

---

**End of Visual Regression Test Scenarios**
