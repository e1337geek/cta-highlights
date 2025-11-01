/**
 * E2E Tests: CTA Highlight Effect
 *
 * Tests the highlight overlay effect, cooldown functionality, and user interactions.
 *
 * @group e2e
 * @group frontend
 * @group highlight
 */

const { test, expect } = require('@playwright/test');
const CTAAdminPage = require('../utils/CTAAdminPage');
const CTAFrontendPage = require('../utils/CTAFrontendPage');

test.describe('CTA Highlight Effect', () => {
	let adminPage;
	let frontendPage;

	test.beforeEach(async ({ page, context }) => {
		adminPage = new CTAAdminPage(page);
		frontendPage = new CTAFrontendPage(page);

		// Clear storage before each test
		await context.clearCookies();
		await frontendPage.clearCooldowns();
	});

	test.describe('Highlight Activation', () => {
		test('should activate highlight when CTA scrolls into view', async ({
			page,
		}) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Highlight Effect Test');
			await page.evaluate(() => {
				const content = `
					<p>Paragraph 1</p>
					<p>Paragraph 2</p>
					<p>Paragraph 3</p>
					<p>Paragraph 4</p>
					<p>Paragraph 5</p>
					[cta_highlights title="Scroll Test"]This should highlight when scrolled into view[/cta_highlights]
					<p>Paragraph 6</p>
				`;
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');
			await frontendPage.goto(permalink);

			// CTA should not be highlighted initially (not in view)
			let isHighlighted = await frontendPage.isCTAHighlighted(0);
			expect(isHighlighted).toBe(false);

			// Scroll CTA into view
			await frontendPage.scrollCTAIntoView(0);

			// Wait for highlight effect
			await frontendPage.waitForHighlight();

			// Now should be highlighted
			isHighlighted = await frontendPage.isCTAHighlighted(0);
			expect(isHighlighted).toBe(true);

			// Overlay should be visible
			const overlayVisible = await frontendPage.isOverlayVisible();
			expect(overlayVisible).toBe(true);
		});

		test('should show close button when highlighted', async ({ page }) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Close Button Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Close Test"]Test content[/cta_highlights]';
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');
			await frontendPage.goto(permalink);

			// Scroll into view and wait for highlight
			await frontendPage.scrollCTAIntoView(0);
			await frontendPage.waitForHighlight();

			// Close button should be visible
			const closeButton = page.locator('.cta-highlights-close').first();
			await expect(closeButton).toBeVisible();
		});

		test('should dismiss highlight when close button clicked', async ({
			page,
		}) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Dismiss Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Dismiss Test"]Click close to dismiss[/cta_highlights]';
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');
			await frontendPage.goto(permalink);

			// Activate highlight
			await frontendPage.scrollCTAIntoView(0);
			await frontendPage.waitForHighlight();

			// Click close
			await frontendPage.clickClose(0);

			// Should no longer be highlighted
			await frontendPage.waitForAnimations();
			const isHighlighted = await frontendPage.isCTAHighlighted(0);
			expect(isHighlighted).toBe(false);

			// Overlay should be hidden
			const overlayVisible = await frontendPage.isOverlayVisible();
			expect(overlayVisible).toBe(false);
		});

		test('should dismiss highlight when overlay clicked', async ({
			page,
		}) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Overlay Click Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Overlay Test"]Click overlay to dismiss[/cta_highlights]';
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');
			await frontendPage.goto(permalink);

			// Activate highlight
			await frontendPage.scrollCTAIntoView(0);
			await frontendPage.waitForHighlight();

			// Click overlay
			const overlay = page.locator('.cta-highlights-overlay');
			await overlay.click();

			// Should dismiss
			await frontendPage.waitForAnimations();
			const overlayVisible = await frontendPage.isOverlayVisible();
			expect(overlayVisible).toBe(false);
		});
	});

	test.describe('Cooldown Functionality', () => {
		test('should store cooldown in localStorage after dismissal', async ({
			page,
		}) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Cooldown Storage Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Cooldown Test" template="default"]Test content[/cta_highlights]';
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');
			await frontendPage.goto(permalink);

			// Activate and dismiss
			await frontendPage.scrollCTAIntoView(0);
			await frontendPage.waitForHighlight();
			await frontendPage.clickClose(0);

			// Check localStorage
			const globalCooldown = await frontendPage.getStoredCooldown(
				'cta_highlights_global_cooldown'
			);
			expect(globalCooldown).not.toBeNull();

			const templateCooldown = await frontendPage.getStoredCooldown(
				'cta_highlights_template_default'
			);
			expect(templateCooldown).not.toBeNull();
		});

		test('should not highlight again during cooldown period', async ({
			page,
		}) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Cooldown Respect Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Cooldown Respect"]Test content[/cta_highlights]';
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');

			// First visit - activate and dismiss
			await frontendPage.goto(permalink);
			await frontendPage.scrollCTAIntoView(0);
			await frontendPage.waitForHighlight();
			await frontendPage.clickClose(0);

			// Reload page (cooldown should still be active)
			await page.reload();

			// Scroll CTA into view
			await frontendPage.scrollCTAIntoView(0);
			await frontendPage.waitForAnimations();

			// Should NOT highlight again
			const isHighlighted = await frontendPage.isCTAHighlighted(0);
			expect(isHighlighted).toBe(false);
		});

		test('should fallback to cookie when localStorage unavailable', async ({
			page,
			context,
		}) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Cookie Fallback Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Cookie Test"]Test content[/cta_highlights]';
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');
			await frontendPage.goto(permalink);

			// Disable localStorage
			await page.evaluate(() => {
				Object.defineProperty(window, 'localStorage', {
					value: null,
					writable: false,
				});
			});

			// Activate and dismiss
			await frontendPage.scrollCTAIntoView(0);
			await frontendPage.waitForHighlight();
			await frontendPage.clickClose(0);

			// Check cookies were set
			const cookies = await context.cookies();
			const ctaCookies = cookies.filter((c) =>
				c.name.startsWith('cta_highlights_')
			);
			expect(ctaCookies.length).toBeGreaterThan(0);
		});
	});

	test.describe('Multiple CTAs', () => {
		test('should only highlight one CTA at a time', async ({ page }) => {
			// Create post with multiple shortcodes
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Multiple CTA Highlight Test');
			await page.evaluate(() => {
				const content = `
					[cta_highlights title="First CTA"]First content[/cta_highlights]
					<p>Separator</p>
					[cta_highlights title="Second CTA"]Second content[/cta_highlights]
				`;
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');
			await frontendPage.goto(permalink);

			// Scroll first CTA into view
			await frontendPage.scrollCTAIntoView(0);
			await frontendPage.waitForHighlight();

			// First should be highlighted
			const first = await frontendPage.isCTAHighlighted(0);
			expect(first).toBe(true);

			// Second should NOT be highlighted
			const second = await frontendPage.isCTAHighlighted(1);
			expect(second).toBe(false);

			// Dismiss first
			await frontendPage.clickClose(0);

			// Scroll second CTA into view
			await frontendPage.scrollCTAIntoView(1);
			await frontendPage.waitForHighlight();

			// Now second should be highlighted
			const secondNow = await frontendPage.isCTAHighlighted(1);
			expect(secondNow).toBe(true);
		});
	});
});
