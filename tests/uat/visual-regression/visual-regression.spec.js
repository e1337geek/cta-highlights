/**
 * Visual Regression Tests
 *
 * Captures screenshots and compares against baseline images to detect
 * unintended visual changes.
 *
 * @group uat
 * @group visual
 */

const { test, expect } = require('@playwright/test');
const CTAAdminPage = require('../../e2e/utils/CTAAdminPage');
const CTAFrontendPage = require('../../e2e/utils/CTAFrontendPage');

// Common screenshot options
const SCREENSHOT_OPTIONS = {
	maxDiffPixels: 100,
	threshold: 0.2,
	animations: 'disabled'
};

test.describe('Visual Regression: Admin Interface', () => {
	let adminPage;

	test.beforeEach(async ({ page }) => {
		adminPage = new CTAAdminPage(page);
	});

	test('CTA list page - empty state', async ({ page }) => {
		await adminPage.goto();
		await page.waitForLoadState('networkidle');

		await expect(page).toHaveScreenshot('admin/cta-list-empty.png', {
			...SCREENSHOT_OPTIONS,
			fullPage: true
		});
	});

	test('CTA edit page - new CTA', async ({ page }) => {
		await page.goto('/wp-admin/post-new.php?post_type=cta_highlight');
		await adminPage.waitForPageLoad();

		// Mask dynamic elements
		await expect(page).toHaveScreenshot('admin/cta-edit-new.png', {
			...SCREENSHOT_OPTIONS,
			fullPage: true,
			mask: [
				page.locator('#post-timestamp'), // Publication date
				page.locator('.notice') // Admin notices
			]
		});
	});

	test('Template meta box', async ({ page }) => {
		await page.goto('/wp-admin/post-new.php?post_type=cta_highlight');
		await adminPage.waitForPageLoad();

		const metaBox = page.locator('#cta_highlights_template_meta');
		await expect(metaBox).toHaveScreenshot('admin/metabox-template.png', SCREENSHOT_OPTIONS);
	});

	test('CTA type meta box', async ({ page }) => {
		await page.goto('/wp-admin/post-new.php?post_type=cta_highlight');
		await adminPage.waitForPageLoad();

		const metaBox = page.locator('#cta_highlights_type_meta');
		await expect(metaBox).toHaveScreenshot('admin/metabox-cta-type.png', SCREENSHOT_OPTIONS);
	});

	test('Post types meta box', async ({ page }) => {
		await page.goto('/wp-admin/post-new.php?post_type=cta_highlight');
		await adminPage.waitForPageLoad();

		const metaBox = page.locator('#cta_highlights_post_types_meta');
		await expect(metaBox).toHaveScreenshot('admin/metabox-post-types.png', SCREENSHOT_OPTIONS);
	});
});

test.describe('Visual Regression: Frontend Shortcode', () => {
	let frontendPage;
	let testPostUrl;

	test.beforeAll(async ({ browser }) => {
		// Create a test post with shortcode
		const page = await browser.newPage();
		const adminPage = new CTAAdminPage(page);

		await page.goto('/wp-admin/post-new.php');
		await adminPage.waitForPageLoad();

		await adminPage.fillTitle('Visual Regression Test Post');
		await page.evaluate(() => {
			const content = `
				<p>Paragraph before CTA</p>
				[cta_highlights title="Subscribe to our Newsletter" template="default"]
				Get weekly updates delivered to your inbox. Join thousands of subscribers!
				[/cta_highlights]
				<p>Paragraph after CTA</p>
			`;
			if (typeof window.tinyMCE !== 'undefined' && window.tinyMCE.activeEditor) {
				window.tinyMCE.activeEditor.setContent(content);
			} else {
				document.getElementById('content').value = content;
			}
		});

		await page.click('#publish');
		await page.waitForSelector('.notice-success');

		testPostUrl = await page.locator('#sample-permalink a').getAttribute('href');
		await page.close();
	});

	test.beforeEach(async ({ page }) => {
		frontendPage = new CTAFrontendPage(page);
	});

	test('Shortcode default template - normal state', async ({ page }) => {
		await frontendPage.goto(testPostUrl);

		const cta = page.locator('.cta-highlights-wrapper').first();
		await cta.waitFor({ state: 'visible' });

		await expect(cta).toHaveScreenshot('frontend/shortcode-default-normal.png', SCREENSHOT_OPTIONS);
	});

	test('Shortcode default template - hover state', async ({ page }) => {
		await frontendPage.goto(testPostUrl);

		const cta = page.locator('.cta-highlights-wrapper').first();
		await cta.waitFor({ state: 'visible' });
		await cta.hover();

		await expect(cta).toHaveScreenshot('frontend/shortcode-default-hover.png', SCREENSHOT_OPTIONS);
	});

	test('Full page with CTA', async ({ page }) => {
		await frontendPage.goto(testPostUrl);
		await page.waitForLoadState('networkidle');

		// Mask dynamic WordPress elements
		await expect(page).toHaveScreenshot('frontend/page-with-cta.png', {
			...SCREENSHOT_OPTIONS,
			fullPage: true,
			mask: [
				page.locator('.post-date'), // Post date
				page.locator('.comment-count') // Comment count
			]
		});
	});
});

test.describe('Visual Regression: Highlight Effect', () => {
	let frontendPage;
	let testPostUrl;

	test.beforeAll(async ({ browser }) => {
		// Create test post with CTA below fold
		const page = await browser.newPage();
		const adminPage = new CTAAdminPage(page);

		await page.goto('/wp-admin/post-new.php');
		await adminPage.waitForPageLoad();

		await adminPage.fillTitle('Highlight Effect Test Post');
		await page.evaluate(() => {
			const content = `
				<p>Paragraph 1</p>
				<p>Paragraph 2</p>
				<p>Paragraph 3</p>
				<p>Paragraph 4</p>
				<p>Paragraph 5</p>
				[cta_highlights title="Special Offer"]Limited time discount![/cta_highlights]
				<p>Paragraph 6</p>
			`;
			if (typeof window.tinyMCE !== 'undefined' && window.tinyMCE.activeEditor) {
				window.tinyMCE.activeEditor.setContent(content);
			} else {
				document.getElementById('content').value = content;
			}
		});

		await page.click('#publish');
		await page.waitForSelector('.notice-success');

		testPostUrl = await page.locator('#sample-permalink a').getAttribute('href');
		await page.close();
	});

	test.beforeEach(async ({ page, context }) => {
		frontendPage = new CTAFrontendPage(page);
		await context.clearCookies();
		await page.evaluate(() => window.localStorage.clear());
	});

	test('Highlight activated - full viewport', async ({ page }) => {
		await frontendPage.goto(testPostUrl);

		// Scroll to activate highlight
		await frontendPage.scrollCTAIntoView(0);
		await frontendPage.waitForHighlight().catch(() => {});
		await page.waitForTimeout(500); // Wait for animation

		await expect(page).toHaveScreenshot('frontend/highlight-activated-desktop.png', {
			...SCREENSHOT_OPTIONS,
			fullPage: false // Just viewport
		});
	});

	test('Close button normal', async ({ page }) => {
		await frontendPage.goto(testPostUrl);
		await frontendPage.scrollCTAIntoView(0);
		await frontendPage.waitForHighlight().catch(() => {});

		const closeBtn = page.locator('.cta-highlights-close').first();
		await expect(closeBtn).toHaveScreenshot('frontend/highlight-close-button.png', SCREENSHOT_OPTIONS);
	});

	test('Close button hover', async ({ page }) => {
		await frontendPage.goto(testPostUrl);
		await frontendPage.scrollCTAIntoView(0);
		await frontendPage.waitForHighlight().catch(() => {});

		const closeBtn = page.locator('.cta-highlights-close').first();
		await closeBtn.hover();

		await expect(closeBtn).toHaveScreenshot('frontend/highlight-close-button-hover.png', SCREENSHOT_OPTIONS);
	});

	test('Overlay appearance', async ({ page }) => {
		await frontendPage.goto(testPostUrl);
		await frontendPage.scrollCTAIntoView(0);
		await frontendPage.waitForHighlight().catch(() => {});

		const overlay = page.locator('.cta-highlights-overlay');
		await expect(page).toHaveScreenshot('frontend/highlight-overlay.png', {
			...SCREENSHOT_OPTIONS,
			fullPage: false
		});
	});
});

test.describe('Visual Regression: Mobile Views', () => {
	let frontendPage;
	let testPostUrl;

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage();
		const adminPage = new CTAAdminPage(page);

		await page.goto('/wp-admin/post-new.php');
		await adminPage.waitForPageLoad();

		await adminPage.fillTitle('Mobile Test Post');
		await page.evaluate(() => {
			const content = `
				<p>Mobile test content</p>
				[cta_highlights title="Mobile CTA"]Optimized for mobile devices[/cta_highlights]
			`;
			if (typeof window.tinyMCE !== 'undefined' && window.tinyMCE.activeEditor) {
				window.tinyMCE.activeEditor.setContent(content);
			} else {
				document.getElementById('content').value = content;
			}
		});

		await page.click('#publish');
		await page.waitForSelector('.notice-success');

		testPostUrl = await page.locator('#sample-permalink a').getAttribute('href');
		await page.close();
	});

	test.beforeEach(async ({ page }) => {
		frontendPage = new CTAFrontendPage(page);
	});

	test('Mobile portrait - iPhone SE', async ({ page }) => {
		await page.setViewportSize({ width: 375, height: 667 });
		await frontendPage.goto(testPostUrl);

		const cta = page.locator('.cta-highlights-wrapper').first();
		await expect(cta).toHaveScreenshot('mobile/shortcode-mobile-portrait.png', SCREENSHOT_OPTIONS);
	});

	test('Mobile landscape', async ({ page }) => {
		await page.setViewportSize({ width: 667, height: 375 });
		await frontendPage.goto(testPostUrl);

		const cta = page.locator('.cta-highlights-wrapper').first();
		await expect(cta).toHaveScreenshot('mobile/shortcode-mobile-landscape.png', SCREENSHOT_OPTIONS);
	});

	test('Tablet - iPad', async ({ page }) => {
		await page.setViewportSize({ width: 768, height: 1024 });
		await frontendPage.goto(testPostUrl);

		const cta = page.locator('.cta-highlights-wrapper').first();
		await expect(cta).toHaveScreenshot('mobile/shortcode-tablet.png', SCREENSHOT_OPTIONS);
	});

	test('Mobile highlight activated', async ({ page, context }) => {
		await page.setViewportSize({ width: 375, height: 667 });
		await context.clearCookies();
		await page.evaluate(() => window.localStorage.clear());

		await frontendPage.goto(testPostUrl);
		await frontendPage.scrollCTAIntoView(0);
		await frontendPage.waitForHighlight().catch(() => {});
		await page.waitForTimeout(500);

		await expect(page).toHaveScreenshot('mobile/highlight-mobile-portrait.png', {
			...SCREENSHOT_OPTIONS,
			fullPage: false
		});
	});
});

test.describe('Visual Regression: Edge Cases', () => {
	test('Very long title', async ({ page }) => {
		const adminPage = new CTAAdminPage(page);

		await page.goto('/wp-admin/post-new.php');
		await adminPage.waitForPageLoad();

		await adminPage.fillTitle('Long Title Test Post');
		await page.evaluate(() => {
			const content = `
				[cta_highlights title="This is a very long title that might wrap to multiple lines and we need to test how it renders in the CTA component"]
				Content here
				[/cta_highlights]
			`;
			if (typeof window.tinyMCE !== 'undefined' && window.tinyMCE.activeEditor) {
				window.tinyMCE.activeEditor.setContent(content);
			} else {
				document.getElementById('content').value = content;
			}
		});

		await page.click('#publish');
		await page.waitForSelector('.notice-success');

		const permalink = await page.locator('#sample-permalink a').getAttribute('href');
		await page.goto(permalink);

		const cta = page.locator('.cta-highlights-wrapper').first();
		await expect(cta).toHaveScreenshot('edge-cases/long-title.png', SCREENSHOT_OPTIONS);
	});

	test('Empty content', async ({ page }) => {
		const adminPage = new CTAAdminPage(page);

		await page.goto('/wp-admin/post-new.php');
		await adminPage.waitForPageLoad();

		await adminPage.fillTitle('Empty Content Test Post');
		await page.evaluate(() => {
			const content = '[cta_highlights title="Empty CTA"][/cta_highlights]';
			if (typeof window.tinyMCE !== 'undefined' && window.tinyMCE.activeEditor) {
				window.tinyMCE.activeEditor.setContent(content);
			} else {
				document.getElementById('content').value = content;
			}
		});

		await page.click('#publish');
		await page.waitForSelector('.notice-success');

		const permalink = await page.locator('#sample-permalink a').getAttribute('href');
		await page.goto(permalink);

		const cta = page.locator('.cta-highlights-wrapper').first();
		await expect(cta).toHaveScreenshot('edge-cases/empty-content.png', SCREENSHOT_OPTIONS);
	});
});
